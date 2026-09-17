<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner','staff']))
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
            switch($action)
            {
                case 'creditor-details':
                        $id = request('creditor_id');
                        $creditor = creditorFind($id);
                        // fetch transactions
                        $sql = "SELECT ccl.*,ci.name as item_name,ci.units,pm.name as paymode_name FROM cashbook_creditor_ledger ccl
                                    LEFT JOIN cashbook_items ci ON ccl.item_id = ci.id
                                    LEFT JOIN cashbook_transactions ct2 ON ccl.transaction_id = ct2.id
                                    LEFT JOIN cashbook_paymodes pm ON ccl.paymode_id = pm.id
                                WHERE ccl.creditor_id = ? ORDER BY ccl.transaction_id ASC";
                        $res = prepared_statements($sql,'i',[$id]);
                        $credits = [];
                        $debits =[];
                    ?>
                        <div class="row mx-1">
                            <div class="col p-2">
                                BALANCE: <?=getCreditorBalance($id);?>
                            </div>
                            <div class="col p-2">
                                <button class="btn btn-sm btn-outline-danger btn-flat" onclick="printMe('printable-div')"><i class="fa fa-print"></i> Print</button>
                            </div>
                        </div>
                        <div class="p-2" id="printable-div">
                            <div class="row mx-1">
                                <div class="col p-2">
                                    <h4><strong>Creditor Name:</strong> <?=$creditor->name;?></h4>
                                    <h4><strong>Contact:</strong> <?=$creditor->contact;?></h4>
                                    <h4><strong>Email:</strong> <?=$creditor->address;?></h4>
                                </div>
                                <div class="col p-2">
                                    <right>
                                        <h3 class='border-bottom p-2'>ACCOUNT STATUS</h3>
                                        <div class="p-2 border p-3 rounded-3 border-dotted">
                                            <!-- fetch the latest balance -->
                                            <?php 
                                                    $stmt = "SELECT balance FROM cashbook_creditor_ledger WHERE creditor_id = ? ORDER BY id DESC LIMIT 1";
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
                                                <th>Credit</th>
                                                <th>Debit</th>
                                                <th>Balance</th>
                                                <th>Action</th>
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
                                                        <td><?=(!empty($r['credit_amount'])) ? number_format($r['credit_amount'],0) : '';?></td>
                                                        <td><?=(!empty($r['debit_amount'])) ? number_format($r['debit_amount'],0) : '';?></td>
                                                        <td><?=number_format($r['balance'],0);?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-flat btn-outline-info transfer-credit" data-id ='<?=$r['id'];?>' data-trans_id ='<?=$r['transaction_id'];?>' title = 'Transfer'><i class="fa fa-share"></i></button>
                                                            <button class="btn btn-sm btn-flat btn-outline-danger delete-credit" title = 'Delete' data-id ='<?=$r['id'];?>' data-trans_id ='<?=$r['transaction_id'];?>'><i class="fa fa-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile;?>
                                                    <tr>
                                                        <th colspan='3'>BALANCE</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th><?=number_format($balance_,0);?></th>
                                                        <th></th>
                                                    </tr>
                                            <?php else:?>
                                                <tr>
                                                    <td colspan='8'><center>No Transactions found</center></td>
                                                </tr>
                                            <?php endif;?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php
                    break;
                
                case 'deletecreditor':
                    $id = request('id');

                    // delete customer
                    $stmt = "DELETE FROM cashbook_creditors WHERE id = ?";
                    $res = prepared_statements($stmt,'i',[$id]);
                    if($res)
                    {
                        echo "Success";
                    }
                    break;
                case 'creditor-edit':
                        $id = request('creditor_id');
                        $Creditor = creditorFind($id);
                        $bkid = $Creditor->book_id;
                        $bal = getCreditorBalance($id);

                        ?>
                            <form id='newCreditorForm' method="post">
                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                <input type="hidden" name="form" value='newCreditorSave'>
                                <input type="hidden" name="creditor_id" value="<?=$id;?>">
                                <input type="hidden" name="action" value='SaveForm'>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="name">NAME:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="name" id="name" value="<?=$Creditor->name;?>" class="form-control">
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="category_id">CONTACT:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="contact" value="<?=$Creditor->contact;?>" id="contact" class="form-control">
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="address">ADDRESS:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="address" value="<?=$Creditor->address;?>" id="address" class="form-control">
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="credit_balance">BALANCE B/F:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="credit_balance" id="credit_balance" value="<?=$bal;?>" class="form-control">
                                    </div>
                                </div>
                                <div class="roww mx-1">
                                    <div class="col p-2">
                                        <button class="btn btn-flat btn-primary right saveCreditor">Save</button>
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

                case 'fetchCreditors':
                    $trans = request('trans_id');
                    $transaction = transactionFind($trans);
                    $book_id = $transaction->book_id;

                    // fetch creditors
                    $stmt = "SELECT * FROM cashbook_creditors WHERE book_id = ?";
                    $res = prepared_statements($stmt,'i',[$book_id]);

                    echo "<option value=''>-- select --</option>";
                    while($r = $res->fetch_assoc())
                    {
                        ?>
                            <option value="<?=$r['id'];?>"><?=$r['name'];?></option>
                        <?php
                    }

                    break;

                case 'transferTrans':
                        $creditor_id = (int)request('creditor_id');
                        $trans_id    = (int)request('trans_id');
                        $id          = (int)request('id');

                        /*
                        ==========================================================
                        VALIDATE INPUT
                        ==========================================================
                        */

                        if ($creditor_id <= 0 || $trans_id <= 0 || $id <= 0) {

                            $_SESSION['error'] = "Invalid transfer information";
                            break;
                        }

                        /*
                        ==========================================================
                        FIND CREDITOR LEDGER RECORD
                        ==========================================================
                        */

                        $sql = "
                            SELECT *
                            FROM cashbook_creditor_ledger
                            WHERE id = ?
                            LIMIT 1
                        ";

                        $res = prepared_statements(
                            $sql,
                            'i',
                            [$id]
                        );

                        if (!$res || $res->num_rows === 0) {

                            $_SESSION['error'] =
                                "Creditor transaction could not be found";

                            break;
                        }

                        $row = myObject($res->fetch_assoc());

                        $old_creditor_id = (int)$row->creditor_id;
                        $book_id         = (int)$row->book_id;

                        /*
                        ==========================================================
                        DON'T TRANSFER TO THE SAME CREDITOR
                        ==========================================================
                        */

                        if ($old_creditor_id === $creditor_id) {

                            $_SESSION['error'] =
                                "The transaction already belongs to this creditor";

                            break;
                        }

                        /*
                        ==========================================================
                        VERIFY TRANSACTION
                        ==========================================================
                        */

                        $transaction = transactionFind($trans_id);

                        if (!$transaction) {

                            $_SESSION['error'] =
                                "Transaction could not be found";

                            break;
                        }

                        /*
                        ==========================================================
                        START DATABASE TRANSACTION
                        ==========================================================
                        */

                        mysqli_begin_transaction($server);

                        try {

                            /*
                            ======================================================
                            1. TRANSFER CREDITOR LEDGER RECORD
                            ======================================================
                            */

                            $sql = "
                                UPDATE cashbook_creditor_ledger
                                SET creditor_id = ?
                                WHERE id = ?
                                AND book_id = ?
                            ";

                            $res = prepared_statements(
                                $sql,
                                'iii',
                                [
                                    $creditor_id,
                                    $id,
                                    $book_id
                                ]
                            );

                            if ($res === false) {
                                throw new Exception(
                                    "Failed to transfer creditor ledger"
                                );
                            }

                            /*
                            ======================================================
                            2. UPDATE MAIN CASHBOOK TRANSACTION
                            ======================================================
                            */

                            $sql = "
                                UPDATE cashbook_transactions
                                SET creditor_id = ?
                                WHERE id = ?
                                AND book_id = ?
                            ";

                            $res = prepared_statements(
                                $sql,
                                'iii',
                                [
                                    $creditor_id,
                                    $trans_id,
                                    $book_id
                                ]
                            );

                            if ($res === false) {
                                throw new Exception(
                                    "Failed to update main transaction creditor"
                                );
                            }

                            /*
                            ======================================================
                            3. REBUILD OLD CREDITOR BALANCE
                            ======================================================
                            */

                            $oldBalance = rebuildCreditorBalance(
                                $old_creditor_id,
                                $book_id
                            );

                            /*
                            ======================================================
                            4. REBUILD NEW CREDITOR BALANCE
                            ======================================================
                            */

                            $newBalance = rebuildCreditorBalance(
                                $creditor_id,
                                $book_id
                            );

                            /*
                            ======================================================
                            5. COMMIT EVERYTHING
                            ======================================================
                            */

                            mysqli_commit($server);

                            $_SESSION['success'] =
                                "Transaction successfully transferred";

                        } catch (Throwable $e) {

                            /*
                            ======================================================
                            ROLLBACK
                            ======================================================
                            */

                            mysqli_rollback($server);

                            $_SESSION['error'] =
                                "Error transferring transaction: "
                                . $e->getMessage();
                        }

                    break;
            }
        }
    }else{
        redirect('../');
    }
?>