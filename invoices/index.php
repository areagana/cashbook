<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Invoices');
        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));
    ?>
            <style>
                @media print {
                    footer{
                        position: fixed;
                        bottom: 10px;
                        left: 0;

                        width: 100%;
                        text-align: center;

                        font-size: 11px;
                        color: #666;
                    }
                }
            </style>
            <div class="container">
                <div class="row mx-1">
                    <div class="col p-2 inline-block">
                        <a href="../books/?bkid=<?=$bsid;?>" class="nav-link">Books</a><i class="fa fa-angle-right"></i>
                        <a class="nav-link">Invoices</a>
                    </div>
                </div>
                <hr>
                <div class="row mx-1">
                    <div class="col p-2">
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h3 class="p-2"><?=strToUpper($book->name);?> - INVOICES</h3>
                            </div>
                            <div class="col p-2">
                                <?php if(hasRole(['owner','partner','staff'])):?>
                                    <div class="col p-3">
                                        <button class="btn btn-sm btn-flat btn-outline-success btn-click right" data-title='add invoice' data-section='invoice'><i class="fa fa-plus-circle"></i> Invoice</button>
                                    </div>
                                <?php endif;?>
                            </div>
                        </div>
                        <hr>
                        <div class="p-2">
                            <?php
                                $sql = "SELECT ci.*,cc.name as customer,COALESCE(sum(cir.total),0) as returned,(COALESCE(ci.balance,0) -COALESCE(sum(cir.total),0)) as newBalance  FROM cashbook_invoices ci 
                                            LEFT JOIN cashbook_customers cc ON cc.id = ci.customer_id
                                            LEFT JOIN cashbook_invoice_returns cir ON cir.invoice_id = ci.id
                                            WHERE ci.book_id =? GROUP BY ci.id ORDER BY ci.invoice_date DESC";
                                $res =prepared_statements($sql,'i',[$book->id]);

                                // fetch balance per customer
                                $stmt = "SELECT COALESCE(sum(credit_amount),0) as credits, COALESCE(sum(debit_amount),0) as debits,(COALESCE(sum(credit_amount),0) - COALESCE(sum(debit_amount),0)) as balance FROM cashbook_transactions WHERE customer_id =?";
                                $s =1;
                            ?>
                                <table class="table table-sm table-striped dataTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice no</th>
                                            <th>Customer</th>
                                            <th class='text-right'>Amount</th>
                                            <th class='text-right'>Return</th>
                                            <th class='text-right'>Paid</th>
                                            <th class='text-right'>Balance</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($res && $res->num_rows > 0): ?>
                                            <?php  while($r = $res->fetch_assoc()):?>
                                                <tr class='hover hover-hide-content'>
                                                    <td><?=$s++;?></td>
                                                    <td><?=$r['invoice_no'];?></td>
                                                    <td><?=$r['customer'];?></td>
                                                    <td class='text-right'><?=number_format($r['total'],0);?></td>
                                                    <td class='text-right'><?=number_format($r['returned'],0);?></td>
                                                    <td class='text-right'><?=number_format($r['paid_amount'],0);?></td>
                                                    <td class='text-right'><?=number_format($r['newBalance'],0);?></td>
                                                    <td class='text-right'>
                                                        <?php if(hasRole(['owner','partner','staff'])):?>
                                                            <span class="hover-display text-sms">
                                                                <?php if(hasRole(['owner'])):?>
                                                                    <button class="btn btn-sm btn-outline-info edit-invoice text-muted" data-id="<?=$r['id'];?>"><i class="fa fa-edit"></i></button>
                                                                    <button class="btn btn-sm btn-outline-danger delete-invoice" data-id="<?=$r['id'];?>"><i class="fa fa-trash"></i></button>
                                                                <?php endif;?>
                                                                <button class="btn btn-sm btn-outline-info create-detail text-muted" data-id="<?=$r['id'];?>"><i class="fa fa-plus"></i></button>
                                                                <button class="btn btn-sm btn-outline-info view-invoice text-muted" data-id="<?=$r['id'];?>" data-title="" title='view'><i class="fa fa-eye"></i></button>
                                                                <button class="btn btn-sm btn-outline-info invoice-return text-muted" data-id="<?=$r['id'];?>" title='Invoice Return'><i class="fa fa-undo" aria-hidden="true"></i></button> 
                                                            </span>
                                                        <?php endif;?>
                                                    </td>
                                                </tr>
                                            <?php endwhile;?>
                                        <?php endif;?>
                                    </tbody>
                                </table>
                        </div>
                    </div>
                </div>
            </div>
             <!-- side modal for a cash in -->
            <div class="p-2 bg-white side-modal-tall absolute border shadow" id='side-modal-cashin'>
                <div class="side-modal-header">
                    <h3 class="side-modal-title text-dark"></h3>
                    <button type='button' class='side-modal-close'>&times;</button>
                </div>
                <div class="side-modal-content">
                    
                </div>
            </div>
            
            <!-- side modal for a cash in -->
            <div class="p-0 bg-white side-modal-full side-modal-full-page absolute border shadow" id='side-modal-cashin'>
                <div class="side-modal-header bg-success">
                    <h3 class="side-modal-title text-dark"></h3>
                    <button type='button' class='side-modal-close'>&times;</button>
                </div>
                <div class="side-modal-content">
                    
                </div>
            </div>

            <!-- central modal -->
            <div class="p-0 bg-white central-modal absolute border shadow" id='central-modal'>
                <div class="central-modal-header bg-success">
                    <h3 class="central-modal-title"></h3>
                    <button type='button' class='central-modal-close'>&times;</button>
                </div>
                <div class="central-modal-content">
                    
                </div>
            </div>
        <?php
            pageFooter();
        ?>
            <script>
                // click button to show the modal
                $(document).on('click','.btn-click',function(){
                    var title = $(this).data('title');
                    title = title.toUpperCase();
                    $('.side-modal-tall').show();
                    $('.side-modal-title').html(title);
                    // display data in the side modal
                    var category = $(this).data('section');
                    fetchData(category);
                });

                function fetchData(sect)
                {
                    var book_id = "<?=encryptor('decrypt',request('bsid'));?>";
                    if(sect !='')
                    {
                        $.ajax({
                            url:'../books/save/index.php',
                            data:{
                                section:sect,
                                book_id:book_id,
                                action:'fetchForm'
                            },
                            beforesend:function(){
                                $('.side-modal-content').html("<h3 class='text-center'>Loading...</h3>");
                            },
                            success:function(res){
                                $('.side-modal-content').html(res);
                            },
                            error:function(err){
                                $('.side-modal-content').html("<h3 class='text-center'>Error Loading data!!</h3>");
                            }
                        });
                    }
                }

                // Call the function for your form
                $(document).on('click','.saveInvoice',function(){
                    submitSingleForm("newInvoiceForm", "../books/save/index.php");
                });

                $(document).on('click','.saveInvoiceItems',function(){
                    submitSingleForm("newInvoiceItemForm", "../books/save/index.php");
                });

                function submitSingleForm(formId, backendUrl) 
                {
                    const form = document.getElementById(formId);
                    xdialog.startSpin();
                    if (!form) {
                        console.error("Form not found:", formId);
                        return;
                    }

                    // Attach submit listener once
                    form.addEventListener("submit", function(e) {
                        e.preventDefault(); // prevent default page reload
                        const formData = new FormData(form);
                        console.log(formData);
                        fetch(backendUrl, {
                            method: "POST",
                            body: formData
                        })
                        .then(res => res.text()) // or .json() if backend returns JSON
                        .then(response => {
                            // Optionally show response below form
                            let responseDiv = document.getElementById("response_" + formId);
                            if (!responseDiv) {
                                responseDiv = document.createElement("div");
                                responseDiv.id = "response_" + formId;
                                form.appendChild(responseDiv);
                            }
                            // console.log(response);
                            xdialog.stopSpin();
                            window.location.reload();
                        })
                        .catch(err => {
                            console.error("AJAX error:", err);
                        });
                    });
                }
                
                // load invoice details form
                function AddInvoiceItem()
                {
                    $.ajax({
                        url:'save/index.php',
                        data:{
                            action:'AddInvoiceItem',
                        },
                        success:function(res){
                            $('.invoiceItemsTbody').append(res);
                        }
                    });
                }

                $(document).on('click','.add-invoice-item',function(){
                    AddInvoiceItem();
                });

                // view customer details
                $(document).on('click','.create-detail',function(){
                    $('#central-modal').show();
                    var title = $(this).data('title')+" Transactions";
                    $('.central-modal-title').html(title);
                    var id = $(this).data('id');

                    $.ajax({
                        url:'save/index.php',
                        data:{
                            invoice_id:id,
                            action:'Invoice-details'
                        },
                        beforeSend:function(){
                            $('.central-modal-content').html("<center><h3>Loading...</h3></center>");
                        },
                        success:function(res){
                            $('.central-modal-content').html(res);
                        },
                        error:function(err){
                            $('.central-modal-content').html("<center><h3>!!! Error Loading data</h3></center>");
                        }
                    });
                });

                $(document).on('click','.view-invoice',function(){
                    $('#central-modal').show();
                    var title = "VIEW INVOICE DETAILS";
                    $('.central-modal-title').html(title);
                    var id = $(this).data('id');

                    $.ajax({
                        url:'save/index.php',
                        data:{
                            invoice_id:id,
                            action:'view-invoice'
                        },
                        beforeSend:function(){
                            $('.central-modal-content').html("<center><h3>Loading...</h3></center>");
                        },
                        success:function(res){
                            $('.central-modal-content').html(res);
                        },
                        error:function(err){
                            $('.central-modal-content').html("<center><h3>!!! Error Loading data</h3></center>");
                        }
                    });
                });

                // record invoice return
                $(document).on('click','.invoice-return',function(){
                    $('#central-modal').show();
                    var title = "RECORD INVOICE RETURNS";
                    $('.central-modal-title').html(title);
                    var id = $(this).data('id');

                    $.ajax({
                        url:'save/index.php',
                        data:{
                            invoice_id:id,
                            action:'view-returns'
                        },
                        beforeSend:function(){
                            $('.central-modal-content').html("<center><h3>Loading...</h3></center>");
                        },
                        success:function(res){
                            $('.central-modal-content').html(res);
                        },
                        error:function(err){
                            $('.central-modal-content').html("<center><h3>!!! Error Loading data</h3></center>");
                        }
                    });
                });
                // calculate amount based on quantity and rates
                $(document).on('blur','.rateClass,.qtyClass',function(){
                    var row = $(this).closest('tr');
                    var rate = row.find('.rateClass').val();
                    var qty = row.find('.qtyClass').val();
                    var amountArea = row.find('.amountClass');

                    var amount = 0;
                    if(qty !== "" && rate !== '')
                    {
                        // amount calculation
                        amount = parseFloat(qty) * parseFloat(rate);
                        amountArea.val(amount);
                        recalculateInvoiceTotals();
                    }
                });

                function recalculateInvoiceTotals()
                {
                    var total = 0;

                    $('.amountClass').each(function () {
                        var val = parseFloat($(this).val()) || 0;
                        total += val;
                    });

                    $('#invoice_total').text(total.toFixed(2));
                    $('.InvoiceAmount').val(total);
                }

                // save returns //saveReturns
                 $(document).on('click','.saveReturns',function(){
                    submitSingleForm("invoiceReturnsForm", "save/index.php");
                });

                $(document).on('input', '.qty_returned', function () {

                    var row = $(this).closest('tr');

                    var rate = parseFloat(
                        row.find('.itemRate').val()
                    ) || 0;

                    var qty = parseFloat(
                        row.find('.qty_returned').val()
                    ) || 0;

                    // original issued quantity
                    var issuedQty = parseFloat(
                        row.find('.issuedQty').val()
                    ) || 0;

                    // prevent over-returning
                    if(qty > issuedQty)
                    {
                        alert('Returned quantity cannot exceed issued quantity');

                        qty = issuedQty;

                        row.find('.qty_returned').val(issuedQty);
                    }

                    // calculate amount
                    var amount = qty * rate;

                    // display formatted amount
                    row.find('.rowTotal').text(
                        amount.toLocaleString()
                    );

                    // hidden raw value
                    row.find('.returnedAmount').val(amount);

                    // recalculate totals
                    recalculateInvoiceTotalsReturned();
                });

                function recalculateInvoiceTotalsReturned()
                {
                    var total = 0;

                    $('.returnedAmount').each(function(){
                        var val = parseFloat($(this).val()) || 0;
                        total += val;
                    });

                    // formatted visible total
                    $('#returnedInvoiceTotal').text(
                        total.toLocaleString(undefined,{
                            minimumFractionDigits:2,
                            maximumFractionDigits:2
                        })
                    );

                    // raw hidden input
                    $('.returnedInvoiceTotal').val(total);
                }

                // remove row from the table
                $(document).on('click','.remove-row',function(){
                    var row = $(this).closest('tr');
                    if (!confirm('Are you sure you want to remove this item?')) {
                        return;
                    }
                    row.hide();
                    row.find('.amountClass').val(0);
                    recalculateInvoiceTotals();
                });

                // delete invoice
                
                // delete customer
                $(document).on('click','.delete-invoice',function(){
                    var id = $(this).data('id');
                    xdialog.confirm('Confirm to delete this invoice?',function(){
                        $.ajax({
                            url:'save/index.php',
                            data:{
                                action:'deleteInvoice',
                                id:id
                            },
                            beforeSend:function(){
                                xdialog.startSpin();
                            },
                            success:function(res){
                                xdialog.stopSpin();
                                xdialog.info("Invoice deleted successfully");
                                window.location.reload();
                            },
                            error:function(err){
                                xdialog.info("Error removing Invoice");
                            }
                        });
                    });
                });

            </script>
        <?php
    }else{
        redirect('../');
    }

?>