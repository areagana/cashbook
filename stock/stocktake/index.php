<?php
/**
 * KASHHUB — STOCK TAKING DASHBOARD
 * Location: stock/stocktake/index.php
 * All writes and modal details are handled by save/index.php.
 */
require_once __DIR__ . '/bootstrap.php';
[$encryptedBookId, $bookId, $book, $userId] = stContext();
$csrf=stToken();

// 1. FILTER: OPEN BY DEFAULT; CLOSED ONLY WHEN REQUESTED.
$showClosed=($_GET['view'] ?? 'open')==='closed';
$statusCondition=$showClosed ? "st.status IN ('approved','posted','cancelled')" : "st.status IN ('draft','counting','review')";
$sessions=stRows("SELECT st.*, COUNT(li.id) total_items, SUM(CASE WHEN li.counted_qty IS NOT NULL THEN 1 ELSE 0 END) counted_items FROM cashbook_stocktakes st LEFT JOIN cashbook_stocktake_items li ON li.stocktake_id=st.id AND li.book_id=st.book_id WHERE st.book_id=? AND $statusCondition GROUP BY st.id ORDER BY st.period_month DESC,st.id DESC",'i',[$bookId]);
$totals=stRows("SELECT COUNT(*) total, SUM(CASE WHEN status IN ('draft','counting','review') THEN 1 ELSE 0 END) open_count, SUM(CASE WHEN status IN ('approved','posted','cancelled') THEN 1 ELSE 0 END) closed_count FROM cashbook_stocktakes WHERE book_id=?",'i',[$bookId])[0];

