<?php
/** Private invoice PDF or public, token-authorized invoice PDF. */
require_once __DIR__ . '/../assets/functions.php';
require_once __DIR__ . '/invoice_document.php';
$type=(string)($_GET['type']??'invoice');

if (!in_array($type,['invoice','returns','payments'],true)) 
{
    http_response_code(400); exit('Invalid PDF type.'); 
}

$token=(string)($_GET['share']??'');

if ($token!=='') 
{
    if (!preg_match('/^[a-f0-9]{64}$/D',$token)) 
    { 
        http_response_code(403); exit('Invalid link.'); 
    }

    $q= prepared_statements('SELECT s.invoice_id,s.book_id FROM cashbook_invoice_shares s INNER JOIN cashbook_invoices i ON i.id=s.invoice_id AND i.book_id=s.book_id WHERE s.token_hash=? AND s.revoked_at IS NULL AND s.expires_at>NOW() LIMIT 1','s',[hash('sha256',$token)]);
    $share= $q ? $q->fetch_assoc():null;

    if (!$share) 
    { 
        http_response_code(403); exit('This invoice link has expired or was revoked.'); 
    }
    $invoiceId=(int)$share['invoice_id']; 
    $bookId=(int)$share['book_id'];
    
    // Public links expose invoice-only data. Payment and return histories remain private.
    $type='invoice';
} else {
    if (!isVerified()) 
    { 
        http_response_code(403); exit('Access denied.'); 
    }

    $invoiceId=filter_input(INPUT_GET,'invoice_id',FILTER_VALIDATE_INT);
    $bookId=(int)encryptor('decrypt',$_SESSION['book_id']??'');

    if (!$invoiceId || !khInvoiceCanAccessBook($bookId)) 
    { 
        http_response_code(403); exit('Book access denied.'); 
    }
}

$data=khInvoiceLoad((int)$invoiceId,$bookId);

if (!$data) 
{ 
    http_response_code(404); exit('Invoice not found.'); 
}
foreach ([__DIR__.'/../vendor/autoload.php',__DIR__.'/../../vendor/autoload.php'] as $autoload) {
    if (is_file($autoload)) { require_once $autoload; if (class_exists(\Dompdf\Dompdf::class)) break; } 
}
if (!class_exists(\Dompdf\Dompdf::class)) 
{ 
    http_response_code(500); exit('Dompdf missing: composer require dompdf/dompdf'); 
}

$options=new \Dompdf\Options();
$options->set('isRemoteEnabled',false); 
$options->set('isPhpEnabled',false); 
$options->set('defaultFont','DejaVu Sans');
$pdf = new \Dompdf\Dompdf($options);
$html='<!doctype html><html>
            <head>
                <meta charset="utf-8"><style>@page{size:A4 portrait;margin:13mm 11mm 15mm}body{margin:0}</style>
            </head>
            <body>'.khInvoiceHtml($data,$type,true).'</body>
        </html>';
$pdf->loadHtml($html,'UTF-8'); $pdf->setPaper('A4','portrait'); $pdf->render();
$filename='Invoice_'.preg_replace('/[^A-Za-z0-9_-]/','_', (string)$data['invoice']['invoice_no']).'_'.$type.'.pdf';
$pdf->stream($filename,['Attachment'=>(($_GET['mode']??'')==='inline'?0:1)]);
exit;
