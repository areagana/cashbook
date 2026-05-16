<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner']))
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
                                WHERE ccl.customer_id = ? ORDER BY ccl.created_at desc";
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
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
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
                                            <?php while($r = $res->fetch_assoc()): $credits[] = $r['credit_amount']; $debits[] = $r['debit_amount'];
                                                $bal = array_sum($credits) - array_sum($debits);
                                            ?>
                                                <tr>
                                                    <td><?=$r['created_at'];?></td>
                                                    <td><?=$r['item_name'];?> </td>
                                                    <td><?=$r['quantity'];?> <?=$r['units'];?></td>
                                                    <td><?=$r['paymode_name'];?></td>
                                                    <td><?=(!empty($r['credit_amount'])) ? number_format($r['credit_amount'],0) : '';?></td>
                                                    <td><?=(!empty($r['debit_amount'])) ? number_format($r['debit_amount'],0) : '';?></td>
                                                    <td><?=number_format($bal,0);?></td>
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
                                            <th><?=number_format(array_sum($credits),0);?></th>
                                            <th><?=number_format(array_sum($debits),0);?></th>
                                            <th><?=number_format((array_sum($credits) - array_sum($debits)),0);?></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                        </div>
                    <?php
                    break;
                case'edit-customer':


                    break;
            }
        }
    }else{
        redirect('../');
    }
?>