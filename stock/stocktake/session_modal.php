<?php
/**
 * KashHub stocktaking session modal.
 * Location: stock/stocktake/session_modal.php
 * Included by save/index.php?action=details.
 * $canReview is set by the save handler; never infer review rights from UI state.
 */
$canReview = isset($canReview) && $canReview === true;
$editable = !empty($editable);
$approver = $canReview && !empty($approver);
$canDelete = $canReview && stocktakeCanDeleteSession($userId, $bookId);
$period = (string)($take['period_month'] ?? '');
$status = (string)($take['status'] ?? '');
$takeId = (int)($take['id'] ?? 0);
?>
<div class="kh-modal-summary" style="grid-template-columns:repeat(<?= $canReview ? '5' : '3'; ?>,minmax(0,1fr));">
    <div><small>Month</small><strong><?=stEscape($period);?></strong></div>
    <div><small>Status</small><strong><?=stEscape(ucfirst($status));?></strong></div>
    <div><small>Counted</small><strong><?=(int)($summary['counted'] ?? 0);?> / <?=(int)($summary['total'] ?? 0);?></strong></div>
    <?php if ($canReview): ?>
        <div><small>Variance items</small><strong><?=(int)($summary['variance'] ?? 0);?></strong></div>
        <div><small>Physical value</small><strong>UGX <?=number_format((float)($summary['counted_value'] ?? 0),2);?></strong></div>
    <?php endif; ?>
</div>

<?php if ($canReview): ?>
    <p class="text-muted small">Expected quantities were captured when this session was created. Approved counts do not automatically adjust live stock balances.</p>
<?php else: ?>
    <p class="text-muted small">Count each item physically and enter the quantity found. Add any observations in Remarks. System quantities and valuations are reserved for review.</p>
<?php endif; ?>

<?php if ($editable): ?>
<form class="kh-stocktake-form" data-action="save_counts" method="post">
    <input type="hidden" name="action" value="save_counts">
    <input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>">
    <input type="hidden" name="take_id" value="<?=$takeId;?>">
    <input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
<?php endif; ?>

<div class="kh-table-scroll">
    <table class="table table-sm table-bordered kh-count-table" style="min-width:<?= $canReview ? '850' : '540'; ?>px;">
        <thead>
        <tr>
            <th>Item / Unit</th>
            <?php if ($canReview): ?><th class="text-right">Expected</th><?php endif; ?>
            <th>Physical qty</th>
            <?php if ($canReview): ?>
                <th class="text-right">Variance</th>
                <th>Unit cost (UGX)</th>
                <th class="text-right">Counted value</th>
            <?php endif; ?>
            <th>Remarks</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($lines as $line):
            $lineId = (int)$line['id'];
            $counted = $line['counted_qty'] ?? null;
            $reason = (string)($line['reason'] ?? '');
            $expected = $canReview ? ($line['expected_qty'] ?? null) : null;
            $unitCost = $canReview ? ($line['unit_cost'] ?? null) : null;
            $difference = $canReview && $counted !== null && $expected !== null
                ? (float)$counted - (float)$expected : null;
            $value = $canReview && $counted !== null && $unitCost !== null
                ? (float)$counted * (float)$unitCost : null;
        ?>
        <tr>
            <td>
                <strong><?=stEscape($line['name'] ?? '');?></strong>
                <small class="d-block text-muted"><?=stEscape($line['units'] ?? '');?></small>
            </td>
            <?php if ($canReview): ?>
                <td class="text-right"><?=$expected===null?'—':number_format((float)$expected,3);?></td>
            <?php endif; ?>
            <td>
                <?php if ($editable): ?>
                    <input type="number" min="0" step="0.001" name="qty[<?=$lineId;?>]"
                           value="<?=stEscape($counted);?>" class="form-control form-control-sm"
                           aria-label="Physical quantity for <?=stEscape($line['name'] ?? 'item');?>">
                <?php else: ?>
                    <?=$counted===null?'—':number_format((float)$counted,3);?>
                <?php endif; ?>
            </td>
            <?php if ($canReview): ?>
                <td class="text-right <?=$difference!==null&&$difference<0?'text-danger':'text-success';?>">
                    <?=$difference===null?'—':number_format($difference,3);?>
                </td>
                <td>
                    <?php if ($editable): ?>
                        <input type="number" min="0" step="0.0001" name="cost[<?=$lineId;?>]"
                               value="<?=stEscape($unitCost);?>" class="form-control form-control-sm"
                               aria-label="Unit cost for <?=stEscape($line['name'] ?? 'item');?>">
                    <?php else: ?>
                        <?=$unitCost===null?'—':number_format((float)$unitCost,4);?>
                    <?php endif; ?>
                </td>
                <td class="text-right"><?=$value===null?'—':number_format($value,2);?></td>
            <?php endif; ?>
            <td>
                <?php if ($editable): ?>
                    <input type="text" maxlength="255" name="reason[<?=$lineId;?>]"
                           value="<?=stEscape($reason);?>" class="form-control form-control-sm"
                           placeholder="Optional observation"
                           aria-label="Remarks for <?=stEscape($line['name'] ?? 'item');?>">
                <?php else: ?>
                    <?=stEscape($reason);?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($editable): ?>
    <div class="mt-3">
        <button type="submit" class="btn btn-success btn-flat"><i class="fa fa-save"></i> Save physical counts</button>
    </div>
