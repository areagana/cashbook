<?php

require_once(__DIR__ . '/../assets/functions.php');

if (isVerified()) {

    pageHeader('Reports');

    $bsid = request('bkid');
    $book = bookFind(encryptor('decrypt', $bsid));

?>

<div class="container-fluid">

    <!-- =========================================================
         BREADCRUMB / PAGE HEADER
    ========================================================== -->

    <div class="row mx-1 align-items-center">

        <div class="col-md-8 p-2 d-flex align-items-center">

            <a href="../books/?bkid=<?= $bsid; ?>" class="nav-link px-1">
                Books
            </a>

            <i class="fa fa-angle-right mx-2"></i>

            <span class="text-muted">
                REPORTS
            </span>

        </div>


        <!-- EXPORT BUTTONS -->

        <div class="col-md-4 p-2 text-right">

            <button
                type="button"
                class="btn btn-sm btn-outline-success btn-flat report-export"
                data-format="excel">

                <i class="fa fa-file-excel-o"></i>
                Excel

            </button>


            <button
                type="button"
                class="btn btn-sm btn-outline-danger btn-flat report-export"
                data-format="pdf">

                <i class="fa fa-file-pdf-o"></i>
                PDF

            </button>


            <button
                type="button"
                class="btn btn-sm btn-outline-secondary btn-flat"
                onclick="printMe('report-print-area')">

                <i class="fa fa-print"></i>
                Print

            </button>

        </div>

    </div>


    <hr class="mt-1">


    <!-- =========================================================
         BUSINESS STATUS SUMMARY
         This is populated immediately when page loads.
         It is replaced when filters are applied.
    ========================================================== -->

    <div id="business-summary">

        <div class="row mx-1">

            <div class="col-12">

                <h5 class="font-weight-bold mb-3">

                    <i class="fa fa-dashboard"></i>
                    BUSINESS STATUS

                </h5>

            </div>


            <!-- CASH IN -->

            <div class="col-xl-3 col-md-6 col-sm-6 mb-3">

                <div class="card shadow-sm border-left-success h-100">

                    <div class="card-body">

                        <div class="row no-gutters align-items-center">

                            <div class="col mr-2">

                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Cash In
                                </div>

                                <div
                                    class="h4 mb-0 font-weight-bold text-gray-800"
                                    id="summary-cashin">

                                    0

                                </div>

                            </div>

                            <div class="col-auto">

                                <i class="fa fa-arrow-circle-down fa-2x text-success"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- CASH OUT -->

            <div class="col-xl-3 col-md-6 col-sm-6 mb-3">

                <div class="card shadow-sm border-left-danger h-100">

                    <div class="card-body">

                        <div class="row no-gutters align-items-center">

                            <div class="col mr-2">

                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Cash Out
                                </div>

                                <div
                                    class="h4 mb-0 font-weight-bold text-gray-800"
                                    id="summary-cashout">

                                    0

                                </div>

                            </div>

                            <div class="col-auto">

                                <i class="fa fa-arrow-circle-up fa-2x text-danger"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- BALANCE -->

            <div class="col-xl-3 col-md-6 col-sm-6 mb-3">

                <div class="card shadow-sm border-left-primary h-100">

                    <div class="card-body">

                        <div class="row no-gutters align-items-center">

                            <div class="col mr-2">

                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Balance
                                </div>

                                <div
                                    class="h4 mb-0 font-weight-bold text-gray-800"
                                    id="summary-balance">

                                    0

                                </div>

                            </div>

                            <div class="col-auto">

                                <i class="fa fa-balance-scale fa-2x text-primary"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TRANSACTIONS -->

            <div class="col-xl-3 col-md-6 col-sm-6 mb-3">

                <div class="card shadow-sm border-left-secondary h-100">

                    <div class="card-body">

                        <div class="row no-gutters align-items-center">

                            <div class="col mr-2">

                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                    Transactions
                                </div>

                                <div
                                    class="h4 mb-0 font-weight-bold text-gray-800"
                                    id="summary-transactions">

                                    0

                                </div>

                            </div>

                            <div class="col-auto">

                                <i class="fa fa-exchange fa-2x text-secondary"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         MAIN REPORT AREA
    ========================================================== -->

    <div class="row mx-1">


        <!-- =====================================================
             REPORT CENTER
        ====================================================== -->

        <div class="col-lg-9 col-md-8 order-2 order-md-1">
            <div class="card shadow-sm" id="report-print-area">
                <div class="card-header bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-0 font-weight-bold"><i class="fa fa-bar-chart"></i>
                                Transaction Report
                            </h5>
                            <small class="text-muted"  id="report-description">
                                All transactions
                            </small>
                        </div>

                        <div class="col-md-4 text-right">
                            <span class="badge badge-light p-2" id="report-count">
                                0 Transactions
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Item</th>
                                    <th>Customer</th>
                                    <th>Details</th>
                                    <th class="text-right">
                                        Cash In
                                    </th>
                                    <th class="text-right">
                                        Cash Out
                                    </th>
                                    <th width="80">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="transactions-tbody">
                                <tr>
                                    <td colspan="9" class="text-center p-5">
                                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                                        <div class="mt-2">
                                            Loading report...
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>

                                <tr class="font-weight-bold bg-light">

                                    <td colspan="4" class="text-right">
                                        TOTAL
                                    </td>

                                    <td
                                        class="text-right"
                                        id="report-total-cashin">

                                        0

                                    </td>

                                    <td class="text-right" id="report-total-cashout">
                                        0
                                    </td>

                                    <td
                                        class="text-right"
                                        id="report-total-balance">

                                        0

                                    </td>
                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             FILTER SIDEBAR
        ====================================================== -->

        <div class="col-lg-3 col-md-4 order-1 order-md-2 mb-3">

            <div class="card shadow-sm">

                <div class="card-header bg-white">

                    <h5 class="mb-0 font-weight-bold">

                        <i class="fa fa-filter"></i>
                        Report Filters

                    </h5>

                </div>


                <div class="card-body">

                    <!-- DATE FROM -->

                    <div class="form-group">

                        <label class="font-weight-bold">
                            Date From
                        </label>

                        <input
                            type="date"
                            name="min_date"
                            data-type="min_date"
                            max="<?= date('Y-m-d'); ?>"
                            class="form-control filter-item">
                    </div>


                    <!-- DATE TO -->

                    <div class="form-group">

                        <label class="font-weight-bold">
                            Date To
                        </label>

                        <input
                            type="date"
                            name="max_date"
                            data-type="max_date"
                            max="<?= date('Y-m-d'); ?>"
                            class="form-control filter-item">

                    </div>


                    <!-- MONTH -->

                    <div class="form-group">

                        <label class="font-weight-bold">
                            Month
                        </label>

                        <?php

                        $sqlm = " SELECT DISTINCT
                                MONTH(created_at) AS month,
                                YEAR(created_at) AS year
                            FROM cashbook_transactions
                            WHERE book_id = ?
                            ORDER BY YEAR(created_at) DESC,
                                    MONTH(created_at) DESC
                        ";

                        $months = prepared_statements(
                            $sqlm,
                            'i',
                            [$book->id]
                        );

                        $month_names = [

                            1 => 'January',
                            2 => 'February',
                            3 => 'March',
                            4 => 'April',
                            5 => 'May',
                            6 => 'June',
                            7 => 'July',
                            8 => 'August',
                            9 => 'September',
                            10 => 'October',
                            11 => 'November',
                            12 => 'December'

                        ];
                        ?>
                        <select name="month"  data-type="month" class="form-control filter-item">
                            <option value="">
                                All Months
                            </option>

                            <?php while ($rm = $months->fetch_assoc()): ?>
                                <option
                                    value="<?= $rm['month']; ?>"
                                    data-year="<?= $rm['year']; ?>">

                                    <?= $month_names[$rm['month']]; ?>
                                    <?= $rm['year']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>


                    <!-- TYPE -->

                    <div class="form-group">

                        <label class="font-weight-bold">
                            Transaction Type
                        </label>
                        <select name="type" data-type="type"  class="form-control filter-item">
                            <option value="">
                                All Transactions
                            </option>
                            <option value="credit">
                                Cash In
                            </option>
                            <option value="debit">
                                Cash Out
                            </option>
                        </select>

                    </div>


                    <!-- CATEGORY -->

                    <div class="form-group">
                        <label class="font-weight-bold">
                            Category
                        </label>

                        <?php

                        $sql = "SELECT *
                            FROM cashbook_categories
                            WHERE book_id = ?
                            ORDER BY name ASC
                        ";

                        $cats = prepared_statements(
                            $sql,
                            'i',
                            [$book->id]
                        );

                        ?>

                        <select
                            name="category"
                            data-type="category"
                            class="form-control filter-item">

                            <option value="">
                                All Categories
                            </option>

                            <?php while ($rc = $cats->fetch_assoc()): ?>

                                <option value="<?= $rc['id']; ?>">

                                    <?= htmlspecialchars($rc['name']); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- CUSTOMER -->

                    <div class="form-group">

                        <label class="font-weight-bold">
                            Customer
                        </label>

                        <?php

                        $sqlc = "
                            SELECT *
                            FROM cashbook_customers
                            WHERE book_id = ?
                            ORDER BY name ASC
                        ";

                        $cust = prepared_statements(
                            $sqlc,
                            'i',
                            [$book->id]
                        );

                        ?>

                        <select
                            name="customer"
                            data-type="customer"
                            class="form-control filter-item">

                            <option value="">
                                All Customers
                            </option>

                            <?php while ($rcu = $cust->fetch_assoc()): ?>

                                <option value="<?= $rcu['id']; ?>">

                                    <?= htmlspecialchars($rcu['name']); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>

                    <!-- ITEM -->

                    <div class="form-group">

                        <label class="font-weight-bold">
                            Item
                        </label>

                        <?php

                        $sqlc = " SELECT * FROM cashbook_items  WHERE book_id = ?  ORDER BY name ASC ";
                        $itm = prepared_statements($sqlc,'i',[$book->id] );
                        ?>

                        <select name="item" data-type="item" class="form-control filter-item">
                            <option value="">
                                All Items
                            </option>

                            <?php while ($rit = $itm->fetch_assoc()): ?>
                                <option value="<?= $rit['id']; ?>">
                                    <?= htmlspecialchars($rit['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                                
                    <hr>


                    <!-- FILTER BUTTON -->

                    <button type="button" class="btn btn-success btn-flat btn-block" id="apply-report-filter">
                        <i class="fa fa-filter"></i>
                        Apply Filters
                    </button>


                    <!-- RESET -->

                    <button  type="button" class="btn btn-outline-secondary btn-flat btn-block" id="reset-report-filter">
                        <i class="fa fa-refresh"></i>
                        Reset
                    </button>

                    <div class="text-center text-muted small mt-3"  id="filter-status">
                        Showing all transactions
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- =============================================================
     SIDE MODAL
============================================================== -->

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


<!-- =============================================================
     CENTRAL MODAL
============================================================== -->

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


<?php pageFooter(); ?>


<script>

    const BOOK_ID = "<?= $book->id; ?>";


    /* =========================================================
       LOAD REPORT
    ========================================================== */

    function loadReport(filters = {})
    {

        filters.action = 'transactionFilter';
        filters.book_id = BOOK_ID;

        $.ajax({

            url: '../books/save/index.php',

            type: 'POST',

            data: filters,

            beforeSend: function () {

                $('.transactions-tbody').html(`

                    <tr>
                        <td colspan="9" class="text-center p-5">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <div class="mt-2">
                                Loading report...
                            </div>
                        </td>
                    </tr>

                `);

            },

            success: function (res) {
                /*
                 * Backend should return the report rows.
                 *
                 * We also support a JSON response if you decide
                 * to upgrade transactionFilter later.
                 */

                try {

                    let data = JSON.parse(res);
                    if (data.status === 'success') {

                        $('.transactions-tbody').html(data.html);
                        updateReportSummary(data);

                    } else {
                        $('.transactions-tbody').html(data.html || emptyReport());
                    }

                } catch (e) {

                    /*
                     * Backward compatibility:
                     *
                     * Your current backend returns HTML.
                     */

                    $('.transactions-tbody').html(res);

                    /*
                     * Recalculate totals from returned table.
                     */

                    calculateTableSummary();
                }

            },

            error: function () {

                $('.transactions-tbody').html(`

                    <tr>

                        <td colspan="9" class="text-center text-danger p-5">

                            <i class="fa fa-exclamation-triangle fa-2x"></i>

                            <div class="mt-2">
                                Failed to load report.
                            </div>

                        </td>

                    </tr>

                `);

            }

        });

    }


    /* =========================================================
       INITIAL REPORT
    ========================================================== */

    $(document).ready(function () {
        loadReport();
    });


    /* =========================================================
       APPLY FILTER
    ========================================================== */

    $('#apply-report-filter').on('click', function () {

        let filters = {};

        $('.filter-item').each(function () {

            let key = $(this).data('type');
            let value = $(this).val();

            if (value !== '') {

                filters[key] = value;

            }

        });


        /*
         * If month is selected, send its year as well.
         */

        let monthOption =
            $('select[data-type="month"] option:selected');


        if (
            monthOption.val() !== '' &&
            monthOption.data('year')
        ) {

            filters.year = monthOption.data('year');

        }


        $('#filter-status').html(
            '<i class="fa fa-spinner fa-spin"></i> Applying filters...'
        );


        loadReport(filters);

    });


    /* =========================================================
       CHANGE FILTER
       Optional: automatically apply
    ========================================================== */

    $(document).on('change', '.filter-item', function () {

        /*
         * We intentionally do not automatically query here.
         *
         * The user can select multiple filters first and then
         * click Apply Filters.
         */

    });


    /* =========================================================
       RESET FILTERS
    ========================================================== */

    $('#reset-report-filter').on('click', function () {

        $('.filter-item').val('');

        $('#filter-status').html(
            'Showing all transactions'
        );

        $('#report-description').html(
            'All transactions'
        );

        loadReport();

    });


    /* =========================================================
       TABLE SUMMARY
    ========================================================== */

    function calculateTableSummary()
    {

        let cashin = 0;
        let cashout = 0;
        let transactions = 0;


        $('.transactions-tbody tr').each(function () {

            /*
             * Skip "no transactions" row.
             */

            if ($(this).find('td').length < 8) {
                return;
            }


            transactions++;


            let inValue =
                parseFloat(
                    $(this).find('.report-cashin').data('amount')
                ) || 0;


            let outValue =
                parseFloat(
                    $(this).find('.report-cashout').data('amount')
                ) || 0;


            cashin += inValue;
            cashout += outValue;

        });


        let balance = cashin - cashout;


        updateSummaryCards(
            cashin,
            cashout,
            balance,
            transactions
        );

    }


    /* =========================================================
       UPDATE SUMMARY
    ========================================================== */

    function updateSummaryCards(
        cashin,
        cashout,
        balance,
        transactions
    )
    {

        $('#summary-cashin').text(
            formatMoney(cashin)
        );


        $('#summary-cashout').text(
            formatMoney(cashout)
        );


        $('#summary-balance').text(
            formatMoney(balance)
        );


        $('#summary-transactions').text(
            transactions
        );


        $('#report-total-cashin').text(
            formatMoney(cashin)
        );


        $('#report-total-cashout').text(
            formatMoney(cashout)
        );


        $('#report-total-balance').text(
            formatMoney(balance)
        );


        $('#report-count').text(
            transactions + ' Transactions'
        );

    }


    /* =========================================================
       JSON RESPONSE SUPPORT
    ========================================================== */

    function updateReportSummary(data)
    {

        let cashin =
            parseFloat(data.cashin || 0);

        let cashout =
            parseFloat(data.cashout || 0);

        let balance =
            parseFloat(
                data.balance !== undefined
                    ? data.balance
                    : cashin - cashout
            );

        let transactions =
            parseInt(data.transactions || 0);


        updateSummaryCards(
            cashin,
            cashout,
            balance,
            transactions
        );


        if (data.description) {

            $('#report-description')
                .html(data.description);

        }


        if (data.filter_status) {

            $('#filter-status')
                .html(data.filter_status);

        }

    }


    /* =========================================================
       MONEY FORMAT
    ========================================================== */

    function formatMoney(value)
    {

        return new Intl.NumberFormat(
            'en-UG',
            {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }
        ).format(value || 0);

    }


    /* =========================================================
       EMPTY REPORT
    ========================================================== */

    function emptyReport()
    {

        return `

            <tr>

                <td
                    colspan="9"
                    class="text-center text-muted p-5">

                    <i class="fa fa-info-circle fa-2x"></i>

                    <div class="mt-2">
                        No transactions found.
                    </div>

                </td>

            </tr>

        `;

    }


    /* =========================================================
       EXPORT
    ========================================================== */

    $(document).on('click', '.report-export', function () {

    const button = $(this);

    const format = button.data('format');

    const params = new URLSearchParams();

    params.append(
        'action',
        'exportTransactionReport'
    );

    params.append(
        'book_id',
        BOOK_ID
    );

    params.append(
        'format',
        format
    );


    /*
    |--------------------------------------------------------------------------
    | CURRENT FILTERS
    |--------------------------------------------------------------------------
    */

    $('.filter-item').each(function () {

        const key =$(this).data('type');
        const value =$(this).val();
        if (value !== '') {

            params.append(
                key,
                value
            );

        }

    });


    /*
    |--------------------------------------------------------------------------
    | MONTH YEAR
    |--------------------------------------------------------------------------
    */

    const monthSelect = $('select[data-type="month"]');
    const monthOption = monthSelect.find('option:selected');

    if (
        monthOption.val() !== '' &&
        monthOption.data('year')
    ) {

        params.set(
            'year',
            monthOption.data('year')
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VISUAL FEEDBACK
    |--------------------------------------------------------------------------
    */

    button .prop('disabled', true).html(
            '<i class="fa fa-spinner fa-spin"></i> Exporting...'
        );


    /*
    |--------------------------------------------------------------------------
    | OPEN DOWNLOAD
    |--------------------------------------------------------------------------
    */

    window.open('../books/save/index.php?' + params.toString(),'_blank');


    /*
    |--------------------------------------------------------------------------
    | RESTORE BUTTON
    |--------------------------------------------------------------------------
    */

    setTimeout(function () {

        if (format === 'excel') {

            button.html(
                '<i class="fa fa-file-excel-o"></i> Excel'
            );

        } else {

            button.html(
                '<i class="fa fa-file-pdf-o"></i> PDF'
            );

        }

        button.prop(
            'disabled',
            false
        );

    }, 1500);

});


    /* =========================================================
       EXISTING MODAL FUNCTIONALITY
    ========================================================== */

    $(document).on(
        'click',
        '.btn-click',
        function () {

            var title =
                $(this).data('title');

            title =
                title.toUpperCase();


            $('.side-modal-tall').show();

            $('.side-modal-title')
                .html(title);


            var category =
                $(this).data('section');

            var id =
                $(this).data('id');


            fetchData(
                category,
                id
            );

        }
    );


    function fetchData(sect, id)
    {

        if (sect != '') {

            $.ajax({

                url: '../books/save/index.php',

                data: {

                    section: sect,

                    book_id: BOOK_ID,

                    action: 'fetchForm',

                    route_id: id

                },

                beforeSend: function () {

                    $('.side-modal-content').html(`

                        <h3 class="text-center p-4">

                            <i class="fa fa-spinner fa-spin"></i>

                            Loading...

                        </h3>

                    `);

                },

                success: function (res) {

                    $('.side-modal-content')
                        .html(res);

                },

                error: function () {

                    $('.side-modal-content').html(`

                        <h3 class="text-center text-danger p-4">

                            Error Loading data!!

                        </h3>

                    `);

                }

            });

        }

    }


    /* =========================================================
       EXISTING FORM SUBMISSION
    ========================================================== */

    $(document).on('click','.saveRoute',
        function () {
            submitSingleForm("newRouteForm","../books/save/index.php");
        }
    );


    function submitSingleForm(
        formId,
        backendUrl
    )
    {

        const form =document.getElementById(formId);


        if (!form) {

            console.error(
                "Form not found:",
                formId
            );

            return;

        }


        form.addEventListener(
            "submit",
            function (e) {

                e.preventDefault();


                const formData =
                    new FormData(form);


                xdialog.startSpin();


                fetch(
                    backendUrl,
                    {
                        method: "POST",
                        body: formData
                    }
                )

                .then(
                    res => res.text()
                )

                .then(
                    response => {

                        xdialog.stopSpin();

                        window.location.reload();

                    }
                )

                .catch(
                    err => {

                        xdialog.stopSpin();

                        console.error(
                            "AJAX error:",
                            err
                        );

                    }
                );

            }
        );

    }

</script>


<?php

} else {

    redirect('../');

}

?>