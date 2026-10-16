<?php
/** HTML fragment returned by save/index.php?action=details. */
?>
<div class="kh-modal-summary">
    <div><small>Month</small><strong><?=stEscape($take['period_month']);?></strong></div>
    <div><small>Status</small><strong><?=stEscape(ucfirst($take['status']));?></strong></div>
    <div><small>Counted</small><strong><?=$summary['counted'];?> / <?=$summary['total'];?></strong></div>
    <div><small>Variance items</small><strong><?=$summary['variance'];?></strong></div>
    <div><small>Physical value</small><strong>UGX <?=number_format($summary['counted_value'],2);?></strong></div>
</div>
<p class="text-muted small">System quantities were captured when this session was created. Approved counts do not automatically adjust stock balances.</p>
<?php if ($editable): ?>
<form class="kh-stocktake-form" data-action="save_counts">
    <input type="hidden" name="action" value="save_counts">
    <input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>">
    <input type="hidden" name="take_id" value="<?=(int)$take['id'];?>">
    <input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
<?php endif; ?>
<div class="kh-table-scroll">
    <table class="table table-sm table-bordered kh-count-table">
        <thead><tr><th>Item</th><th class="text-right">Expected</th><th>Physical qty</th><th class="text-right">Variance</th><th>Unit cost (UGX)</th><th class="text-right">Counted value</th><th>Remarks</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $line):
            $counted=$line['counted_qty'];
            $difference=$counted===null?null:(float)$counted-(float)$line['expected_qty'];
            $value=$counted===null||$line['unit_cost']===null?null:(float)$counted*(float)$line['unit_cost'];
        ?>
            <tr>
                <td><strong><?=stEscape($line['name']);?></strong><small class="d-block text-muted"><?=stEscape($line['units']);?></small></td>
                <td class="text-right"><?=number_format((float)$line['expected_qty'],3);?></td>
                <td><?php if($editable): ?><input type="number" min="0" step="0.001" name="qty[<?=(int)$line['id'];?>]" value="<?=stEscape($counted);?>" class="form-control form-control-sm"><?php else: ?><?=$counted===null?'—':number_format((float)$counted,3);?><?php endif; ?></td>
                <td class="text-right <?=$difference!==null&&$difference<0?'text-danger':'text-success';?>"><?=$difference===null?'—':number_format($difference,3);?></td>
                <td><?php if($editable): ?><input type="number" min="0" step="0.0001" name="cost[<?=(int)$line['id'];?>]" value="<?=stEscape($line['unit_cost']);?>" class="form-control form-control-sm"><?php else: ?><?=$line['unit_cost']===null?'—':number_format((float)$line['unit_cost'],4);?><?php endif; ?></td>
                <td class="text-right"><?=$value===null?'—':number_format($value,2);?></td>
                <td><?php if($editable): ?><input type="text" maxlength="255" name="reason[<?=(int)$line['id'];?>]" value="<?=stEscape($line['reason']);?>" class="form-control form-control-sm"><?php else: ?><?=stEscape($line['reason']);?><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($editable): ?>
    <div class="mt-3"><button type="submit" class="btn btn-success btn-flat"><i class="fa fa-save"></i> Save physical counts</button></div>
</form>
<form class="kh-stocktake-form mt-2" data-action="submit_review" data-confirm="Submit for review? Save any unsaved counts first.">
    <input type="hidden" name="action" value="submit_review"><input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>"><input type="hidden" name="take_id" value="<?=(int)$take['id'];?>"><input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
    <button type="submit" class="btn btn-outline-primary btn-flat">Submit for review</button>
</form>
<?php endif; ?>
<?php if (!in_array($take['status'], ['approved', 'posted', 'cancelled'], true)): ?>
    <a class="btn btn-outline-danger btn-flat mt-3" href="print.php?bkid=<?=urlencode($encryptedBookId);?>&amp;take_id=<?=(int)$take['id'];?>">
        <i class="fa fa-file-pdf"></i> Download provisional PDF (Not approved)
    </a>
<?php endif; ?>
<?php if ($take['status']==='review' && $approver): ?>
<form class="kh-stocktake-form mt-3" data-action="approve" data-confirm="Approve and close this stock count? This does not post stock movements.">
    <input type="hidden" name="action" value="approve"><input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>"><input type="hidden" name="take_id" value="<?=(int)$take['id'];?>"><input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
    <button type="submit" class="btn btn-success btn-flat"><i class="fa fa-check-circle"></i> Approve and close</button>
</form>
<?php endif; ?>
<?php if ($take['status']==='review' && $approver): ?>
<form class="kh-stocktake-form mt-2" data-action="return_corrections" data-confirm="Return this record for corrections before approval?">
    <input type="hidden" name="action" value="return_corrections">
    <input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>">
    <input type="hidden" name="take_id" value="<?=(int)$take['id'];?>">
    <input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
    <button type="submit" class="btn btn-outline-warning btn-flat"><i class="fa fa-edit"></i> Return for corrections</button>
</form>
<?php endif; ?>
<?php if (in_array($take['status'],['approved','posted'],true)): ?>
    <a class="btn btn-outline-success btn-flat mt-3" target="_blank" rel="noopener" href="print.php?bkid=<?=urlencode($encryptedBookId);?>&amp;take_id=<?=(int)$take['id'];?>"><i class="fa fa-file-pdf"></i> Approved stock report (PDF)</a>
<?php endif; ?>
<?php if ($summary['missing_cost']): ?><p class="small text-warning mt-2"><?=$summary['missing_cost'];?> counted items have no unit cost. The valuation is incomplete.</p><?php endif; ?>

<?php if (stocktakeCanDeleteSession($userId, $bookId) && $take['status'] !== 'posted'): ?>
<section class="mt-4 pt-3 border-top">
    <h5 class="text-danger"><i class="fa fa-trash-alt"></i> Owner / Superadministrator: Delete and start again</h5>
    <p class="small text-muted">Deletes this session after saving an audit snapshot. Only use for erroneous or test counts. Posted or financially linked sessions cannot be deleted.</p>
    <form class="kh-stocktake-form" data-action="delete_session" data-confirm="Permanently remove this stock-taking session from active records? An audit copy will be kept. This cannot be undone from the dashboard.">
        <input type="hidden" name="action" value="delete_session">
        <input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>">
        <input type="hidden" name="take_id" value="<?=(int)$take['id'];?>">
        <input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
        <label for="kh-delete-reason" class="small font-weight-bold">Reason for deletion</label>
        <textarea id="kh-delete-reason" name="delete_reason" class="form-control form-control-sm mb-2" minlength="10" maxlength="500" required placeholder="e.g. Test count entered during staff training; replacing with verified October stock count."></textarea>
        <button type="submit" class="btn btn-outline-danger btn-flat"><i class="fa fa-trash-alt"></i> Delete stock-taking session</button>
    </form>
</section>
<?php endif; ?>