</form>
<form class="kh-stocktake-form mt-2" data-action="submit_review" method="post"
      data-confirm="Submit for review? Save any unsaved counts first.">
    <input type="hidden" name="action" value="submit_review">
    <input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>">
    <input type="hidden" name="take_id" value="<?=$takeId;?>">
    <input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
    <button type="submit" class="btn btn-outline-primary btn-flat">Submit for review</button>
</form>
<?php endif; ?>

<?php // Full PDFs must remain reviewer-only until print.php enforces a redacted operator report. ?>
<?php if ($canReview && !in_array($status,['approved','posted','cancelled'],true)): ?>
    <a class="btn btn-outline-danger btn-flat mt-3" target="_blank" rel="noopener"
       href="print.php?bkid=<?=urlencode($encryptedBookId);?>&amp;take_id=<?=$takeId;?>">
        <i class="fa fa-file-pdf"></i> Download provisional PDF (Not approved)
    </a>
<?php endif; ?>

<?php if ($status==='review' && $approver): ?>
    <form class="kh-stocktake-form mt-3" data-action="approve" method="post"
          data-confirm="Approve and close this stock count? This does not post stock movements.">
        <input type="hidden" name="action" value="approve">
        <input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>">
        <input type="hidden" name="take_id" value="<?=$takeId;?>">
        <input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
        <button type="submit" class="btn btn-success btn-flat"><i class="fa fa-check-circle"></i> Approve and close</button>
    </form>
    <form class="kh-stocktake-form mt-2" data-action="return_corrections" method="post"
          data-confirm="Return this record for corrections before approval?">
        <input type="hidden" name="action" value="return_corrections">
        <input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>">
        <input type="hidden" name="take_id" value="<?=$takeId;?>">
        <input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
        <button type="submit" class="btn btn-outline-warning btn-flat"><i class="fa fa-edit"></i> Return for corrections</button>
    </form>
<?php endif; ?>

<?php if ($canReview && in_array($status,['approved','posted'],true)): ?>
    <a class="btn btn-outline-success btn-flat mt-3" target="_blank" rel="noopener"
       href="print.php?bkid=<?=urlencode($encryptedBookId);?>&amp;take_id=<?=$takeId;?>">
        <i class="fa fa-file-pdf"></i> Approved stock report (PDF)
    </a>
<?php endif; ?>
<?php if ($canReview && !empty($summary['missing_cost'])): ?>
    <p class="small text-warning mt-2"><?=(int)$summary['missing_cost'];?> counted items have no unit cost. The valuation is incomplete.</p>
<?php endif; ?>

<?php if ($canDelete && $status!=='posted'): ?>
<section class="mt-4 pt-3 border-top">
    <h5 class="text-danger"><i class="fa fa-trash-alt"></i> Owner / Superadministrator: Delete and start again</h5>
    <p class="small text-muted">Deletes this session after saving an audit snapshot. Posted or financially linked sessions cannot be deleted.</p>
    <form class="kh-stocktake-form" data-action="delete_session" method="post"
          data-confirm="Permanently remove this stock-taking session? An audit copy will be kept.">
        <input type="hidden" name="action" value="delete_session">
        <input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>">
        <input type="hidden" name="take_id" value="<?=$takeId;?>">
        <input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
        <label for="kh-delete-reason" class="small font-weight-bold">Reason for deletion</label>
        <textarea id="kh-delete-reason" name="delete_reason" class="form-control form-control-sm mb-2"
                  minlength="10" maxlength="500" required></textarea>
        <button type="submit" class="btn btn-outline-danger btn-flat"><i class="fa fa-trash-alt"></i> Delete stock-taking session</button>
    </form>
</section>
<?php endif; ?>
