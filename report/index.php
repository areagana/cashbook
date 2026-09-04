<?php
require_once(__DIR__ . '/../assets/functions.php');

if (isVerified()) {

    pageHeader('Financial Reports');

    $bsid = request('bkid');
    $book = bookFind(encryptor('decrypt', $bsid));

    if (!$book) {
        redirect('../');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Report filter data
    |--------------------------------------------------------------------------
    */

    // Categories
    $sql = "SELECT id, name
        FROM cashbook_categories
        WHERE book_id = ?
        ORDER BY name ASC
    ";
    $cats = prepared_statements($sql, 'i', [$book->id]);

    // Customers
    $sql = "
        SELECT id, name
        FROM cashbook_customers
        WHERE book_id = ?
        ORDER BY name ASC
    ";
    $customers = prepared_statements($sql, 'i', [$book->id]);

    // Available months
    $sql = "
        SELECT DISTINCT
            YEAR(created_at) AS year,
            MONTH(created_at) AS month
        FROM cashbook_transactions
        WHERE book_id = ?
        ORDER BY year DESC, month DESC
    ";
    $months = prepared_statements($sql, 'i', [$book->id]);

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

    /*
    |--------------------------------------------------------------------------
    | Initial summary
    |--------------------------------------------------------------------------
    |
    | This assumes:
    | credit_amount = money coming in
    | debit_amount  = money going out
    |
    */

    $summary_sql = "SELECT COALESCE(SUM(credit_amount), 0) AS credit, COALESCE(SUM(debit_amount), 0) AS debit,
            COUNT(*) AS transactions FROM cashbook_transactions WHERE book_id = ?";

    $summary_res = prepared_statements($summary_sql, 'i',[$book->id]);
    $summary = $summary_res->fetch_assoc();

    $total_credit = (float)($summary['credit'] ?? 0);
    $total_debit = (float)($summary['debit'] ?? 0);
    $net = $total_credit - $total_debit;
    $transaction_count = (int)($summary['transactions'] ?? 0);
?>

<div class="container-fluid py-3">

    <!-- ==========================================================
         HEADER
    =========================================================== -->

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="../books/?bkid=<?= $bsid; ?>"
                   class="text-decoration-none">
                    <i class="fa fa-book"></i> Books
                </a>

                <i class="fa fa-angle-right text-muted"></i>

                <span class="text-muted">
                    Reports
                </span>
            </div>

            <h3 class="mt-2 mb-0">
                <i class="fa fa-chart-line me-2"></i>
                Financial Report
            </h3>

            <small class="text-muted">
                <?= htmlspecialchars($book->name ?? 'Cashbook'); ?>
            </small>
        </div>

        <div class="d-flex gap-2 mt-2 mt-md-0">

            <button type="button" class="btn btn-outline-secondary" id="btn-reset-filters">
                <i class="fa fa-refresh"></i>
                Reset
            </button>

            <button type="button"
                    class="btn btn-outline-primary"
                    id="btn-print-report">
                <i class="fa fa-print"></i>
                Print
            </button>

        </div>

    </div>


    <!-- ==========================================================
         FINANCIAL SUMMARY
    =========================================================== -->

    <div class="row g-3 mb-4">

        <!-- CASH IN -->
        <div class="col-12 col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <small class="text-muted">
                                TOTAL CASH IN
                            </small>

                            <h4 class="mt-2 mb-0"
                                id="summary-credit">
                                <?= number_format($total_credit, 2); ?>
                            </h4>
                        </div>

                        <div class="text-success fs-3">
                            <i class="fa fa-arrow-down"></i>
                        </div>

                    </div>

                    <small class="text-muted">
                        Money received
                    </small>

                </div>

            </div>

        </div>


        <!-- CASH OUT -->
        <div class="col-12 col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <small class="text-muted">
                                TOTAL CASH OUT
                            </small>

                            <h4 class="mt-2 mb-0"
                                id="summary-debit">
                                <?= number_format($total_debit, 2); ?>
                            </h4>
                        </div>

                        <div class="text-danger fs-3">
                            <i class="fa fa-arrow-up"></i>
                        </div>

                    </div>

                    <small class="text-muted">
                        Money spent
                    </small>

                </div>

            </div>

        </div>


        <!-- NET -->
        <div class="col-12 col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <small class="text-muted">
                                NET MOVEMENT
                            </small>

                            <h4 class="mt-2 mb-0"
                                id="summary-net">
                                <?= number_format($net, 2); ?>
                            </h4>
                        </div>

                        <div class="text-primary fs-3">
                            <i class="fa fa-balance-scale"></i>
                        </div>

                    </div>

                    <small class="text-muted">
                        Cash in minus cash out
                    </small>

                </div>

            </div>

        </div>


        <!-- TRANSACTIONS -->
        <div class="col-12 col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <small class="text-muted">
                                TRANSACTIONS
                            </small>

                            <h4 class="mt-2 mb-0"
                                id="summary-count">
                                <?= number_format($transaction_count); ?>
                            </h4>
                        </div>

                        <div class="text-info fs-3">
                            <i class="fa fa-list"></i>
                        </div>

                    </div>

                    <small class="text-muted">
                        Recorded transactions
                    </small>

                </div>

            </div>

        </div>

    </div>


    <!-- ==========================================================
         FILTER PANEL
    =========================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <strong>
                        <i class="fa fa-filter me-1"></i>
                        Report Filters
                    </strong>

                    <div class="small text-muted">
                        Narrow the report to exactly what you want to analyse.
                    </div>
                </div>

                <span class="badge bg-light text-dark"
                      id="filter-status">
                    All transactions
                </span>

            </div>

        </div>


        <div class="card-body">

            <div class="row g-2">

                <!-- FROM -->
                <div class="col-12 col-md-6 col-lg-2">

                    <label class="form-label small fw-bold">
                        From
                    </label>

                    <input type="date"
                           id="filter-min-date"
                           data-type="min_date"
                           class="form-control filter-item">

                </div>


                <!-- TO -->
                <div class="col-12 col-md-6 col-lg-2">

                    <label class="form-label small fw-bold">
                        To
                    </label>

                    <input type="date"
                           id="filter-max-date"
                           data-type="max_date"
                           class="form-control filter-item"
                           max="<?= date('Y-m-d'); ?>">

                </div>


                <!-- MONTH -->
                <div class="col-12 col-md-6 col-lg-2">

                    <label class="form-label small fw-bold">
                        Month
                    </label>

                    <select id="filter-month"
                            data-type="month"
                            class="form-control filter-item">

                        <option value="">
                            All months
                        </option>

                        <?php while ($m = $months->fetch_assoc()): ?>

                            <option value="<?= $m['year'] . '-' . str_pad($m['month'], 2, '0', STR_PAD_LEFT); ?>">
                                <?= $month_names[(int)$m['month']] . ' ' . $m['year']; ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- TYPE -->
                <div class="col-12 col-md-6 col-lg-2">

                    <label class="form-label small fw-bold">
                        Transaction
                    </label>

                    <select id="filter-type"
                            data-type="type"
                            class="form-control filter-item">

                        <option value="">
                            All types
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
                <div class="col-12 col-md-6 col-lg-2">

                    <label class="form-label small fw-bold">
                        Category
                    </label>

                    <select id="filter-category"
                            data-type="category"
                            class="form-control filter-item">

                        <option value="">
                            All categories
                        </option>

                        <?php while ($cat = $cats->fetch_assoc()): ?>

                            <option value="<?= $cat['id']; ?>">
                                <?= htmlspecialchars($cat['name']); ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- CUSTOMER -->
                <div class="col-12 col-md-6 col-lg-2">

                    <label class="form-label small fw-bold">
                        Customer
                    </label>

                    <select id="filter-customer"
                            data-type="customer"
                            class="form-control filter-item">

                        <option value="">
                            All customers
                        </option>

                        <?php while ($customer = $customers->fetch_assoc()): ?>

                            <option value="<?= $customer['id']; ?>">
                                <?= htmlspecialchars($customer['name']); ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

            </div>

        </div>

    </div>


    <!-- ==========================================================
         REPORT BODY
    =========================================================== -->

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col">
                    <strong>
                        Transaction Statement
                    </strong>
                    <div class="small text-muted">
                        Detailed financial activity
                    </div>
                </div>
                <div class="col-auto">
                    <span class="badge bg-success-subtle text-success"
                          id="credit-label">
                        Credit: 0.00
                    </span>
                    <span class="badge bg-danger-subtle text-danger"
                          id="debit-label">
                        Debit: 0.00
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="transactions-table">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Customer</th>
                            <th>Details</th>
                            <th class="text-end"> Credit</th>
                            <th class="text-end"> Debit</th>
                            <th width="80"> Action </th>
                        </tr>
                    </thead>
                    <tbody class="transactions-tbody">
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="spinner-border spinner-border-sm"></div>
                                <div class="mt-2 text-muted">
                                    Loading transactions...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                         <tr>
                            <th colspan="5"class="text-end"> TOTAL</th>
                            <th class="text-end" id="table-credit"> 0.00</th>
                            <th class="text-end"id="table-debit">0.00 </th>
                            <th class="text-end" id="table-net"> 0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

</div>


<!-- ==========================================================
     SIDE MODAL
=========================================================== -->

<div class="p-0 bg-white side-modal-tall absolute border shadow"id="side-modal-cashin">
    <div class="side-modal-header bg-success">
        <h3 class="side-modal-title text-white"></h3>
        <button type="button"class="side-modal-close">&times;</button>
    </div>
    <div class="side-modal-content"></div>
</div>


<!-- ==========================================================
     CENTRAL MODAL
=========================================================== -->

<div class="p-0 bg-white central-modal absolute border shadow"id="central-modal">
    <div class="central-modal-header bg-success">
        <h3 class="central-modal-title"></h3>
        <button type="button"class="central-modal-close">&times;</button>
    </div>
    <div class="central-modal-content"></div>
</div>


<?php pageFooter(); ?>


<script>

$(function () {

    const bookId = "<?= $book->id; ?>";


    /*
    |--------------------------------------------------------------------------
    | Format money
    |--------------------------------------------------------------------------
    */

    function money(value) {

        value = parseFloat(value || 0);

        return value.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Collect filters
    |--------------------------------------------------------------------------
    */

    function getFilters() {

        let filters = {
            action: 'transactionFilter',
            book_id: bookId
        };

        $('.filter-item').each(function () {
            const key = $(this).data('type');
            const value = $(this).val();

            if (value !== '') {
                filters[key] = value;
            }

        });

        return filters;
    }


    /*
    |--------------------------------------------------------------------------
    | Filter description
    |--------------------------------------------------------------------------
    */

    function updateFilterStatus() {

        let active = [];

        $('.filter-item').each(function () {

            const value = $(this).val();

            if (value !== '') {
                active.push(value);
            }

        });

        $('#filter-status').text(
            active.length
                ? active.length + ' filter' + (active.length > 1 ? 's' : '') + ' active'
                : 'All transactions'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Load report
    |--------------------------------------------------------------------------
    */

    function loadReport() {

        const filters = getFilters();

        updateFilterStatus();

        $('.transactions-tbody').html(`
            <tr>
                <td colspan="9" class="text-center py-5">
                    <div class="spinner-border"></div>
                    <div class="mt-2 text-muted">
                        Preparing report...
                    </div>
                </td>
            </tr>
        `);


        $.ajax({

            url: '../books/save/index.php',

            type: 'POST',

            data: filters,

            success: function (res) {

                $('.transactions-tbody').html(res);

                calculateTableTotals();

            },

            error: function (xhr) {

                console.error(xhr.responseText);

                $('.transactions-tbody').html(`
                    <tr>
                        <td colspan="9"
                            class="text-center text-danger py-5">

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


    /*
    |--------------------------------------------------------------------------
    | Calculate visible table totals
    |--------------------------------------------------------------------------
    |
    | Assumes:
    | column 6 = credit
    | column 7 = debit
    |
    */

    function calculateTableTotals() 
    {

        let credit = 0;
        let debit = 0;
        let count = 0;

        $('.transactions-tbody tr').each(function () {

            const cells = $(this).find('td');

            if (cells.length < 7) {
                return;
            }

            const c = parseFloat(
                $(cells[5]).text().replace(/,/g, '')
            ) || 0;

            const d = parseFloat(
                $(cells[6]).text().replace(/,/g, '')
            ) || 0;

            credit += c;
            debit += d;
            count++;

        });


        const net = credit - debit;

        $('#table-credit').text(money(credit));
        $('#table-debit').text(money(debit));
        $('#table-net').text(money(net));

        $('#credit-label').text(
            'Credit: ' + money(credit)
        );

        $('#debit-label').text(
            'Debit: ' + money(debit)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'change',
        '.filter-item',
        function () {

            loadReport();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Reset
    |--------------------------------------------------------------------------
    */

    $('#btn-reset-filters').on('click', function () {

        $('.filter-item').val('');

        loadReport();

    });


    /*
    |--------------------------------------------------------------------------
    | Print
    |--------------------------------------------------------------------------
    */

    $('#btn-print-report').on('click', function () {

        window.print();

    });


    /*
    |--------------------------------------------------------------------------
    | Transaction modal
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-click',
        function () {

            const title =
                String($(this).data('title') || 'Details')
                    .toUpperCase();

            const section =
                $(this).data('section');

            const id =
                $(this).data('id');


            $('.side-modal-tall').show();

            $('.side-modal-title').text(title);

            $('.side-modal-content').html(`
                <div class="text-center p-5">
                    <div class="spinner-border"></div>
                    <div class="mt-2">
                        Loading...
                    </div>
                </div>
            `);


            $.ajax({

                url: '../books/save/index.php',

                type: 'POST',

                data: {

                    section: section,

                    book_id: bookId,

                    action: 'fetchForm',

                    route_id: id

                },

                success: function (res) {

                    $('.side-modal-content').html(res);

                },

                error: function () {

                    $('.side-modal-content').html(`
                        <div class="text-center text-danger p-5">
                            Error loading information.
                        </div>
                    `);

                }

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Initial load
    |--------------------------------------------------------------------------
    */

    loadReport();

});

</script>


<style>

.report-summary-card {
    transition: transform .2s ease, box-shadow .2s ease;
}

.report-summary-card:hover {
    transform: translateY(-2px);
}

#transactions-table th {
    white-space: nowrap;
}

#transactions-table td {
    vertical-align: middle;
}

@media print {

    .side-modal-tall,
    .central-modal,
    button,
    .btn,
    .card-header .badge,
    .filter-item,
    #btn-reset-filters,
    #btn-print-report {
        display: none !important;
    }

    .card {
        box-shadow: none !important;
        border: 0 !important;
    }

    body {
        background: white !important;
    }

}

</style>

<?php
} else {
    redirect('../');
}
?>