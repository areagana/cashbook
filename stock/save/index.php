<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified())
    {
        $user = auth();
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            function insertStockTransaction(int $book_id,int $item_id,string $type,float $quantity,int $user_id) 
            {
                global $server;
                if ($quantity <= 0) {
                    return ['status' => false, 'message' => 'Quantity must be greater than zero'];
                }

                if (!in_array($type, ['stock_in', 'stock_out'])) {
                    return ['status' => false, 'message' => 'Invalid transaction type'];
                }

                $server->begin_transaction();

                try {

                    // 1️⃣ Lock last balance row
                    $stmt = $server->prepare("
                        SELECT balance
                        FROM cashbook_stocks
                        WHERE book_id = ? AND item_id = ?
                        ORDER BY id DESC
                        LIMIT 1
                        FOR UPDATE
                    ");
                    $stmt->bind_param("ii", $book_id, $item_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $previous_balance = 0;
                    if ($row = $result->fetch_assoc()) {
                        $previous_balance = (float) $row['balance'];
                    }

                    // 2️⃣ Negative stock protection
                    if ($type === 'stock_out' && $previous_balance < $quantity) {
                        throw new Exception(
                            "Insufficient stock. Available: {$previous_balance}"
                        );
                    }

                    // 3️⃣ Calculate new balance
                    $new_balance = ($type === 'stock_in')
                        ? $previous_balance + $quantity
                        : $previous_balance - $quantity;

                    // 4️⃣ Insert transaction
                    $insert = $server->prepare("
                        INSERT INTO cashbook_stocks
                        (book_id, item_id, transaction_type, quantity, balance, user_id)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $insert->bind_param(
                        "iisddi",
                        $book_id,
                        $item_id,
                        $type,
                        $quantity,
                        $new_balance,
                        $user_id
                    );
                    $insert->execute();

                    $server->commit();

                    return [
                        'status'  => true,
                        'balance' => $new_balance,
                        'message' => 'Stock transaction recorded successfully'
                    ];

                } catch (Exception $e) {

                    $server->rollback();

                    return [
                        'status'  => false,
                        'message' => $e->getMessage()
                    ];
                }
            }

            $action = request('action');

            switch($action)
            {
                case 'ItemDetails':
                    $id=(int)request('item_id');
                    $item=itemFind($id);
                    if(!$item){echo '<div class="alert alert-danger">Stock item not found.</div>';break;}
                    $esc=static fn($value)=>htmlspecialchars((string)($value??''),ENT_QUOTES,'UTF-8');
                    $fmt=static fn($value)=>number_format((float)$value,2);
                    $sql="SELECT id,created_at,transaction_type,quantity_in,quantity_out,balance,reference,unit_cost,total_cost
                          FROM cashbook_stocks WHERE item_id=? AND book_id=? ORDER BY id ASC";
                    $res=prepared_statements($sql,'ii',[(int)$item->id,(int)$item->book_id]);
                    $movements=[];$totalIn=0;$totalOut=0;
                    if($res){while($record=$res->fetch_assoc()){
                        $totalIn+=(float)$record['quantity_in'];$totalOut+=(float)$record['quantity_out'];
                        $movements[]=$record;
                    }}
                    $balanceResult=prepared_statements('SELECT balance FROM cashbook_item_stock_balances WHERE item_id=? AND book_id=? LIMIT 1','ii',[(int)$item->id,(int)$item->book_id]);
                    $balanceRow=$balanceResult?$balanceResult->fetch_assoc():null;
                    $balance=(float)($balanceRow['balance']??0);
                    $stockStatus=$balance<0?'Negative stock':($balance==0?'Out of stock':($balance<10?'Low stock':'In stock'));
                    ?>
                    <style>
                    .kh-stock-detail{font-family:Arial,sans-serif;color:#253347;font-size:13px}
                    .kh-stock-detail *{box-sizing:border-box}.kh-stock-detail .kh-detail-top{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
                    .kh-stock-detail h3{font-size:19px;font-weight:800;margin:0}.kh-stock-detail .kh-sub{color:#667085;font-size:12px;margin-top:4px}
                    .kh-stock-detail .kh-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:14px 0 18px}
                    .kh-stock-detail .kh-metric{border:1px solid #d9e2e7;border-left:3px solid #176b51;padding:12px;background:#fff}
                    .kh-stock-detail .kh-metric small{display:block;color:#667085;font-weight:700;text-transform:uppercase;font-size:10px;margin-bottom:6px}
                    .kh-stock-detail .kh-metric strong{font-size:18px;font-weight:800;font-variant-numeric:tabular-nums}
                    .kh-stock-detail .kh-table-wrap{overflow-x:auto;border:1px solid #d9e2e7}
                    .kh-stock-detail table{width:100%;border-collapse:collapse;min-width:760px;font-size:12px}
                    .kh-stock-detail th,.kh-stock-detail td{padding:10px 9px;border-bottom:1px solid #e4e9ee;text-align:left}
                    .kh-stock-detail th{background:#edf4f1;color:#244c3d;font-size:11px;text-transform:uppercase}
                    .kh-stock-detail .kh-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
                    .kh-stock-detail .kh-empty{text-align:center;padding:25px;color:#667085}
                    .kh-stock-detail .kh-pill{display:inline-block;padding:4px 8px;background:#eef4f1;color:#176b51;font-weight:700}
                    .kh-stock-form{font-family:Arial,sans-serif;color:#253347}.kh-stock-form .kh-form-heading{padding:12px 14px;background:#edf4f1;border-left:3px solid #176b51;margin-bottom:16px}
                    .kh-stock-form .kh-form-heading strong{display:block;font-size:15px}.kh-stock-form .kh-form-heading small{color:#667085}
                    .kh-stock-form .kh-form-field{margin-bottom:14px}.kh-stock-form label{font-size:12px;font-weight:700;margin-bottom:6px;display:block}
                    .kh-stock-form input.form-control{border-radius:2px}.kh-stock-form .kh-form-actions{display:flex;justify-content:flex-end;gap:8px;border-top:1px solid #e4e9ee;padding-top:12px}
                    @media(max-width:650px){.kh-stock-detail .kh-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.kh-stock-detail .kh-metric strong{font-size:15px}}
                    </style>
                    <div class="kh-stock-detail">
                        <div class="kh-detail-top">
                            <div><h3><?=$esc($item->name);?></h3><div class="kh-sub">Stock movement history · <?=$esc($item->units);?> · Item #<?=(int)$item->id;?></div></div>
                            <div class="d-flex flex-wrap" style="gap:6px">
                                <button type="button" class="btn btn-sm btn-flat btn-outline-primary stock-control" data-id="<?=(int)$item->id;?>" data-type="stock_in"><i class="fa fa-plus-circle"></i> Add Stock</button>
                                <button type="button" class="btn btn-sm btn-flat btn-outline-danger stock-control" data-id="<?=(int)$item->id;?>" data-type="stock_out"><i class="fa fa-minus-circle"></i> Issue Stock</button>
                            </div>
                        </div>
                        <div class="kh-metrics">
                            <div class="kh-metric"><small>Available Balance</small><strong><?=$fmt($balance);?> <?=$esc($item->units);?></strong></div>
                            <div class="kh-metric"><small>Total Received</small><strong><?=$fmt($totalIn);?></strong></div>
                            <div class="kh-metric"><small>Total Issued</small><strong><?=$fmt($totalOut);?></strong></div>
                            <div class="kh-metric"><small>Stock Status</small><span class="kh-pill"><?=$esc($stockStatus);?></span></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2"><strong>STOCK MOVEMENT HISTORY</strong><small class="text-muted"><?=count($movements);?> records</small></div>
                        <div class="kh-table-wrap"><table><thead><tr><th>#</th><th>Date</th><th>Movement</th><th>Reference</th><th class="kh-num">Qty In</th><th class="kh-num">Qty Out</th><th class="kh-num">Balance</th><th>Action</th></tr></thead><tbody>
                        <?php if($movements):foreach($movements as $index=>$record):?>
                            <tr><td><?=$index+1;?></td><td><?=!empty($record['created_at'])?$esc(date('d M Y',strtotime($record['created_at']))):'—';?></td>
                            <td><?=$esc(str_replace('_',' ',ucwords((string)$record['transaction_type'],'_')));?></td><td><?=$esc($record['reference']?:'—');?></td>
                            <td class="kh-num"><?=((float)$record['quantity_in']!=0)?$fmt($record['quantity_in']):'—';?></td>
                            <td class="kh-num"><?=((float)$record['quantity_out']!=0)?$fmt($record['quantity_out']):'—';?></td>
                            <td class="kh-num"><strong><?=$fmt($record['balance']);?></strong></td>
                            <td><button type="button" class="btn btn-sm btn-flat btn-outline-danger delete-stock-record" data-id="<?=(int)$record['id'];?>" title="Delete stock record"><i class="fa fa-trash"></i></button></td></tr>
                        <?php endforeach;else:?><tr><td colspan="8" class="kh-empty">No stock movements have been recorded for this item.</td></tr><?php endif;?></tbody></table></div>
                        <small class="text-muted d-block mt-2">Balances shown in history are stored movement balances. Historical adjustments may require stock reconciliation.</small>
                    </div>
                    <?php
                    break;

                case 'stock_in':
                case 'stock_out':
                    $movementType=$action;
                    $item=itemFind((int)request('id'));
                    if(!$item){echo '<div class="alert alert-danger">Stock item not found.</div>';break;}
                    $isStockIn=$movementType==='stock_in';
                    $formId=$isStockIn?'AddStockForm':'IssueStockForm';
                    $saveClass=$isStockIn?'saveNewStock':'saveIssueStock';
                    $heading=$isStockIn?'Receive Stock':'Issue Stock';
                    $esc=static fn($value)=>htmlspecialchars((string)($value??''),ENT_QUOTES,'UTF-8');
                    ?>
                    <div class="kh-stock-form">
                        <div class="kh-form-heading"><strong><?=$heading;?></strong><small><?=$esc($item->name);?> · <?=$esc($item->units);?></small></div>
                        <form id="<?=$formId;?>" method="post">
                            <input type="hidden" name="book_id" value="<?=(int)$item->book_id;?>">
                            <input type="hidden" name="type" value="<?=$movementType;?>">
                            <input type="hidden" name="item_id" id="item_id" value="<?=(int)$item->id;?>">
                            <input type="hidden" name="form" value="<?=$formId;?>">
                            <input type="hidden" id="item_title" value="<?=$esc($item->name);?>">
                            <input type="hidden" name="action" value="SaveForm">
                            <div class="kh-form-field"><label for="quantity">Quantity (<?=$esc($item->units);?>)</label><input type="number" name="quantity" id="quantity" class="form-control" min="0.01" step="any" required placeholder="Enter quantity"></div>
                            <?php if($isStockIn):?><div class="kh-form-field"><label for="unit_cost">Cost per <?=$esc($item->units);?> (UGX)</label><input type="number" name="unit_cost" id="unit_cost" class="form-control" min="0" step="any" placeholder="Optional unit cost"></div><?php endif;?>
                            <div class="kh-form-actions"><button type="submit" class="btn btn-flat btn-success <?=$saveClass;?>"><i class="fa fa-check"></i> Save <?=$heading;?></button></div>
                        </form>
                    </div>
                    <?php
                    break;

                case 'SaveForm':
                    $form = request('form');
                     switch($form)
                     {
                        case 'AddStockForm':
                                $bkid = request('book_id');
                                $itid = request('item_id');
                                $qty = request('quantity');
                                $unitCost = request('unit_cost');
                                $type  = request('type');
                                $user_id = auth()->id;
                                
                                stockIn($itid,$qty);
                                // call save data function
                                // $response = json_encode(insertStockTransaction($bkid,$itid,$type,$qty,$user_id));
                                // echo $response;
                            break;
                        case 'IssueStockForm':
                                $bkid = request('book_id');
                                $itid = request('item_id');
                                $qty = request('quantity');
                                $type  = request('type');
                                $user_id = auth()->id;
                                stockOut($itid,$qty);
                                // call save data function
                                // $response = json_encode(insertStockTransaction($bkid,$itid,$type,$qty,$user_id));
                                // echo $response;
                            break;
                        case 'newItemSave':
                                $book_id = request('book_id');
                                $details = request('item_details');
                                $name = request('item_name');
                                $units = request('item_units');
                                $user_id = auth()->id;

                                // ssave the content
                                $sql = "INSERT INTO cashbook_items SET name = ?,book_id = ?, details = ?,units=?,user_id=?";
                                prepared_statements($sql,'sissi',[$name,$book_id,$details,$units,$user_id]);
                                echo json_encode(['message'=>'Item Saved']);
                            break;
                     }

                    break;

                case 'deleteStockRecord':
                        $id = request('record_id');

                        // select record to check if it exists
                        $sql = "SELECT * FROM cashbook_stocks WHERE id = ?";
                        $res = prepared_statements($sql,'i',[$id]);
                        $row = $res->fetch_assoc();
                        $qtyIn = $row['quantity_in'];
                        $qtyOut = $row['quantity_out'];
                        $type = $row['transaction_type'];
                        $item_id = $row['item_id'];

                        //fetch current stock balance and add the deleted qty back to the stock balance
                        $balance = getItemStockBalance($item_id);

                        // check type and record based on value
                        if($type == 'stock_in')
                        {
                            $new_balance = $balance - $qtyIn;
                            updateStockItemBalance($item_id,$new_balance);
                        }else
                        {
                            $new_balance = $balance + $qtyOut;
                            updateStockItemBalance($item_id,$new_balance);
                        }
                        
                        $sql = "DELETE FROM cashbook_stocks WHERE id = ?";
                        prepared_statements($sql,'i',[$id]);
                        echo json_encode(['message'=>'Record Deleted']);
                    break;

            }
        }
    }else{
        redirect(back());
    }

?>