<?php
    require_once(__dir__.'/../assets/functions.php');

    if(isVerified())
    {
        pageHeader('Paymodes');

        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));

        /*
        |--------------------------------------------------------------------------
        | PAYMENT MODES
        |--------------------------------------------------------------------------
        */
        $sql = "
            SELECT *
            FROM cashbook_paymodes
            WHERE book_id = ?
            ORDER BY name ASC
        ";

        $res = prepared_statements(
            $sql,
            'i',
            [$book->id]
        );

        $paymodes = [];

        if($res)
        {
            while($r = $res->fetch_assoc())
            {
                $paymodes[] = $r;
            }
        }

        $paymodeCount = count($paymodes);

        /*
        |--------------------------------------------------------------------------
        | PAYMENT MODE TRANSACTION SUMMARY
        |--------------------------------------------------------------------------
        |
        | Real money movement only:
        | cash-in requires a matching cashbook_cashins.transaction_id;
        | cash-out requires a matching cashbook_cashouts.transaction_id.
        | All cards, charts, insights and table totals therefore use verified movement.
        |
        */
        $summarySql = "
            SELECT
                t.paymode_id,

                COALESCE(
                    SUM(
                        CASE
                            WHEN t.credit_amount > 0
                            AND EXISTS (
                                SELECT 1
                                FROM cashbook_cashins ci
                                WHERE ci.transaction_id = t.id
                            )
                            THEN t.credit_amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS cashin,

                COALESCE(
                    SUM(
                        CASE
                            WHEN t.debit_amount > 0
                            AND EXISTS (
                                SELECT 1
                                FROM cashbook_cashouts co
                                WHERE co.transaction_id = t.id
                            )
                            THEN t.debit_amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS cashout

            FROM cashbook_transactions t
            WHERE t.book_id = ?
            GROUP BY t.paymode_id
        ";

        $summaryRes = prepared_statements(
            $summarySql,
            'i',
            [$book->id]
        );

        $paymodeSummary = [];

        if($summaryRes)
        {
            while($summary = $summaryRes->fetch_assoc())
            {
                $paymodeSummary[(int)$summary['paymode_id']] = [
                    'cashin'  => (float)$summary['cashin'],
                    'cashout' => (float)$summary['cashout']
                ];
            }
        }

        $totalCashin = 0;
        $totalCashout = 0;
        $activePaymodes = 0;

        foreach($paymodes as &$paymode)
        {
            $id = (int)$paymode['id'];

            $paymode['cashin'] =
                $paymodeSummary[$id]['cashin'] ?? 0;

            $paymode['cashout'] =
                $paymodeSummary[$id]['cashout'] ?? 0;

            $paymode['balance'] =
                $paymode['cashin'] - $paymode['cashout'];

            $totalCashin += $paymode['cashin'];
            $totalCashout += $paymode['cashout'];

            if(
                $paymode['cashin'] != 0 ||
                $paymode['cashout'] != 0
            )
            {
                $activePaymodes++;
            }
        }

        unset($paymode);

        $netMovement =
            $totalCashin - $totalCashout;
        ?>

        <style>
            .paymode-summary-card {
                border-radius: 8px;
                min-height: 125px;
            }

            .paymode-summary-card .summary-icon {
                font-size: 34px;
                opacity: .16;
            }

            .paymode-table td,
            .paymode-table th {
                vertical-align: middle;
            }

            .paymode-search {
                max-width: 300px;
            }

            .paymode-chart-card {
                border: 0;
                border-radius: 8px;
            }

            .paymode-chart-container {
                position: relative;
                height: 320px;
            }

            .paymode-doughnut-container {
                position: relative;
                height: 320px;
            }

            .paymode-insight {
                border-left: 3px solid #ddd;
                padding-left: 12px;
            }

            @media(max-width: 767px)
            {
                .paymode-search {
                    max-width: 100%;
                }

                .paymode-chart-container,
                .paymode-doughnut-container {
                    height: 280px;
                }
            }
        </style>


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
                        Paymodes
                    </a>

                </div>

            </div>

            <hr>


            <!-- PAGE HEADER -->
            <div class="row mx-1 align-items-center">

                <div class="col-md-8 p-2">

                    <h3 class="mb-1">
                        <?=strToUpper($book->name);?> - PAYMENT MODES
                    </h3>

                    <small class="text-muted">
                        Manage payment channels and monitor cash movement by payment mode
                    </small>

                </div>


                <?php if(hasRole(['owner','partner'])):?>

                    <div class="col-md-4 p-2 text-md-right">

                        <button
                            class="btn btn-sm btn-flat btn-outline-success btn-click"
                            data-title="add paymode"
                            data-section="paymode">

                            <i class="fa fa-plus-circle"></i>
                            Paymode

                        </button>

                    </div>

                <?php endif;?>

            </div>

            <hr>


            <!-- SUMMARY CARDS -->
            <div class="row mx-1">

                <!-- PAYMENT MODES -->
                <div class="col-xl-3 col-md-6 p-2">

                    <div class="card paymode-summary-card shadow-sm border-left border-info">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted text-uppercase">
                                        Payment Modes
                                    </small>

                                    <h4 class="mt-2 mb-0 text-info">
                                        <?=$paymodeCount;?>
                                    </h4>

                                </div>

                                <div class="summary-icon text-info">
                                    <i class="fa fa-credit-card"></i>
                                </div>

                            </div>

                            <small class="text-muted">
                                <?=$activePaymodes;?> with transactions
                            </small>

                        </div>

                    </div>

                </div>


                <!-- CASH IN -->
                <div class="col-xl-3 col-md-6 p-2">

                    <div class="card paymode-summary-card shadow-sm border-left border-success">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted text-uppercase">
                                        Cash In
                                    </small>

                                    <h4 class="mt-2 mb-0 text-success">
                                        <?=number_format($totalCashin,2);?>
                                    </h4>

                                </div>

                                <div class="summary-icon text-success">
                                    <i class="fa fa-arrow-circle-down"></i>
                                </div>

                            </div>

                            <small class="text-muted">
                                Total received across paymodes
                            </small>

                        </div>

                    </div>

                </div>


                <!-- CASH OUT -->
                <div class="col-xl-3 col-md-6 p-2">

                    <div class="card paymode-summary-card shadow-sm border-left border-danger">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted text-uppercase">
                                        Cash Out
                                    </small>

                                    <h4 class="mt-2 mb-0 text-danger">
                                        <?=number_format($totalCashout,2);?>
                                    </h4>

                                </div>

                                <div class="summary-icon text-danger">
                                    <i class="fa fa-arrow-circle-up"></i>
                                </div>

                            </div>

                            <small class="text-muted">
                                Total paid out across paymodes
                            </small>

                        </div>

                    </div>

                </div>


                <!-- NET MOVEMENT -->
                <div class="col-xl-3 col-md-6 p-2">

                    <div class="card paymode-summary-card shadow-sm border-left border-primary">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted text-uppercase">
                                        Net Movement
                                    </small>

                                    <h4 class="mt-2 mb-0 <?=($netMovement >= 0 ? 'text-primary' : 'text-danger');?>">
                                        <?=number_format($netMovement,2);?>
                                    </h4>

                                </div>

                                <div class="summary-icon text-primary">
                                    <i class="fa fa-exchange"></i>
                                </div>

                            </div>

                            <small class="text-muted">
                                Cash in less cash out
                            </small>

                        </div>

                    </div>

                </div>

            </div>



            <!-- PAYMENT MODE VISUALS -->
            <div class="row mx-1">

                <!-- CASH FLOW BY PAYMENT MODE -->
                <div class="col-lg-8 p-2">

                    <div class="card paymode-chart-card shadow-sm">

                        <div class="card-header bg-white">

                            <strong>
                                <i class="fa fa-bar-chart mr-2"></i>
                                Cash Flow by Payment Mode
                            </strong>

                            <br>

                            <small class="text-muted">
                                Compare cash received and cash paid out through each payment channel
                            </small>

                        </div>

                        <div class="card-body">

                            <?php if(!empty($paymodes)):?>

                                <div class="paymode-chart-container">
                                    <canvas id="paymodeCashFlowChart"></canvas>
                                </div>

                            <?php else:?>

                                <div class="text-center text-muted p-4">
                                    No payment mode data available for the chart.
                                </div>

                            <?php endif;?>

                        </div>

                    </div>

                </div>


                <!-- CASH-IN DISTRIBUTION -->
                <div class="col-lg-4 p-2">

                    <div class="card paymode-chart-card shadow-sm">

                        <div class="card-header bg-white">

                            <strong>
                                <i class="fa fa-pie-chart mr-2"></i>
                                Cash-In Distribution
                            </strong>

                            <br>

                            <small class="text-muted">
                                Share of receipts by payment mode
                            </small>

                        </div>

                        <div class="card-body">

                            <?php if($totalCashin > 0):?>

                                <div class="paymode-doughnut-container">
                                    <canvas id="paymodeDistributionChart"></canvas>
                                </div>

                            <?php else:?>

                                <div class="text-center text-muted p-4">
                                    No cash-in transactions available for distribution.
                                </div>

                            <?php endif;?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PAYMENT MODE INSIGHTS -->
            <div class="row mx-1">

                <?php
                    $topCashinMode = null;
                    $topCashoutMode = null;
                    $highestBalanceMode = null;

                    foreach($paymodes as $mode)
                    {
                        if(
                            $topCashinMode === null ||
                            $mode['cashin'] > $topCashinMode['cashin']
                        )
                        {
                            $topCashinMode = $mode;
                        }

                        if(
                            $topCashoutMode === null ||
                            $mode['cashout'] > $topCashoutMode['cashout']
                        )
                        {
                            $topCashoutMode = $mode;
                        }

                        if(
                            $highestBalanceMode === null ||
                            $mode['balance'] > $highestBalanceMode['balance']
                        )
                        {
                            $highestBalanceMode = $mode;
                        }
                    }
                ?>

                <div class="col-md-4 p-2">

                    <div class="card shadow-sm border-0">

                        <div class="card-body">

                            <div class="paymode-insight">

                                <small class="text-muted text-uppercase">
                                    Highest Cash In
                                </small>

                                <h5 class="mb-1 mt-1">
                                    <?=htmlspecialchars($topCashinMode['name'] ?? 'N/A');?>
                                </h5>

                                <small class="text-success">
                                    <?=number_format($topCashinMode['cashin'] ?? 0,2);?>
                                </small>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-4 p-2">

                    <div class="card shadow-sm border-0">

                        <div class="card-body">

                            <div class="paymode-insight">

                                <small class="text-muted text-uppercase">
                                    Highest Cash Out
                                </small>

                                <h5 class="mb-1 mt-1">
                                    <?=htmlspecialchars($topCashoutMode['name'] ?? 'N/A');?>
                                </h5>

                                <small class="text-danger">
                                    <?=number_format($topCashoutMode['cashout'] ?? 0,2);?>
                                </small>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-4 p-2">

                    <div class="card shadow-sm border-0">

                        <div class="card-body">

                            <div class="paymode-insight">

                                <small class="text-muted text-uppercase">
                                    Highest Net Balance
                                </small>

                                <h5 class="mb-1 mt-1">
                                    <?=htmlspecialchars($highestBalanceMode['name'] ?? 'N/A');?>
                                </h5>

                                <small class="<?= (($highestBalanceMode['balance'] ?? 0) >= 0 ? 'text-primary' : 'text-danger'); ?>">
                                    <?=number_format($highestBalanceMode['balance'] ?? 0,2);?>
                                </small>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PAYMENT MODE REGISTER -->
            <div class="row mx-1">

                <div class="col-12 p-2">

                    <div class="card shadow-sm border-0">

                        <div class="card-header bg-white">

                            <div class="row align-items-center">

                                <div class="col-md-7">

                                    <strong>
                                        <i class="fa fa-table mr-2"></i>
                                        Payment Mode Summary
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        Cash movement grouped by payment channel
                                    </small>

                                </div>


                                <div class="col-md-5 mt-2 mt-md-0">

                                    <input
                                        type="text"
                                        id="paymodeSearch"
                                        class="form-control form-control-sm paymode-search float-md-right"
                                        placeholder="Search payment mode...">

                                </div>

                            </div>

                        </div>


                        <div class="table-responsive">

                            <table class="table table-sm table-striped table-hover paymode-table mb-0">

                                <thead>

                                    <tr>
                                        <th>#</th>
                                        <th>Payment Mode</th>
                                        <th>Details</th>
                                        <th class="text-right">Cash In</th>
                                        <th class="text-right">Cash Out</th>
                                        <th class="text-right">Balance</th>
                                        <th class="text-right">Action</th>
                                    </tr>

                                </thead>


                                <tbody id="paymodeTableBody">

                                <?php if(empty($paymodes)):?>

                                    <tr>

                                        <td
                                            colspan="7"
                                            class="text-center text-muted p-4">

                                            No payment modes found.

                                        </td>

                                    </tr>

                                <?php else:?>

                                    <?php $count = 1;?>

                                    <?php foreach($paymodes as $r):?>

                                        <tr class="hover hover-hide-content">

                                            <td>
                                                <?=$count++;?>
                                            </td>


                                            <td>

                                                <strong>
                                                    <?=htmlspecialchars($r['name'] ?? '');?>
                                                </strong>

                                            </td>


                                            <td class="text-muted">
                                                <?=htmlspecialchars($r['details'] ?? '');?>
                                            </td>


                                            <td class="text-right text-success">

                                                <?=number_format(
                                                    $r['cashin'],
                                                    2
                                                );?>

                                            </td>


                                            <td class="text-right text-danger">

                                                <?=number_format(
                                                    $r['cashout'],
                                                    2
                                                );?>

                                            </td>


                                            <td class="text-right <?=($r['balance'] >= 0 ? 'text-primary' : 'text-danger');?>">

                                                <strong>
                                                    <?=number_format(
                                                        $r['balance'],
                                                        2
                                                    );?>
                                                </strong>

                                            </td>


                                            <td class="text-right">

                                                <?php if(hasRole(['owner','partner'])):?>

                                                    <span class="hover-display text-sms">

                                                        <button
                                                            class="btn btn-sm btn-outline-info edit-paymode btn-click text-muted"
                                                            data-title="paymode-edit"
                                                            data-section="paymode-edit"
                                                            data-id="<?=$r['id'];?>"
                                                            title="Edit Paymode">

                                                            <i class="fa fa-edit"></i>

                                                        </button>


                                                        <button
                                                            class="btn btn-sm btn-outline-danger delete-paymode"
                                                            data-id="<?=$r['id'];?>"
                                                            title="Delete Paymode">

                                                            <i class="fa fa-trash"></i>

                                                        </button>

                                                    </span>

                                                <?php endif;?>

                                            </td>

                                        </tr>

                                    <?php endforeach;?>

                                <?php endif;?>

                                </tbody>


                                <?php if(!empty($paymodes)):?>

                                    <tfoot>

                                        <tr class="font-weight-bold">

                                            <td colspan="3" class="text-right">
                                                TOTAL
                                            </td>

                                            <td class="text-right text-success">
                                                <?=number_format($totalCashin,2);?>
                                            </td>

                                            <td class="text-right text-danger">
                                                <?=number_format($totalCashout,2);?>
                                            </td>

                                            <td class="text-right <?=($netMovement >= 0 ? 'text-primary' : 'text-danger');?>">
                                                <?=number_format($netMovement,2);?>
                                            </td>

                                            <td></td>

                                        </tr>

                                    </tfoot>

                                <?php endif;?>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- SIDE MODAL -->
        <div
            class="p-0 bg-white side-modal-tall absolute border shadow"
            id="side-modal-cashin">

            <div class="side-modal-header bg-success">

                <h3 class="side-modal-title text-white"></h3>

                <button
                    type="button"
                    class="side-modal-close">
                    &times;
                </button>

            </div>

            <div class="side-modal-content"></div>

        </div>


        <?php
            pageFooter();
        ?>


        <script>

            /*
            |--------------------------------------------------------------------------
            | PAYMENT MODE CHART DATA
            |--------------------------------------------------------------------------
            */
            const paymodeLabels =
                <?=json_encode(
                    array_map(function($row){
                        return $row['name'];
                    }, $paymodes),
                    JSON_HEX_TAG |
                    JSON_HEX_APOS |
                    JSON_HEX_AMP |
                    JSON_HEX_QUOT
                );?>;

            const paymodeCashin =
                <?=json_encode(
                    array_map(function($row){
                        return (float)$row['cashin'];
                    }, $paymodes)
                );?>;

            const paymodeCashout =
                <?=json_encode(
                    array_map(function($row){
                        return (float)$row['cashout'];
                    }, $paymodes)
                );?>;


            /*
            |--------------------------------------------------------------------------
            | CASH FLOW BAR CHART
            |--------------------------------------------------------------------------
            */
            const paymodeCashFlowCanvas =
                document.getElementById('paymodeCashFlowChart');

            if(
                paymodeCashFlowCanvas &&
                typeof Chart !== 'undefined'
            )
            {
                new Chart(
                    paymodeCashFlowCanvas,
                    {
                        type:'bar',

                        data:{
                            labels:paymodeLabels,

                            datasets:[
                                {
                                    label:'Cash In',
                                    data:paymodeCashin,
                                    borderWidth:1
                                },
                                {
                                    label:'Cash Out',
                                    data:paymodeCashout,
                                    borderWidth:1
                                }
                            ]
                        },

                        options:{
                            responsive:true,
                            maintainAspectRatio:false,

                            scales:{
                                y:{
                                    beginAtZero:true,

                                    ticks:{
                                        callback:function(value)
                                        {
                                            return Number(value).toLocaleString();
                                        }
                                    }
                                }
                            },

                            plugins:{
                                legend:{
                                    position:'top'
                                },

                                tooltip:{
                                    callbacks:{
                                        label:function(context)
                                        {
                                            return context.dataset.label +
                                                ': ' +
                                                Number(context.raw).toLocaleString(
                                                    undefined,
                                                    {
                                                        minimumFractionDigits:2,
                                                        maximumFractionDigits:2
                                                    }
                                                );
                                        }
                                    }
                                }
                            }
                        }
                    }
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CASH-IN DISTRIBUTION DOUGHNUT
            |--------------------------------------------------------------------------
            */
            const paymodeDistributionCanvas =
                document.getElementById('paymodeDistributionChart');

            if(
                paymodeDistributionCanvas &&
                typeof Chart !== 'undefined'
            )
            {
                new Chart(
                    paymodeDistributionCanvas,
                    {
                        type:'doughnut',

                        data:{
                            labels:paymodeLabels,

                            datasets:[
                                {
                                    data:paymodeCashin,
                                    borderWidth:1
                                }
                            ]
                        },

                        options:{
                            responsive:true,
                            maintainAspectRatio:false,

                            plugins:{
                                legend:{
                                    position:'bottom'
                                },

                                tooltip:{
                                    callbacks:{
                                        label:function(context)
                                        {
                                            const value =
                                                Number(context.raw);

                                            const total =
                                                paymodeCashin.reduce(
                                                    function(sum,item)
                                                    {
                                                        return sum + Number(item);
                                                    },
                                                    0
                                                );

                                            const percentage =
                                                total > 0
                                                    ? ((value / total) * 100).toFixed(1)
                                                    : 0;

                                            return context.label +
                                                ': ' +
                                                value.toLocaleString(
                                                    undefined,
                                                    {
                                                        minimumFractionDigits:2,
                                                        maximumFractionDigits:2
                                                    }
                                                ) +
                                                ' (' +
                                                percentage +
                                                '%)';
                                        }
                                    }
                                }
                            }
                        }
                    }
                );
            }


            /*
            |--------------------------------------------------------------------------
            | OPEN PAYMODE FORM
            |--------------------------------------------------------------------------
            */
            $(document).on('click','.btn-click',function(){

                var title =
                    $(this).data('title');

                title =
                    title.toUpperCase();

                $('.side-modal-tall').show();

                $('.side-modal-title').html(title);

                var category =
                    $(this).data('section');

                var id =
                    $(this).data('id');

                fetchData(
                    category,
                    id
                );

            });


            function fetchData(sect,id)
            {
                var book_id =
                    "<?=encryptor('decrypt',request('bsid'));?>";

                if(sect != '')
                {
                    $.ajax({

                        url:'../books/save/index.php',

                        data:{
                            section:sect,
                            book_id:book_id,
                            action:'fetchForm',
                            mode_id:id
                        },

                        beforeSend:function(){

                            $('.side-modal-content').html(
                                "<h3 class='text-center p-4'>Loading...</h3>"
                            );

                        },

                        success:function(res){

                            $('.side-modal-content').html(res);

                        },

                        error:function(){

                            $('.side-modal-content').html(
                                "<h3 class='text-center text-danger p-4'>Error Loading data!!</h3>"
                            );

                        }

                    });
                }
            }


            /*
            |--------------------------------------------------------------------------
            | SAVE PAYMODE
            |--------------------------------------------------------------------------
            */
            $(document).on('click','.savePaymode',function(){

                submitSingleForm(
                    "newPaymodeForm",
                    "../books/save/index.php"
                );

            });


            function submitSingleForm(formId,backendUrl)
            {
                const form =
                    document.getElementById(formId);

                if(!form)
                {
                    console.error(
                        "Form not found:",
                        formId
                    );

                    return;
                }

                /*
                 * Prevent duplicate submit handlers when the modal
                 * is opened more than once.
                 */
                if(form.dataset.submitBound === '1')
                {
                    return;
                }

                form.dataset.submitBound = '1';

                form.addEventListener(
                    "submit",
                    function(e)
                    {
                        e.preventDefault();

                        if(typeof xdialog !== 'undefined')
                        {
                            xdialog.startSpin();
                        }

                        const formData =
                            new FormData(form);

                        fetch(
                            backendUrl,
                            {
                                method:"POST",
                                body:formData
                            }
                        )

                        .then(function(res){
                            return res.text();
                        })

                        .then(function(response){

                            window.location.reload();

                        })

                        .catch(function(err){

                            console.error(
                                "AJAX error:",
                                err
                            );

                            if(
                                typeof xdialog !== 'undefined' &&
                                xdialog.stopSpin
                            )
                            {
                                xdialog.stopSpin();
                            }

                        });

                    }
                );
            }


            /*
            |--------------------------------------------------------------------------
            | SEARCH PAYMENT MODES
            |--------------------------------------------------------------------------
            */
            $(document).on('keyup','#paymodeSearch',function(){

                var value =
                    $(this).val().toLowerCase();

                $('#paymodeTableBody tr').each(function(){

                    var rowText =
                        $(this).text().toLowerCase();

                    $(this).toggle(
                        rowText.indexOf(value) !== -1
                    );

                });

            });

        </script>

        <?php
    }
    else
    {
        redirect('../');
    }
?>
