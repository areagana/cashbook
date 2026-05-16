<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner']))
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
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
            }
        }
    }else{
        redirect('../');
    }
?>