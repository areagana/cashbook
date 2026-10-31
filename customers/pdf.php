<?php
/** KashHub customer statement: multi-page A4 PDF (Dompdf). */
require_once __DIR__ . '/../assets/functions.php';
require_once __DIR__ . '/../assets/document_identity.php';

if (!isVerified() || !hasRole(['owner', 'partner', 'staff', 'superadministrator'])) {
    http_response_code(403);
    exit('Access denied.');
}

$customerId = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);
if (!$customerId || $customerId < 1) {
    http_response_code(400);
    exit('Invalid customer.');
}

$customer = getCustomer($customerId);
if (!$customer) {
    http_response_code(404);
    exit('Customer not found.');
}
$bookId = (int)$customer->book_id;

/* SECURITY: Add the same book-membership/permission check used by your customer page.
 * The supplied original index.php does not enforce this check either.
 * Do not expose this endpoint to production users until book access is verified. */
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
    exit('PDF library missing. Install Dompdf using: composer require dompdf/dompdf');
}

$documentIdentity = khDocumentIdentity($bookId);
$documentLogo = khDocumentLogoDataUri($documentIdentity);
$documentCompanyLines = khDocumentCompanyLines($documentIdentity);

$escape = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
$money = static fn($value) => number_format((float)$value, 2);
$sql = "SELECT ccl.*, ci.name AS item_name, ci.units, pm.name AS paymode_name
        FROM cashbook_customer_ledger ccl
        LEFT JOIN cashbook_items ci ON ci.id = ccl.item_id AND ci.book_id = ccl.book_id
        LEFT JOIN cashbook_paymodes pm ON pm.id = ccl.paymode_id AND pm.book_id = ccl.book_id
        WHERE ccl.customer_id = ? AND ccl.book_id = ?
        ORDER BY ccl.created_at ASC, ccl.transaction_id ASC";
$result = prepared_statements($sql, 'ii', [$customerId, $bookId]);
if (!$result) {
    http_response_code(500);
    exit('Unable to retrieve customer ledger.');
}
$rows = [];
$totalDebit = $totalCredit = $running = 0.0;
$firstDate = $lastDate = '';
while ($row = $result->fetch_assoc()) {
    $debit = (float)($row['debit_amount'] ?? 0);
    $credit = (float)($row['credit_amount'] ?? 0);
    $totalDebit += $debit;
    $totalCredit += $credit;
    $running = round($running + $debit - $credit, 2);
    $row['statement_balance'] = $running;
    $rows[] = $row;
    if (!empty($row['created_at'])) {
        if (!$firstDate) $firstDate = $row['created_at'];
        $lastDate = $row['created_at'];
    }
}
$status = $running > .005 ? 'AMOUNT DUE' : ($running < -.005 ? 'CUSTOMER CREDIT' : 'SETTLED');
$statusColor = $running > .005 ? '#b42318' : ($running < -.005 ? '#067647' : '#175cd3');
$period = $firstDate ? date('d M Y', strtotime($firstDate)) . ' - ' . date('d M Y', strtotime($lastDate)) : 'No transactions';

