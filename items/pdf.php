<?php
/**
 * KashHub item statement: downloadable multi-page A4 PDF.
 * Uses the same document style as the existing customer statement.
 * No new statement page or folder is required.
 */
require_once __DIR__ . '/item_helpers.php';
require_once __DIR__ . '/../assets/document_identity.php';

if (!hasRole(['owner', 'partner', 'staff', 'superadministrator', 'manager', 'stock manager'])) {
    http_response_code(403);
    exit('Access denied.');
}

// Keep book-level access consistent with the site's permission helper, if provided.
if (function_exists('khCanAccessBook') && !khCanAccessBook($bookId)) {
    http_response_code(403);
    exit('You cannot access this business book.');
}

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php'
];
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        if (class_exists(\Dompdf\Dompdf::class)) break;
    }
}
if (!class_exists(\Dompdf\Dompdf::class)) {
    http_response_code(500);
    exit('Dompdf unavailable. Check Composer installation and vendor/autoload.php.');
}

$documentIdentity = khDocumentIdentity($bookId);
$documentLogo = khDocumentLogoDataUri($documentIdentity);
$documentCompanyLines = khDocumentCompanyLines($documentIdentity);

$transactions = itemQuery(
    'SELECT id, created_at, details, credit_amount, debit_amount, quantity, rate, type
     FROM cashbook_transactions
     WHERE book_id = ? AND item_id = ?
     ORDER BY created_at ASC, id ASC',
    'ii', [$bookId, $itemId]
)->fetch_all(MYSQLI_ASSOC);

$stocks = itemQuery(
    'SELECT id, created_at, transaction_type, quantity_in, quantity_out,
            balance, unit_cost, total_cost, reference
     FROM cashbook_stocks
     WHERE book_id = ? AND item_id = ?
     ORDER BY created_at ASC, id ASC',
    'ii', [$bookId, $itemId]
)->fetch_all(MYSQLI_ASSOC);

$totalCredits = $totalDebits = $totalStockIn = $totalStockOut = 0.0;
foreach ($transactions as $row) {
    $totalCredits += (float)$row['credit_amount'];
    $totalDebits += (float)$row['debit_amount'];
}
foreach ($stocks as $row) {
    $totalStockIn += (float)$row['quantity_in'];
    $totalStockOut += (float)$row['quantity_out'];
}
$netMovement = $totalStockIn - $totalStockOut;
$dates = array_merge(array_column($transactions, 'created_at'), array_column($stocks, 'created_at'));
$dates = array_values(array_filter($dates));
sort($dates);
$period = $dates ? date('d M Y', strtotime($dates[0])) . ' - ' . date('d M Y', strtotime(end($dates))) : 'No records';
$esc = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
$num = static fn($value) => number_format((float)$value, 2);
$date = static fn($value) => $value ? date('d-m-Y', strtotime($value)) : '-';
$units = trim((string)($item['units'] ?? ''));

