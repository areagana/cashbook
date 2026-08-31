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
                                WHERE ccl.customer_id = ? ORDER BY ccl.created_at ASC";
                        $res = prepared_statements($sql,'i',[$id]);
                        $credits = [];
                        $debits =[];
                    ?>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h4><strong>Customer Name:</strong> <?=$customer->name;?></h4>
                                <h4><strong>Contact:</strong> <?=$customer->contact;?></h4>
                                <h4><strong>Email:</strong> <?=$customer->address;?></h4>
                            </div>
                            <div class="col p-2">
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
                            </div>
                        </div>
                        <hr>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <table class="table table-striped table-bordered dataTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Trans_id</th>
                                            <th>Type</th>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Pay Mode</th>
                                            <th>Credit</th>
                                            <th>Debit</th>
                                            <th>Balance</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                        <tbody>
                                        <?php if($res->num_rows > 0):?>
                                            <?php while($r = $res->fetch_assoc()): 
                                                $credits[] = $r['credit_amount']; 
                                                $debits[] = $r['debit_amount'];
                                                $bal = array_sum($credits) - array_sum($debits);
                                            ?>
                                                <tr>
                                                    <td><?=date_format(date_create($r['created_at']),"d-m-Y");?></td>
                                                    <td><?=$r['id'];?></td>
                                                    <td><?=$r['type'];?></td>
                                                    <td><?=$r['item_name'] ?? $r['details'];?> </td>
                                                    <td><?=$r['quantity'];?> <?=$r['units'];?></td>
                                                    <td><?=$r['paymode_name'];?></td>
                                                    <td><?=(!empty($r['credit_amount'])) ? number_format($r['credit_amount'],0) : '';?></td>
                                                    <td><?=(!empty($r['debit_amount'])) ? number_format($r['debit_amount'],0) : '';?></td>
                                                    <td><?=number_format($r['balance'],0);?></td>
                                                    <td></td>
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
                                            <th><?=number_format(array_sum($credits),0);?></th>
                                            <th><?=number_format(array_sum($debits),0);?></th>
                                            <th><?=number_format((array_sum($credits) - array_sum($debits)),0);?></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-3 p-2">
                                <h3 class="p-2">INVOICES</h3>
                                <div class="p">

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
            }
        }
    }else{
        redirect('../');
    }
?>