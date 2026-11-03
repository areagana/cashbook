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
                    $invoiceId = (int)request('invoice_id');
                    $bookId = (int)encryptor('decrypt', $_SESSION['book_id'] ?? '');
                    require_once __DIR__ . '/../invoice_document.php';
                    if (!khInvoiceCanAccessBook($bookId)) {
                        http_response_code(403);
                        echo '<div class="alert alert-danger">Access denied.</div>';
                        break;
                    }
                    $invoiceData = khInvoiceLoad($invoiceId, $bookId);
                    if (!$invoiceData) {
                        echo '<div class="alert alert-warning">Invoice not found.</div>';
                        break;
                    }
                    $invoice = $invoiceData['invoice'];
                    $invoiceId = (int)$invoice['id'];
                    ?>
                    <div class="p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between" style="gap:8px">
                        <strong><i class="fa fa-file-text-o"></i> Invoice preview</strong>
                        <div class="d-flex flex-wrap" style="gap:6px">
                            <a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="pdf.php?invoice_id=<?=$invoiceId;?>&amp;type=invoice">Invoice PDF</a>
                            <a class="btn btn-sm btn-outline-success" target="_blank" rel="noopener" href="pdf.php?invoice_id=<?=$invoiceId;?>&amp;type=returns">With Returns</a>
                            <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="pdf.php?invoice_id=<?=$invoiceId;?>&amp;type=payments">With Payments</a>
                            <button type="button" class="btn btn-sm btn-outline-success kh-share-whatsapp" data-invoice-id="<?=$invoiceId;?>"><i class="fa fa-whatsapp"></i> WhatsApp</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary kh-share-copy" data-invoice-id="<?=$invoiceId;?>"><i class="fa fa-link"></i> Copy link</button>
                            <button type="button" class="btn btn-sm btn-outline-danger kh-share-revoke" data-invoice-id="<?=$invoiceId;?>"><i class="fa fa-ban"></i> Revoke link</button>
                            <button type="button" class="btn btn-sm btn-outline-dark" onclick="printMe('invoicePrintable')"><i class="fa fa-print"></i> Print</button>
                        </div>
                    </div>
                    <div id="invoicePrintable" class="p-3" style="background:#fff; overflow-x:auto">
                        <?=khInvoiceHtml($invoiceData, 'invoice', false);?>
                    </div>
                    <?php
                    break;
                case 'view-returns':
                        $id = (int)request('invoice_id');
                        $book_id = (int)encryptor('decrypt', $_SESSION['book_id'] ?? '');
                        $invoiceQuery = prepared_statements(
                            'SELECT ci.*, cc.name AS customer, NULL AS contact
                             FROM cashbook_invoices ci
                             LEFT JOIN cashbook_customers cc ON cc.id = ci.customer_id AND cc.book_id = ci.book_id
                             WHERE ci.id = ? AND ci.book_id = ? LIMIT 1',
                            'ii', [$id, $book_id]
                        );
                        $invoiceRow = $invoiceQuery ? $invoiceQuery->fetch_assoc() : null;
                        if (!$invoiceRow) {
                            echo '<div class="alert alert-danger">Invoice not found in this book.</div>';
                            break;
                        }
                        $invoice = (object)$invoiceRow;
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
                    // Delete an unpaid, unreturned invoice and all its non-cash
                    // postings, then rebuild the customer's receivable balance.
                    $invoiceId = (int)request('id');
                    $bookId = (int)encryptor('decrypt', $_SESSION['book_id'] ?? '');

                    if ($invoiceId <= 0 || $bookId <= 0) {
                        http_response_code(400);
                        echo 'Invalid invoice or business book.';
                        break;
                    }
                    if (!hasRole(['owner','superadministrator'])) {
                        http_response_code(403);
                        echo 'You do not have permission to delete invoices.';
                        break;
                    }
                    if (function_exists('khCan') && !khCan('invoices.view', $bookId)) {
                        http_response_code(403);
                        echo 'Access denied for this book.';
                        break;
                    }

                    try {
                        $server->begin_transaction();
                        $result = prepared_statements(
                            'SELECT id,customer_id,book_id,paid_amount,returned_amount
                             FROM cashbook_invoices WHERE id=? AND book_id=? FOR UPDATE',
                            'ii', [$invoiceId,$bookId]
                        );
                        $invoice = $result ? $result->fetch_assoc() : null;
                        if (!$invoice) throw new RuntimeException('Invoice not found in this book.');
                        $customerId = (int)$invoice['customer_id'];

                        if (abs((float)$invoice['paid_amount']) > 0.005 ||
                            abs((float)$invoice['returned_amount']) > 0.005) {
                            throw new DomainException('This invoice has payments or returns. Resolve them before deletion.');
                        }

                        // Do not trust cached invoice totals alone: check linked
                        // transactions, ledger entries and return headers.
                        $returns = prepared_statements(
                            'SELECT id FROM cashbook_invoice_returns WHERE invoice_id=? LIMIT 1',
                            'i', [$invoiceId]
                        );
                        if ($returns->num_rows) {
                            throw new DomainException('Invoice returns exist. Resolve them before deletion.');
                        }
                        $ledger = prepared_statements(
                            'SELECT id,type,customer_id,transaction_id FROM cashbook_customer_ledger
                             WHERE invoice_id=? AND book_id=? FOR UPDATE',
                            'ii', [$invoiceId,$bookId]
                        );
                        $transactionIds = [];
                        while ($row = $ledger->fetch_assoc()) {
                            if ((int)$row['customer_id'] !== $customerId ||
                                !in_array($row['type'], ['invoice','invoice_edit'], true)) {
                                throw new DomainException('Other customer ledger activity is linked to this invoice. Reconcile before deletion.');
                            }
                            if ((int)$row['transaction_id'] > 0) {
                                $transactionIds[(int)$row['transaction_id']] = true;
                            }
                        }
                        $transactions = prepared_statements(
                            'SELECT id,type,customer_id FROM cashbook_transactions
                             WHERE invoice_id=? AND book_id=? FOR UPDATE',
                            'ii', [$invoiceId,$bookId]
                        );
                        while ($row = $transactions->fetch_assoc()) {
                            if ((int)$row['customer_id'] !== $customerId ||
                                !in_array($row['type'], ['invoice','invoice_edit'], true)) {
                                throw new DomainException('A payment or other transaction is linked to this invoice. Reconcile before deletion.');
                            }
                            $transactionIds[(int)$row['id']] = true;
                        }

                        // Check for any other invoice-linked tables, such as
                        // payment allocations or sharing records, before deleting.
                        // Unknown dependencies are not silently discarded.
                        $linkedTables = prepared_statements(
                            "SELECT TABLE_NAME FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA=DATABASE() AND COLUMN_NAME='invoice_id'
                             AND TABLE_NAME LIKE 'cashbook\\_%'"
                        );
                        $handled = ['cashbook_customer_ledger','cashbook_transactions',
                                    'cashbook_invoice_items','cashbook_invoice_returns',
                                    'cashbook_invoice_return_items'];
                        while ($tableRow = $linkedTables->fetch_assoc()) {
                            $table = $tableRow['TABLE_NAME'];
                            if (in_array($table,$handled,true)) continue;
                            if (!preg_match('/^[a-zA-Z0-9_]+$/',$table)) {
                                throw new RuntimeException('Invalid invoice dependency metadata.');
                            }
                            $linked = prepared_statements(
                                "SELECT 1 FROM `{$table}` WHERE invoice_id=? LIMIT 1",'i',[$invoiceId]
                            );
                            if ($linked->num_rows) {
                                throw new DomainException('Invoice has linked records in '.$table.'. Resolve these before deletion.');
                            }
                        }

                        // Remove ledger postings first, then their non-cash
                        // transaction references. Never remove actual cash flows.
                        prepared_statements(
                            "DELETE FROM cashbook_customer_ledger
                             WHERE invoice_id=? AND book_id=? AND type IN ('invoice','invoice_edit')",
                            'ii', [$invoiceId,$bookId]
                        );
                        foreach (array_keys($transactionIds) as $transactionId) {
                            prepared_statements(
                                "DELETE FROM cashbook_transactions
                                 WHERE id=? AND invoice_id=? AND book_id=?
                                 AND type IN ('invoice','invoice_edit')",
                                'iii', [$transactionId,$invoiceId,$bookId]
                            );
                        }
                        prepared_statements('DELETE FROM cashbook_invoice_items WHERE invoice_id=?',
                            'i',[$invoiceId]);
                        prepared_statements('DELETE FROM cashbook_invoices WHERE id=? AND book_id=?',
                            'ii',[$invoiceId,$bookId]);

                        rebuildCustomerLedgerBalances($customerId,$bookId);
                        $server->commit();
                        echo 'Success';
                    } catch (Throwable $error) {
                        $server->rollback();
                        error_log('Invoice deletion failed: '.$error->getMessage());
                        http_response_code($error instanceof DomainException ? 409 : 400);
                        echo 'Invoice deletion failed: '.$error->getMessage();
                    }
                    break;
                case 'removeInvoiceItem':
                    // Remove an invoice item and synchronize the receivable atomically.
                    $itemId = (int)request('id');
                    $bookId = (int)encryptor('decrypt', $_SESSION['book_id'] ?? '');
                    if ($itemId <= 0 || $bookId <= 0) {
                        http_response_code(400);
                        echo 'Invalid invoice item or book.';
                        break;
                    }
                    if (!hasRole(['owner','partner','superadministrator'])) {
                        http_response_code(403);
                        echo 'You do not have permission to remove invoice items.';
                        break;
                    }
                    try {
                        $server->begin_transaction();
                        $itemResult = prepared_statements(
                            'SELECT ii.id,ii.invoice_id,ii.quantity,ii.total FROM cashbook_invoice_items ii
                             INNER JOIN cashbook_invoices i ON i.id=ii.invoice_id
                             WHERE ii.id=? AND i.book_id=? FOR UPDATE',
                            'ii', [$itemId,$bookId]
                        );
                        $item = $itemResult ? $itemResult->fetch_assoc() : null;
                        if (!$item) throw new RuntimeException('Invoice item not found in this book.');
                        $invoiceId = (int)$item['invoice_id'];
                        $invoiceResult = prepared_statements(
                            'SELECT id,customer_id,total,paid_amount,returned_amount,balance
                             FROM cashbook_invoices WHERE id=? AND book_id=? FOR UPDATE',
                            'ii', [$invoiceId,$bookId]
                        );
                        $invoice = $invoiceResult ? $invoiceResult->fetch_assoc() : null;
                        if (!$invoice) throw new RuntimeException('Invoice not found.');

                        // Returns are linked to individual invoice items; preserve their audit trail.
                        $returns = prepared_statements(
                            'SELECT 1 FROM cashbook_invoice_return_items WHERE invoice_item_id=? LIMIT 1',
                            'i', [$itemId]
                        );
                        if ($returns && $returns->num_rows) {
                            throw new RuntimeException('This item has recorded returns. Reverse or resolve those returns before removing it.');
                        }

                        prepared_statements(
                            'DELETE FROM cashbook_invoice_items WHERE id=? AND invoice_id=?',
                            'ii', [$itemId,$invoiceId]
                        );
                        $totalResult = prepared_statements(
                            'SELECT COALESCE(SUM(total),0) AS amount FROM cashbook_invoice_items WHERE invoice_id=?',
                            'i', [$invoiceId]
                        );
                        $newTotal = round((float)$totalResult->fetch_assoc()['amount'],2);
                        $paid = round((float)$invoice['paid_amount'],2);
                        $returned = round((float)$invoice['returned_amount'],2);
                        $newBalance = round($newTotal-$paid-$returned,2);
                        if ($newBalance < -0.005) {
                            throw new RuntimeException('Cannot reduce invoice below its recorded payments and returns.');
                        }
                        prepared_statements(
                            'UPDATE cashbook_invoices SET total=?,balance=? WHERE id=? AND book_id=?',
                            'ddii', [$newTotal,max(0,$newBalance),$invoiceId,$bookId]
                        );

                        // Append a referenced invoice adjustment; preserve the original debit.
                        if (!khPostInvoiceCustomerDebit($invoiceId,$bookId,(int)$user_id)) {
                            throw new RuntimeException('Could not update the customer invoice debit.');
                        }
                        $server->commit();
                        echo 'Success';
                    } catch (Throwable $error) {
                        $server->rollback();
                        error_log('Invoice item removal: '.$error->getMessage());
                        http_response_code(400);
                        echo 'Unable to remove item: '.$error->getMessage();
                    }
                    break;
            }
        }
    }else{
        redirect('../');
    }
?>