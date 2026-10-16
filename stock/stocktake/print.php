<?php
/** Approved stock report. Location: stock/stocktake/print.php */
require_once __DIR__ . '/bootstrap.php';
[$encryptedBookId,$bookId,$book,$userId]=stContext();
$takeId=(int)($_GET['take_id'] ?? 0);
$take=stSession($takeId,$bookId);
if ($take['status'] === 'cancelled') { http_response_code(403); exit('Cancelled stock counts cannot be printed.'); }
$isApproved = in_array($take['status'], ['approved', 'posted'], true);
$reportStatus = $isApproved ? 'APPROVED' : 'NOT APPROVED — FOR PHYSICAL VERIFICATION ONLY';
$lines=stLines($takeId,$bookId);
$summary=stSummary($lines);
$document='<!doctype html><html><head><meta charset="UTF-8"><title>Stock Count '.stEscape($take['period_month']).'</title><style>
@page{size:A4 landscape;margin:12mm}body{font-family:DejaVu Sans,Arial,sans-serif;font-size:10px;color:#243244}h1{font-size:18px;color:#176b51;margin-bottom:4px}h2{font-size:12px}.sub{color:#667085}.summary{margin:12px 0;padding:10px;border:1px solid #ccd6da}table{border-collapse:collapse;width:100%;margin-top:12px}th,td{border:1px solid #cbd5df;padding:7px;text-align:left}th{background:#edf3f1}td.num{text-align:right}.footer{margin-top:16px;font-size:9px;color:#667085}.toolbar{margin-bottom:15px}@media print{.toolbar{display:none}}</style></head><body>';
$document.='<h1>'.stEscape($book->name).' — '.($isApproved ? 'Approved Stock Taking Report' : 'Provisional Stock Verification Sheet').'</h1>';
if (!$isApproved) {
    $document.='<div style="border:2px solid #b42318;color:#b42318;text-align:center;font-weight:bold;font-size:17px;padding:10px;margin:12px 0">NOT APPROVED — FOR PHYSICAL VERIFICATION ONLY</div>';
}
$document.='<div class="sub">Month: '.stEscape($take['period_month']).' | Count date: '.stEscape($take['count_date']).' | Status: '.stEscape($reportStatus).' | '.($isApproved ? 'Approved: '.stEscape($take['approved_at']) : 'Approval: Pending').' | Reference: ST-'.(int)$take['id'].'</div>';
$document.='<div class="summary"><strong>Items:</strong> '.$summary['total'].' &nbsp; <strong>Counted:</strong> '.$summary['counted'].' &nbsp; <strong>Variance items:</strong> '.$summary['variance'].' &nbsp; <strong>Physical value:</strong> UGX '.number_format($summary['counted_value'],2).' &nbsp; <strong>Missing costs:</strong> '.$summary['missing_cost'].'</div>';
$document.='<table><thead><tr><th>#</th><th>Item</th><th>Unit</th><th>Expected</th><th>Counted</th><th>Variance</th><th>Unit cost (UGX)</th><th>Physical value (UGX)</th><th>Remarks</th></tr></thead><tbody>';
foreach($lines as $index=>$line){
    $qty=$line['counted_qty'];$cost=$line['unit_cost'];
    $variance=$qty===null?'—':number_format((float)$qty-(float)$line['expected_qty'],3);
    $value=$qty===null||$cost===null?'—':number_format((float)$qty*(float)$cost,2);
    $document.='<tr><td>'.($index+1).'</td><td>'.stEscape($line['name']).'</td><td>'.stEscape($line['units']).'</td><td class="num">'.number_format((float)$line['expected_qty'],3).'</td><td class="num">'.($qty===null?'—':number_format((float)$qty,3)).'</td><td class="num">'.$variance.'</td><td class="num">'.($cost===null?'—':number_format((float)$cost,4)).'</td><td class="num">'.$value.'</td><td>'.stEscape($line['reason']).'</td></tr>';
}
$document.='</tbody></table><div class="footer">'.($isApproved ? 'This is an approved physical stock count.' : 'PROVISIONAL DOCUMENT: physical counts are subject to review and correction. This is not an approved inventory statement.').' Approval does not post inventory adjustments. Valuation is incomplete if unit costs are missing. Expected quantities were captured at session creation.</div></body></html>';

// Load Dompdf from the supported Composer locations.
$autoloaders = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];

foreach ($autoloaders as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        if (class_exists('Dompdf\Dompdf')) {
            break;
        }
    }
}

if (!class_exists('Dompdf\Dompdf')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit('PDF download unavailable: Dompdf is not installed. Run composer require dompdf/dompdf in your project, then retry.');
}

try {
    $pdf = new \Dompdf\Dompdf([
        'isRemoteEnabled' => false,
        'defaultFont' => 'DejaVu Sans',
    ]);

    $pdf->loadHtml($document, 'UTF-8');
    $pdf->setPaper('A4', 'landscape');
    $pdf->render();

    $filename = ($isApproved ? 'KashHub_Approved_Stocktake_' : 'KashHub_Provisional_Stocktake_') .
        preg_replace('/[^0-9-]/', '', (string)$take['period_month']) .
        '_ST-' . (int)$take['id'] . '.pdf';

    // Force a download rather than showing a PDF preview in the browser.
    // Clear any buffered output so the PDF is not corrupted by stray markup.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $pdf->stream($filename, ['Attachment' => true]);
    exit;
} catch (Throwable $exception) {
    error_log('KashHub stocktake PDF error: ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Unable to generate the stock-taking PDF. Check the server error log.');
}
