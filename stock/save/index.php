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
                case 'ItemDetails';
                    $id = request('item_id');
                    $item = itemFind($id);
                    
                    //fetch item records
                    $sql = "SELECT * FROM cashbook_items WHERE id =?";
                    $s =0;
                    ?>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h3 class="p-2"><?=$item->name;?></h3>
                            </div>
                            <div class="col p-2">
                                <button class="btn btn-flat btn-outline-danger right stock-control m-2" data-id ="<?=$item->id;?>" data-type='stock_out'><i class="fa"></i> Issue Stock</button>
                                <button class="btn btn-flat btn-outline-primary right stock-control m-2" data-id ="<?=$item->id;?>" data-type='stock_in'><i class="fa fa-plus-circle"></i> Add Stock</button>
                            </div>
                        </div>
                        <hr>
                        <div class="row mx-1">
                            <?php
                                // fetch stock records from the database based on the item
                                $sql = "SELECT * FROM cashbook_stocks WHERE item_id = ? ORDER BY id desc";
                                $res = prepared_statements($sql,'i',[$item->id]);
                                $balance
                            ?>
                            <div class="col p-2">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Qty</th>
                                            <th>Balance</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php while($r = $res->fetch_assoc()):?>
                                        <tr>
                                            <td><?=++$s;?></td>
                                            <td><?=$r['created_at'];?></td>
                                            <td><?=$r['transaction_type'];?></td>
                                            <td><?=$r['quantity'];?></td>
                                            <td><?=$r['balance'];?></td>
                                            <td></td>
                                        </tr>
                                    <?php endwhile;?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-3 p-2 border-left">
                                <h3 class="p-2 border-bottom text-center">STOCK BALANCE</h3>
                                <h3 class="balance-info text-center">
                                    <?php
                                        $query = "SELECT balance FROM cashbook_stocks WHERE item_id = ? ORDER BY id desc LIMIT 1";
                                        $rs = prepared_statements($query,'i',[$item->id]);
                                        $row = $rs->fetch_assoc();
                                        echo isset($row['balance']) ? number_format($row['balance'],0)." ".$item->units : 0 ." ".$item->units;
                                    ?>
                                </h3>
                            </div>
                        </div>

                    <?php
                break;
                case 'stock_in':
                    $item = itemFind(request('id'));
                    ?>
                        <form id='AddStockForm' method="post">
                            <div class="row mx-1">
                                <input type="hidden" name="book_id" value="<?=$item->book_id;?>">
                                <input type="hidden" name="type" value='stock_in'>
                                <input type="hidden" name="item_id" id='item_id' value="<?=$item->id;?>">
                                <input type="hidden" name="form" value='AddStockForm'>
                                <input type="hidden" id='item_title' value="<?=$item->name;?>">
                                <input type="hidden" name="action" value='SaveForm'>
                                <div class="col-md-3 p-2">
                                    <label for="quantity">QUANTITY:</label>
                                </div>
                                <div class="col p-2">
                                    <input type="text" name="quantity" id="quantity" class="form-control">
                                </div>
                            </div>
                            <div class="row mx-1">
                                <div class="col-md-3 p-2">
                                    <label for="unit_cost">COST PER (<?=strToUpper($item->units);?>):</label>
                                </div>
                                <div class="col p-2">
                                    <input type="text" name="unit_cost" id="unit_cost" class="form-control">
                                </div>
                            </div>
                            <div class="roww mx-1">
                                <div class="col p-2">
                                    <button type='submit' class="btn btn-flat btn-primary right saveNewStock">Save</button>
                                </div>
                            </div>
                        </form>

                    <?php
                    break;
                case 'stock_out':
                        $item = itemFind(request('id'));
                    ?>
                        <form id='IssueStockForm' method="post">
                            <div class="row mx-1">
                                <input type="hidden" name="book_id" value="<?=$item->book_id;?>">
                                <input type="hidden" name="type" value='stock_out'>
                                <input type="hidden" name="item_id" id='item_id' value="<?=$item->id;?>">
                                <input type="hidden" name="form" value='IssueStockForm'>
                                <input type="hidden" id='item_title' value="<?=$item->name;?>">
                                <input type="hidden" name="action" value='SaveForm'>
                                <div class="col-md-3 p-2">
                                    <label for="quantity">QUANTITY:</label>
                                </div>
                                <div class="col p-2">
                                    <input type="text" name="quantity" id="quantity" class="form-control">
                                </div>
                            </div>
                            <div class="roww mx-1">
                                <div class="col p-2">
                                    <button type='submit' class="btn btn-flat btn-primary right saveIssueStock">Save</button>
                                </div>
                            </div>
                        </form>
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
                                // call save data function
                                $response = json_encode(insertStockTransaction($bkid,$itid,$type,$qty,$user_id));
                                echo $response;
                            break;
                        case 'IssueStockForm':
                                $bkid = request('book_id');
                                $itid = request('item_id');
                                $qty = request('quantity');
                                $type  = request('type');
                                $user_id = auth()->id;
                                // call save data function
                                $response = json_encode(insertStockTransaction($bkid,$itid,$type,$qty,$user_id));
                                echo $response;
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
            }
        }
    }else{
        redirect(back());
    }

?>