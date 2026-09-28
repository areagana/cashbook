<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Invoices');
        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));
    ?>
            <style>
                .invoice-summary-card {
                    border-radius: 8px;
                    min-height: 125px;
                }

                .invoice-summary-card .summary-icon {
                    font-size: 34px;
                    opacity: .16;
                }

                .invoice-table td,
                .invoice-table th {
                    vertical-align: middle;
                }

                @media print {
                    footer {
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

            <?php
                /*
                |--------------------------------------------------------------------------
                | INVOICE DATA
                |--------------------------------------------------------------------------
                */
                $sql = "
                    SELECT
                        ci.*,
                        cc.name AS customer,
                        COALESCE(SUM(cir.total),0) AS returned,
                        (
                            COALESCE(ci.balance,0) -
                            COALESCE(SUM(cir.total),0)
                        ) AS newBalance

                    FROM cashbook_invoices ci

                    LEFT JOIN cashbook_customers cc
                        ON cc.id = ci.customer_id

                    LEFT JOIN cashbook_invoice_returns cir
                        ON cir.invoice_id = ci.id

                    WHERE ci.book_id = ?

                    GROUP BY ci.id

                    ORDER BY ci.invoice_date DESC
                ";

                $res = prepared_statements(
                    $sql,
                    'i',
                    [$book->id]
                );

                $invoiceRows = [];

                $invoiceCount = 0;
                $invoiceTotal = 0;
                $returnedTotal = 0;
                $paidTotal = 0;
                $balanceTotal = 0;

                $paidInvoices = 0;
                $partialInvoices = 0;
                $unpaidInvoices = 0;

                if($res && $res->num_rows > 0)
                {
                    while($r = $res->fetch_assoc())
                    {
                        $r['total'] =
                            (float)($r['total'] ?? 0);

                        $r['returned'] =
                            (float)($r['returned'] ?? 0);

                        $r['paid_amount'] =
                            (float)($r['paid_amount'] ?? 0);

                        $r['newBalance'] =
                            (float)($r['newBalance'] ?? 0);

                        $invoiceRows[] = $r;

                        $invoiceCount++;
                        $invoiceTotal += $r['total'];
                        $returnedTotal += $r['returned'];
                        $paidTotal += $r['paid_amount'];
                        $balanceTotal += $r['newBalance'];

                        if($r['newBalance'] <= 0)
                        {
                            $paidInvoices++;
                        }
                        elseif($r['paid_amount'] > 0)
                        {
                            $partialInvoices++;
                        }
                        else
                        {
                            $unpaidInvoices++;
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | EXISTING CUSTOMER BALANCE QUERY
                |--------------------------------------------------------------------------
                |
                | Retained because invoice forms loaded by this page may rely on it.
                |
                */
                $stmt = "
                    SELECT
                        COALESCE(SUM(credit_amount),0) AS credits,
                        COALESCE(SUM(debit_amount),0) AS debits,
                        (
                            COALESCE(SUM(credit_amount),0) -
                            COALESCE(SUM(debit_amount),0)
                        ) AS balance

                    FROM cashbook_transactions

                    WHERE customer_id = ?
                ";
            ?>

            <div class="container-fluid">

                <!-- BREADCRUMB / PAGE NAVIGATION -->
                <div class="row mx-1 align-items-center">

                    <div class="col-md-8 p-2">

                        <a
                            href="../books/?bkid=<?=$bsid;?>"
                            class="nav-link d-inline-block">
                            Books
                        </a>

                        <i class="fa fa-angle-right"></i>

                        <a class="nav-link d-inline-block">
                            Invoices
                        </a>

                    </div>

                </div>

                <hr>


                <!-- PAGE HEADER -->
                <div class="row mx-1 align-items-center">

                    <div class="col-md-8 p-2">

                        <h3 class="mb-1">
                            <?=strToUpper($book->name);?> - INVOICES
                        </h3>

                        <small class="text-muted">
                            Customer invoices, payments, returns and outstanding balances
                        </small>

                    </div>


                    <?php if(hasRole(['owner','partner','staff','superadministrator'])):?>

                        <div class="col-md-4 p-2 text-md-right">

                            <button
                                class="btn btn-sm btn-flat btn-outline-success btn-click"
                                data-title="add invoice"
                                data-section="invoice">

                                <i class="fa fa-plus-circle"></i>
                                Invoice

                            </button>

                        </div>

                    <?php endif;?>

                </div>

                <hr>


                <!-- SUMMARY CARDS -->
                <div class="row mx-1">

                    <!-- TOTAL INVOICED -->
                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card invoice-summary-card shadow-sm border-left border-info">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Total Invoiced
                                        </small>

                                        <h4 class="mt-2 mb-0 text-info">
                                            <?=number_format($invoiceTotal,0);?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-info">
                                        <i class="fa fa-file-text-o"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    <?=$invoiceCount;?> invoices issued
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- TOTAL PAID -->
                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card invoice-summary-card shadow-sm border-left border-success">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Total Paid
                                        </small>

                                        <h4 class="mt-2 mb-0 text-success">
                                            <?=number_format($paidTotal,0);?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-success">
                                        <i class="fa fa-check-circle"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    <?=$paidInvoices;?> fully settled
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- OUTSTANDING -->
                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card invoice-summary-card shadow-sm border-left border-warning">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Outstanding
                                        </small>

                                        <h4 class="mt-2 mb-0 text-warning">
                                            <?=number_format($balanceTotal,0);?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-warning">
                                        <i class="fa fa-credit-card"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    <?=$partialInvoices;?> part paid
                                    &bull;
                                    <?=$unpaidInvoices;?> unpaid
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- RETURNS -->
                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card invoice-summary-card shadow-sm border-left border-danger">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Invoice Returns
                                        </small>

                                        <h4 class="mt-2 mb-0 text-danger">
                                            <?=number_format($returnedTotal,0);?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-danger">
                                        <i class="fa fa-undo"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    Total value returned
                                </small>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- INVOICE REGISTER -->
                <div class="row mx-1">

                    <div class="col-12 p-2">

                        <div class="card shadow-sm border-0">

                            <div class="card-header bg-white">

                                <div class="row align-items-center">

                                    <div class="col-md-8">

                                        <strong>
                                            <i class="fa fa-table mr-2"></i>
                                            Invoice Register
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            Customer invoices and their current payment status
                                        </small>

                                    </div>

                                    <div class="col-md-4 text-md-right">

                                        <span class="badge badge-secondary">
                                            <?=$invoiceCount;?> Records
                                        </span>

                                    </div>

                                </div>

                            </div>


                            <div class="table-responsive">

                                <table class="table table-sm table-striped table-hover dataTable invoice-table mb-0">

                                    <thead>

                                        <tr>
                                            <th>#</th>
                                            <th>Invoice No.</th>
                                            <th>Customer</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Return</th>
                                            <th class="text-right">Paid</th>
                                            <th class="text-right">Balance</th>
                                            <th>Status</th>
                                            <th class="text-right">Action</th>
                                        </tr>

                                    </thead>


                                    <tbody>

                                    <?php if(!empty($invoiceRows)):?>

                                        <?php $s = 1;?>

                                        <?php foreach($invoiceRows as $r):?>

                                            <?php
                                                $rb =
                                                    (float)$r['newBalance'];

                                                $rp =
                                                    (float)$r['paid_amount'];

                                                if($rb <= 0)
                                                {
                                                    $status =
                                                        'Paid';

                                                    $statusClass =
                                                        'badge-success';
                                                }
                                                elseif($rp > 0)
                                                {
                                                    $status =
                                                        'Part Paid';

                                                    $statusClass =
                                                        'badge-warning';
                                                }
                                                else
                                                {
                                                    $status =
                                                        'Unpaid';

                                                    $statusClass =
                                                        'badge-danger';
                                                }
                                            ?>

                                            <tr class="hover hover-hide-content">

                                                <td>
                                                    <?=$s++;?>
                                                </td>


                                                <td>

                                                    <strong>
                                                        <?=htmlspecialchars($r['invoice_no']);?>
                                                    </strong>

                                                </td>


                                                <td>
                                                    <?=htmlspecialchars($r['customer'] ?? '');?>
                                                </td>


                                                <td class="text-right">

                                                    <?=number_format(
                                                        $r['total'],
                                                        0
                                                    );?>

                                                </td>


                                                <td class="text-right text-danger">

                                                    <?=number_format(
                                                        $r['returned'],
                                                        0
                                                    );?>

                                                </td>


                                                <td class="text-right text-success">

                                                    <?=number_format(
                                                        $r['paid_amount'],
                                                        0
                                                    );?>

                                                </td>


                                                <td class="
                                                    text-right
                                                    <?=(
                                                        $rb > 0
                                                            ? 'text-primary'
                                                            : 'text-success'
                                                    );?>
                                                ">

                                                    <strong>
                                                        <?=number_format(
                                                            $rb,
                                                            0
                                                        );?>
                                                    </strong>

                                                </td>


                                                <td>

                                                    <span class="badge <?=$statusClass;?>">
                                                        <?=$status;?>
                                                    </span>

                                                </td>


                                                <td class="text-right">

                                                    <?php if(hasRole(['owner','partner','staff','superadministrator'])):?>

                                                        <span class="hover-display text-sms">

                                                            <?php if(hasRole(['owner','superadministrator'])):?>

                                                                <button
                                                                    class="btn btn-sm btn-outline-info edit-invoice text-muted"
                                                                    data-id="<?=$r['id'];?>"
                                                                    title="Edit Invoice">

                                                                    <i class="fa fa-edit"></i>

                                                                </button>


                                                                <button
                                                                    class="btn btn-sm btn-outline-danger delete-invoice"
                                                                    data-id="<?=$r['id'];?>"
                                                                    title="Delete Invoice">

                                                                    <i class="fa fa-trash"></i>

                                                                </button>

                                                            <?php endif;?>


                                                            <button
                                                                class="btn btn-sm btn-outline-info create-detail text-muted"
                                                                data-id="<?=$r['id'];?>"
                                                                title="Add Invoice Details">

                                                                <i class="fa fa-plus"></i>

                                                            </button>


                                                            <button
                                                                class="btn btn-sm btn-outline-info view-invoice text-muted"
                                                                data-id="<?=$r['id'];?>"
                                                                data-title=""
                                                                title="View Invoice">

                                                                <i class="fa fa-eye"></i>

                                                            </button>


                                                            <button
                                                                class="btn btn-sm btn-outline-info invoice-return text-muted"
                                                                data-id="<?=$r['id'];?>"
                                                                title="Invoice Return">

                                                                <i class="fa fa-undo"></i>

                                                            </button>

                                                        </span>

                                                    <?php endif;?>

                                                </td>

                                            </tr>

                                        <?php endforeach;?>


                                    <?php else:?>

                                        <tr>

                                            <td
                                                colspan="9"
                                                class="text-center text-muted p-4">

                                                No invoices found.

                                            </td>

                                        </tr>

                                    <?php endif;?>

                                    </tbody>

                                </table>

                            </div>

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
            <div class="p-0 bg-white side-modal-full side-modal-full-page absolute border shadow" id='side-modal-full'>
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
                    var totall = 0;

                    $('.amountClass').each(function () {
                        var val = parseFloat($(this).val()) || 0;
                        total += val;
                    });

                    $('.trTotal').each(function () {
                        var valt = parseFloat($(this).text()) || 0;
                        totall += valt;
                    });

                    
                    $('#invoice_total').text(total.toFixed(2));
                    $('#invoice_total').text(totall.toFixed(2));
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

                // function recalculateInvoiceTotalsReturnedOnView()
                // {
                //     var total = 0;

                //     $('.trTotal').each(function(){
                //         var val = parseFloat($(this).text()) || 0;
                //         total += val;
                //     });

                //     // formatted visible total
                //     $('#returnedInvoiceTotal').text(
                //         total.toLocaleString(undefined,{
                //             minimumFractionDigits:2,
                //             maximumFractionDigits:2
                //         })
                //     );

                //     // raw hidden input
                //     $('.returnedInvoiceTotal').val(total);
                // }

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

                // remove an item from an invoice
                $(document).on('click','.remove-invoice-item',function(){
                    const button = $(this);
                    const id = button.data('id');
                    const row = button.closest('tr');

                    // run a function to complete the task
                    xdialog.confirm("Continue to remove this item?",function(){
                        $.ajax({
                            url:'save/index.php',
                            data:{
                                action:'removeInvoiceItem',
                                id:id
                            },
                            beforeSend:function(){
                                xdialog.startSpin();
                            },
                            success:function(res){
                                xdialog.stopSpin();
                                row.fadeOut(300, function () {
                                    $(this).remove();
                                });
                                // recalculate the total for the invoice
                                console.log(res);
                                recalculateInvoiceTotals();
                            },
                            error:function(err){
                                console.log(err);
                                xdialog.alert('error removing item');
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