ob_start();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><style>
@page { size: A4 portrait; margin: 13mm 11mm 15mm; }
body { margin:0; font-family: DejaVu Sans, sans-serif; font-size: 9px; color:#182230; }
* { box-sizing:border-box; }
.header { width:100%; border-bottom:3px solid #176b51; padding-bottom:12px; margin-bottom:14px; }
.header td { vertical-align:top; border:0; }
.brand { font-size:18px; font-weight:bold; }
.company-logo { max-width:58px; max-height:52px; margin-bottom:5px; }
.company-line { color:#667085; font-size:8px; line-height:1.4; }
.subtitle, .muted { color:#667085; font-size:9px; line-height:1.6; }
.doc { text-align:right; }
.doc h2 { font-size:14px; margin:0 0 7px; letter-spacing:1px; }
.doc p { margin:2px 0; }
.info-layout { width:100%; margin-bottom:11px; border-spacing:7px 0; border-collapse:separate; }
.info-layout td { border:1px solid #d0d5dd; padding:12px; vertical-align:top; width:50%; }
.label { font-size:8px; color:#667085; font-weight:bold; letter-spacing:.5px; }
.customer-name { font-size:15px; font-weight:bold; margin:7px 0; }
.amount { text-align:right; background:#f8fafc; border-left:4px solid #176b51 !important; }
.amount strong { font-size:19px; }
.stats { width:100%; border-collapse:separate; border-spacing:7px 0; margin-bottom:16px; }
.stats td { border:1px solid #d0d5dd; padding:9px; width:33.33%; }
.stats strong { font-size:11px; }
h3 { font-size:12px; margin:0 0 9px; letter-spacing:.3px; }
.ledger { width:100%; border-collapse:collapse; table-layout:fixed; font-size:7.3px; }
.ledger th, .ledger td { border:1px solid #cbd5e1; padding:5px 3px; vertical-align:top; overflow-wrap:break-word; }
.ledger thead { display:table-header-group; }
.ledger thead th { background:#edf2f5; text-transform:uppercase; color:#344054; font-size:7px; }
.ledger tbody tr:nth-child(even) { background:#fafbfc; }
.ledger tr { page-break-inside:avoid; }
.ledger .num { text-align:right; }
.ledger tfoot { display:table-row-group; }
.ledger tfoot th, .ledger tfoot td { background:#e8f3ee; font-weight:bold; border-top:2px solid #176b51; }
.footer { border-top:1px solid #d0d5dd; padding-top:10px; margin-top:14px; width:100%; color:#667085; font-size:8px; }
.footer td { vertical-align:top; width:50%; }
</style></head><body>
<table class="header"><tr><td style="width:48%"><?php if ($documentLogo): ?><img class="company-logo" src="<?= htmlspecialchars($documentLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="Company logo"><?php endif; ?>
<div class="brand"><?= htmlspecialchars($documentIdentity['company_name'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php foreach ($documentCompanyLines as $companyLine): ?><div class="company-line"><?= htmlspecialchars($companyLine, ENT_QUOTES, 'UTF-8'); ?></div><?php endforeach; ?>
<div class="company-line"><b>Book:</b> <?= htmlspecialchars($documentIdentity['book_name'], ENT_QUOTES, 'UTF-8'); ?></div><div class="subtitle">Customer account statement<br>Financial records &amp; outstanding balances</div></td><td class="doc"><h2>ACCOUNT STATEMENT</h2><p class="muted">Generated: <?=date('d M Y, h:i A');?></p><p class="muted">Period: <?=$escape($period);?></p><p class="muted">Customer ID: <?=$customerId;?></p></td></tr></table>
<table class="info-layout"><tr><td><div class="label">STATEMENT FOR</div><div class="customer-name"><?=$escape($customer->name ?? '');?></div><div class="muted"><b>Contact:</b> <?=$escape($customer->contact ?? 'Not provided');?><br><b>Address:</b> <?=$escape($customer->address ?? 'Not provided');?></div></td><td class="amount"><div class="label"><?=$escape($status);?></div><p><strong style="color:<?=$statusColor;?>">UGX <?=$money(abs($running));?></strong></p><div class="muted"><?=count($rows);?> ledger entries</div></td></tr></table>
<table class="stats"><tr><td><div class="label">TOTAL DEBITS</div><br><strong>UGX <?=$money($totalDebit);?></strong></td><td><div class="label">TOTAL CREDITS</div><br><strong>UGX <?=$money($totalCredit);?></strong></td><td><div class="label">NET BALANCE</div><br><strong>UGX <?=$money($running);?></strong></td></tr></table>
<h3>TRANSACTION HISTORY</h3>
<table class="ledger"><colgroup><col style="width:11%"><col style="width:8%"><col style="width:9%"><col style="width:20%"><col style="width:8%"><col style="width:9%"><col style="width:11%"><col style="width:11%"><col style="width:13%"></colgroup><thead><tr><th>Date</th><th>Ref.</th><th>Type</th><th>Description / Item</th><th>Qty</th><th>Mode</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?>
<tr><td><?=!empty($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : '-';?></td><td><?=$escape($row['transaction_id'] ?? '-');?></td><td><?=$escape(str_replace('_', ' ', (string)($row['type'] ?? '')));?></td><td><?=$escape(($row['item_name'] ?? '') ?: ($row['details'] ?? ''));?></td><td><?=!empty($row['quantity']) ? $money($row['quantity']) : '-';?> <?=$escape($row['units'] ?? '');?></td><td><?=$escape($row['paymode_name'] ?? '');?></td><td class="num"><?=((float)($row['debit_amount'] ?? 0) != 0) ? $money($row['debit_amount']) : '-';?></td><td class="num"><?=((float)($row['credit_amount'] ?? 0) != 0) ? $money($row['credit_amount']) : '-';?></td><td class="num"><b><?=$money($row['statement_balance']);?></b></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="9" style="text-align:center">No ledger transactions recorded for this customer.</td></tr><?php endif; ?>
</tbody><tfoot><tr><th colspan="6" style="text-align:right">CLOSING TOTAL</th><td class="num"><?=$money($totalDebit);?></td><td class="num"><?=$money($totalCredit);?></td><td class="num"><?=$money($running);?></td></tr></tfoot></table>
<table class="footer"><tr><td><b>Balance guide:</b> Positive = customer owes the business. Negative = customer has credit. Figures are in Uganda shillings (UGX).</td><td style="text-align:right">Prepared for <?= htmlspecialchars($documentIdentity['company_name'], ENT_QUOTES, 'UTF-8'); ?><br>Generated by KashHub · Computer-generated statement</td></tr></table>
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
$filename = 'Customer_Statement_' . $customerId . '_' . date('Ymd') . '.pdf';
$pdf->stream($filename, ['Attachment' => (($_GET['mode'] ?? '') === 'inline') ? 0 : 1]);
exit;
