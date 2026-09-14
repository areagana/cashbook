<?php
    require_once(__dir__.'/../assets/functions.php');

    if(isVerified())
    {
        pageHeader('Categories');

        $bsid = request('bkid');
        $book = bookFind(encryptor('decrypt',$bsid));

        /*
        |--------------------------------------------------------------------------
        | CATEGORY SUMMARY DATA
        |--------------------------------------------------------------------------
        | We keep the existing category CRUD functionality, but add financial
        | summaries based on cashbook_transactions.
        |
        | Convention used here:
        |   credit_amount = CASH IN
        |   debit_amount  = CASH OUT
        |--------------------------------------------------------------------------
        */

        $categorySql = "
            SELECT
                c.id,
                c.name,
                c.details,
                COALESCE(SUM(t.credit_amount), 0) AS cashin,
                COALESCE(SUM(t.debit_amount), 0) AS cashout,
                COALESCE(SUM(t.credit_amount), 0) -
                COALESCE(SUM(t.debit_amount), 0) AS net
            FROM cashbook_categories c
            LEFT JOIN cashbook_transactions t
                ON t.category_id = c.id
                AND t.book_id = c.book_id
            WHERE c.book_id = ?
            GROUP BY c.id, c.name, c.details
            ORDER BY c.name ASC
        ";

        $categoryRes = prepared_statements($categorySql, 'i', [$book->id]);

        $categories = [];

        $totalCashIn = 0;
        $totalCashOut = 0;

        while($row = $categoryRes->fetch_assoc())
        {
            $row['cashin'] = (float)$row['cashin'];
            $row['cashout'] = (float)$row['cashout'];
            $row['net'] = (float)$row['net'];

            $categories[] = $row;

            $totalCashIn += $row['cashin'];
            $totalCashOut += $row['cashout'];
        }

        $netCash = $totalCashIn - $totalCashOut;

        $categoryCount = count($categories);

        /*
        |--------------------------------------------------------------------------
        | TOP CATEGORY
        |--------------------------------------------------------------------------
        */
        $topCashInCategory = null;
        $topCashOutCategory = null;

        foreach($categories as $cat)
        {
            if($topCashInCategory === null || $cat['cashin'] > $topCashInCategory['cashin'])
            {
                $topCashInCategory = $cat;
            }

            if($topCashOutCategory === null || $cat['cashout'] > $topCashOutCategory['cashout'])
            {
                $topCashOutCategory = $cat;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | HELPER
        |--------------------------------------------------------------------------
        */
        function categoryMoney($amount)
        {
            return number_format((float)$amount, 2);
        }
?>
<style>
    .category-dashboard-card {
        border-radius: 8px;
        min-height: 120px;
    }

    .category-dashboard-card .icon {
        font-size: 34px;
        opacity: .18;
    }

    .category-chart-card {
        border: 0;
        border-radius: 8px;
    }

    .category-chart-container {
        position: relative;
        height: 330px;
    }

    .category-summary-table td,
    .category-summary-table th {
        vertical-align: middle;
    }

    .category-total-row {
        font-weight: 700;
        background: #f5f5f5;
    }

    .category-positive {
        font-weight: 600;
    }

    .category-negative {
        font-weight: 600;
    }

    .category-progress {
        height: 6px;
        border-radius: 10px;
    }

    .category-filter {
        max-width: 300px;
    }

    @media(max-width: 767px)
    {
        .category-chart-container {
            height: 280px;
        }

        .category-dashboard-card {
            margin-bottom: 10px;
        }
    }
</style>

<div class="container-fluid">

    <!-- BREADCRUMB -->
    <div class="row mx-1">
        <div class="col p-2 inline-block">
            <a href="../books/?bkid=<?=$bsid;?>" class="nav-link d-inline-block">
                Books
            </a>
            <i class="fa fa-angle-right"></i>
            <a class="nav-link d-inline-block">
                Categories
            </a>
        </div>
    </div>

    <hr>

    <!-- PAGE HEADER -->
    <div class="row mx-1 align-items-center">
        <div class="col-md-8 p-2">
            <h3 class="mb-1">BOOK CATEGORY</h3>
            <small class="text-muted">
                <?=htmlspecialchars($book->name ?? 'Book');?> —
                Category financial overview
            </small>
        </div>

        <?php if(hasRole(['owner','partner'])):?>
            <div class="col-md-4 p-2 text-md-right">
                <button
                    class="btn btn-sm btn-flat btn-outline-success btn-click"
                    data-title="add category"
                    data-section="category">
                    <i class="fa fa-plus-circle"></i>
                    Category
                </button>
            </div>
        <?php endif;?>
    </div>

    <hr>

    <!-- SUMMARY CARDS -->
    <div class="row mx-1">

        <div class="col-lg-3 col-md-6 p-2">
            <div class="card category-dashboard-card shadow-sm border-left border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase">
                                Total Cash In
                            </small>
                            <h4 class="mt-2 mb-0 text-success">
                                <?=categoryMoney($totalCashIn);?>
                            </h4>
                        </div>
                        <div class="icon text-success">
                            <i class="fa fa-arrow-circle-down"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        All category cash-ins
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 p-2">
            <div class="card category-dashboard-card shadow-sm border-left border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase">
                                Total Cash Out
                            </small>
                            <h4 class="mt-2 mb-0 text-danger">
                                <?=categoryMoney($totalCashOut);?>
                            </h4>
                        </div>
                        <div class="icon text-danger">
                            <i class="fa fa-arrow-circle-up"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        All category cash-outs
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 p-2">
            <div class="card category-dashboard-card shadow-sm border-left <?=($netCash >= 0 ? 'border-primary' : 'border-warning');?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase">
                                Net Cash Flow
                            </small>
                            <h4 class="mt-2 mb-0 <?=($netCash >= 0 ? 'text-primary' : 'text-warning');?>">
                                <?=categoryMoney($netCash);?>
                            </h4>
                        </div>
                        <div class="icon">
                            <i class="fa fa-balance-scale"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        Cash In − Cash Out
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 p-2">
            <div class="card category-dashboard-card shadow-sm border-left border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase">
                                Categories
                            </small>
                            <h4 class="mt-2 mb-0 text-info">
                                <?=$categoryCount;?>
                            </h4>
                        </div>
                        <div class="icon text-info">
                            <i class="fa fa-tags"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        Categories in this book
                    </small>
                </div>
            </div>
        </div>

    </div>

    <!-- TOP CATEGORY INSIGHTS -->
    <div class="row mx-1">

        <div class="col-md-6 p-2">
            <div class="alert alert-light border shadow-sm mb-2">
                <i class="fa fa-arrow-down text-success mr-2"></i>
                <strong>Highest Cash-In:</strong>
                <?php if($topCashInCategory):?>
                    <?=htmlspecialchars($topCashInCategory['name']);?>
                    <span class="float-right text-success">
                        <?=categoryMoney($topCashInCategory['cashin']);?>
                    </span>
                <?php else:?>
                    No category transactions yet.
                <?php endif;?>
            </div>
        </div>

        <div class="col-md-6 p-2">
            <div class="alert alert-light border shadow-sm mb-2">
                <i class="fa fa-arrow-up text-danger mr-2"></i>
                <strong>Highest Cash-Out:</strong>
                <?php if($topCashOutCategory):?>
                    <?=htmlspecialchars($topCashOutCategory['name']);?>
                    <span class="float-right text-danger">
                        <?=categoryMoney($topCashOutCategory['cashout']);?>
                    </span>
                <?php else:?>
                    No category transactions yet.
                <?php endif;?>
            </div>
        </div>

    </div>

    <!-- CHARTS -->
    <div class="row mx-1">

        <div class="col-lg-8 p-2">
            <div class="card category-chart-card shadow-sm">
                <div class="card-header bg-white">
                    <strong>
                        <i class="fa fa-bar-chart mr-2"></i>
                        Cash In vs Cash Out by Category
                    </strong>
                </div>

                <div class="card-body">
                    <div class="category-chart-container">
                        <canvas id="categoryCashFlowChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 p-2">
            <div class="card category-chart-card shadow-sm">
                <div class="card-header bg-white">
                    <strong>
                        <i class="fa fa-pie-chart mr-2"></i>
                        Cash Flow Distribution
                    </strong>
                </div>

                <div class="card-body">
                    <div class="category-chart-container">
                        <canvas id="categoryDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- CATEGORY SUMMARY TABLE -->
    <div class="row mx-1">
        <div class="col p-2">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <div class="row align-items-center">

                        <div class="col-md-7">
                            <strong>
                                <i class="fa fa-table mr-2"></i>
                                Category Financial Summary
                            </strong>
                        </div>

                        <div class="col-md-5 mt-2 mt-md-0">
                            <input
                                type="text"
                                id="categorySummarySearch"
                                class="form-control form-control-sm category-filter float-md-right"
                                placeholder="Search category...">
                        </div>

                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table table-sm table-striped table-hover category-summary-table mb-0">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Details</th>
                                <th class="text-right">Cash In</th>
                                <th class="text-right">Cash Out</th>
                                <th class="text-right">Net</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody id="categorySummaryBody">

                        <?php if(empty($categories)):?>

                            <tr>
                                <td colspan="7" class="text-center text-muted p-4">
                                    No categories found.
                                </td>
                            </tr>

                        <?php else:?>

                            <?php $s = 0; ?>

                            <?php foreach($categories as $r):?>

                                <tr class="hover hover-hide-content">

                                    <td><?=++$s;?></td>

                                    <td>
                                        <strong>
                                            <?=htmlspecialchars($r['name']);?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?=htmlspecialchars($r['details'] ?? '');?>
                                    </td>

                                    <td class="text-right text-success">
                                        <?=categoryMoney($r['cashin']);?>
                                    </td>

                                    <td class="text-right text-danger">
                                        <?=categoryMoney($r['cashout']);?>
                                    </td>

                                    <td class="text-right <?=($r['net'] >= 0 ? 'text-success' : 'text-danger');?>">
                                        <strong>
                                            <?=categoryMoney($r['net']);?>
                                        </strong>
                                    </td>

                                    <td class="text-right">

                                        <?php if(hasRole(['owner','partner'])):?>

                                            <span class="hover-display text-sms">

                                                <button
                                                    class="btn btn-sm btn-outline-info edit-category text-muted btn-click"
                                                    data-title="Edit category"
                                                    data-section="edit-category"
                                                    data-id="<?=$r['id'];?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>

                                                <button
                                                    class="btn btn-sm btn-outline-danger delete-category"
                                                    data-id="<?=$r['id'];?>">
                                                    <i class="fa fa-trash"></i>
                                                </button>

                                            </span>

                                        <?php endif;?>

                                    </td>

                                </tr>

                            <?php endforeach;?>

                        <?php endif;?>

                        </tbody>

                        <?php if(!empty($categories)):?>

                            <tfoot>

                                <tr class="category-total-row">

                                    <td colspan="3" class="text-right">
                                        TOTAL
                                    </td>

                                    <td class="text-right text-success">
                                        <?=categoryMoney($totalCashIn);?>
                                    </td>

                                    <td class="text-right text-danger">
                                        <?=categoryMoney($totalCashOut);?>
                                    </td>

                                    <td class="text-right <?=($netCash >= 0 ? 'text-success' : 'text-danger');?>">
                                        <?=categoryMoney($netCash);?>
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

    <!-- EXISTING CATEGORY MANAGEMENT -->
    <div class="row mx-1 mt-3">

        <div class="col p-2">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">
                    <strong>
                        <i class="fa fa-tags mr-2"></i>
                        Category Management
                    </strong>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-sm table-striped dataTable mb-0">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Details</th>
                                    <th>Cash In</th>
                                    <th>Cash Out</th>
                                    <th>Net</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php $s = 0; ?>

                            <?php foreach($categories as $r):?>

                                <tr class="hover hover-hide-content">

                                    <td><?=++$s;?></td>

                                    <td><?=htmlspecialchars($r['name']);?></td>

                                    <td><?=htmlspecialchars($r['details'] ?? '');?></td>

                                    <td class="text-success">
                                        <?=categoryMoney($r['cashin']);?>
                                    </td>

                                    <td class="text-danger">
                                        <?=categoryMoney($r['cashout']);?>
                                    </td>

                                    <td class="<?=($r['net'] >= 0 ? 'text-success' : 'text-danger');?>">
                                        <strong>
                                            <?=categoryMoney($r['net']);?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php if(hasRole(['owner','partner'])):?>

                                            <span class="hover-display text-sms">

                                                <button
                                                    class="btn btn-sm btn-outline-info edit-category text-muted btn-click"
                                                    data-title="Edit category"
                                                    data-section="edit-category"
                                                    data-id="<?=$r['id'];?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>

                                                <button
                                                    class="btn btn-sm btn-outline-danger delete-category"
                                                    data-id="<?=$r['id'];?>">
                                                    <i class="fa fa-trash"></i>
                                                </button>

                                            </span>

                                        <?php endif;?>

                                    </td>

                                </tr>

                            <?php endforeach;?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- EXISTING SIDE MODAL -->
<div class="p-0 bg-white side-modal-tall absolute border shadow" id="side-modal-cashin">

    <div class="side-modal-header bg-success">
        <h3 class="side-modal-title text-white"></h3>
        <button type="button" class="side-modal-close">&times;</button>
    </div>

    <div class="side-modal-content"></div>

</div>

<?php
        pageFooter();
?>

<script>
    /*
    |--------------------------------------------------------------------------
    | CATEGORY DATA FOR CHARTS
    |--------------------------------------------------------------------------
    */

    const categoryLabels = <?=json_encode(array_column($categories, 'name'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);?>;

    const categoryCashIn = <?=json_encode(array_map(function($row){
        return (float)$row['cashin'];
    }, $categories));?>;

    const categoryCashOut = <?=json_encode(array_map(function($row){
        return (float)$row['cashout'];
    }, $categories));?>;


    /*
    |--------------------------------------------------------------------------
    | BAR CHART
    |--------------------------------------------------------------------------
    */

    const categoryChartElement = document.getElementById('categoryCashFlowChart');

    if(categoryChartElement)
    {
        new Chart(categoryChartElement, {
            type: 'bar',

            data: {
                labels: categoryLabels,

                datasets: [
                    {
                        label: 'Cash In',
                        data: categoryCashIn,
                        borderWidth: 1
                    },
                    {
                        label: 'Cash Out',
                        data: categoryCashOut,
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
                            callback: function(value) {
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
                            label: function(context) {

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
    | DISTRIBUTION CHART
    |--------------------------------------------------------------------------
    |
    | This shows the relative total activity per category:
    | Cash In + Cash Out.
    |--------------------------------------------------------------------------
    */

    const distributionElement = document.getElementById('categoryDistributionChart');

    if(distributionElement)
    {
        const categoryActivity = categoryLabels.map(function(label, index) {
            return (categoryCashIn[index] || 0) + (categoryCashOut[index] || 0);
        });

        new Chart(distributionElement, {
            type: 'doughnut',

            data: {
                labels: categoryLabels,

                datasets: [
                    {
                        data: categoryActivity,
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
                            label: function(context) {

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
    | CATEGORY SEARCH
    |--------------------------------------------------------------------------
    */

    $(document).on('keyup', '#categorySummarySearch', function(){

        const value = $(this).val().toLowerCase();

        $('#categorySummaryBody tr').each(function(){

            const rowText = $(this).text().toLowerCase();

            $(this).toggle(rowText.indexOf(value) !== -1);

        });

    });


    /*
    |--------------------------------------------------------------------------
    | EXISTING MODAL FUNCTIONALITY
    |--------------------------------------------------------------------------
    */

    $(document).on('click','.btn-click',function(){

        var title = $(this).data('title');

        title = title.toUpperCase();

        $('.side-modal-tall').show();

        $('.side-modal-title').html(title);

        var category = $(this).data('section');

        var id = $(this).data('id');

        fetchData(category,id);

    });


    function fetchData(sect,id)
    {
        var book_id = "<?=$book->id;?>";

        if(sect != '')
        {
            $.ajax({

                url:'../books/save/index.php',

                data:{
                    section:sect,
                    book_id:book_id,
                    action:'fetchForm',
                    category_id:id
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
    | EXISTING CATEGORY SAVE FUNCTIONALITY
    |--------------------------------------------------------------------------
    */

    $(document).on('click','.saveCategory',function(){

        submitSingleForm(
            "newCategoryForm",
            "../books/save/index.php"
        );

    });


    function submitSingleForm(formId, backendUrl)
    {
        const form = document.getElementById(formId);

        if(!form)
        {
            console.error("Form not found:", formId);
            return;
        }

        xdialog.startSpin();

        /*
        | Prevent duplicate submit listeners.
        */
        if(form.dataset.submitBound === '1')
        {
            return;
        }

        form.dataset.submitBound = '1';

        form.addEventListener("submit", function(e){

            e.preventDefault();

            const formData = new FormData(form);

            fetch(backendUrl, {
                method: "POST",
                body: formData
            })

            .then(res => res.text())

            .then(response => {

                let responseDiv =
                    document.getElementById("response_" + formId);

                if(!responseDiv)
                {
                    responseDiv = document.createElement("div");

                    responseDiv.id = "response_" + formId;

                    form.appendChild(responseDiv);
                }

                /*
                | Preserve existing behaviour: refresh after save.
                */
                window.location.reload();

            })

            .catch(err => {

                console.error("AJAX error:", err);

                if(typeof xdialog !== 'undefined' && xdialog.stopSpin)
                {
                    xdialog.stopSpin();
                }

            });

        });
    }
</script>

<?php
    }
    else
    {
        redirect('../');
    }
?>
