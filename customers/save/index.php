<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner','staff']))
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
            switch($action)
            {
                case 'Customer-details':
                        $id = request('customer_id');

                        $customer = getCustomer($id);
                        // fetch transactions
                        $sql = "SELECT ccl.*,ci.name as item_name,ci.units,pm.name as paymode_name FROM cashbook_customer_ledger ccl
                                LEFT JOIN cashbook_items ci ON ccl.item_id = ci.id
                                INNER JOIN cashbook_transactions ct2 ON ccl.transaction_id = ct2.id
                                LEFT JOIN cashbook_paymodes pm ON ccl.paymode_id = pm.id
                                WHERE ccl.customer_id = ? ORDER BY ccl.transaction_id ASC";
                        $res = prepared_statements($sql,'i',[$id]);
                        $credits = [];
                        $debits =[];
                    ?>
                    <div class="row mx-1">
                        <div class="col p-2">
                            BALANCE: <?=getCustomerBalance($id);?>
                        </div>
                        <div class="col p-2">
                            <button class="btn btn-sm btn-outline-danger btn-flat" onclick="printMe('printable-div')"><i class="fa fa-print"></i> Print</button>
                        </div>
                    </div>
                    <div class="p-2" id="printable-div">
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h4><strong>Customer Name:</strong> <?=$customer->name;?></h4>
                                <h4><strong>Contact:</strong> <?=$customer->contact;?></h4>
                                <h4><strong>Email:</strong> <?=$customer->address;?></h4>
                            </div>
                            <div class="col p-2">
                                <right>
                                    <h3 class='border-bottom p-2'>ACCOUNT STATUS</h3>
                                    <div class="p-2 border p-3 rounded-3 border-dotted">
                                        <!-- fetch the latest balance -->
                                        <?php 
                                                $stmt = "SELECT balance FROM cashbook_customer_ledger WHERE customer_id = ? ORDER BY id DESC LIMIT 1";
                                                $rr = prepared_statements($stmt,'i',[$id]);
                                                $row = $rr->fetch_assoc();
                                        ?>
                                        <h3>Balance: <?=(!empty($row['balance'])) ? number_format($row['balance'],0) : number_format(0,0);?></h3>
                                    </div>
                                </right>
                            </div>
                        </div>
                        <hr>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h4 class="text-center"><center>TRANSACTIONS STATEMENT</center></h4>
                                <table class="table table-striped table-bordered dataTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Trans_id</th>
                                            <th>Type</th>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Pay Mode</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                        <tbody>
                                        <?php if($res->num_rows > 0):
                                            $balance_ = 0;
                                        ?>
                                            <?php while($r = $res->fetch_assoc()): 
                                                $credits[] = $r['credit_amount']; 
                                                $debits[] = $r['debit_amount'];
                                                $bal = array_sum($credits) - array_sum($debits);
                                                $balance_ = $r['balance'];
                                            ?>
                                                <tr>
                                                    <td><?=date_format(date_create($r['created_at']),"d-m-Y");?></td>
                                                    <td><?=$r['transaction_id'];?></td>
                                                    <td><?=$r['type'];?></td>
                                                    <td><?=$r['item_name'] ?? $r['details'];?> </td>
                                                    <td><?=$r['quantity'];?> <?=$r['units'];?></td>
                                                    <td><?=$r['paymode_name'];?></td>
                                                    <td><?=(!empty($r['debit_amount'])) ? number_format($r['debit_amount'],0) : '';?></td>
                                                    <td><?=(!empty($r['credit_amount'])) ? number_format($r['credit_amount'],0) : '';?></td>
                                                    <td><?=number_format($r['balance'],0);?></td>
                                                </tr>
                                            <?php endwhile;?>
                                        <?php else:?>
                                            <tr>
                                                <td colspan='8'><center>No Transactions found</center></td>
                                            </tr>
                                        <?php endif;?>
                                        <tr>
                                            <th colspan='3'>BALANCE</th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th><?=number_format($balance_,0);?></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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