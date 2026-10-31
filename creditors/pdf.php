<?php
/** Company-branded creditor statement, A4 PDF. */
require_once __DIR__.'/../assets/functions.php';
require_once __DIR__.'/../assets/document_identity.php';
require_once __DIR__.'/statement_data.php';

if (!isVerified() || !hasRole(['owner', 'partner', 'staff', 'superadministrator'])) {
    http_response_code(403);
    exit('Access denied.');
}
$id = filter_input(INPUT_GET, 'creditor_id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid creditor ID.'); }
$creditor = creditorFind($id);
if (!$creditor) { http_response_code(404); exit('Creditor not found.'); }
$bookId = (int)$creditor->book_id;
if (function_exists('khCanAccessBook') && !khCanAccessBook($bookId)) {
    http_response_code(403);
    exit('Book access denied.');
}
foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../vendor/autoload.php'] as $autoload) {
    if (is_file($autoload)) require_once $autoload;
    if (class_exists(\Dompdf\Dompdf::class)) break;
}
if (!class_exists(\Dompdf\Dompdf::class)) { http_response_code(500); exit('Dompdf unavailable.'); }

$identity = khDocumentIdentity($bookId);
$logo = khDocumentLogoDataUri($identity);
$lines = khDocumentCompanyLines($identity);
$statement = khCreditorStatement($id, $bookId);
$esc = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
$money = static fn($value) => number_format((float)$value, 2);
$balance = $statement['running'];
$status = $balance > .005 ? 'AMOUNT OWED TO CREDITOR' : ($balance < -.005 ? 'ADVANCE / OVERPAYMENT' : 'ACCOUNT SETTLED');
$register = prepared_statements('SELECT balance FROM cashbook_creditor_balances WHERE creditor_id=? AND book_id=? LIMIT 1', 'ii', [$id, $bookId]);
$registerRow = $register ? $register->fetch_assoc() : null;
$discrepancy = $registerRow && abs((float)$registerRow['balance'] - $balance) > .01;
ob_start();
?>
<!doctype html><html><head><meta charset="utf-8"><style>
@page{size:A4 portrait;margin:13mm 11mm 15mm}body{font-family:DejaVu Sans,sans-serif;color:#182230;font-size:9px;margin:0}*{box-sizing:border-box}
.header{width:100%;border-bottom:3px solid #176b51;padding-bottom:12px;margin-bottom:14px}.header td{border:0;vertical-align:top}.brand{font-size:18px;font-weight:bold}.muted{color:#667085;font-size:8px;line-height:1.6}.doc{text-align:right}.doc h2{font-size:14px;margin:0 0 7px}
.panels,.stats{width:100%;border-spacing:7px 0;border-collapse:separate;margin-bottom:13px}.panels td,.stats td{border:1px solid #d0d5dd;padding:10px;vertical-align:top}.panels td{width:50%}.stats td{width:25%}.label{font-size:8px;color:#667085;font-weight:bold}.big{font-size:17px;font-weight:bold}.amount{text-align:right;background:#f8fafc;border-left:4px solid #176b51!important}
h3{font-size:12px;margin:13px 0 8px}.ledger{width:100%;border-collapse:collapse;table-layout:fixed;font-size:7.4px}.ledger th,.ledger td{border:1px solid #cbd5e1;padding:5px 3px;vertical-align:top;overflow-wrap:break-word}.ledger thead{display:table-header-group}.ledger thead th{background:#edf2f5;color:#344054;text-transform:uppercase}.ledger tbody tr:nth-child(even){background:#fafbfc}.ledger tr{page-break-inside:avoid}.ledger .num{text-align:right}.ledger tfoot td{background:#e8f3ee;font-weight:bold;border-top:2px solid #176b51}.footer{border-top:1px solid #d0d5dd;padding-top:9px;margin-top:14px;color:#667085;font-size:8px}.warning{padding:8px;border:1px solid #d97706;color:#92400e;margin-bottom:10px}
</style></head><body>
<table class="header"><tr><td style="width:55%">
<?php if ($logo): ?><img src="<?=$esc($logo);?>" style="max-height:48px;max-width:90px" alt=""><br><?php endif; ?>
<div class="brand"><?=$esc($identity['company_name']);?></div>
<div class="muted"><?php foreach ($lines as $line): ?><?=$esc($line);?><br><?php endforeach; ?><strong>Book: <?=$esc($identity['book_name']);?></strong></div>
</td><td class="doc"><h2>CREDITOR STATEMENT</h2><div class="muted">Generated: <?=date('d M Y, h:i A');?><br>Period: <?=$esc($statement['period']);?><br>Creditor ID: <?=$id;?></div></td></tr></table>
<table class="panels"><tr><td><div class="label">STATEMENT FOR</div><p class="big"><?=$esc($creditor->name ?? '');?></p><div class="muted">Contact: <?=$esc($creditor->contact ?? 'Not provided');?><br>Address: <?=$esc($creditor->address ?? 'Not provided');?></div></td><td class="amount"><div class="label"><?=$esc($status);?></div><p class="big">UGX <?=$money(abs($balance));?></p><div class="muted"><?=count($statement['rows']);?> movements</div></td></tr></table>
<table class="stats"><tr><td><div class="label">OPENING B/F</div><p><b>UGX <?=$money($statement['opening']);?></b></p></td><td><div class="label">AMOUNTS ADDED</div><p><b>UGX <?=$money($statement['totalCredit']);?></b></p></td><td><div class="label">PAYMENTS MADE</div><p><b>UGX <?=$money($statement['totalDebit']);?></b></p></td><td><div class="label">CLOSING C/D</div><p><b>UGX <?=$money($balance);?></b></p></td></tr></table>
<?php if ($discrepancy || $statement['differences']): ?><div class="warning"><strong>Reconciliation needed.</strong> Ledger calculations differ from one or more stored balances. Review records before issuing as final confirmation.</div><?php endif; ?>
<h3>TRANSACTION HISTORY</h3>
<table class="ledger"><colgroup><col style="width:12%"><col style="width:9%"><col style="width:13%"><col style="width:24%"><col style="width:10%"><col style="width:11%"><col style="width:11%"><col style="width:10%"></colgroup><thead><tr><th>Date</th><th>Ref.</th><th>Transaction</th><th>Description</th><th>Mode</th><th>Added (+)</th><th>Paid (-)</th><th>Balance</th></tr></thead><tbody>
<?php if ($statement['hasOpening']): ?><tr style="background:#e8f3ee"><td><?=$statement['firstDate'] ? date('d-m-Y',strtotime($statement['firstDate'])) : '—';?></td><td>—</td><td>Opening B/F</td><td colspan="2">Balance brought forward</td><td>—</td><td>—</td><td class="num"><?=$money($statement['opening']);?></td></tr><?php endif; ?>
<?php foreach ($statement['rows'] as $row): ?><tr><td><?=!empty($row['created_at']) ? date('d-m-Y',strtotime($row['created_at'])) : '—';?></td><td><?=$esc($row['transaction_id'] ?? '—');?></td><td><?=$esc($row['statement_activity']);?></td><td><?=$esc($row['statement_description']);?></td><td><?=$esc($row['paymode_name'] ?? '');?></td><td class="num"><?=((float)$row['credit_amount'] != 0) ? $money($row['credit_amount']) : '—';?></td><td class="num"><?=((float)$row['debit_amount'] != 0) ? $money($row['debit_amount']) : '—';?></td><td class="num"><b><?=$money($row['statement_balance']);?></b></td></tr><?php endforeach; ?>
<?php if (!$statement['hasOpening']): ?><tr><td colspan="8" style="text-align:center">No creditor ledger transactions.</td></tr><?php endif; ?>
</tbody><tfoot><tr><td colspan="5" style="text-align:right">CLOSING BALANCE C/D</td><td class="num"><?=$money($statement['totalCredit']);?></td><td class="num"><?=$money($statement['totalDebit']);?></td><td class="num"><?=$money($balance);?></td></tr></tfoot></table>
<div class="footer">Opening balance + amounts added − payments made = closing balance. All amounts in UGX.<br>Computer-generated statement · Generated through KashHub</div>
</body></html>
<?php
$html = ob_get_clean();
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$pdf = new \Dompdf\Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'portrait');
$pdf->render();
$pdf->stream('Creditor_Statement_'.$id.'_'.date('Ymd').'.pdf', ['Attachment' => (($_GET['mode'] ?? '') === 'inline' ? 0 : 1)]);
exit;
