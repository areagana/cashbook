<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner','staff','superadministrator']))
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
            $user_id = auth()->id;

            switch($action)
            {
                
                case 'Invoice-details':
                    $id = (int)request('invoice_id');
                    $book_id = (int)encryptor('decrypt',$_SESSION['book_id']);

                    $sql = "SELECT ci.*,cc.name AS customer
                            FROM cashbook_invoices ci
                            INNER JOIN cashbook_customers cc ON cc.id=ci.customer_id AND cc.book_id=ci.book_id
                            WHERE ci.id=? AND ci.book_id=?";
                    $res = prepared_statements($sql,'ii',[$id,$book_id]);
                    $invoice = $res ? $res->fetch_assoc() : null;

                    if(!$invoice){
                        echo '<div class="alert alert-danger">Invoice not found.</div>';
                        break;
                    }

                    $stmt = "SELECT cii.*,ci.name AS item,ci.units
                            FROM cashbook_invoice_items cii
                            INNER JOIN cashbook_items ci ON ci.id=cii.item_id AND ci.book_id=?
                            WHERE cii.invoice_id=? ORDER BY cii.id ASC";
                    $res = prepared_statements($stmt,'ii',[$book_id,$id]);
                    $savedTotal = 0;
                    ?>
                        <div class="invoice-details" data-invoice-id="<?=$id;?>">
                            <div class="row mx-1 align-items-center">
                                <div class="col-md-8 p-2">
                                    <div class="p-1"><strong>Invoice No:</strong> <?=htmlspecialchars($invoice['invoice_no']);?></div>
                                    <div class="p-1"><strong>Customer:</strong> <?=htmlspecialchars($invoice['customer']);?></div>
                                    <div class="p-1"><strong>Date:</strong> <?=htmlspecialchars($invoice['invoice_date']);?></div>
                                </div>
                                <div class="col-md-4 p-2">
                                    <button type="button" class="btn btn-block btn-outline-info btn-flat add-invoice-item">
                                        <i class="fa fa-plus"></i> Add Item
                                    </button>
                                </div>
                            </div>
                            <hr>

                            <form method="POST" id="newInvoiceItemForm">
                                <input type="hidden" name="invoice_id" value="<?=$id;?>">
                                <input type="hidden" name="form" value="newInvoiceItemSave">
                                <input type="hidden" name="action" value="SaveForm">
                                <input type="hidden" name="InvoiceAmount" class="InvoiceAmount" value="0">

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered invoice-table">
                                        <thead>
                                            <tr>
                                                <th>Item Name</th>
                                                <th style="min-width:90px">Qty</th>
                                                <th style="min-width:120px">Rate</th>
                                                <th style="min-width:130px">Amount</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="invoiceItemsTbody">
                                            <?php if($res): while($r=$res->fetch_assoc()):
                                                $amount = (float)$r['total'];
                                                $savedTotal += $amount;
                                            ?>
                                            <tr class="invoice-item-row saved-item-row" data-total="<?=$amount;?>">
                                                <td><?=htmlspecialchars($r['item']);?></td>
                                                <td><?=number_format((float)$r['quantity'],2);?> <?=htmlspecialchars($r['units']??'');?></td>
                                                <td class="text-right"><?=number_format((float)$r['unit_price'],0);?></td>
                                                <td class="text-right amountClass"><?=number_format($amount,0);?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-flat btn-sm remove-invoice-item" data-id="<?=(int)$r['id'];?>">
                                                        <i class="fa fa-minus"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endwhile; endif;?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-right">GRAND TOTAL (UGX)</th>
                                                <th id="invoice_total" class="text-right"><?=number_format($savedTotal,0);?></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-end p-2">
                                    <button type="submit" class="btn btn-flat btn-outline-primary saveInvoiceItems">
                                        <i class="fa fa-save"></i> Save Invoice
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php
                    break;


                
                case 'AddInvoiceItem':
                        $book_id = (int)encryptor('decrypt',$_SESSION['book_id']);
                        $sqli = "SELECT id,name,units FROM cashbook_items WHERE book_id=? ORDER BY name ASC";
                        $ress = prepared_statements($sqli,'i',[$book_id]);
                    ?>
                        <tr class="invoice-item-row newItemRow" data-total="0">
                            <td>
                                <select name="item_id[]" class="form-control invoiceItemSelect" required>
                                    <option value="">-- Select Item --</option>
                                    <?php if($ress): while($rr=$ress->fetch_assoc()):?>
                                        <option value="<?=(int)$rr['id'];?>">
                                            <?=htmlspecialchars($rr['name']);?>
                                            <?=!empty($rr['units'])?' ('.htmlspecialchars($rr['units']).')':'';?>
                                        </option>
                                    <?php endwhile; endif;?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="qty[]" class="form-control qtyClass"
                                    min="0.001" step="any" placeholder="Qty" required>
                            </td>
                            <td>
                                <input type="number" name="rate[]" class="form-control rateClass"
                                    min="0" step="any" placeholder="Rate" required>
                            </td>
                            <td>
                                <input type="text" name="amount[]" class="form-control amountClass text-right"
                                    value="0" readonly>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-flat remove-row">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </td>
                        </tr>
                    <?php
                    break;
                case 'view-invoice':
                        $id = request('invoice_id');
                        $invoice = invoiceFind($id);
                        ?>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h3><?=bookFind($invoice->book_id)->name;?></h3>
                            </div>
                            <div class="col p-2">
                                <button class="btn btn-flat btn-outline-info invoice-return" data-id ='<?=$id;?>'>RECEIVE RETURN</button>
                            </div>
                            <div class="p-2">
                                <button class="btn btn-outline-danger btn-flat right" onclick="printMe('invoicePrintable')"><i class="fa fa-print"></i> PRINT</button>
                            </div>
                        </div>
                        <hr>
                        <div class="p-2" id='invoicePrintable'>
                            <table class="table table-bordered invoice-info-table">
                                <tr>
                                    <th colspan='2'><center><h3><?=strToUpper(bookFind($invoice->book_id)->name);?> - INVOICE</h3></center></th>
                                </tr>
                                <tr>
                                    <td width="50%" valign="top">
                                        <h3 class="pb-2">
                                            CUSTOMER DETAILS
                                        </h3>
                                        <hr>

                                        <div><strong>CUSTOMER:</strong> <?=$invoice->customer;?></div>
                                        <div><strong>DATE:</strong> <?=$invoice->invoice_date;?></div>
                                        <div><strong>CONTACT:</strong> <?=$invoice->contact;?></div>
                                    </td>

                                    <td width="50%" valign="top">
                                        <h3 class="pb-2">
                                            INVOICE DETAILS
                                        </h3>
                                        <hr>

                                        <div><strong>INVOICE NO:</strong> <?=$invoice->invoice_no;?></div>
                                        <div><strong>INVOICE DATE:</strong> <?=$invoice->invoice_date;?></div>
                                        <div><strong>AMOUNT:</strong> <?=number_format($invoice->total);?></div>

                                    </td>
                                </tr>
                            </table>
                            <hr>
                            <div class="p-2 h3">
                                <center>  <strong>BALANCE: <?=number_format($invoice->balance,0);?></strong></center>
                            </div>
                            <hr>
                            <div class="row mx-1">
                                <div class="col p-2 table-responsive">
                                    <?php
                                        $stmt = "SELECT cii.*,ci.name as item,ci.units FROM cashbook_invoice_items cii 
                                                    INNER JOIN cashbook_items ci ON ci.id = cii.item_id
                                                    WHERE cii.invoice_id = ? ORDER BY id ASC";
                                        $res = prepared_statements($stmt,'i',[$id]);
                                        
                                        $amounts = [];
                                        $s = 1;
                                    ?>
                                    <table class="table table-striped table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>S/NO</th>
                                                <th>Item name</th>
                                                <th>Qty</th>
                                                <th>Rate</th>
                                                <th>Amount</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody class='invoiceItemsTbody'>                            
                                        </tbody>
                                        <?php while($r = $res->fetch_assoc()): $amounts[] = $r['total'];?>
                                            <tr class='invoice-item-row'>
                                                <td><?=$s++;?></td>
                                                <td><?=$r['item'];?></td>
                                                <td><?=$r['quantity'];?> <?=$r['units'];?></td>
                                                <td><?=number_format($r['unit_price'],0);?></td>
                                                <td class='trTotal'><?=number_format($r['total'],0);?></td>
                                                <td>
                                                    <button class="btn btn-outline-danger btn-flat btn-sm remove-invoice-item" data-id ="<?=$r['id'];?>"><i class="fa fa-minus"></i></button>
                                                </td>
                                            </tr>
                                        <?php endwhile;?>
                                        <tr class='h5'>
                                            <td colspan='4'>TOTAL</td>
                                            <td id='invoice_total'><?=number_format(array_sum($amounts),0);?></td>
                                            <td></td>
                                        </tr>
                                    </table>
                                    <hr>
                                    <div class="p-2 h5">
                                        <center> <h5 class="p-2 text-center">INVOICE RETURN</h5></center>
                                    </div>
                                    <hr>
                                    <div class="p-2">
                                        <?php
                                        $stmtr = "SELECT cii.*,ci.name as item,ci.units FROM cashbook_invoice_return_items cii 
                                                    INNER JOIN cashbook_items ci ON ci.id = cii.item_id
                                                    WHERE cii.invoice_id = ? ORDER BY id ASC";
                                        $res = prepared_statements($stmtr,'i',[$id]);
                                        
                                        $returnAmounts = [];
                                        $s = 1;
                                    ?>
                                        <table class="table table-striped table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>S/NO</th>
                                                    <th>Item name</th>
                                                    <th>Qty Returned</th>
                                                    <th>Rate</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class='invoiceItemsTbody'>                            
                                            </tbody>
                                            <?php while($rr = $res->fetch_assoc()): $returnAmounts[] = $rr['total'];?>
                                                <tr class='invoice-item-row'>
                                                    <td><?=$s++;?></td>
                                                    <td><?=$rr['item'];?></td>
                                                    <td><?=$rr['qty_returned'];?> <?=$rr['units'];?></td>
                                                    <td><?=number_format($rr['unit_price'],0);?></td>
                                                    <td><?=number_format($rr['total'],0);?></td>
                                                </tr>
                                            <?php endwhile;?>
                                            <tr class='h5'>
                                                <td colspan='4'>TOTAL</td>
                                                <td id='invoice_total'><?=number_format(array_sum($returnAmounts),0);?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <?php
                                        // fetch invoice payments
                                    ?>
                                    <table class="table table-bordered table-striped table-sm">
                                        <tr class='h5'>
                                            <td colspan='4'>INVOICE BALANCE</td>
                                            <td id='invoice_total'><?=number_format((array_sum($amounts)-array_sum($returnAmounts)),2);?></td>
                                        </tr>
                                    </table>
                                    <hr>
                                    <footer>
                                        Print Date: <?=date('d-m-Y  H:i');?>  <center><i>Printed By : <?=auth()->name;?></i></center>
                                    </footer>
                                </div>
                            </div>
                        </div>
                        <?php
                    break;
                case 'view-returns':
                        $id = request('invoice_id');
                        $invoice = invoiceFind($id);
                        ?>
                            <form id='invoiceReturnsForm' method="post">
                                <input type="hidden" name="form" value='newInvoiceReturnSave'>
                                <input type="hidden" name="action" value='SaveForm'>
                                <div class="form-row">
                                    <div class="col p-2">INVOICE NO:</div>
                                    <div class="col p-2"><?=$invoice->invoice_no;?></div>
                                    <input type="hidden" name="invoiceId" value='<?=$invoice->id;?>'>
                                </div>
                                <div class="form-row">
                                    <div class="col p-2">CUSTOMER:</div>
                                    <div class="col p-2"><?=$invoice->customer;?></div>
                                </div>
                                <div class="form-row">
                                    <div class="col p-2">AMOUNT:</div>
                                    <div class="col p-2"><?=$invoice->total;?></div>
                                </div>
                                <hr>
                                    <h3 class="p-2"><center>INVOICE RETURNS</center></h3>
                                <hr>
                                <div class="form-row">
                                    <div class="col p-2">
                                        <div class="col p-2 table-responsive">
                                            <?php
                                                $stmt = "SELECT cii.*,ci.name as item,ci.units FROM cashbook_invoice_items cii 
                                                            INNER JOIN cashbook_items ci ON ci.id = cii.item_id
                                                            WHERE cii.invoice_id = ? ORDER BY id ASC";
                                                $res = prepared_statements($stmt,'i',[$id]);
                                                
                                                $amounts = [];
                                                $s = 1;
                                            ?>
                                            <table class="table table-striped table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>S/NO</th>
                                                        <th>Item name</th>
                                                        <th>Qty</th>
                                                        <th>Rate</th>
                                                        <th>Returned</th>
                                                        <th>Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody class='invoiceItemsTbody'>                            
                                                </tbody>
                                                <?php while($r = $res->fetch_assoc()): $amounts[] = $r['total'];?>
                                                    <tr class='invoice-item-row-returned'>
                                                        <td><?=$s++;?></td>
                                                        <td class='itemId'><?=$r['item'];?>
                                                            <input type="hidden" name="itemId[]" value='<?=$r['item_id'];?>'>
                                                            <input type="hidden" name="invoice_item_id[]" value='<?=$r['id'];?>'>
                                                            <input type="hidden" name="issuedQty[]" class='issuedQty' value="<?=$r['quantity'];?>">
                                                        </td>
                                                        <td><?=$r['quantity'];?> <?=$r['units'];?></td>
                                                        <td>
                                                            <input type="hidden" name="itemRate[]" class='itemRate' value="<?=$r['unit_price'];?>">
                                                            <?=number_format($r['unit_price'],0);?>
                                                        </td>
                                                        <td width='10%'><input type="text" name="qty_returned[]" class="form-control qty_returned" placeholder='Qty..'></td>
                                                        <td class='rowTotal'></td>
                                                        <input type="hidden" name="returnedAmount[]" class='returnedAmount'>
                                                    </tr>
                                                <?php endwhile;?>
                                                <tr class='h3'>
                                                    <td colspan='5'>TOTAL</td>
                                                    <td id='returnedInvoiceTotal'>0</td>
                                                </tr>
                                                <input type="hidden" name="returnedInvoiceTotal" class='returnedInvoiceTotal'>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col p-2">
                                        <button type="submit" class="btn btn-outline-primary btn-flat saveReturns">SUBMIT</button>
                                    </div>
                                </div>
                            </form>
                        <?php
                    break;
                case 'SaveForm':
                    $form = request('form');
                        switch($form)
                        {
                            case 'newInvoiceReturnSave':

                                $invoice_id = (int)request('invoiceId');
                                $user_id = (int)auth()->id;

                                $invoice = invoiceFind($invoice_id);

                                if(!$invoice){
                                    $_SESSION['error'] = "Invoice not found";
                                    break;
                                }

                                $book_id = (int)$invoice->book_id;
                                $customer_id = (int)$invoice->customer_id;
                                $invoice_no = $invoice->invoice_no;

                                $itemIds = $_POST['invoice_item_id'] ?? [];
                                $returnQtys = $_POST['qty_returned'] ?? [];

                                if(!is_array($itemIds) || !is_array($returnQtys) ||
                                !count($itemIds) || count($itemIds)!==count($returnQtys)){
                                    $_SESSION['error'] = "Invalid return items";
                                    break;
                                }

                                mysqli_begin_transaction($server);

                                try {

                                    // Lock invoice during the return operation.
                                    $lock = prepared_statements(
                                        "SELECT id,customer_id,book_id,balance
                                        FROM cashbook_invoices
                                        WHERE id=? AND book_id=?
                                        FOR UPDATE",
                                        'ii',
                                        [$invoice_id,$book_id]
                                    );

                                    if(!$lock || !$lock->num_rows){
                                        throw new Exception("Invoice unavailable");
                                    }

                                    $lockedInvoice = $lock->fetch_assoc();

                                    $returnItems = [];
                                    $returnTotal = 0;

                                    foreach($itemIds as $k=>$invoice_item_id){

                                        $invoice_item_id = (int)$invoice_item_id;
                                        $qty = (float)$returnQtys[$k];

                                        if($qty <= 0) continue;

                                        // Read original invoice item from the database.
                                        $itemResult = prepared_statements(
                                            "SELECT id,item_id,quantity,unit_price
                                            FROM cashbook_invoice_items
                                            WHERE id=? AND invoice_id=?",
                                            'ii',
                                            [$invoice_item_id,$invoice_id]
                                        );

                                        if(!$itemResult || !$itemResult->num_rows){
                                            throw new Exception("Invalid invoice item");
                                        }

                                        $item = $itemResult->fetch_assoc();

                                        // Total quantity returned in previous returns.
                                        $previous = prepared_statements(
                                            "SELECT COALESCE(SUM(qty_returned),0) AS qty
                                            FROM cashbook_invoice_return_items
                                            WHERE invoice_item_id=?",
                                            'i',
                                            [$invoice_item_id]
                                        );

                                        if(!$previous){
                                            throw new Exception("Cannot check previous returns");
                                        }

                                        $previousQty = (float)$previous->fetch_assoc()['qty'];
                                        $availableQty = (float)$item['quantity']-$previousQty;

                                        if($qty > $availableQty){
                                            throw new Exception("Returned quantity exceeds available quantity");
                                        }

                                        $rate = (float)$item['unit_price'];
                                        $amount = round($qty*$rate,2);

                                        $returnItems[] = [
                                            'invoice_item_id'=>$invoice_item_id,
                                            'item_id'=>(int)$item['item_id'],
                                            'qty'=>$qty,
                                            'rate'=>$rate,
                                            'amount'=>$amount
                                        ];

                                        $returnTotal += $amount;
                                    }

                                    $returnTotal = round($returnTotal,2);

                                    if($returnTotal <= 0){
                                        throw new Exception("No valid return quantities");
                                    }

                                    /*
                                    * This policy does not permit a return greater than
                                    * the remaining outstanding invoice balance.
                                    *
                                    * Paid-invoice returns requiring refunds or customer
                                    * credits must be handled by a separate credit/refund flow.
                                    */
                                    if($returnTotal > (float)$lockedInvoice['balance']){
                                        throw new Exception(
                                            "Return exceeds outstanding balance. Use a refund or customer credit process."
                                        );
                                    }

                                    // Save return header.
                                    $saveReturn = prepared_statements(
                                        "INSERT INTO cashbook_invoice_returns
                                        (invoice_id,customer_id,total,user_id)
                                        VALUES (?,?,?,?)",
                                        'iidi',
                                        [$invoice_id,$customer_id,$returnTotal,$user_id]
                                    );

                                    if(!$saveReturn){
                                        throw new Exception("Failed to save invoice return");
                                    }

                                    $return_id = (int)$server->insert_id;

                                    // Save returned items.
                                    foreach($returnItems as $item){

                                        $saved = prepared_statements(
                                            "INSERT INTO cashbook_invoice_return_items
                                            (return_id,invoice_item_id,item_id,qty_returned,
                                            unit_price,total,user_id,invoice_id)
                                            VALUES (?,?,?,?,?,?,?,?)",
                                            'iiiddiii',
                                            [
                                                $return_id,
                                                $item['invoice_item_id'],
                                                $item['item_id'],
                                                $item['qty'],
                                                $item['rate'],
                                                $item['amount'],
                                                $user_id,
                                                $invoice_id
                                            ]
                                        );

                                        if(!$saved){
                                            throw new Exception("Failed to save returned item");
                                        }
                                    }

                                    // Record the customer credit transaction.
                                    $trans = prepared_statements(
                                        "INSERT INTO cashbook_transactions
                                        (customer_id,details,credit_amount,user_id,type,book_id,invoice_id)
                                        VALUES (?,?,?,?,?,?,?)",
                                        'isdisii',
                                        [
                                            $customer_id,$invoice_no,$returnTotal,
                                            $user_id,'invoice_return',$book_id,$invoice_id
                                        ]
                                    );

                                    if(!$trans){
                                        throw new Exception("Failed to save return transaction");
                                    }

                                    $trans_id = (int)$server->insert_id;

                                    // Update customer ledger and customer balance summary.
                                    $currentBalance = (float)getCustomerBalance($customer_id);
                                    $newBalance = round($currentBalance-$returnTotal,2);

                                    $ledger = prepared_statements(
                                        "INSERT INTO cashbook_customer_ledger
                                        (customer_id,book_id,type,credit_amount,
                                        transaction_id,details,balance,user_id,invoice_id)
                                        VALUES (?,?,?,?,?,?,?,?,?)",
                                        'iisdisdii',
                                        [
                                            $customer_id,$book_id,'invoice_return',
                                            $returnTotal,$trans_id,$invoice_no,
                                            $newBalance,$user_id,$invoice_id
                                        ]
                                    );

                                    if(!$ledger){
                                        throw new Exception("Failed to update customer ledger");
                                    }

                                    $balanceSaved = saveCustomerBalance(
                                        $customer_id,$newBalance,date('Y-m-d H:i:s')
                                    );

                                    if(!$balanceSaved){
                                        throw new Exception("Failed to update customer balance");
                                    }

                                    // Invoice balance already includes deductions for returns.
                                    $updated = prepared_statements(
                                        "UPDATE cashbook_invoices
                                        SET balance=balance-?,
                                            returned_amount=COALESCE(returned_amount,0)+?
                                        WHERE id=? AND book_id=?",
                                        'ddii',
                                        [$returnTotal,$returnTotal,$invoice_id,$book_id]
                                    );

                                    if(!$updated){
                                        throw new Exception("Failed to update invoice balance");
                                    }

                                    mysqli_commit($server);

                                    $_SESSION['success'] = "Invoice return saved successfully";

                                }catch(Throwable $e){

                                    mysqli_rollback($server);

                                    error_log("Invoice Return Error: ".$e->getMessage());

                                    $_SESSION['error'] = "Unable to save invoice return: ".$e->getMessage();
                                }

                            break;
                        }
                    break;
                case 'deleteInvoice':
                    $id = request('id');

                    // delete customer
                    $stmt = "DELETE FROM cashbook_invoices WHERE id = ?";
                    $res = prepared_statements($stmt,'i',[$id]);

                    // get transaction if
                    $check = "SELECT ct.id as trans_id FROM cashbook_transactions
                        INNER JOIN cashbook_invoice ci ON ci.invoice_no = ct.details 
                        WHERE ci.id = ?";
                    $tt = prepared_statements($check,'i',[$id]);
                    $rr = $tt->fetch_assoc();
                    $tid = $rr['trans_id'];

                    if($res)
                    {
                        // track transaction edits
                        trackTransactionEdits($tid,'invoice-deletion');
                        // adjust customer ledger records
                        echo "Success";
                    }
                    break;
                case 'removeInvoiceItem':
                        $id = request('id');

                        // fetch item amount to adjust the invoice details
                        $sql = "SELECT * FROM cashbook_invoice_items WHERE id = ?";
                        $res = prepared_statements($sql,'i',[$id]);
                        $row = $res->fetch_assoc();
                        // print_r($row);

                        if($row) 
                        {
                            $invoice_id = $row['invoice_id'];
                            $invoice = invoiceFind($invoice_id);
                            $amount = $row['total'];
                            $customer_id = $invoice->customer_id;
                            $book_id = $invoice->book_id;

                            // Delete item
                            if(removeInvoiceItem($id))
                            {
                                $stmt = "UPDATE cashbook_invoices i
                                SET i.total = (
                                    SELECT COALESCE(SUM(ii.total), 0)
                                    FROM cashbook_invoice_items ii
                                    WHERE ii.invoice_id = i.id
                                )
                                WHERE i.id = ?";
                                prepared_statements($stmt, 'i', [$invoice_id]);

                                // update customer ledger to capture the changes
                                $query = "UPDATE cashbook_customer_ledger SET debit_amount = ?, invoice_amount = ? WHERE invoice_id = ?";
                                prepared_statements($query,'iii',[$amount,$amount,$invoice_id]);

                                $type = 'invoice_edit';
                                $current_balance = getCustomerBalance($customer_id);
                                $balance = $current_balance - $amount;

                                // insert a new transaction updating customer balance for ledger,re-credit their account
                                $insert = "INSERT INTO cashbook_customer_ledger SET customer_id = ?,credit_amount = ?, invoice_id = ?,book_id = ?, type = ?, details = ?,balance = ?,user_id = ?";
                                prepared_statements($insert,'iiiissii',[$customer_id,$amount,$invoice_id,$book_id,$type,$invoice->invoice_no,$balance,$user_id]);
                            }else{
                                echo "Failed to remove item";
                            }                            
                        }
                    break;
            }
        }
    }else{
        redirect('../');
    }
?>