ob_start();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><style>
@page { size:A4 portrait; margin:13mm 11mm 15mm; }
body { margin:0; font-family:DejaVu Sans,sans-serif; font-size:9px; color:#182230; }
* { box-sizing:border-box; }
.header { width:100%; border-bottom:3px solid #176b51; padding-bottom:12px; margin-bottom:14px; }
.header td { border:0; vertical-align:top; }
.brand { font-size:18px; font-weight:bold; }
.company-logo { max-width:58px; max-height:52px; margin-bottom:5px; }
.company-line { color:#667085; font-size:8px; line-height:1.4; }
.subtitle,.muted { color:#667085; font-size:9px; line-height:1.6; }
.doc { text-align:right; }
.doc h2 { font-size:14px; margin:0 0 7px; letter-spacing:1px; }
.doc p { margin:2px 0; }
.info { width:100%; margin-bottom:11px; border-spacing:7px 0; border-collapse:separate; }
.info td { border:1px solid #d0d5dd; padding:12px; vertical-align:top; width:50%; }
.label { font-size:8px; color:#667085; font-weight:bold; letter-spacing:.5px; }
.item-name { font-size:15px; font-weight:bold; margin:7px 0; }
.amount { text-align:right; background:#f8fafc; border-left:4px solid #176b51 !important; }
.amount strong { font-size:19px; color:#176b51; }
.stats { width:100%; border-collapse:separate; border-spacing:7px 0; margin-bottom:16px; }
.stats td { border:1px solid #d0d5dd; padding:9px; width:25%; }
.stats strong { font-size:11px; }
h3 { font-size:12px; margin:14px 0 9px; letter-spacing:.3px; }
.ledger { width:100%; border-collapse:collapse; table-layout:fixed; font-size:7.3px; }
.ledger th,.ledger td { border:1px solid #cbd5e1; padding:5px 3px; vertical-align:top; overflow-wrap:break-word; }
.ledger thead { display:table-header-group; }
.ledger thead th { background:#edf2f5; text-transform:uppercase; color:#344054; font-size:7px; }
.ledger tbody tr:nth-child(even) { background:#fafbfc; }
.ledger tr { page-break-inside:avoid; }
.ledger .num { text-align:right; }
.ledger tfoot { display:table-row-group; }
.ledger tfoot th,.ledger tfoot td { background:#e8f3ee; font-weight:bold; border-top:2px solid #176b51; }
.note { color:#667085; font-size:8px; margin:10px 0; line-height:1.5; }
.footer { border-top:1px solid #d0d5dd; padding-top:10px; margin-top:14px; width:100%; color:#667085; font-size:8px; }
.footer td { width:50%; vertical-align:top; }
</style></head>
<body>
<table class="header"><tr>
<td style="width:48%"><?php if ($documentLogo): ?><img class="company-logo" src="<?= htmlspecialchars($documentLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="Company logo"><?php endif; ?>
<div class="brand"><?= htmlspecialchars($documentIdentity['company_name'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php foreach ($documentCompanyLines as $companyLine): ?><div class="company-line"><?= htmlspecialchars($companyLine, ENT_QUOTES, 'UTF-8'); ?></div><?php endforeach; ?>
<div class="company-line"><b>Book:</b> <?= htmlspecialchars($documentIdentity['book_name'], ENT_QUOTES, 'UTF-8'); ?></div><div class="subtitle">Item transaction statement<br>Inventory movements &amp; transaction records</div></td>
<td class="doc"><h2>ITEM STATEMENT</h2><p class="muted">Generated: <?= date('d M Y, h:i A'); ?></p><p class="muted">Period: <?= $esc($period); ?></p><p class="muted">Item ID: <?= $itemId; ?></p></td>
</tr></table>
<table class="info"><tr>
<td><div class="label">STATEMENT FOR</div><div class="item-name"><?= $esc($item['name']); ?></div>
<div class="muted"><b>Business:</b> <?= $esc($book->name); ?><br><b>Units:</b> <?= $esc($units ?: '-'); ?><br><b>Details:</b> <?= $esc($item['details'] ?? '-'); ?></div></td>
<td class="amount"><div class="label">NET RECORDED STOCK MOVEMENT</div><p><strong><?= $num($netMovement); ?> <?= $esc($units); ?></strong></p>
<div class="muted"><?= count($transactions); ?> transactions / <?= count($stocks); ?> stock movements</div></td>
</tr></table>
<table class="stats"><tr>
<td><div class="label">TRANSACTION CREDITS</div><br><strong>UGX <?= $num($totalCredits); ?></strong></td>
<td><div class="label">TRANSACTION DEBITS</div><br><strong>UGX <?= $num($totalDebits); ?></strong></td>
<td><div class="label">STOCK IN</div><br><strong><?= $num($totalStockIn); ?></strong></td>
<td><div class="label">STOCK OUT</div><br><strong><?= $num($totalStockOut); ?></strong></td>
</tr></table>
<h3>TRANSACTION HISTORY</h3>
<table class="ledger"><colgroup><col style="width:14%"><col style="width:10%"><col style="width:32%"><col style="width:14%"><col style="width:10%"><col style="width:10%"><col style="width:10%"></colgroup>
<thead><tr><th>Date</th><th>Ref.</th><th>Description</th><th>Type</th><th>Qty</th><th>Credit</th><th>Debit</th></tr></thead><tbody>
<?php foreach ($transactions as $row): ?>
<tr><td><?= $date($row['created_at']); ?></td><td><?= (int)$row['id']; ?></td><td><?= $esc($row['details']); ?></td><td><?= $esc($row['type']); ?></td><td class="num"><?= $num($row['quantity']); ?></td><td class="num"><?= $num($row['credit_amount']); ?></td><td class="num"><?= $num($row['debit_amount']); ?></td></tr>
<?php endforeach; ?>
<?php if (!$transactions): ?><tr><td colspan="7" style="text-align:center">No transactions recorded.</td></tr><?php endif; ?>
</tbody><tfoot><tr><th colspan="5" style="text-align:right">TOTAL</th><td class="num"><?= $num($totalCredits); ?></td><td class="num"><?= $num($totalDebits); ?></td></tr></tfoot></table>
<h3>STOCK MOVEMENT HISTORY</h3>
<table class="ledger"><colgroup><col style="width:15%"><col style="width:16%"><col style="width:15%"><col style="width:12%"><col style="width:12%"><col style="width:15%"><col style="width:15%"></colgroup>
<thead><tr><th>Date</th><th>Movement</th><th>Reference</th><th>Qty In</th><th>Qty Out</th><th>Recorded Balance</th><th>Cost (UGX)</th></tr></thead><tbody>
<?php foreach ($stocks as $row): ?>
<tr><td><?= $date($row['created_at']); ?></td><td><?= $esc(str_replace('_',' ',$row['transaction_type'])); ?></td><td><?= $esc($row['reference']); ?></td><td class="num"><?= $num($row['quantity_in']); ?></td><td class="num"><?= $num($row['quantity_out']); ?></td><td class="num"><?= $num($row['balance']); ?></td><td class="num"><?= $num($row['total_cost']); ?></td></tr>
<?php endforeach; ?>
<?php if (!$stocks): ?><tr><td colspan="7" style="text-align:center">No stock movements recorded.</td></tr><?php endif; ?>
</tbody><tfoot><tr><th colspan="3" style="text-align:right">TOTAL MOVEMENT</th><td class="num"><?= $num($totalStockIn); ?></td><td class="num"><?= $num($totalStockOut); ?></td><td colspan="2"></td></tr></tfoot></table>
<p class="note"><b>Statement guide:</b> Stock in increases recorded movement; stock out decreases it. Net movement is not necessarily stock on hand if opening stock or adjustments are stored elsewhere. Transaction credits/debits are accounting records, not necessarily cash receipts/payments or profit.</p>
<table class="footer"><tr><td>Figures are shown in Uganda shillings (UGX) unless marked as quantities.</td><td style="text-align:right">Prepared for <?= htmlspecialchars($documentIdentity['company_name'], ENT_QUOTES, 'UTF-8'); ?><br>Generated by KashHub · Computer-generated statement</td></tr></table>
</body></html>
<?php
$html = ob_get_clean();
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', false);
$options->set('isPhpEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$pdf = new \Dompdf\Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'portrait');
$pdf->render();
$filename = 'Item_Statement_' . $itemId . '_' . date('Ymd') . '.pdf';
$pdf->stream($filename, ['Attachment' => (($_GET['mode'] ?? '') === 'inline') ? 0 : 1]);
exit;
