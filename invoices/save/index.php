<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner','staff']))
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
            $user_id = auth()->id;

            switch($action)
            {
                case 'Invoice-details':
                        $id = request('invoice_id');

                        // fetch transactions
                        $sql = "SELECT ci.*,cc.name as customer FROM cashbook_invoices ci 
                                INNER JOIN cashbook_customers cc ON cc.id = ci.customer_id WHERE ci.id = ?";
                        $res = prepared_statements($sql,'i',[$id]);
                        $invoice = myObject($res->fetch_assoc());
                    ?>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <div class="p-2"><strong>Invoice No: <?=$invoice->invoice_no;?></strong></div>
                                <div class="p-2"><strong>Customer: <?=$invoice->customer;?></strong></div>
                                <div class="p-2"><strong>Date: <?=$invoice->invoice_date;?></strong></div>
                            </div>
                            <div class="col p-2">
                                <button class="btn btn-block btn-outline-info add-invoice-item"><i class="fa fa-plus"></i> Add Item</button>
                            </div>
                        </div>
                        <hr>
                        <div class="row mx-1">
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
                                </div>
                                <form method="POST" id="newInvoiceItemForm">
                                    <input type="hidden" name="invoice_id" value="<?=$invoice->id;?>">
                                    <input type="hidden" name="form" value='newInvoiceItemSave'>
                                    <input type="hidden" name="action" value='SaveForm'>
                                    <input type="hidden" name="InvoiceAmount" class="InvoiceAmount">

                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Item name</th>
                                                <th>Qty</th>
                                                <th>Rate</th>
                                                <th>Amount</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class='invoiceItemsTbody'>
                                            <?php while($r = $res->fetch_assoc()): $amounts[] = $r['total'];?>
                                            <tr class='invoice-item-row'>
                                                <td><?=$r['item'];?></td>
                                                <td><?=$r['quantity'];?> <?=$r['units'];?></td>
                                                <td><?=number_format($r['unit_price'],0);?></td>
                                                <td class ='amountClass'><?=number_format($r['total'],0);?></td>
                                                <td>
                                                    <button class="btn btn-outline-danger btn-flat btn-sm remove-invoice-item" data-id ="<?=$r['id'];?>"><i class="fa fa-minus"></i></button>
                                                </td>
                                            </tr>
                                        <?php endwhile;?>                            
                                        </tbody>
                                        <tr>
                                            <th colspan='3'>TOTAL</th>
                                            <th id='invoice_total'></th>
                                            <th></th>
                                        </tr>
                                    </table>
                                    <hr>
                                </form>
                                <div class="p-2">
                                    <button type="submit" form = 'newInvoiceItemForm' class="btn btn-flat btn-outline-primary right saveInvoiceItems">Save Invoice</button>
                                </div>
                            </div>
                        </div>
                    <?php
                    break;

                case 'AddInvoiceItem':
                        $book_id = encryptor('decrypt',$_SESSION['book_id']);
                        $sqli = "SELECT * FROM cashbook_items WHERE book_id = ?";
                        $ress = prepared_statements($sqli,'i',[$book_id]);
                    ?>
                        <tr class='newItemRow'>
                            <td>
                                <select name="item_id[]" class="form-control" required>
                                    <option value="">--Select--</option>
                                    <?php while($rr = $ress->fetch_assoc()):?>
                                        <option value="<?=$rr['id'];?>"><?=$rr['name'];?></option>
                                    <?php endwhile;?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="qty[]" class="form-control qtyClass" required>
                            </td>
                            <td>
                                <input type="text" name="rate[]" class="form-control rateClass" required>
                            </td>
                            <td>
                                <input type="text" name="amount[]" class="form-control amountClass" required readonly>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary btn-flat remove-row"><i class="fa fa-minus"></i></button>
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

                                $invoice_id = request('invoiceId');
                                $returnAMount = request('returnedInvoiceTotal');
                                $invoice = invoiceFind($invoice_id);
                                $auth = auth();

                                // save in the returns table
                                $rt = "INSERT INTO cashbook_invoice_returns SET invoice_id = ?, customer_id = ?, total = ?, user_id = ?";
                                $insert_id = prepared_statements($rt,'iidi',[$invoice_id,$invoice->customer_id,$returnAMount,$auth->id]);
                                
                                // debit customer page
                                $balance = getCustomerBalance($invoice->customer_id);

                                $trans = "INSERT INTO cashbook_transactions SET customer_id = ?, details = ?, credit_amount = ?, user_id = ?,type=?,book_id = ?";
                                $trans_id = prepared_statements($trans,'isdisi',[$invoice->customer_id,$invoice->invoice_no,$returnAMount,$auth->id,'invoice_return',$invoice->book_id]);

                                // update ledger and clear customer invoice

                                $ctledger = "INSERT INTO cashbook_customer_ledger SET customer_id = ?, book_id = ?,type = ?,
                                credit_amount = ?,transaction_id = ?,details = ?,balance = ?,user_id =?,invoice_id = ?";
                                
                                $newBalance = $balance - $returnAMount;

                                // save data in the customer ledger book
                                prepared_statements($ctledger,'iisdisdii',[
                                    $invoice->customer_id,$invoice->book_id,'invoice_return',
                                    $returnAMount,$trans_id,$invoice->invoice_no,
                                    $newBalance,$auth->id,$invoice_id
                                ]);

                                // update invoice table to reflect the returned amount
                                $updateInvoice = "UPDATE cashbook_invoices SET balance = balance - ?, returned_amount = returned_amount + ? WHERE id = ?";
                                prepared_statements($updateInvoice,'ddi',[$returnAMount,$returnAMount,$invoice_id]);

                                // item save statament
                                $stmt = "INSERT INTO cashbook_invoice_return_items 
                                    SET return_id = ?,invoice_item_id = ?,item_id = ?,qty_returned = ?,
                                        unit_price = ?,total = ?, user_id = ?,invoice_id = ?";
                                
                                // loop through posts
                                foreach($_POST['itemId'] as $k => $item)
                                {
                                    $item_id = $item;
                                    $inv_item_id = $_POST['invoice_item_id'][$k];
                                    $issue_qty = $_POST['issuedQty'][$k];
                                    $itemRate = $_POST['itemRate'][$k];
                                    $qty_returned = $_POST['qty_returned'][$k];
                                    $item_amt = $_POST['returnedAmount'][$k];

                                    // save in the returns items table
                                    prepared_statements($stmt,'iiiiddii',[$insert_id,$inv_item_id,$item_id,$qty_returned,$itemRate,$item_amt,$auth->id,$invoice_id]);
                                }

                                //return session
                                $_SESSION['success'] = "Data Saved";

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