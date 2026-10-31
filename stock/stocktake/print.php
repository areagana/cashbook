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
// Compute monetary totals from the same saved rows used in the report.
// Unknown quantities or costs are excluded, never treated as verified zero values.
$totals = [
    'expected' => 0.0, 'counted' => 0.0, 'variance' => 0.0,
    'shortage' => 0.0, 'surplus' => 0.0,
    'missing_cost' => 0, 'missing_expected' => 0, 'uncounted' => 0,
];
foreach ($lines as $line) {
    $expected = $line['expected_qty'];
    $counted = $line['counted_qty'];
    $cost = $line['unit_cost'];
    if ($counted === null) $totals['uncounted']++;
    if ($counted !== null && $cost === null) $totals['missing_cost']++;
    if ($expected === null) $totals['missing_expected']++;
    if ($cost !== null && $expected !== null) {
        $totals['expected'] += (float)$expected * (float)$cost;
    }
    if ($cost !== null && $counted !== null) {
        $totals['counted'] += (float)$counted * (float)$cost;
    }
    if ($cost !== null && $counted !== null && $expected !== null) {
        $difference = ((float)$counted - (float)$expected) * (float)$cost;
        $totals['variance'] += $difference;
        if ($difference < 0) $totals['shortage'] += abs($difference);
        else $totals['surplus'] += $difference;
    }
}
$money = static fn(float $amount): string => 'UGX ' . number_format($amount, 2);
$document = '<!doctype html><html><head><meta charset="UTF-8"><title>Stock Count '.stEscape($take['period_month']).'</title><style>
@page{size:A4 landscape;margin:12mm}
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:9px;color:#243244}
h1{font-size:18px;color:#176b51;margin-bottom:5px}
.sub{color:#667085;line-height:1.7}
.summary{margin:12px 0;padding:10px;border:1px solid #ccd6da;line-height:2}
.summary strong{color:#176b51}
table{border-collapse:collapse;width:100%;margin-top:12px}
th,td{border:1px solid #cbd5df;padding:6px;text-align:left}
th{background:#edf3f1}
td.num,th.num{text-align:right}
tfoot td{background:#e5f1eb;font-weight:bold}
.warning{margin:10px 0;padding:8px;border:1px solid #e5b85d;background:#fff7e6}
.footer{margin-top:16px;font-size:9px;color:#667085;line-height:1.6}
</style></head><body>';
$document .= '<h1>'.stEscape($book->name).' — '.($isApproved ? 'Approved Stock Taking Report' : 'Provisional Stock Verification Sheet').'</h1>';
if (!$isApproved) {
    $document .= '<div style="border:2px solid #b42318;color:#b42318;text-align:center;font-weight:bold;font-size:15px;padding:10px;margin:12px 0">NOT APPROVED — FOR PHYSICAL VERIFICATION ONLY</div>';
}
$document .= '<div class="sub">Month: '.stEscape($take['period_month']).' | Count date: '.stEscape($take['count_date']).' | Status: '.stEscape($reportStatus).' | '.($isApproved ? 'Approved: '.stEscape($take['approved_at']) : 'Approval: Pending').' | Reference: ST-'.(int)$take['id'].'</div>';
$document .= '<div class="summary">'
    .'<strong>Items:</strong> '.count($lines).' &nbsp; '
    .'<strong>Counted:</strong> '.(count($lines)-$totals['uncounted']).' &nbsp; '
    .'<strong>Expected stock value:</strong> '.$money($totals['expected']).' &nbsp; '
    .'<strong>Counted stock value:</strong> '.$money($totals['counted']).'<br>'
    .'<strong>Shortage value:</strong> '.$money($totals['shortage']).' &nbsp; '
    .'<strong>Surplus value:</strong> '.$money($totals['surplus']).' &nbsp; '
    .'<strong>Net stock variance:</strong> '.$money($totals['variance']).'</div>';
if ($totals['missing_cost'] || $totals['missing_expected'] || $totals['uncounted']) {
    $document .= '<div class="warning"><strong>Incomplete valuation:</strong> '
        .$totals['missing_cost'].' counted items without unit cost; '
        .$totals['missing_expected'].' items without expected quantity; '
        .$totals['uncounted'].' uncounted items. Totals represent only rows with the required figures. '
        .'Expected and counted totals may cover different sets of items; do not subtract them to infer net variance.</div>';
}
$document .= '<table><thead><tr><th>#</th><th>Item</th><th>Unit</th><th class="num">Expected</th><th class="num">Counted</th><th class="num">Variance</th><th class="num">Unit cost (UGX)</th><th class="num">Counted value (UGX)</th><th>Remarks</th></tr></thead><tbody>';
foreach ($lines as $index => $line) {
    $qty = $line['counted_qty'];
    $expected = $line['expected_qty'];
    $cost = $line['unit_cost'];
    $variance = $qty === null || $expected === null ? '—' : number_format((float)$qty - (float)$expected, 3);
    $value = $qty === null || $cost === null ? '—' : number_format((float)$qty * (float)$cost, 2);
    $document .= '<tr><td>'.($index+1).'</td><td>'.stEscape($line['name']).'</td><td>'.stEscape($line['units']).'</td>'
        .'<td class="num">'.($expected === null ? '—' : number_format((float)$expected, 3)).'</td>'
        .'<td class="num">'.($qty === null ? '—' : number_format((float)$qty, 3)).'</td>'
        .'<td class="num">'.$variance.'</td>'
        .'<td class="num">'.($cost === null ? '—' : number_format((float)$cost, 4)).'</td>'
        .'<td class="num">'.$value.'</td><td>'.stEscape($line['reason']).'</td></tr>';
}
$document .= '</tbody><tfoot><tr><td colspan="7" class="num">TOTAL COUNTED STOCK VALUE (UGX)</td><td class="num">'.number_format($totals['counted'], 2).'</td><td></td></tr></tfoot></table>';
$document .= '<div class="footer">'.($isApproved ? 'This is an approved physical stock count.' : 'PROVISIONAL DOCUMENT: physical counts are subject to review and correction. This is not an approved inventory statement.')
    .' Approval does not post inventory adjustments. Monetary variances are calculated only where expected quantity, counted quantity and unit cost are all available. '
    .'Expected quantities are session snapshots and may not represent historical balances. Stock valuation is not profit; gross profit requires sales revenue and cost of goods sold.</div></body></html>';

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