// 2. PAGE SHELL (LEVEL TWO)
pageHeaderLevel2('Stock Taking & Reconciliation');
?>
<style>
/* Dashboard */
.kh-take{background:#f5f7fa;min-height:75vh;padding:20px;color:#243244}
.kh-take .kh-panel{background:#fff;border:1px solid #dce4e8;margin-bottom:16px}
.kh-take .kh-panel-head{padding:16px 20px;border-bottom:1px solid #e4e8ed;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.kh-take .kh-panel-body{padding:18px 20px}
.kh-take h2{font-size:22px;font-weight:800;margin:0}
.kh-take h3{font-size:16px;font-weight:700;margin:0}
.kh-take .kh-muted{color:#667085;font-size:12px}
.kh-take .kh-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
.kh-take .kh-metric{background:#fff;border:1px solid #dce4e8;border-top:3px solid #176b51;padding:16px}
.kh-take .kh-metric strong{display:block;font-size:26px}
.kh-take .btn{border-radius:2px!important}
.kh-take .btn-success{background:#176b51;border-color:#176b51}
.kh-table-scroll{overflow-x:auto}
.kh-take .kh-table{width:100%;border-collapse:collapse;min-width:690px;font-size:13px}
.kh-take .kh-table th,.kh-take .kh-table td{padding:12px;border-bottom:1px solid #e4e8ed}
.kh-take .kh-table th{background:#edf3f1;font-size:11px;text-transform:uppercase}
.kh-take .kh-status{display:inline-block;padding:4px 8px;background:#eaf4ef;color:#176b51;font-size:11px;font-weight:700}
.kh-take .kh-modal{position:fixed;inset:0;z-index:1080;display:none;align-items:center;justify-content:center;padding:14px;background:rgba(16,24,40,.64)}
.kh-take .kh-modal.is-open{display:flex}
.kh-take .kh-modal-dialog{background:#fff;width:min(1200px,100%);max-height:92vh;display:flex;flex-direction:column;border:1px solid #dce4e8;box-shadow:0 12px 36px rgba(0,0,0,.18)}
.kh-take .kh-modal-header{padding:14px 18px;border-bottom:1px solid #e4e8ed;display:flex;align-items:center;justify-content:space-between}
.kh-take .kh-modal-content{padding:18px;overflow-y:auto}
.kh-take .kh-modal-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;margin-bottom:16px}
.kh-take .kh-modal-summary>div{background:#f5f7fa;padding:12px;border:1px solid #e4e8ed}
.kh-take .kh-modal-summary small{display:block;color:#667085}
.kh-take .kh-modal-summary strong{display:block;font-size:16px}
.kh-take .kh-count-table{min-width:850px;font-size:12px}
.kh-take .kh-count-table input{min-width:100px}
@media(max-width:750px){.kh-take{padding:10px}.kh-take .kh-metrics{grid-template-columns:repeat(2,1fr)}.kh-take .kh-modal-summary{grid-template-columns:repeat(2,1fr)}.kh-take .kh-modal-content{padding:10px}}
</style>
<div class="container-fluid kh-take" id="kh-stocktake-page">
    <!-- 3. HEADER -->
    <section class="kh-panel">
        <div class="kh-panel-head">
            <div>
                <div class="kh-muted"><a href="../?bkid=<?=urlencode($encryptedBookId);?>">Stock Management</a> / Stock Taking</div>
                <h2><i class="fa fa-clipboard-check"></i> <?=stEscape($book->name);?> — Stock Taking</h2>
                <div class="kh-muted">Physical inventory, reconciliation and approved stock valuations</div>
            </div>
            <a class="btn btn-outline-secondary btn-flat" href="../?bkid=<?=urlencode($encryptedBookId);?>">Back to stock</a>
        </div>
    </section>
    <div id="kh-message" role="status" aria-live="polite"></div>

    <!-- 4. DASHBOARD COUNTERS -->
    <section class="kh-metrics">
        <div class="kh-metric"><span class="kh-muted">All sessions</span><strong><?=(int)$totals['total'];?></strong></div>
        <div class="kh-metric"><span class="kh-muted">Open / In review</span><strong><?=(int)$totals['open_count'];?></strong></div>
        <div class="kh-metric"><span class="kh-muted">Closed / Approved</span><strong><?=(int)$totals['closed_count'];?></strong></div>
    </section>

    <!-- 5. CREATE SESSION -->
    <section class="kh-panel">
        <div class="kh-panel-head"><h3><i class="fa fa-calendar-plus"></i> Start a monthly stock count</h3></div>
        <div class="kh-panel-body">
            <form id="kh-create-form" class="form-inline" method="post">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="bkid" value="<?=stEscape($encryptedBookId);?>">
                <input type="hidden" name="csrf" value="<?=stEscape($csrf);?>">
                <label class="mr-2" for="kh-period">Month</label>
                <input id="kh-period" class="form-control mr-3 mb-2" type="month" name="period" value="<?=date('Y-m');?>" required>
                <label class="mr-2" for="kh-date">Count date</label>
                <input id="kh-date" class="form-control mr-3 mb-2" type="date" name="count_date" value="<?=date('Y-m-d');?>" required>
                <button type="submit" class="btn btn-success btn-flat mb-2"><i class="fa fa-plus"></i> Create session</button>
            </form>
            <p class="kh-muted mb-0">Expected balances are captured at creation time. Historical month-end balances require dated stock movement reconstruction.</p>
        </div>
    </section>

    <!-- 6. SESSION REGISTER -->
    <section class="kh-panel">
        <div class="kh-panel-head">
            <h3><?=$showClosed?'Closed stock-taking records':'Open stock-taking records';?></h3>
            <div class="btn-group btn-group-sm" role="group" aria-label="Session filter">
                <a class="btn <?=$showClosed?'btn-outline-success':'btn-success';?> btn-flat" href="?bkid=<?=urlencode($encryptedBookId);?>&amp;view=open">Open</a>
                <a class="btn <?=$showClosed?'btn-success':'btn-outline-success';?> btn-flat" href="?bkid=<?=urlencode($encryptedBookId);?>&amp;view=closed">Closed</a>
            </div>
        </div>
        <div class="kh-table-scroll">
            <table class="kh-table">
                <thead><tr><th>Month</th><th>Count date</th><th>Status</th><th>Count progress</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><strong><?=stEscape($session['period_month']);?></strong></td>
                        <td><?=stEscape($session['count_date']);?></td>
                        <td><span class="kh-status"><?=stEscape(ucfirst($session['status']));?></span></td>
                        <td><?=(int)$session['counted_items'];?> / <?=(int)$session['total_items'];?></td>
                        <td><?=stEscape(substr((string)$session['created_at'],0,10));?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-success btn-flat kh-open-session" data-id="<?=(int)$session['id'];?>"><i class="fa fa-folder-open"></i> Open</button>
                            <?php if($session['status'] !== 'cancelled'): ?>
                                <a target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger btn-flat" href="print.php?bkid=<?=urlencode($encryptedBookId);?>&amp;take_id=<?=(int)$session['id'];?>"><i class="fa fa-file-pdf"></i> <?=$session['status']==='approved'||$session['status']==='posted'?'Approved PDF':'Provisional PDF';?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(!$sessions): ?><tr><td colspan="6" class="text-center text-muted p-4">No <?=$showClosed?'closed':'open';?> stock-taking sessions. <?=$showClosed?'Switch to Open to see active counts.':'Create a monthly count to get started.';?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- 7. CENTRAL SESSION MODAL -->
    <div id="kh-session-modal" class="kh-modal" role="dialog" aria-modal="true" aria-labelledby="kh-modal-title" aria-hidden="true">
        <div class="kh-modal-dialog">
            <div class="kh-modal-header">
                <h3 id="kh-modal-title">Stock-taking details</h3>
                <button type="button" id="kh-close-modal" class="btn btn-outline-secondary btn-sm btn-flat" aria-label="Close"><i class="fa fa-times"></i></button>
            </div>
            <div class="kh-modal-content" id="kh-modal-body"></div>
        </div>
    </div>
</div>
<script>
/* 8. STOCK-TAKING AJAX INTERACTIONS — NO POST TO INDEX */
// Wait until the Level 2 footer has loaded jQuery and the shared scripts.
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.jQuery === 'undefined') {
        console.error('KashHub Stock Taking: jQuery did not load. Check Level 2 asset paths.');
        return;
    }
    (function($){
    'use strict';
    const bookId=<?=json_encode($encryptedBookId);?>;
    const backend='save/index.php';
    const modal=$('#kh-session-modal');
    let activeSession=null;
    let lastTrigger=null;
    const escapeHtml=value=>$('<div>').text(String(value??'')).html();

    function message(text,error=false){$('#kh-message').html('<div class="alert '+(error?'alert-danger':'alert-success')+'">'+escapeHtml(text)+'</div>');}
    function closeModal(){modal.removeClass('is-open').attr('aria-hidden','true');$('body').css('overflow','');if(lastTrigger)lastTrigger.focus();}
    function openSession(id,trigger){
        activeSession=id;
        lastTrigger=trigger||lastTrigger;
        $('#kh-modal-body').html('<p class="text-muted p-3"><i class="fa fa-spinner fa-spin"></i> Loading stock count...</p>');
        modal.addClass('is-open').attr('aria-hidden','false');
        $('body').css('overflow','hidden');
        $('#kh-close-modal').trigger('focus');
        $.get(backend,{action:'details',bkid:bookId,take_id:id}).done(html=>$('#kh-modal-body').html(html)).fail(xhr=>$('#kh-modal-body').html('<div class="alert alert-danger">'+escapeHtml(xhr.responseText||'Unable to load session.')+'</div>'));
    }
    $(document).on('click','.kh-open-session',function(){openSession($(this).data('id'),this);});
    $('#kh-close-modal').on('click',closeModal);
    modal.on('click',function(event){if(event.target===this)closeModal();});
    $(document).on('keydown.khStocktake',function(event){if(event.key==='Escape'&&modal.hasClass('is-open'))closeModal();});
    $(document).on('submit','#kh-create-form,.kh-stocktake-form',function(event){
        event.preventDefault();
        const form=this;
        if(form.dataset.busy==='1')return;
        if(form.dataset.confirm&&!window.confirm(form.dataset.confirm))return;
        form.dataset.busy='1';
        const button=$(form).find('button[type=submit]').prop('disabled',true);
        $.ajax({url:backend,type:'POST',data:$(form).serialize(),dataType:'json'})
            .done(function(result){
                if(!result.status){message(result.message||'Action failed.',true);return;}
                message(result.message||'Saved successfully.');
                if(form.dataset.action==='delete_session'){
                    window.location.href='?bkid='+encodeURIComponent(bookId)+'&view=open';
                    return;
                }
                if(result.take_id){
                    // Reload the dashboard to update status and counts after each save.
                    // The session can be reopened from the refreshed register.
                    window.location.href='?bkid='+encodeURIComponent(bookId)+'&view='+((form.dataset.action==='approve')?'closed':'open')+'&take_id='+encodeURIComponent(result.take_id);
                }else window.location.reload();
            })
            .fail(xhr=>message(xhr.responseJSON?.message||'Unable to save. Check the server response.',true))
            .always(()=>{form.dataset.busy='0';button.prop('disabled',false);});
    });
    const requestedId=new URLSearchParams(window.location.search).get('take_id');
    if(requestedId&&/^\d+$/.test(requestedId))openSession(Number(requestedId),null);
    })(window.jQuery);

    (function () {
    'use strict';

    document.body.classList.add('kh-stocktake-nav');

    const sidebar = document.getElementById('sidebar');
    const desktopButton = document.getElementById('toggle-btn');
    const mobileButton = document.getElementById('mobile-menu-btn');
    const mobileClose = document.getElementById('mobile-close-btn');
    const overlay = document.getElementById('sidebar-overlay');

    if (!sidebar) return;

    function setExpanded(expanded) {
        sidebar.classList.toggle('expand', expanded);
        if (desktopButton) desktopButton.setAttribute('aria-expanded', String(expanded));
    }

    function setMobileOpen(open) {
        sidebar.classList.toggle('kh-mobile-open', open);
        sidebar.classList.toggle('active', open);
        if (overlay) {
            overlay.classList.toggle('kh-mobile-open', open);
            overlay.classList.toggle('active', open);
        }
        if (mobileButton) mobileButton.setAttribute('aria-expanded', String(open));
    }

    // Respect the current collapsed-by-default preference.
    setExpanded(false);

    function handleToggle(button, callback) {
        if (!button) return;
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            callback();
        }, true);
    }

    handleToggle(desktopButton, function () {
        setExpanded(!sidebar.classList.contains('expand'));
    });
    handleToggle(mobileButton, function () {
        setMobileOpen(!sidebar.classList.contains('kh-mobile-open'));
    });
    handleToggle(mobileClose, function () { setMobileOpen(false); });
    handleToggle(overlay, function () { setMobileOpen(false); });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setMobileOpen(false);
    });
})();
});
</script>
<?php pageFooterLevel2(); ?>
