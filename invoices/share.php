<?php
/** Generate/revoke revocable, expiring invoice-only customer links. */
require_once __DIR__ . '/../assets/functions.php';
require_once __DIR__ . '/invoice_document.php';
header('Content-Type: application/json; charset=utf-8');
function khShareReply(int $status,array $payload): void {http_response_code($status);echo json_encode($payload);exit;}
if ($_SERVER['REQUEST_METHOD']!=='POST' || !isVerified()) khShareReply(403,['success'=>false,'message'=>'Access denied.']);
$invoiceId=filter_input(INPUT_POST,'invoice_id',FILTER_VALIDATE_INT);
$bookId=(int)encryptor('decrypt',$_SESSION['book_id']??'');
if (!$invoiceId || !khInvoiceCanAccessBook($bookId)) khShareReply(403,['success'=>false,'message'=>'Book access denied.']);
$data=khInvoiceLoad((int)$invoiceId,$bookId);
if (!$data) khShareReply(404,['success'=>false,'message'=>'Invoice not found.']);
$action=(string)($_POST['action']??'create');
if ($action==='revoke') {
    $q=prepared_statements('UPDATE cashbook_invoice_shares SET revoked_at=NOW() WHERE invoice_id=? AND book_id=? AND revoked_at IS NULL','ii',[(int)$invoiceId,$bookId]);
    if (!$q) khShareReply(500,['success'=>false,'message'=>'Could not revoke the links.']);
    khShareReply(200,['success'=>true,'message'=>'Previous sharing links revoked.']);
}
if ($action!=='create') khShareReply(400,['success'=>false,'message'=>'Invalid action.']);
try {$token=bin2hex(random_bytes(32));} catch(Throwable $e) {khShareReply(500,['success'=>false,'message'=>'Could not create a secure token.']);}
$hash=hash('sha256',$token);
$q=prepared_statements('INSERT INTO cashbook_invoice_shares (invoice_id,book_id,token_hash,created_by,expires_at) VALUES (?,?,?,?,DATE_ADD(NOW(),INTERVAL 30 DAY))','iisi',[(int)$invoiceId,$bookId,$hash,(int)auth()->id]);
if (!$q) khShareReply(500,['success'=>false,'message'=>'Could not save sharing link. Install the supplied SQL table first.']);
// Uses the actual current host and the installed invoice directory.
$isHttps=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') || (($_SERVER['SERVER_PORT']??'')==='443');
$scheme=$isHttps?'https':'http';
$host=$_SERVER['HTTP_HOST']??'';
if (!preg_match('/^[A-Za-z0-9.:-]+$/D',$host)) khShareReply(500,['success'=>false,'message'=>'Invalid host configuration.']);
$directory=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/invoices/share.php')),'/');
$url=$scheme.'://'.$host.$directory.'/pdf.php?share='.$token.'&type=invoice&mode=inline';
$inv=$data['invoice'];
$message='Hello '.($inv['customer']?:'Customer').",\n\n".'Your invoice '.($inv['invoice_no']??'').' from '.(khDocumentIdentity($bookId)['company_name']??'our business').' is ready.'."\n".'Amount: UGX '.number_format((float)$inv['total'],2)."\n".'Outstanding: UGX '.number_format((float)$inv['balance'],2)."\n\n".'View/download invoice: '.$url."\n\nThank you for your business.";
khShareReply(200,['success'=>true,'url'=>$url,'message'=>$message]);
