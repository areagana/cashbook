<?php /** HTML fragment. Sensitive columns are NEVER rendered for operators. */
$total=count($lines); $counted=0; $variances=0;
foreach ($lines as $line) {
    if ($line['counted_qty']!==null) {
        $counted++;
        if ($reviewer && $line['expected_qty']!==null && abs((float)$line['counted_qty']-(float)$line['expected_qty'])>.0005) $variances++;
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div><strong><?=dsEsc($session['count_date']);?></strong> &nbsp; <span class="badge badge-secondary"><?=dsEsc(ucfirst($session['status']));?></span></div>
    <div class="text-muted small">Counted <?=$counted;?> / <?=$total;?><?= $reviewer ? ' · Discrepancies '.$variances : '';?></div>
</div>
<p class="small text-muted"><?= $reviewer ? 'Variance = physical count − expected stock, where a verified expected snapshot exists. Backdated counts without a historical snapshot have no calculated variance.' : 'Enter physical quantities without reference to system balances. Leave uncounted items blank and add remarks where necessary.';?></p>
<?php if ($editable): ?>
<form class="kh-daily-form" data-action="save_counts">
    <input type="hidden" name="action" value="save_counts">
    <input type="hidden" name="bkid" value="<?=dsEsc($encryptedBookId);?>">
    <input type="hidden" name="daily_id" value="<?=(int)$session['id'];?>">
    <input type="hidden" name="csrf" value="<?=dsEsc($csrf);?>">
<?php endif; ?>
<div class="table-responsive">
<table class="table table-sm table-bordered kh-daily-table">
<thead><tr><th>Item / Unit</th><?php if($reviewer): ?><th class="text-right">Expected</th><?php endif; ?><th>Physical count</th><?php if($reviewer): ?><th class="text-right">Variance</th><?php endif; ?><th>Remarks</th></tr></thead>
<tbody>
<?php foreach($lines as $line):
    $qty=$line['counted_qty'];
    $diff=$reviewer && $qty!==null && $line['expected_qty']!==null ? (float)$qty-(float)$line['expected_qty'] : null;
?>
<tr>
    <td><strong><?=dsEsc($line['name']);?></strong><small class="d-block text-muted"><?=dsEsc($line['units']);?></small></td>
    <?php if($reviewer): ?><td class="text-right"><?=$line['expected_qty']===null?'Not available':number_format((float)$line['expected_qty'],3);?></td><?php endif; ?>
    <td><?php if($editable): ?><input class="form-control form-control-sm" type="number" min="0" step="0.001" name="qty[<?=(int)$line['id'];?>]" value="<?=dsEsc($qty);?>" placeholder="Not counted"><?php else: ?><?=$qty===null?'—':number_format((float)$qty,3);?><?php endif; ?></td>
    <?php if($reviewer): ?><td class="text-right <?=$diff!==null&&$diff<0?'text-danger':'text-success';?>"><?=$diff===null?'—':number_format($diff,3);?></td><?php endif; ?>
    <td><?php if($editable): ?><input class="form-control form-control-sm" maxlength="255" name="remarks[<?=(int)$line['id'];?>]" value="<?=dsEsc($line['remarks']);?>" placeholder="Optional remarks"><?php else: ?><?=dsEsc($line['remarks']);?><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php if($editable): ?>
<button type="submit" class="btn btn-success btn-flat"><i class="fa fa-save"></i> Save counts</button>
</form>
<form class="kh-daily-form mt-2" data-confirm="Submit current saved counts for review?" data-action="submit_review">
<input type="hidden" name="action" value="submit_review"><input type="hidden" name="bkid" value="<?=dsEsc($encryptedBookId);?>"><input type="hidden" name="daily_id" value="<?=(int)$session['id'];?>"><input type="hidden" name="csrf" value="<?=dsEsc($csrf);?>">
<button class="btn btn-outline-primary btn-flat" type="submit">Submit for review</button></form>
<?php endif; ?>
<?php if($reviewer && $session['status']==='review'): ?>
<form class="kh-daily-form mt-3" data-action="approve" data-confirm="Approve this daily count? No stock adjustments will be posted.">
<input type="hidden" name="action" value="approve"><input type="hidden" name="bkid" value="<?=dsEsc($encryptedBookId);?>"><input type="hidden" name="daily_id" value="<?=(int)$session['id'];?>"><input type="hidden" name="csrf" value="<?=dsEsc($csrf);?>">
<label class="small font-weight-bold">Reviewer notes</label><textarea class="form-control form-control-sm mb-2" name="review_notes" rows="2" placeholder="Explain variances and follow-up actions"></textarea>
<button type="submit" class="btn btn-success btn-flat">Approve reconciliation</button></form>
<form class="kh-daily-form mt-2" data-action="return_corrections" data-confirm="Return for corrections?">
<input type="hidden" name="action" value="return_corrections"><input type="hidden" name="bkid" value="<?=dsEsc($encryptedBookId);?>"><input type="hidden" name="daily_id" value="<?=(int)$session['id'];?>"><input type="hidden" name="csrf" value="<?=dsEsc($csrf);?>">
<button type="submit" class="btn btn-outline-warning btn-flat">Return for corrections</button></form>
<?php endif; ?>
<?php if($reviewer && !empty($session['review_notes'])): ?><div class="alert alert-light border mt-3"><strong>Review notes:</strong> <?=nl2br(dsEsc($session['review_notes']));?></div><?php endif; ?>
