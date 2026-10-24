<?php
/** Reviewer-only daily variance history, used before monthly reconciliation. */
require_once __DIR__.'/bootstrap.php';
[$encryptedBookId,$bookId,$book,$userId]=dsContext();
if (!dsCanReview($bookId)) { http_response_code(403); exit('Only reviewers can see daily variance reports.'); }
$month=(string)($_GET['month']??date('Y-m'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month)) $month=date('Y-m');
$from=$month.'-01';$to=(new DateTimeImmutable($from))->modify('+1 month')->format('Y-m-d');
$rows=dsRows('SELECT d.count_date,d.status,i.name,i.units,l.expected_qty,l.counted_qty,l.remarks,l.counted_at FROM cashbook_daily_stocktake_items l JOIN cashbook_daily_stocktakes d ON d.id=l.daily_id AND d.book_id=l.book_id JOIN cashbook_items i ON i.id=l.item_id AND i.book_id=l.book_id WHERE d.book_id=? AND d.count_date>=? AND d.count_date<? AND l.counted_qty IS NOT NULL ORDER BY d.count_date DESC,i.name','iss',[$bookId,$from,$to]);
pageHeaderLevel2('Daily Stock Variances');
?>
<style>.kh-daily-report{padding:18px;background:#f5f7fa;min-height:70vh}.kh-daily-report .sheet{background:white;border:1px solid #dce4e8;padding:20px}.kh-daily-report th{background:#edf3f1;font-size:12px}.kh-daily-report .btn{border-radius:2px!important}@media print{.no-print{display:none!important}.kh-daily-report{padding:0;background:white}}</style>
<div class="container-fluid kh-daily-report"><div class="sheet"><div class="d-flex flex-wrap justify-content-between align-items-center mb-3"><div><h3 class="mb-1"><?=dsEsc($book->name);?> — Daily variance history</h3><small><?=dsEsc($month);?> · Physical minus expected · Counts are snapshots, not automatic ledger adjustments</small></div><div class="no-print"><a class="btn btn-outline-secondary btn-sm" href="index.php?bkid=<?=urlencode($encryptedBookId);?>&amp;month=<?=urlencode($month);?>">Back</a> <button class="btn btn-outline-success btn-sm" onclick="window.print()">Print</button></div></div>
<div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>Date</th><th>Item</th><th>Status</th><th class="text-right">Expected</th><th class="text-right">Counted</th><th class="text-right">Variance</th><th>Remarks</th></tr></thead><tbody>
<?php foreach($rows as $r): $diff=(float)$r['counted_qty']-(float)$r['expected_qty']; ?><tr><td><?=dsEsc($r['count_date']);?></td><td><?=dsEsc($r['name']);?> <small><?=dsEsc($r['units']);?></small></td><td><?=dsEsc($r['status']);?></td><td class="text-right"><?=number_format((float)$r['expected_qty'],3);?></td><td class="text-right"><?=number_format((float)$r['counted_qty'],3);?></td><td class="text-right <?=$diff<0?'text-danger':($diff>0?'text-warning':'text-success');?>"><?=number_format($diff,3);?></td><td><?=dsEsc($r['remarks']);?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="7" class="text-center p-4">No physical counts recorded for this month.</td></tr><?php endif; ?></tbody></table></div><p class="small text-muted">Do not sum daily variances as monthly losses: repeated shortages can appear across days. Investigate movements and authorized adjustments before month-end.</p></div></div>
<?php pageFooterLevel2(); ?>
