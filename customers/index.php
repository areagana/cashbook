<?php
    require_once(__dir__.'/../assets/functions.php');

    if(isVerified())
    {
        pageHeader('Customers');

        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER FINANCIAL SUMMARY
        |--------------------------------------------------------------------------
        |
        | Customer balance is taken from cashbook_customer_balances, while
        | transaction totals come from cashbook_transactions.
        |
        | Convention:
        |   credit_amount = cash coming from customer
        |   debit_amount  = amount charged/owed by customer
        |
        | This page therefore gives us:
        |   - Customer count
        |   - Customers owing
        |   - Total outstanding
        |   - Total customer cash-in
        |   - Total customer cash-out
        |   - Net customer movement
        |
        |--------------------------------------------------------------------------
        */

        $summarySql = "SELECT
                    COUNT(*) AS customer_count,

                    SUM(
                        CASE
                            WHEN COALESCE(cb.balance,0) > 0
                            THEN 1
                            ELSE 0
                        END
                    ) AS owing_customers,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN COALESCE(cb.balance,0) > 0
                                THEN cb.balance
                                ELSE 0
                            END
                        ),
                        0
                    ) AS outstanding_balance

                FROM cashbook_customers c

                LEFT JOIN cashbook_customer_balances cb
                    ON cb.customer_id = c.id
                    AND cb.book_id = c.book_id

                WHERE c.book_id = ?
            ";

        $summaryRes = prepared_statements($summarySql, 'i', [$book->id]);
        $summary = $summaryRes ? $summaryRes->fetch_assoc() : [];

        $customerCount = (int)($summary['customer_count'] ?? 0);
        $owingCustomers = (int)($summary['owing_customers'] ?? 0);
        $outstandingBalance = (float)($summary['outstanding_balance'] ?? 0);
        $cashIn = (float)($summary['cashin'] ?? 0);
        $cashOut = (float)($summary['cashout'] ?? 0);
        $netMovement = $cashIn - $cashOut;

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER DATA
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT
                c.*,
                COUNT(DISTINCT cci.item_id) AS items,
                cr.name AS route,
                crm.name AS route_manager,
                COALESCE(cb.balance,0) AS balance,

                COALESCE(SUM(t.credit_amount),0) AS cashin,
                COALESCE(SUM(t.debit_amount),0) AS cashout

            FROM cashbook_customers c

            LEFT JOIN cashbook_customer_balances cb
                ON cb.customer_id = c.id
                AND cb.book_id = c.book_id

            LEFT JOIN cashbook_routes cr
                ON cr.id = c.route_id

            LEFT JOIN cashbook_route_managers crm
                ON crm.id = c.route_manager_id

            LEFT JOIN cashbook_customer_items cci
                ON cci.customer_id = c.id

            LEFT JOIN cashbook_transactions t
                ON t.customer_id = c.id
                AND t.book_id = c.book_id

            WHERE c.book_id = ?

            GROUP BY
                c.id,
                cr.name,
                crm.name,
                cb.balance

            ORDER BY
                COALESCE(cb.balance,0) DESC,
                c.name ASC
        ";

        $transactionSummarySql = " SELECT
                    COALESCE(SUM(credit_amount),0) AS cashin,
                    COALESCE(SUM(debit_amount),0) AS cashout
                FROM cashbook_transactions
                WHERE book_id = ?
                AND customer_id > 0
            ";

            $transactionSummaryRes = prepared_statements(
                $transactionSummarySql,
                'i',
                [$book->id]
            );

            $transactionSummary = $transactionSummaryRes
                ? $transactionSummaryRes->fetch_assoc()
                : [];

            $cashIn = (float)($transactionSummary['cashin'] ?? 0);
            $cashOut = (float)($transactionSummary['cashout'] ?? 0);

        $res = prepared_statements($sql,'i',[$book->id]);

        $customers = [];

        $totalItems = 0;
        $customersWithTransactions = 0;

        while($r = $res->fetch_assoc())
        {
            $r['balance'] = (float)$r['balance'];
            $r['cashin'] = (float)$r['cashin'];
            $r['cashout'] = (float)$r['cashout'];

            $customers[] = $r;

            $totalItems += (int)$r['items'];

            if($r['cashin'] != 0 || $r['cashout'] != 0)
            {
                $customersWithTransactions++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER BALANCE TABLE
        |--------------------------------------------------------------------------
        */

        $customersSql = "
            SELECT
                ccb.customer_id,
                cc.name AS customer_name,
                ccb.balance
            FROM cashbook_customer_balances ccb
            INNER JOIN cashbook_customers cc
                ON cc.id = ccb.customer_id
            WHERE ccb.book_id = ?
              AND ccb.balance > 0
            ORDER BY ccb.balance DESC
        ";

        $customersResult = prepared_statements(
            $customersSql,
            'i',
            [$book->id]
        );

        $balanceCustomers = [];

        $balanceTotal = 0;

        if($customersResult)
        {
            while($customer = $customersResult->fetch_assoc())
            {
                $customer['balance'] = (float)$customer['balance'];

                $balanceCustomers[] = $customer;
                $balanceTotal += $customer['balance'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TOP CUSTOMERS
        |--------------------------------------------------------------------------
        */

        $topCustomer = null;

        foreach($customers as $customer)
        {
            if(
                $topCustomer === null ||
                $customer['balance'] > $topCustomer['balance']
            )
            {
                $topCustomer = $customer;
            }
        }

        function customerMoney($amount)
        {
            return number_format((float)$amount, 2);
        }
?>
<style>
    .customer-summary-card {
        border-radius: 8px;
        min-height: 125px;
    }

    .customer-summary-card .summary-icon {
        font-size: 34px;
        opacity: .16;
    }

    .customer-chart-card {
        border: 0;
        border-radius: 8px;
    }

    .customer-chart-container {
        position: relative;
        height: 330px;
    }

    .customer-table td,
    .customer-table th {
        vertical-align: middle;
    }

    .customer-total-row {
        font-weight: 700;
        background: #f5f5f5;
    }

    .customer-filter {
        max-width: 300px;
    }

    .customer-balance-positive {
        font-weight: 700;
    }

    .customer-mini-stat {
        border-left: 3px solid #ddd;
        padding-left: 12px;
    }

    @media(max-width: 767px)
    {
        .customer-chart-container {
            height: 280px;
        }

        .customer-filter {
            max-width: 100%;
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
                Customers
            </a>
        </div>

        <div class="col-md-4 p-2 text-md-right">
            <a
                href="index_ai.php?bsid=<?=$bsid;?>"
                class="btn btn-sm btn-flat btn-outline-primary">
                <i class="fa fa-magic"></i>
                AI PAGE
            </a>
        </div>

    </div>

    <hr>

    <!-- PAGE HEADER -->
    <div class="row mx-1 align-items-center">

        <div class="col-md-8 p-2">
            <h3 class="mb-1">
                <?=strToUpper($book->name);?> - CUSTOMERS
            </h3>

            <small class="text-muted">
                Customer accounts, balances and transaction overview
            </small>
        </div>

        <?php if(hasRole(['owner','partner','staff'])):?>

            <div class="col-md-4 p-2 text-md-right">

                <button
                    class="btn btn-sm btn-flat btn-outline-success btn-click"
                    data-title="add customer"
                    data-section="customer">

                    <i class="fa fa-plus-circle"></i>
                    Customer

                </button>

            </div>

        <?php endif;?>

    </div>

    <hr>

    <!-- SUMMARY CARDS -->
    <div class="row mx-1">

        <div class="col-xl-3 col-md-6 p-2">

            <div class="card customer-summary-card shadow-sm border-left border-info">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <small class="text-muted text-uppercase">
                                Customers
                            </small>

                            <h4 class="mt-2 mb-0 text-info">
                                <?=$customerCount;?>
                            </h4>

                        </div>

                        <div class="summary-icon text-info">
                            <i class="fa fa-users"></i>
                        </div>

                    </div>

                    <small class="text-muted">
                        Total customer accounts
                    </small>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6 p-2">

            <div class="card customer-summary-card shadow-sm border-left border-warning">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <small class="text-muted text-uppercase">
                                Outstanding
                            </small>

                            <h4 class="mt-2 mb-0 text-warning">
                                <?=customerMoney($outstandingBalance);?>
                            </h4>

                        </div>

                        <div class="summary-icon text-warning">
                            <i class="fa fa-credit-card"></i>
                        </div>

                    </div>

                    <small class="text-muted">
                        Total customer balances
                    </small>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6 p-2">

            <div class="card customer-summary-card shadow-sm border-left border-success">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <small class="text-muted text-uppercase">
                                Customer Cash In
                            </small>

                            <h4 class="mt-2 mb-0 text-success">
                                <?=customerMoney($cashIn);?>
                            </h4>

                        </div>

                        <div class="summary-icon text-success">
                            <i class="fa fa-arrow-circle-down"></i>
                        </div>

                    </div>

                    <small class="text-muted">
                        Payments received from customers
                    </small>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6 p-2">

            <div class="card customer-summary-card shadow-sm border-left border-danger">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <small class="text-muted text-uppercase">
                                Customer Cash Out
                            </small>

                            <h4 class="mt-2 mb-0 text-danger">
                                <?=customerMoney($cashOut);?>
                            </h4>

                        </div>

                        <div class="summary-icon text-danger">
                            <i class="fa fa-arrow-circle-up"></i>
                        </div>

                    </div>

                    <small class="text-muted">
                        Customer-related cash out
                    </small>

                </div>

            </div>

        </div>

    </div>


    <!-- SECONDARY INSIGHTS -->
    <div class="row mx-1">

        <div class="col-md-4 p-2">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <div class="customer-mini-stat">

                        <small class="text-muted text-uppercase">
                            Customers Owing
                        </small>

                        <h5 class="mb-0 mt-1">
                            <?=$owingCustomers;?>
                        </h5>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4 p-2">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <div class="customer-mini-stat">

                        <small class="text-muted text-uppercase">
                            Customer Transactions
                        </small>

                        <h5 class="mb-0 mt-1">
                            <?=$customersWithTransactions;?>
                        </h5>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4 p-2">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <div class="customer-mini-stat">

                        <small class="text-muted text-uppercase">
                            Net Customer Movement
                        </small>

                        <h5 class="mb-0 mt-1 <?=($netMovement >= 0 ? 'text-success' : 'text-danger');?>">
                            <?=customerMoney($netMovement);?>
                        </h5>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- CHARTS -->
    <div class="row mx-1">

        <div class="col-lg-8 p-2">

            <div class="card customer-chart-card shadow-sm">

                <div class="card-header bg-white">

                    <strong>
                        <i class="fa fa-bar-chart mr-2"></i>
                        Customer Cash Flow
                    </strong>

                </div>

                <div class="card-body">

                    <div class="customer-chart-container">

                        <canvas id="customerCashFlowChart"></canvas>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-lg-4 p-2">

            <div class="card customer-chart-card shadow-sm">

                <div class="card-header bg-white">

                    <strong>
                        <i class="fa fa-pie-chart mr-2"></i>
                        Customer Balance Distribution
                    </strong>

                </div>

                <div class="card-body">

                    <div class="customer-chart-container">

                        <canvas id="customerBalanceChart"></canvas>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- CUSTOMER FINANCIAL SUMMARY -->
    <div class="row mx-1">

        <div class="col-12 p-2">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <div class="row align-items-center">

                        <div class="col-md-7">

                            <strong>
                                <i class="fa fa-table mr-2"></i>
                                Customer Financial Summary
                            </strong>

                        </div>

                        <div class="col-md-5 mt-2 mt-md-0">

                            <input
                                type="text"
                                id="customerSummarySearch"
                                class="form-control form-control-sm customer-filter float-md-right"
                                placeholder="Search customer, route or manager...">

                        </div>

                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table table-sm table-striped table-hover customer-table mb-0">

                        <thead>

                            <tr>

                                <th>#</th>
                                <th>Customer</th>
                                <th>Route</th>
                                <th>Manager</th>
                                <th class="text-right">Items</th>
                                <th class="text-right">Cash In</th>
                                <th class="text-right">Cash Out</th>
                                <th class="text-right">Balance</th>
                                <th class="text-right">Action</th>

                            </tr>

                        </thead>

                        <tbody id="customerSummaryBody">

                        <?php if(empty($customers)):?>

                            <tr>

                                <td colspan="9" class="text-center text-muted p-4">

                                    No customers found.

                                </td>

                            </tr>

                        <?php else:?>

                            <?php $s = 1; ?>

                            <?php foreach($customers as $r):?>

                                <tr class="hover hover-hide-content">

                                    <td>
                                        <?=$s++;?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?=htmlspecialchars($r['name']);?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?=htmlspecialchars($r['route'] ?? '');?>
                                    </td>

                                    <td>
                                        <?=htmlspecialchars($r['route_manager'] ?? '');?>
                                    </td>

                                    <td class="text-right">
                                        <?=$r['items'];?>
                                    </td>

                                    <td class="text-right text-success">
                                        <?=customerMoney($r['cashin']);?>
                                    </td>

                                    <td class="text-right text-danger">
                                        <?=customerMoney($r['cashout']);?>
                                    </td>

                                    <td class="text-right <?=($r['balance'] > 0 ? 'text-warning' : 'text-success');?>">

                                        <strong>
                                            <?=customerMoney($r['balance']);?>
                                        </strong>

                                    </td>

                                    <td class="text-right">

                                        <?php if(hasRole(['owner','partner','staff'])):?>

                                            <span class="hover-display text-sms">

                                                <?php if(hasRole(['owner','partner'])):?>

                                                    <button
                                                        class="btn btn-sm btn-outline-info edit-customer text-muted"
                                                        data-id="<?=$r['id'];?>"
                                                        data-title="<?=htmlspecialchars($r['name']);?>">

                                                        <i class="fa fa-edit"></i>

                                                    </button>

                                                    <button
                                                        class="btn btn-sm btn-outline-danger delete-customer"
                                                        data-id="<?=$r['id'];?>">

                                                        <i class="fa fa-trash"></i>

                                                    </button>

                                                <?php endif;?>

                                                <button
                                                    class="btn btn-sm btn-outline-info view-customer text-muted"
                                                    data-id="<?=$r['id'];?>"
                                                    data-title="<?=htmlspecialchars($r['name']);?>">

                                                    <i class="fa fa-eye"></i>

                                                </button>

                                                <button
                                                    class="btn btn-sm btn-outline-secondary customer-attach-item"
                                                    data-id="<?=$r['id'];?>">

                                                    <i class="fa fa-plus"></i>

                                                </button>

                                            </span>

                                        <?php endif;?>

                                    </td>

                                </tr>

                            <?php endforeach;?>

                        <?php endif;?>

                        </tbody>

                        <tfoot>

                            <tr class="customer-total-row">

                                <td colspan="5" class="text-right">
                                    TOTAL
                                </td>

                                <td class="text-right text-success">
                                    <?=customerMoney($cashIn);?>
                                </td>

                                <td class="text-right text-danger">
                                    <?=customerMoney($cashOut);?>
                                </td>

                                <td class="text-right text-warning">
                                    <?=customerMoney($outstandingBalance);?>
                                </td>

                                <td></td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>

    </div>


    <!-- OUTSTANDING CUSTOMER ACCOUNTS -->
    <div class="row mx-1">

        <div class="col-12 p-2">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <strong>
                        <i class="fa fa-credit-card mr-2"></i>
                        Customers With Outstanding Balances
                    </strong>

                    <span class="badge badge-warning float-right">
                        <?=$owingCustomers;?>
                    </span>

                </div>

                <div class="table-responsive">

                    <table class="table table-sm table-bordered mb-0">

                        <thead>

                            <tr>

                                <th>#</th>
                                <th>Customer</th>
                                <th class="text-right">Outstanding Balance</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if(empty($balanceCustomers)):?>

                            <tr>

                                <td colspan="3" class="text-center text-muted p-4">
                                    No customers have an outstanding balance.
                                </td>

                            </tr>

                        <?php else:?>

                            <?php $count = 1; ?>

                            <?php foreach($balanceCustomers as $customer):?>

                                <tr>

                                    <td>
                                        <?=$count++;?>
                                    </td>

                                    <td>
                                        <?=htmlspecialchars($customer['customer_name']);?>
                                    </td>

                                    <td class="text-right text-warning">

                                        <strong>
                                            <?=customerMoney($customer['balance']);?>
                                        </strong>

                                    </td>

                                </tr>

                            <?php endforeach;?>

                        <?php endif;?>

                        </tbody>

                        <tfoot>

                            <tr>

                                <th colspan="2" class="text-right">
                                    Total Outstanding:
                                </th>

                                <th class="text-right text-warning">
                                    <?=customerMoney($balanceTotal);?>
                                </th>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- EXISTING SIDE MODAL -->
<div
    class="p-0 bg-white side-modal-tall absolute border shadow"
    id="side-modal-customer">

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


<!-- EXISTING CENTRAL MODAL -->
<div
    class="p-0 bg-white central-modal absolute border shadow"
    id="central-modal">

    <div class="central-modal-header bg-success">

        <h3 class="central-modal-title"></h3>

        <button
            type="button"
            class="central-modal-close">
            &times;
        </button>

    </div>

    <div class="central-modal-content"></div>

</div>


<?php
        pageFooter();
?>

<script>

    /*
    |--------------------------------------------------------------------------
    | CHART DATA
    |--------------------------------------------------------------------------
    */

    const customerLabels =
        <?=json_encode(
            array_map(function($row){
                return $row['name'];
            }, $customers),
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
        );?>;

    const customerCashIn =
        <?=json_encode(
            array_map(function($row){
                return (float)$row['cashin'];
            }, $customers)
        );?>;

    const customerCashOut =
        <?=json_encode(
            array_map(function($row){
                return (float)$row['cashout'];
            }, $customers)
        );?>;

    const customerBalances =
        <?=json_encode(
            array_map(function($row){
                return (float)$row['balance'];
            }, $customers)
        );?>;


    /*
    |--------------------------------------------------------------------------
    | CASH FLOW BAR CHART
    |--------------------------------------------------------------------------
    */

    const cashFlowCanvas =
        document.getElementById('customerCashFlowChart');

    if(cashFlowCanvas && typeof Chart !== 'undefined')
    {
        new Chart(cashFlowCanvas, {

            type: 'bar',

            data: {

                labels: customerLabels,

                datasets: [

                    {
                        label: 'Cash In',
                        data: customerCashIn,
                        borderWidth: 1
                    },

                    {
                        label: 'Cash Out',
                        data: customerCashOut,
                        borderWidth: 1
                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {

                            callback: function(value)
                            {
                                return Number(value).toLocaleString();
                            }

                        }

                    }

                },

                plugins: {

                    legend: {
                        position: 'top'
                    },

                    tooltip: {

                        callbacks: {

                            label: function(context)
                            {
                                return context.dataset.label + ': ' +
                                    Number(context.raw).toLocaleString(
                                        undefined,
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }
                                    );
                            }

                        }

                    }

                }

            }

        });
    }


    /*
    |--------------------------------------------------------------------------
    | BALANCE DISTRIBUTION
    |--------------------------------------------------------------------------
    */

    const balanceCanvas =
        document.getElementById('customerBalanceChart');

    if(balanceCanvas && typeof Chart !== 'undefined')
    {
        const positiveBalances =
            customerBalances.filter(function(balance){
                return balance > 0;
            }).reduce(function(total,balance){
                return total + balance;
            },0);

        const clearAccounts =
            customerBalances.filter(function(balance){
                return balance <= 0;
            }).length;

        new Chart(balanceCanvas, {

            type: 'doughnut',

            data: {

                labels: [
                    'Outstanding Balance',
                    'Clear Accounts'
                ],

                datasets: [

                    {
                        data: [
                            positiveBalances,
                            clearAccounts
                        ],
                        borderWidth: 1
                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        position: 'bottom'
                    },

                    tooltip: {

                        callbacks: {

                            label: function(context)
                            {
                                return context.label + ': ' +
                                    Number(context.raw).toLocaleString(
                                        undefined,
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }
                                    );
                            }

                        }

                    }

                }

            }

        });
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER SEARCH
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'keyup',
        '#customerSummarySearch',
        function()
        {
            const value = $(this)
                .val()
                .toLowerCase();

            $('#customerSummaryBody tr').each(function()
            {
                const rowText = $(this)
                    .text()
                    .toLowerCase();

                $(this).toggle(
                    rowText.indexOf(value) !== -1
                );
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | ADD CUSTOMER MODAL
    |--------------------------------------------------------------------------
    */

    $(document).on('click','.btn-click',function(){

        var title = $(this).data('title');

        title = title.toUpperCase();

        $('.side-modal-tall').show();

        $('.side-modal-title').html(title);

        var category = $(this).data('section');

        fetchData(category);

    });


    function fetchData(sect)
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
                    action:'fetchForm'

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
    | SAVE CUSTOMER
    |--------------------------------------------------------------------------
    */

    $(document).on('click','.saveCustomer',function(){

        submitSingleForm(
            "newCustomerForm",
            "../books/save/index.php"
        );

    });


    function submitSingleForm(formId, backendUrl)
    {
        const form = document.getElementById(formId);

        if(!form)
        {
            console.error(
                "Form not found:",
                formId
            );

            return;
        }

        /*
        | Avoid attaching the same submit listener more than once.
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

                    let responseDiv =
                        document.getElementById(
                            "response_" + formId
                        );

                    if(!responseDiv)
                    {
                        responseDiv =
                            document.createElement("div");

                        responseDiv.id =
                            "response_" + formId;

                        form.appendChild(responseDiv);
                    }

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
    | VIEW CUSTOMER
    |--------------------------------------------------------------------------
    */

    $(document).on('click','.view-customer',function(){

        $('#central-modal').show();

        var title =
            $(this).data('title') +
            " Transactions";

        $('.central-modal-title').html(title);

        var id =
            $(this).data('id');

        $.ajax({

            url:'save/index.php',

            data:{

                customer_id:id,
                action:'Customer-details'

            },

            beforeSend:function(){

                $('.central-modal-content').html(
                    "<center><h3>Loading...</h3></center>"
                );

            },

            success:function(res){

                $('.central-modal-content').html(res);

            },

            error:function(){

                $('.central-modal-content').html(
                    "<center><h3>!!! Error Loading data</h3></center>"
                );

            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | EDIT CUSTOMER
    |--------------------------------------------------------------------------
    */

    $(document).on('click','.edit-customer',function(){

        $('#side-modal-customer').show();

        var title =
            $(this).data('title') +
            " Edit";

        $('.side-modal-title').html(title);

        var id =
            $(this).data('id');

        $.ajax({

            url:'save/index.php',

            data:{

                customer_id:id,
                action:'Customer-edit'

            },

            beforeSend:function(){

                $('.side-modal-content').html(
                    "<center><h3>Loading...</h3></center>"
                );

            },

            success:function(res){

                $('.side-modal-content').html(res);

            },

            error:function(){

                $('.side-modal-content').html(
                    "<center><h3>!!! Error Loading data</h3></center>"
                );

            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | DELETE CUSTOMER
    |--------------------------------------------------------------------------
    */

    $(document).on('click','.delete-customer',function(){

        var id =
            $(this).data('id');

        xdialog.confirm(
            'Confirm to delete customer?',
            function()
            {

                $.ajax({

                    url:'save/index.php',

                    data:{

                        action:'deleteCustomer',
                        id:id

                    },

                    beforeSend:function(){

                        xdialog.startSpin();

                    },

                    success:function(res){

                        xdialog.stopSpin();

                        xdialog.info(
                            "Customer deleted successfully"
                        );

                        window.location.reload();

                    },

                    error:function(){

                        xdialog.stopSpin();

                        xdialog.info(
                            "Error removing customer"
                        );

                    }

                });

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | ATTACH ITEMS TO CUSTOMER
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.customer-attach-item',
        function()
        {
            var id =
                $(this).data('id');

            var book_id =
                '<?=$book->id;?>';

            $('.side-modal-title').html(
                "ATTACH ITEMS TO CUSTOMER"
            );

            $('#side-modal-customer').show();

            $.ajax({

                url:'save/index.php',

                data:{

                    action:'findItems',
                    id:id,
                    book_id:book_id

                },

                beforeSend:function(){

                    $('.side-modal-content').html(
                        "<center><h3>Loading...</h3></center>"
                    );

                },

                success:function(res){

                    $('.side-modal-content').html(res);

                },

                error:function(){

                    xdialog.info(
                        "Error loading items"
                    );

                }

            });

        }
    );

</script>

<?php
    }
    else
    {
        redirect('../');
    }
?>
