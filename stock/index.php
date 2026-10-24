<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        $bsid = request('bkid');
        $book = bookFind(encryptor('decrypt',$bsid));
        if(!$book){
            $_SESSION['error']='Business book not found.';
            redirect('../books/');
        }
        // Enforce book-scoped permissions before rendering any inventory data.
        $bookId = (int)$book->id;
        $canViewStock = khCan('stock.view', $bookId);
        $canStocktake = khCan('stocktake.view', $bookId);
        $canManageItems = khCan('items.view', $bookId);
        if (!$canViewStock && !$canStocktake) {
            http_response_code(403);
            exit('Access denied. You do not have permission to view stock for this book.');
        }
        pageHeader('Stock');
        $stockRows=[];
        $stockSql="SELECT i.id,i.name,i.units,COALESCE(sb.balance,0) AS balance
                   FROM cashbook_items i
                   LEFT JOIN (SELECT book_id,item_id,MAX(balance) AS balance FROM cashbook_item_stock_balances GROUP BY book_id,item_id) sb
                     ON sb.item_id=i.id AND sb.book_id=i.book_id
                   WHERE i.book_id=? ORDER BY i.name ASC";
        $stockResult=prepared_statements($stockSql,'i',[(int)$book->id]);
        if($stockResult){while($row=$stockResult->fetch_assoc()){$stockRows[]=$row;}}
        $totalItems=count($stockRows);$lowStock=0;$outOfStock=0;$negativeStock=0;$healthyStock=0;
        foreach($stockRows as $stockRow){
            $balance=(float)$stockRow['balance'];
            if($balance<0)$negativeStock++;
            elseif($balance==0)$outOfStock++;
            elseif($balance<10)$lowStock++;
            else $healthyStock++;
        }
        $esc=static function($value){return htmlspecialchars((string)($value??''),ENT_QUOTES,'UTF-8');};
        ?>
        <style>
        .stock-body{--stock-green:#176b51;--stock-border:#dce3e8;--stock-muted:#667085;color:#263446;padding:16px 18px 32px;background:#f5f7fa;min-height:80vh}
        .stock-body *{box-sizing:border-box}.stock-hero{background:#fff;border:1px solid var(--stock-border);padding:22px 24px;margin-bottom:16px;border-left:4px solid var(--stock-green)}
        .stock-crumb{font-size:12px;color:var(--stock-muted);margin-bottom:10px}.stock-crumb a{color:var(--stock-green);text-decoration:none}.stock-hero h2{font-size:23px;font-weight:800;margin:0 0 5px}.stock-hero p{margin:0;color:var(--stock-muted);font-size:13px}
        .stock-header-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.stock-hero-line{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}.stock-metrics{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:18px}
        .stock-metric{background:#fff;border:1px solid var(--stock-border);padding:16px;border-top:3px solid #176b51}.stock-metric strong{display:block;font-size:27px;font-weight:800;line-height:1.25}.stock-metric span{font-size:12px;color:var(--stock-muted)}
        .stock-metric.warning{border-top-color:#f0ad4e}.stock-metric.danger{border-top-color:#dc3545}.stock-metric.dark{border-top-color:#a32020}.stock-metric.info{border-top-color:#2b6cb0}
        .stock-panel{background:#fff;border:1px solid var(--stock-border);margin-bottom:16px}.stock-panel-head{padding:15px 17px;border-bottom:1px solid var(--stock-border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .stock-panel-head h3{font-size:16px;font-weight:800;margin:0}.stock-panel-head small{color:var(--stock-muted)}.stock-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .stock-tools input,.stock-tools select{border:1px solid #cbd5df;background:#fff;padding:8px 10px;font-size:13px;min-height:36px;border-radius:2px}.stock-tools input{min-width:200px}
        .stock-table-wrap{overflow-x:auto}.stock-table{width:100%;border-collapse:collapse;font-size:13px}.stock-table th,.stock-table td{padding:12px 14px;border-bottom:1px solid #e7ebef;text-align:left;vertical-align:middle}
        .stock-table th{font-size:11px;letter-spacing:.4px;text-transform:uppercase;background:#f8fafb;color:#475467}.stock-table tbody tr:hover{background:#f5faf8}.stock-table .stock-number{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
        .stock-status{display:inline-block;padding:4px 8px;font-size:11px;font-weight:700;border-radius:2px}.stock-status.ok{background:#e5f5ec;color:#167148}.stock-status.low{background:#fff3d6;color:#946200}.stock-status.empty{background:#fde8e8;color:#b42318}.stock-status.negative{background:#f9d6d6;color:#8d1717}
        .stock-card{cursor:pointer}.stock-card:focus-visible{outline:2px solid var(--stock-green);outline-offset:-2px}.stock-action{border-radius:2px!important}.stock-note{padding:12px 17px;background:#fbfcfd;color:#667085;font-size:12px}.stock-empty{padding:28px;text-align:center;color:#667085}
        @media(max-width:1000px){.stock-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:650px){.stock-body{padding:10px}.stock-hero{padding:16px}.stock-hero h2{font-size:19px}.stock-metrics{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.stock-metric{padding:12px}.stock-metric strong{font-size:22px}.stock-header-actions{width:100%}.stock-header-actions .btn{flex:1;white-space:normal}.stock-tools{width:100%}.stock-tools input{min-width:0;flex:1}.stock-panel-head{align-items:flex-start}}
        </style>
        <div class="container-fluid stock-body" id="stock-dashboard">
            <section class="stock-hero">
                <div class="stock-crumb">Stock Management &nbsp; / &nbsp; <?=$esc($book->name);?></div>
                <div class="stock-hero-line">
                    <div><h2><i class="fa fa-cubes" aria-hidden="true"></i> <?=$esc($book->name);?> — Stock Management</h2><p>Inventory balances, stock alerts and item movement controls</p></div>
                    <div class="stock-header-actions">
                        <?php if($canStocktake):?>
                            <a href="daily/?bkid=<?=$esc($bsid);?>" class="btn btn-success btn-flat stock-action">
                                <i class="fa fa-calendar-check"></i> Daily Stock Taking
                            </a>
                            <a href="stocktake/?bkid=<?=$esc($bsid);?>" class="btn btn-outline-success btn-flat stock-action">
                                <i class="fa fa-clipboard-check"></i> Stock Taking &amp; Reconciliation
                            </a>
                        <?php endif;?>
                        <?php if($canManageItems):?>
                            <button type="button" class="btn btn-success btn-flat btn-click stock-action" data-title="Add Stock Item" data-section="item">
                                <i class="fa fa-plus-circle"></i> Add Stock Item
                            </button>
                        <?php endif;?>
                    </div>
                </div>
            </section>
            <section class="stock-metrics" aria-label="Stock overview">
                <div class="stock-metric info"><span>Total items</span><strong><?=$totalItems;?></strong></div>
                <div class="stock-metric"><span>Healthy stock (10+)</span><strong><?=$healthyStock;?></strong></div>
                <div class="stock-metric warning"><span>Low stock (1–9.99)</span><strong><?=$lowStock;?></strong></div>
                <div class="stock-metric danger"><span>Out of stock (0)</span><strong><?=$outOfStock;?></strong></div>
                <div class="stock-metric dark"><span>Negative balances</span><strong><?=$negativeStock;?></strong></div>
            </section>
            <section class="stock-panel">
                <div class="stock-panel-head">
                    <div><h3>Inventory register</h3><small>Select an item to view stock details and available actions</small></div>
                    <div class="stock-tools">
                        <input type="search" id="stock-search" placeholder="Search stock items..." aria-label="Search stock items">
                        <select id="stock-filter" aria-label="Filter stock status"><option value="all">All statuses</option><option value="ok">Healthy</option><option value="low">Low stock</option><option value="empty">Out of stock</option><option value="negative">Negative balance</option></select>
                    </div>
                </div>
                <div class="stock-table-wrap">
                    <table class="stock-table">
                        <thead><tr><th>#</th><th>Item</th><th class="stock-number">Available quantity</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach($stockRows as $index=>$item):
                            $balance=(float)$item['balance'];
                            $status=$balance<0?'negative':($balance==0?'empty':($balance<10?'low':'ok'));
                            $statusLabel=['negative'=>'Negative stock','empty'=>'Out of stock','low'=>'Low stock','ok'=>'In stock'][$status];
                        ?>
                            <tr class="stock-card" tabindex="0" role="button" data-item="<?=(int)$item['id'];?>" data-title="<?=$esc($item['name']);?>" data-status="<?=$status;?>" data-search="<?=$esc(mb_strtolower((string)$item['name']));?>">
                                <td><?=$index+1;?></td><td><strong><?=$esc($item['name']);?></strong></td>
                                <td class="stock-number"><?=number_format($balance,2);?> <?=$esc($item['units']);?></td>
                                <td><span class="stock-status <?=$status;?>"><?=$statusLabel;?></span></td>
                                <td><button type="button" class="btn btn-sm btn-outline-success btn-flat stock-action">View details <i class="fa fa-angle-right"></i></button></td>
                            </tr>
                        <?php endforeach;?>
                        <?php if(!$stockRows):?><tr><td colspan="5" class="stock-empty">No stock items found. Add an item to begin tracking inventory.</td></tr><?php endif;?>
                        </tbody>
                    </table>
                </div>
                <div class="stock-note"><span id="stock-visible-count"><?=$totalItems;?></span> of <?=$totalItems;?> items displayed. Low stock is currently defined as a positive balance below 10 units; the threshold is not adjusted for individual item units.</div>
            </section>
        </div>
        <!-- Existing modal containers retained for backend compatibility -->
        <div class="p-2 bg-white side-modal-tall absolute border shadow" id="side-modal-cashin" style="display:none">
            <div class="side-modal-header"><h3 class="side-modal-title text-dark"></h3><button type="button" class="side-modal-close" aria-label="Close">&times;</button></div><div class="side-modal-content"></div>
        </div>
        <div class="p-0 bg-white central-modal absolute border shadow" id="central-modal" style="display:none">
            <div class="central-modal-header bg-success"><h3 class="central-modal-title"></h3><button type="button" class="central-modal-close" aria-label="Close">&times;</button></div><div class="central-modal-content"></div>
        </div>
        <?php pageFooter(); ?>
        <script>
        (function($){
            'use strict';
            const bookId=<?=json_encode((int)$book->id);?>;
            const backend='save/index.php';
            const bookSaveBackend='../books/save/index.php';
            const safeText=(value)=>String(value??'');
            function notify(message){if(window.xdialog&&typeof xdialog.info==='function')xdialog.info(message);else alert(message);}
            function showLoading(selector){$(selector).html('<div class="p-4 text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');}
            function openSide(title){$('.side-modal-title').text(title);$('.side-modal-tall').show();}
            function openDetails(itemId,title){
                $('.central-modal-title').text(title);$('#central-modal').show();showLoading('.central-modal-content');
                $.ajax({url:backend,data:{action:'ItemDetails',item_id:itemId},success:function(html){$('.central-modal-content').html(html);},error:function(){$('.central-modal-content').html('<div class="alert alert-danger">Unable to load item details.</div>');}});
            }
            function loadForm(itemId,type){
                openSide(type.replace(/_/g,' ').toUpperCase());showLoading('.side-modal-content');
                $.ajax({url:backend,data:{type:type,id:itemId,action:type},success:function(html){$('.side-modal-content').html(html);},error:function(){$('.side-modal-content').html('<div class="alert alert-danger">Unable to load the stock form.</div>');}});
            }
            function fetchData(section){
                if(!section)return;
                showLoading('.side-modal-content');
                $.ajax({url:'../books/save/index.php',data:{section:section,book_id:bookId,action:'fetchForm'},success:function(html){$('.side-modal-content').html(html);},error:function(){$('.side-modal-content').html('<div class="alert alert-danger">Unable to load the item form.</div>');}});
            }
            function refreshStockSection(){
                const query=new URL(window.location.href);
                $.get(query.toString(),function(html){
                    const $newBody=$('<div>').append($.parseHTML(html,document,true)).find('#stock-dashboard');
                    if($newBody.length){$('#stock-dashboard').replaceWith($newBody);applyFilters();}
                });
            }
            function applyFilters(){
                const query=($('#stock-search').val()||'').toLocaleLowerCase().trim();
                const status=$('#stock-filter').val()||'all';let visible=0;
                $('#stock-dashboard .stock-card').each(function(){
                    const match=($(this).attr('data-search')||'').toLocaleLowerCase().includes(query)&&(status==='all'||$(this).attr('data-status')===status);
                    $(this).toggle(match);if(match)visible++;
                });
                $('#stock-visible-count').text(visible);
            }
            $(document).on('input','#stock-search',applyFilters).on('change','#stock-filter',applyFilters);
            $(document).on('click','.btn-click',function(){openSide(safeText($(this).data('title')).toUpperCase());fetchData($(this).data('section'));});
            $(document).on('click','.stock-card',function(){openDetails($(this).data('item'),$(this).data('title'));});
            $(document).on('keydown','.stock-card',function(event){if(event.key==='Enter'||event.key===' '){event.preventDefault();$(this).trigger('click');}});
            $(document).on('click','.stock-control',function(event){event.stopPropagation();loadForm($(this).data('id'),$(this).data('type'));});
            $(document).on('click','.side-modal-close',function(){$('.side-modal-tall').hide();});
            $(document).on('click','.central-modal-close',function(){$('#central-modal').hide();});
            function submitStockForm(formId,url){
                const form=document.getElementById(formId);
                if(!form){notify('Stock form not found.');return;}
                if(form.dataset.submitting==='1')return;
                if(typeof form.reportValidity==='function'&&!form.reportValidity())return;
                form.dataset.submitting='1';
                if(window.xdialog&&typeof xdialog.startSpin==='function')xdialog.startSpin();
                fetch(url,{method:'POST',body:new FormData(form)}).then(async response=>{
                    const body=await response.text();let result;
                    try{result=JSON.parse(body);}catch(error){if(formId==='newItemForm' && response.ok && !/<(?:!doctype|html|body)\b/i.test(body)){result={success:true,message:'Stock item saved.'};}else{throw new Error('Unexpected server response: '+body.slice(0,150));}}
                    if(!response.ok||result.success===false||result.status===false||result.error){throw new Error(result.message||result.error||'Unable to save stock.');}
                    return result;
                }).then(result=>{
                    $('.side-modal-tall').hide();notify(result.message||'Stock saved successfully.');
                    const itemId=$('#item_id').val(),itemTitle=$('#item_title').val();
                    if(itemId && formId!=='newItemForm')openDetails(itemId,itemTitle||'Stock item');
                    refreshStockSection();
                }).catch(error=>notify(error.message||'Unable to save stock.')).finally(()=>{
                    form.dataset.submitting='0';if(window.xdialog&&typeof xdialog.stopSpin==='function')xdialog.stopSpin();
                });
            }
            $(document).on('submit','#IssueStockForm,#AddStockForm,#newItemForm',function(event){event.preventDefault();submitStockForm(this.id,this.id==='newItemForm'?bookSaveBackend:backend);});
            $(document).on('click','.saveIssueStock,.saveNewStock,.saveItem',function(event){
                event.preventDefault();const id=$(this).hasClass('saveIssueStock')?'IssueStockForm':($(this).hasClass('saveNewStock')?'AddStockForm':'newItemForm');
                submitStockForm(id,id==='newItemForm'?bookSaveBackend:backend);
            });
            $(document).on('click','.delete-stock-record',function(event){
                event.preventDefault();const recordId=$(this).data('id');
                const deleteRecord=function(confirmed){if(!confirmed)return;
                    $.ajax({url:backend,method:'POST',data:{action:'deleteStockRecord',record_id:recordId},dataType:'json',success:function(result){
                        if(result.success===false||result.status===false||result.error){notify(result.message||result.error||'Deletion failed.');return;}
                        notify(result.message||'Stock record deleted.');$('#central-modal').hide();refreshStockSection();
                    },error:function(){notify('Error deleting stock record.');}});
                };
                if(window.xdialog&&typeof xdialog.confirm==='function')xdialog.confirm('Are you sure you want to delete this stock record?',deleteRecord);
                else deleteRecord(window.confirm('Are you sure you want to delete this stock record?'));
            });
            window.loadModalContent=openDetails;window.loadForm=loadForm;window.fetchData=fetchData;
            window.refreshStockSection=refreshStockSection;window.submitSingleForm=submitStockForm;
        })(jQuery);
        </script>
        <?php
    }else{
        redirect('../');
    }

?>