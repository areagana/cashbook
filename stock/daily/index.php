<?php
/** KashHub Daily Stock Tracker dashboard. Location: stock/daily/index.php */
require_once __DIR__.'/bootstrap.php';
[$encryptedBookId,$bookId,$book,$userId]=dsContext();
$reviewer=dsCanReview($bookId);
$csrf=dsToken();
$month=(string)($_GET['month']??date('Y-m'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month)) $month=date('Y-m');
$from=$month.'-01'; $to=(new DateTimeImmutable($from))->modify('+1 month')->format('Y-m-d');
$sessions=dsRows('SELECT d.*,COUNT(l.id) AS total_items,SUM(l.counted_qty IS NOT NULL) AS counted_items FROM cashbook_daily_stocktakes d LEFT JOIN cashbook_daily_stocktake_items l ON l.daily_id=d.id AND l.book_id=d.book_id WHERE d.book_id=? AND d.count_date>=? AND d.count_date<? GROUP BY d.id ORDER BY d.count_date DESC','iss',[$bookId,$from,$to]);
$stats=dsRows('SELECT COUNT(*) total, SUM(status=\'approved\') approved, SUM(status=\'review\') pending FROM cashbook_daily_stocktakes WHERE book_id=? AND count_date>=? AND count_date<?','iss',[$bookId,$from,$to])[0];
pageHeaderLevel2('Daily Stock Tracking');
?>
<style>
.kh-daily{background:#f5f7fa;padding:18px;min-height:75vh;color:#243244}.kh-daily .panel{background:white;border:1px solid #dce4e8;margin-bottom:15px}.kh-daily .panel-head{padding:15px 18px;border-bottom:1px solid #e4e8ed;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}.kh-daily .panel-body{padding:17px}.kh-daily h2{font-size:21px;font-weight:800;margin:0}.kh-daily .metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:15px}.kh-daily .metric{background:white;border:1px solid #dce4e8;border-top:3px solid #176b51;padding:15px}.kh-daily .metric strong{display:block;font-size:25px}.kh-daily .btn{border-radius:2px!important}.kh-daily .table-responsive{overflow-x:auto}.kh-daily .kh-daily-table{min-width:560px}.kh-daily .kh-daily-table th{background:#f0f5f3;font-size:11px;text-transform:uppercase}.kh-daily-modal{position:fixed;inset:0;z-index:1085;background:rgba(16,24,40,.65);display:none;align-items:center;justify-content:center;padding:14px}.kh-daily-modal.open{display:flex}.kh-daily-dialog{background:#fff;width:min(1150px,100%);max-height:92vh;display:flex;flex-direction:column}.kh-daily-modal-head{padding:14px 18px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center}.kh-daily-modal-body{padding:17px;overflow:auto}@media(max-width:650px){.kh-daily{padding:10px}.kh-daily .metrics{gap:7px}.kh-daily .metric{padding:10px}.kh-daily .metric strong{font-size:19px}}
</style>
<div class="container-fluid kh-daily">
<section class="panel"><div class="panel-head"><div><small><a href="../?bkid=<?=urlencode($encryptedBookId);?>">Stock Management</a> / Daily Tracking</small><h2><i class="fa fa-calendar-check"></i> <?=dsEsc($book->name);?> — Daily Stock Tracker</h2><small class="text-muted">Blind physical counts and daily variance investigations</small></div><a class="btn btn-outline-secondary btn-flat" href="../?bkid=<?=urlencode($encryptedBookId);?>">Back to stock</a></div></section>
<div id="kh-daily-message" aria-live="polite"></div>
<section class="metrics"><div class="metric"><small>Daily sessions</small><strong><?=(int)$stats['total'];?></strong></div><div class="metric"><small>Awaiting review</small><strong><?=(int)$stats['pending'];?></strong></div><div class="metric"><small>Approved</small><strong><?=(int)$stats['approved'];?></strong></div></section>
<?php if(dsCanStartSession($bookId)): ?>
<section class="panel"><div class="panel-head"><strong>Open today's physical count</strong></div><div class="panel-body"><form id="kh-daily-create" class="form-inline" method="post"><input type="hidden" name="action" value="create"><input type="hidden" name="bkid" value="<?=dsEsc($encryptedBookId);?>"><input type="hidden" name="csrf" value="<?=dsEsc($csrf);?>"><label for="daily-date" class="mr-2">Count date</label><input class="form-control mr-2" type="date" id="daily-date" name="count_date" value="<?=date('Y-m-d');?>" readonly><button class="btn btn-success btn-flat" type="submit">Create today's session</button></form><small class="text-muted">Snapshot uses current stock balances at creation. Open sessions at the intended counting cutoff, after recording the day's movements.</small></div></section>
<?php endif; ?>
<section class="panel"><div class="panel-head"><strong>Daily stock register</strong><form method="get" class="form-inline"><input type="hidden" name="bkid" value="<?=dsEsc($encryptedBookId);?>"><label for="daily-month" class="mr-2">Month</label><input id="daily-month" class="form-control form-control-sm mr-2" type="month" name="month" value="<?=dsEsc($month);?>"><button class="btn btn-sm btn-outline-success btn-flat">Filter</button></form></div>
<div class="table-responsive"><table class="table table-bordered table-hover mb-0"><thead><tr><th>Date</th><th>Status</th><th>Count progress</th><th>Created</th><th>Action</th></tr></thead><tbody>
<?php foreach($sessions as $session): ?><tr><td><strong><?=dsEsc($session['count_date']);?></strong></td><td><?=dsEsc(ucfirst($session['status']));?></td><td><?=(int)$session['counted_items'];?> / <?=(int)$session['total_items'];?></td><td><?=dsEsc(substr((string)$session['created_at'],0,16));?></td><td><button type="button" class="btn btn-sm btn-outline-success btn-flat kh-open-daily" data-id="<?=(int)$session['id'];?>">Open count</button></td></tr><?php endforeach; ?>
<?php if(!$sessions): ?><tr><td colspan="5" class="text-center text-muted p-4">No daily stock sessions for this month.</td></tr><?php endif; ?></tbody></table></div></section>
<?php if($reviewer): ?><section class="panel"><div class="panel-head"><strong>Monthly discrepancy follow-up</strong><a class="btn btn-sm btn-outline-primary btn-flat" href="report.php?bkid=<?=urlencode($encryptedBookId);?>&amp;month=<?=urlencode($month);?>">Open variance report</a></div><div class="panel-body text-muted small">Review the daily variance history and investigate unresolved shortages before the monthly stocktaking session.</div></section><?php endif; ?>
</div>
<div class="kh-daily-modal" id="kh-daily-modal" role="dialog" aria-modal="true" aria-labelledby="kh-daily-title" aria-hidden="true"><div class="kh-daily-dialog"><div class="kh-daily-modal-head"><strong id="kh-daily-title">Daily stock count</strong><button type="button" class="btn btn-sm btn-outline-secondary" id="kh-daily-close" aria-label="Close">&times;</button></div><div class="kh-daily-modal-body" id="kh-daily-content"></div></div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    if(!window.jQuery)return;
    (function($){'use strict';
        const bookId=<?=json_encode($encryptedBookId);?>,backend='save/index.php',modal=$('#kh-daily-modal');
        function esc(s){return $('<div>').text(String(s??'')).html();}
        function msg(s,error){$('#kh-daily-message').html('<div class="alert '+(error?'alert-danger':'alert-success')+'">'+esc(s)+'</div>');}
        function close(){modal.removeClass('open').attr('aria-hidden','true');$('body').css('overflow','');}
        function open(id){modal.addClass('open').attr('aria-hidden','false');$('body').css('overflow','hidden');$('#kh-daily-content').html('<p class="p-3 text-muted">Loading count...</p>');$.get(backend,{action:'details',bkid:bookId,daily_id:id}).done(html=>$('#kh-daily-content').html(html)).fail(xhr=>$('#kh-daily-content').html('<div class="alert alert-danger">'+esc(xhr.responseText||'Unable to load count.')+'</div>'));}
        $(document).on('click','.kh-open-daily',function(){open($(this).data('id'));});
        $('#kh-daily-close').on('click',close);modal.on('click',function(e){if(e.target===this)close();});
        $(document).on('keydown',function(e){if(e.key==='Escape')close();});
        $(document).on('submit','#kh-daily-create,.kh-daily-form',function(e){e.preventDefault();const form=this;if(form.dataset.busy==='1')return;if(form.dataset.confirm&&!confirm(form.dataset.confirm))return;form.dataset.busy='1';const button=$(form).find('button[type=submit]').prop('disabled',true);$.ajax({url:backend,type:'POST',data:$(form).serialize(),dataType:'json'}).done(r=>{if(!r.status){msg(r.message||'Action failed.',true);return;}msg(r.message||'Saved.');window.location.href='?bkid='+encodeURIComponent(bookId)+'&month='+<?=json_encode($month);?>+'&daily_id='+encodeURIComponent(r.daily_id);}).fail(xhr=>msg(xhr.responseJSON?.message||'Unable to save.',true)).always(()=>{form.dataset.busy='0';button.prop('disabled',false);});});
        const requested=new URLSearchParams(location.search).get('daily_id');if(requested&&/^\d+$/.test(requested))open(Number(requested));
    })(window.jQuery);
});
</script>
<?php pageFooterLevel2(); ?>
