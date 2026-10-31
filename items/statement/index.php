<?php
require_once __DIR__.'/common.php';
$transactions = itemQuery('SELECT id, created_at, details, credit_amount, debit_amount, quantity, rate, type, customer_id, category_id FROM cashbook_transactions WHERE book_id = ? AND item_id = ? ORDER BY created_at DESC, id DESC', 'ii', [$bookId,$itemId]);
$stocks = itemQuery('SELECT id, created_at, transaction_type, quantity_in, quantity_out, balance, unit_cost, total_cost, reference FROM cashbook_stocks WHERE book_id = ? AND item_id = ? ORDER BY created_at DESC, id DESC', 'ii', [$bookId,$itemId]);
$references = itemReferences($itemId);
$otherItems = itemQuery('SELECT id, name, units FROM cashbook_items WHERE book_id = ? AND id <> ? ORDER BY name', 'ii', [$bookId,$itemId]);
pageHeader('Item Statement');
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div><h3 class="mb-1"><i class="fa fa-list-alt"></i> Item Statement: <?=itemEsc($item['name']);?></h3><small class="text-muted"><?=itemEsc($book->name);?> | Unit: <?=itemEsc($item['units']??'-');?></small></div>
    <a class="btn btn-outline-secondary" href="../?bsid=<?=urlencode($bsid);?>">Back to Items</a>
  </div>
  <div class="alert alert-info">Transaction credits and debits are accounting entries, not necessarily actual cash received or paid. Stock movements are shown separately to prevent double counting.</div>
  <div class="card mb-3"><div class="card-header font-weight-bold">Transaction history</div><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>Date</th><th>Details</th><th>Type</th><th class="text-right">Quantity</th><th class="text-right">Credit</th><th class="text-right">Debit</th><th>Action</th></tr></thead><tbody>
  <?php $hasTransactions=false; while($row=$transactions->fetch_assoc()): $hasTransactions=true; ?>
    <tr><td><?=itemEsc($row['created_at']);?></td><td><?=itemEsc($row['details']);?></td><td><?=itemEsc($row['type']);?></td><td class="text-right"><?=number_format((float)$row['quantity'],2);?></td><td class="text-right"><?=number_format((float)$row['credit_amount'],2);?></td><td class="text-right"><?=number_format((float)$row['debit_amount'],2);?></td><td>
    <?php if(isItemManager()): ?><button class="btn btn-sm btn-outline-primary transfer-btn" data-id="<?= (int)$row['id'];?>">Transfer</button><?php endif;?></td></tr>
  <?php endwhile; if(!$hasTransactions): ?><tr><td colspan="7" class="text-center text-muted p-3">No transactions recorded.</td></tr><?php endif;?></tbody></table></div></div>
  <div class="card mb-3"><div class="card-header font-weight-bold">Stock movements</div><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>Date</th><th>Movement</th><th>Reference</th><th class="text-right">In</th><th class="text-right">Out</th><th class="text-right">Recorded Balance</th><th class="text-right">Cost</th></tr></thead><tbody>
  <?php $hasStock=false; while($row=$stocks->fetch_assoc()): $hasStock=true; ?><tr><td><?=itemEsc($row['created_at']);?></td><td><?=itemEsc($row['transaction_type']);?></td><td><?=itemEsc($row['reference']);?></td><td class="text-right"><?=number_format((float)$row['quantity_in'],2);?></td><td class="text-right"><?=number_format((float)$row['quantity_out'],2);?></td><td class="text-right"><?=number_format((float)$row['balance'],2);?></td><td class="text-right"><?=number_format((float)$row['total_cost'],2);?></td></tr><?php endwhile; if(!$hasStock): ?><tr><td colspan="7" class="text-center text-muted p-3">No stock movements.</td></tr><?php endif;?></tbody></table></div></div>
  <div class="card mb-3"><div class="card-header font-weight-bold">Related records / deletion eligibility</div><div class="card-body">
  <?php if(!$references): ?><span class="badge badge-success">No related records — item may be deleted</span><?php else: ?><p class="text-warning">This item cannot be deleted until all references are resolved.</p><?php foreach($references as $table=>$count): ?><span class="badge badge-secondary mr-2 mb-2"><?=itemEsc($table);?>: <?=$count;?></span><?php endforeach; endif;?></div></div>
  <?php if(isItemManager()): ?>
  <div class="card mb-3"><div class="card-header font-weight-bold">Transfer one transaction to another item</div><div class="card-body"><p class="text-muted">Transfers related cash and ledger item references together. Transactions linked to stock movements are blocked until their stock balances can be reconciled safely.</p>
  <form id="transferForm" class="form-row align-items-end"><input type="hidden" name="action" value="transfer"><input type="hidden" name="bsid" value="<?=itemEsc($bsid);?>"><input type="hidden" name="item_id" value="<?=$itemId;?>">
  <div class="col-md-3 mb-2"><label>Transaction ID</label><input class="form-control" id="transactionId" type="number" min="1" name="transaction_id" required></div>
  <div class="col-md-5 mb-2"><label>Move to item</label><select class="form-control" name="target_item_id" required><option value="">Select target item</option><?php while($other=$otherItems->fetch_assoc()): ?><option value="<?=(int)$other['id'];?>"><?=itemEsc($other['name'].' ('.($other['units']??'').')');?></option><?php endwhile;?></select></div>
  <div class="col-md-4 mb-2"><button class="btn btn-primary btn-block" type="submit">Transfer Transaction</button></div></form>
  <div id="actionMessage" class="mt-2"></div></div></div>
  <?php endif; ?>
</div>
<script>
$(document).on('click','.transfer-btn',function(){ $('#transactionId').val($(this).data('id')); $('#transferForm')[0].scrollIntoView({behavior:'smooth'}); });
$(document).on('submit','#transferForm',function(e){e.preventDefault(); if(!confirm('Transfer this transaction to the selected item?'))return; $.ajax({url:'action.php',method:'POST',data:$(this).serialize(),dataType:'json',success:function(r){alert(r.message);if(r.ok)location.reload();},error:function(){alert('Transfer failed. Check server logs.');}});});
</script>
<?php pageFooter(); ?>
