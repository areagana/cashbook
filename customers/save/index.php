<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner','staff','superadministrator']))
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
            switch($action)
            {
                case 'Customer-details':
                    $id=(int)request('customer_id');
                    $customer=getCustomer($id);
                    if(!$customer){
                        echo '<div class="alert alert-danger m-3">Customer not found.</div>';
                        break;
                    }
                    $book_id=(int)$customer->book_id;
                    // IMPORTANT: enforce your existing authenticated user's book-access check here.
                    $esc=static function($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');};
                    $money=static function($v){
                        return number_format((float)$v,2);
                    };
                    $sql="SELECT ccl.*,ci.name AS item_name,ci.units,pm.name AS paymode_name
                        FROM cashbook_customer_ledger ccl
                        LEFT JOIN cashbook_items ci ON ci.id=ccl.item_id AND ci.book_id=ccl.book_id
                        LEFT JOIN cashbook_paymodes pm ON pm.id=ccl.paymode_id AND pm.book_id=ccl.book_id
                        WHERE ccl.customer_id=? AND ccl.book_id=? ORDER BY ccl.id ASC";
                    $res=prepared_statements($sql,'ii',[$id,$book_id]);
                    $rows=[];
                    $totalDebit=0.0;
                    $totalCredit=0.0;
                    $running=0.0;
                    $firstDate='';$lastDate='';
                    if($res){
                        while($r=$res->fetch_assoc()){
                            $debit=(float)($r['debit_amount']??0);$credit=(float)($r['credit_amount']??0);
                            $totalDebit+=$debit;
                            $totalCredit+=$credit;
                            $running=round($running+$debit-$credit,2);
                            $r['statement_balance']=$running;$rows[]=$r;
                            $d= $r['created_at']??'';
                            if($d){
                                if(!$firstDate)$firstDate=$d;$lastDate=$d;
                            }
                        }
                    }
                    $status=$running>0.005?'AMOUNT DUE':($running<-.005?'CUSTOMER CREDIT':'SETTLED');
                    $statusColor=$running>0.005?'#b42318':($running<-.005?'#067647':'#175cd3');
                    $period=$firstDate?date('d M Y',strtotime($firstDate)).' — '.date('d M Y',strtotime($lastDate)):'No transactions';
                    $printId='kh-customer-print-'.$id;
                    ?>
                    <style>
                    .kh-statement{--kh-ink:#182230;--kh-muted:#667085;--kh-border:#d0d5dd;color:var(--kh-ink);font-family:Arial,Helvetica,sans-serif;background:#fff;font-size:13px}
                    .kh-statement *{box-sizing:border-box}.kh-statement .kh-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;background:#f8fafc;border-bottom:1px solid var(--kh-border)}
                    .kh-statement .kh-sheet{padding:22px 26px}.kh-statement .kh-head{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;border-bottom:3px solid #176b51;padding-bottom:17px}
                    .kh-statement .kh-brand{font-size:22px;font-weight:800;letter-spacing:.2px}.kh-statement .kh-subtitle{color:var(--kh-muted);font-size:11px;margin-top:5px;line-height:1.5}
                    .kh-statement .kh-doc{text-align:right}.kh-statement .kh-doc h2{font-size:17px;letter-spacing:1px;margin:0 0 8px;font-weight:800}.kh-statement .kh-doc p{margin:3px 0;color:var(--kh-muted);font-size:11px}
                    .kh-statement .kh-details{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(200px,1fr);gap:18px;margin:20px 0}
                    .kh-statement .kh-panel{border:1px solid var(--kh-border);padding:14px 16px}.kh-statement .kh-panel-label{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--kh-muted);font-weight:800;margin-bottom:9px}
                    .kh-statement .kh-customer-name{font-size:17px;font-weight:800;margin-bottom:8px}.kh-statement .kh-info{line-height:1.75;color:#475467;overflow-wrap:anywhere}
                    .kh-statement .kh-amount{text-align:right;border-left:4px solid #176b51;background:#f8fafc}.kh-statement .kh-amount-value{font-size:25px;font-weight:800;white-space:nowrap;font-variant-numeric:tabular-nums}
                    .kh-statement .kh-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:20px}
                    .kh-statement .kh-stat{border:1px solid var(--kh-border);padding:11px 13px}.kh-statement .kh-stat small{display:block;font-size:10px;text-transform:uppercase;color:var(--kh-muted);font-weight:700;margin-bottom:6px}.kh-statement .kh-stat strong{font-size:16px;font-variant-numeric:tabular-nums}
                    .kh-statement .kh-section{font-size:14px;font-weight:800;letter-spacing:.5px;margin:0 0 10px}.kh-statement .kh-scroll{overflow-x:auto}
                    .kh-statement table{width:100%;border-collapse:collapse;font-size:11px}.kh-statement th,.kh-statement td{border:1px solid #cbd5e1;padding:8px 7px;vertical-align:top}
                    .kh-statement thead th{background:#edf2f5;color:#344054;text-transform:uppercase;font-size:9px;letter-spacing:.25px}
                    .kh-statement tbody tr:nth-child(even){background:#fafbfc}.kh-statement .kh-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.kh-statement .kh-date,.kh-statement .kh-nowrap{white-space:nowrap}
                    .kh-statement tfoot th,.kh-statement tfoot td{background:#e8f3ee;font-weight:800;border-top:2px solid #176b51}
                    .kh-statement .kh-foot{display:flex;justify-content:space-between;gap:18px;border-top:1px solid var(--kh-border);margin-top:19px;padding-top:12px;color:var(--kh-muted);font-size:10px;line-height:1.6}
                    .kh-statement .kh-foot>div{max-width:48%}.kh-statement .kh-empty{text-align:center;color:var(--kh-muted);padding:20px}
                    @media(max-width:650px){.kh-statement .kh-sheet{padding:14px}.kh-statement .kh-head{gap:10px}.kh-statement .kh-brand{font-size:16px}.kh-statement .kh-doc h2{font-size:13px}.kh-statement .kh-details{grid-template-columns:1fr}.kh-statement .kh-stats{grid-template-columns:1fr 1fr}.kh-statement .kh-stat strong{font-size:14px}}
                    @media print{ @page{size:A4 portrait;margin:12mm}body *{visibility:hidden!important} .kh-statement .kh-sheet,.kh-statement .kh-sheet *{visibility:visible!important}
                    .kh-statement .kh-sheet{position:absolute!important;left:0!important;top:0!important;width:100%!important;margin:0!important;padding:0!important;border:0!important;box-shadow:none!important}
                    .kh-statement .kh-toolbar{display:none!important}.kh-statement .kh-scroll{overflow:visible!important}.kh-statement table{font-size:9px!important;table-layout:auto!important}
                    .kh-statement th,.kh-statement td{padding:5px 4px!important}.kh-statement thead{display:table-header-group}.kh-statement tfoot{display:table-footer-group}
                    .kh-statement tr{break-inside:avoid;page-break-inside:avoid}.kh-statement .kh-details,.kh-statement .kh-stats,.kh-statement .kh-head{break-inside:avoid;page-break-inside:avoid}
                    .kh-statement .kh-foot{break-inside:avoid}.kh-statement .kh-num{white-space:nowrap}.kh-statement .kh-amount-value{font-size:20px!important}
                    .kh-statement{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
                    </style>
                    <div class="kh-statement" id="<?=$esc($printId);?>">
                        <div class="kh-toolbar"><strong><i class="fa fa-file-text-o"></i> Customer Account Statement</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-flat" onclick="window.print()"><i class="fa fa-print"></i> Print A4 Statement</button>
                        </div>
                        <div class="kh-sheet" id="printable-div">
                            <div class="kh-head"><div><div class="kh-brand">KASHHUB</div><div class="kh-subtitle">Customer account statement<br>Financial records &amp; outstanding balances</div></div>
                            <div class="kh-doc"><h2>ACCOUNT STATEMENT</h2><p>Generated: <?=date('d M Y, h:i A');?></p><p>Period: <?=$esc($period);?></p><p>Customer ID: <?=$id;?></p></div>
                            </div>
                            <div class="kh-details">
                            <div class="kh-panel"><div class="kh-panel-label">Statement for</div><div class="kh-customer-name"><?=$esc($customer->name??'');?></div>
                                <div class="kh-info"><strong>Contact:</strong> <?=$esc($customer->contact??'Not provided');?><br><strong>Address:</strong> <?=$esc($customer->address??'Not provided');?></div>
                            </div>
                            <div class="kh-panel kh-amount"><div class="kh-panel-label"><?=$esc($status);?></div><div class="kh-amount-value" style="color:<?=$statusColor;?>">UGX <?=$money(abs($running));?></div><div class="kh-subtitle"><?=count($rows);?> ledger entries</div></div>
                            </div>
                            <div class="kh-stats"><div class="kh-stat"><small>Total Debits</small><strong>UGX <?=$money($totalDebit);?></strong></div><div class="kh-stat"><small>Total Credits</small><strong>UGX <?=$money($totalCredit);?></strong></div><div class="kh-stat"><small>Net Balance</small><strong>UGX <?=$money($running);?></strong></div></div>
                            <h3 class="kh-section">TRANSACTION HISTORY</h3>
                            <div class="kh-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Ref.</th>
                                            <th>Type</th>
                                            <th>Description / Item</th>
                                            <th class="">Qty</th>
                                            <th>Mode</th>
                                            <th class="kh-num">Debit</th>
                                            <th class="kh-num">Credit</th>
                                            <th class="kh-num">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if($rows):foreach($rows as $r):?>
                                    <tr>
                                        <td class="kh-date"><?=!empty($r['created_at'])?date('d-m-Y',strtotime($r['created_at'])):'—';?></td>
                                        <td class="kh-nowrap"><?=$esc($r['transaction_id']??'—');?></td>
                                        <td><?=$esc(str_replace('_',' ',(string)($r['type']??'')));?></td>
                                        <td><?=$esc(($r['item_name']??'')?:($r['details']??''));?></td>
                                        <td class=""><?=!empty($r['quantity'])?$money($r['quantity']):'—';?> <?=$esc($r['units']??'');?></td>
                                        <td><?=$esc($r['paymode_name']??'');?></td>
                                        <td class="kh-num"><?=((float)($r['debit_amount']??0)!=0)?$money($r['debit_amount']):'—';?></td>
                                        <td class="kh-num"><?=((float)($r['credit_amount']??0)!=0)?$money($r['credit_amount']):'—';?></td>
                                        <td class="kh-num"><strong><?=$money($r['statement_balance']);?></strong></td>
                                    </tr>
                                    <?php endforeach;else:?><tr><td colspan="9" class="kh-empty">No ledger transactions recorded for this customer.</td></tr><?php endif;?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="6" style="text-align:right">CLOSING TOTAL</th>
                                            <td class="kh-num"><?=$money($totalDebit);?></td>
                                            <td class="kh-num"><?=$money($totalCredit);?></td>
                                            <td class="kh-num"><?=$money($running);?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="kh-foot"><div><strong>Balance guide:</strong> Positive = customer owes the business. Negative = customer has credit. Figures are in Uganda shillings (UGX).</div><div style="text-align:right">Generated through KashHub<br>Computer-generated statement</div></div>
                        </div>
                    </div>
                    <?php
                break;
                case 'deleteCustomer':
                    $id = request('id');

                    // delete customer
                    $stmt = "DELETE FROM cashbook_customers WHERE id = ?";
                    $res = prepared_statements($stmt,'i',[$id]);
                    if($res)
                    {
                        echo "Success";
                    }
                    break;
                case 'Customer-edit':
                        $id = request('customer_id');
                        $customer = getCustomer($id);
                        $bkid = $customer->book_id;
                        ?>
                            <form id='newCustomerForm' method="post">
                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                <input type="hidden" name="form" value='newCustomerSave'>
                                <input type="hidden" name="customer_id" value="<?=$id;?>">
                                <input type="hidden" name="action" value='SaveForm'>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="name">NAME:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="name" id="name" value="<?=$customer->name;?>" class="form-control">
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="category_id">CONTACT:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="contact" value="<?=$customer->contact;?>" id="contact" class="form-control">
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="address">ADDRESS:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="address" value="<?=$customer->address;?>" id="address" class="form-control">
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="route">ROUTE:</label>
                                    </div>
                                    <?php
                                        $sql = "SELECT * FROM cashbook_routes WHERE book_id = ?";
                                        $res = prepared_statements($sql,'i',[$bkid]);
                                    ?>
                                    <div class="col p-2">
                                        <select name="route_id" id="route_id" class="form-control search-select" required>
                                            <option value="<?=$customer->route_id;?>"><?=$customer->route;?></option>
                                            
                                            <?php while($rw = $res->fetch_assoc()):?>
                                                <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                            <?php endwhile;?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="address">ROUTE MANAGER:</label>
                                    </div>
                                    <?php
                                        $sql = "SELECT * FROM cashbook_route_managers WHERE book_id = ?";
                                        $res = prepared_statements($sql,'i',[$bkid]);
                                    ?>
                                    <div class="col p-2">
                                        <select name="route_manager_id" id="route_manager_id" class="form-control search-select" required>
                                            <option value="<?=$customer->route_manager_id;?>"><?=$customer->route_manager;?></option>
                                            <?php while($rw = $res->fetch_assoc()):?>
                                                <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                            <?php endwhile;?>
                                        </select>
                                    </div>
                                </div>
                                <div class="roww mx-1">
                                    <div class="col p-2">
                                        <button class="btn btn-flat btn-primary right saveCustomer">Save</button>
                                    </div>
                                </div>
                            </form>
                        <?php
                    break;

                case 'findItems':
                        $id = request('id');
                        $bkid = request('book_id');
                        $stmt = "SELECT ci.id,ci.name AS item,cci.customer_id,cci.item_id FROM cashbook_items ci
                                    LEFT JOIN (
                                        SELECT DISTINCT customer_id, item_id
                                        FROM cashbook_customer_items
                                        WHERE customer_id = ?
                                    ) cci ON cci.item_id = ci.id 
                                WHERE ci.book_id = ? ORDER BY ci.name ASC ";
                        $res = prepared_statements($stmt,'ii',[$id,$bkid]);
                    ?>
                        <form action="save/index.php" method="post">
                            <input type="hidden" name="customer_id" value="<?=$id;?>">
                            <input type="hidden" name="action" value="AttachItems">
                            <div class="p-2 col-lg-3 col-3">
                                <?php
                                    $attached_all =[];
                                    while($r = $res->fetch_assoc()):
                                        $attached = !empty($r['customer_id']);
                                        if ($attached && !in_array($r['item_id'],$attached_all)) {
                                            $attached_all[] = $r['item_id'];
                                            ?>
                                            <div class="row mx-1">
                                                <div class="col p-2">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="item_id[]" id="item<?=$r['id'];?>" value="<?=$r['id'];?>" class="form-check-input" checked>
                                                        <label class='form-check-label' for="item<?=$r['id'];?>"><?=$r['item'];?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        } else {
                                            ?>
                                                <div class="row mx-1">
                                                    <div class="col p-2">
                                                        <div class="form-check">
                                                            <input type="checkbox" name="item_id[]" id="item<?=$r['id'];?>" value="<?=$r['id'];?>" class="form-check-input">
                                                            <label class='form-check-label' for="item<?=$r['id'];?>"><?=$r['item'];?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                        }
                                    endwhile;
                                ?>
                            </div>
                            <div class="P-2">
                                <button type="submit" name='AttachItems' class="btn btn-flat btn-primary right">Save</button>
                            </div>
                        </form>
                    <?php
                    break;
                case 'AttachItems':
                        $customer_id = request('customer_id');
                        $items = $_POST['item_id'];

                        // loop data entry
                        $stmt = "INSERT IGNORE INTO cashbook_customer_items (customer_id, item_id) VALUES (?, ?)";
                        // check to detach
                        $query = "SELECT item_id FROM cashbook_customer_items WHERE customer_id = ?";
                        $res = prepared_statements($query,'i',[$customer_id]);
                        $available =[];

                        while($r = $res->fetch_assoc())
                        {
                            $available[] = $r['item_id'];

                            // detach existing if not selected
                            if(!in_array($r['item_id'],$items))
                            {
                                customerDettachItem($customer_id,$r['item_id']);
                            }
                        }

                        // attach all selected items
                        foreach($items as $item)
                        {
                            prepared_statements($stmt,'ii',[$customer_id,$item]);
                        }
                        $_SESSION['success'] = "Data saved";
                        redirect(back());
                    break;
            }
        }
    }else{
        redirect('../');
    }
?>