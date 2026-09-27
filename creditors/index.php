<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Creditors');
        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));
    ?>
        <?php
            /*
            |--------------------------------------------------------------------------
            | CREDITORS AND ACCOUNT BALANCES
            |--------------------------------------------------------------------------
            */
            $sql = "
                SELECT
                    c.*,
                    COALESCE(cb.balance,0) AS balance
                FROM cashbook_creditors c
                LEFT JOIN cashbook_creditor_balances cb
                    ON cb.creditor_id = c.id
                WHERE c.book_id = ?
                ORDER BY cb.balance DESC, c.name ASC
            ";

            $res = prepared_statements(
                $sql,
                'i',
                [$book->id]
            );

            $creditors = [];

            if($res)
            {
                while($r = $res->fetch_assoc())
                {
                    $r['balance'] = (float)$r['balance'];
                    $creditors[] = $r;
                }
            }

            $creditorCount = count($creditors);
            $positiveCreditors = 0;
            $zeroCreditors = 0;
            $negativeCreditors = 0;
            $totalOutstanding = 0;

            foreach($creditors as $creditor)
            {
                $balance = (float)$creditor['balance'];

                if($balance > 0)
                {
                    $positiveCreditors++;
                    $totalOutstanding += $balance;
                }
                elseif($balance < 0)
                {
                    $negativeCreditors++;
                }
                else
                {
                    $zeroCreditors++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | CREDITOR LEDGER MOVEMENT
            |--------------------------------------------------------------------------
            | The ledger is the creditor-specific transaction source already used
            | by this module. The query below provides movement for dashboard
            | analysis without changing the stored creditor balance calculation.
            */
            $ledgerSql = "SELECT
                    cl.creditor_id,
                    COALESCE(SUM(cl.credit_amount),0) AS credits,
                    COALESCE(SUM(cl.debit_amount),0) AS debits
                FROM cashbook_creditor_ledger cl
                WHERE cl.book_id = ?
                GROUP BY cl.creditor_id
            ";

            $ledgerResult = prepared_statements(
                $ledgerSql,
                'i',
                [$book->id]
            );

            $ledgerMap = [];
            $totalCreditMovement = 0;
            $totalDebitMovement = 0;

            if($ledgerResult)
            {
                while($ledger = $ledgerResult->fetch_assoc())
                {
                    $creditorId = (int)$ledger['creditor_id'];

                    $ledgerMap[$creditorId] = [
                        'credits' => (float)$ledger['credits'],
                        'debits'  => (float)$ledger['debits']
                    ];

                    $totalCreditMovement += (float)$ledger['credits'];
                    $totalDebitMovement += (float)$ledger['debits'];
                }
            }

            foreach($creditors as &$creditor)
            {
                $creditorId = (int)$creditor['id'];

                $creditor['credits'] =
                    $ledgerMap[$creditorId]['credits'] ?? 0;

                $creditor['debits'] =
                    $ledgerMap[$creditorId]['debits'] ?? 0;
            }

            unset($creditor);

            /*
            |--------------------------------------------------------------------------
            | MONTHLY CREDITOR LEDGER TREND - CURRENT YEAR
            |--------------------------------------------------------------------------
            */
            $year = date('Y');

            $monthlySql = "
                SELECT
                    MONTH(cl.created_at) AS month_no,
                    COALESCE(SUM(cl.credit_amount),0) AS credits,
                    COALESCE(SUM(cl.debit_amount),0) AS debits
                FROM cashbook_creditor_ledger cl
                WHERE cl.book_id = ?
                    AND YEAR(cl.created_at) = ?
                GROUP BY MONTH(cl.created_at)
                ORDER BY MONTH(cl.created_at) ASC
            ";

            $monthlyResult = prepared_statements(
                $monthlySql,
                'ii',
                [$book->id,$year]
            );

            $monthlyMap = [];

            if($monthlyResult)
            {
                while($monthRow = $monthlyResult->fetch_assoc())
                {
                    $monthlyMap[(int)$monthRow['month_no']] = [
                        'credits' => (float)$monthRow['credits'],
                        'debits'  => (float)$monthRow['debits']
                    ];
                }
            }

            $monthlyLabels = [];
            $monthlyCredits = [];
            $monthlyDebits = [];

            for($month = 1; $month <= 12; $month++)
            {
                $monthlyLabels[] = date(
                    'M',
                    mktime(0,0,0,$month,1,$year)
                );

                $monthlyCredits[] =
                    $monthlyMap[$month]['credits'] ?? 0;

                $monthlyDebits[] =
                    $monthlyMap[$month]['debits'] ?? 0;
            }

            /*
            |--------------------------------------------------------------------------
            | CHART DATA - TOP OUTSTANDING CREDITORS
            |--------------------------------------------------------------------------
            */
            $topOutstanding = array_values(
                array_filter(
                    $creditors,
                    function($creditor)
                    {
                        return (float)$creditor['balance'] > 0;
                    }
                )
            );

            usort(
                $topOutstanding,
                function($a,$b)
                {
                    return $b['balance'] <=> $a['balance'];
                }
            );

            $topOutstanding = array_slice(
                $topOutstanding,
                0,
                10
            );
        ?>

        <style>
            .creditor-summary-card {
                border-radius: 8px;
                min-height: 125px;
            }

            .creditor-summary-card .summary-icon {
                font-size: 34px;
                opacity: .16;
            }

            .creditor-chart-card {
                border: 0;
                border-radius: 8px;
            }

            .creditor-chart-container {
                position: relative;
                height: 330px;
            }

            .creditor-table td,
            .creditor-table th {
                vertical-align: middle;
            }

            .creditor-search {
                max-width: 300px;
            }

            .creditor-mini-stat {
                border-left: 3px solid #ddd;
                padding-left: 12px;
            }

            @media(max-width:767px)
            {
                .creditor-search {
                    max-width: 100%;
                }

                .creditor-chart-container {
                    height: 290px;
                }
            }
        </style>


        <div class="container-fluid">

            <!-- BREADCRUMB -->
            <div class="row mx-1 align-items-center">

                <div class="col-md-8 p-2">

                    <a
                        href="../books/?bkid=<?=$bsid;?>"
                        class="nav-link d-inline-block">
                        Books
                    </a>

                    <i class="fa fa-angle-right"></i>

                    <a class="nav-link d-inline-block">
                        Creditors
                    </a>

                </div>

            </div>

            <hr>


            <!-- PAGE HEADER -->
            <div class="row mx-1 align-items-center">

                <div class="col-md-8 p-2">

                    <h3 class="mb-1">
                        <?=strToUpper($book->name);?> - CREDITORS
                    </h3>

                    <small class="text-muted">
                        Monitor creditor balances, account movement and transaction activity
                    </small>

                </div>


                <?php if(hasRole(['owner','partner','staff'])):?>

                    <div class="col-md-4 p-2 text-md-right">

                        <button
                            class="btn btn-sm btn-flat btn-outline-success btn-click"
                            data-title="add creditor"
                            data-section="creditor">

                            <i class="fa fa-plus-circle"></i>
                            Creditor

                        </button>

                    </div>

                <?php endif;?>

            </div>

            <hr>


            <!-- SUMMARY CARDS -->
            <div class="row mx-1">

                <div class="col-xl-3 col-md-6 p-2">

                    <div class="card creditor-summary-card shadow-sm border-left border-info">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted text-uppercase">
                                        Creditors
                                    </small>

                                    <h4 class="mt-2 mb-0 text-info">
                                        <?=number_format($creditorCount);?>
                                    </h4>

                                </div>

                                <div class="summary-icon text-info">
                                    <i class="fa fa-users"></i>
                                </div>

                            </div>

                            <small class="text-muted">
                                <?=$positiveCreditors;?> with outstanding balances
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6 p-2">

                    <div class="card creditor-summary-card shadow-sm border-left border-danger">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted text-uppercase">
                                        Outstanding Balance
                                    </small>

                                    <h4 class="mt-2 mb-0 text-danger">
                                        <?=number_format($totalOutstanding,0);?>
                                    </h4>

                                </div>

                                <div class="summary-icon text-danger">
                                    <i class="fa fa-money"></i>
                                </div>

                            </div>

                            <small class="text-muted">
                                Total positive creditor balances
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6 p-2">

                    <div class="card creditor-summary-card shadow-sm border-left border-warning">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted text-uppercase">
                                        Ledger Credits
                                    </small>

                                    <h4 class="mt-2 mb-0 text-warning">
                                        <?=number_format($totalCreditMovement,0);?>
                                    </h4>

                                </div>

                                <div class="summary-icon text-warning">
                                    <i class="fa fa-arrow-circle-up"></i>
                                </div>

                            </div>

                            <small class="text-muted">
                                Recorded creditor credit movement
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6 p-2">

                    <div class="card creditor-summary-card shadow-sm border-left border-success">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted text-uppercase">
                                        Ledger Debits
                                    </small>

                                    <h4 class="mt-2 mb-0 text-success">
                                        <?=number_format($totalDebitMovement,0);?>
                                    </h4>

                                </div>

                                <div class="summary-icon text-success">
                                    <i class="fa fa-arrow-circle-down"></i>
                                </div>

                            </div>

                            <small class="text-muted">
                                Recorded creditor debit movement
                            </small>

                        </div>

                    </div>

                </div>

            </div>


            <!-- QUICK ACCOUNT INSIGHTS -->
            <div class="row mx-1">

                <div class="col-md-4 p-2">

                    <div class="card shadow-sm border-0">

                        <div class="card-body">

                            <div class="creditor-mini-stat">

                                <small class="text-muted text-uppercase">
                                    Outstanding Accounts
                                </small>

                                <h5 class="mt-1 mb-0 text-danger">
                                    <?=number_format($positiveCreditors);?>
                                </h5>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-4 p-2">

                    <div class="card shadow-sm border-0">

                        <div class="card-body">

                            <div class="creditor-mini-stat">

                                <small class="text-muted text-uppercase">
                                    Cleared / Zero Balance
                                </small>

                                <h5 class="mt-1 mb-0 text-success">
                                    <?=number_format($zeroCreditors);?>
                                </h5>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-4 p-2">

                    <div class="card shadow-sm border-0">

                        <div class="card-body">

                            <div class="creditor-mini-stat">

                                <small class="text-muted text-uppercase">
                                    Negative Balance Accounts
                                </small>

                                <h5 class="mt-1 mb-0 text-primary">
                                    <?=number_format($negativeCreditors);?>
                                </h5>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- GRAPHS -->
            <div class="row mx-1">

                <!-- TOP OUTSTANDING CREDITORS -->
                <div class="col-lg-6 p-2">

                    <div class="card creditor-chart-card shadow-sm">

                        <div class="card-header bg-white">

                            <strong>
                                <i class="fa fa-bar-chart mr-2"></i>
                                Top Outstanding Creditors
                            </strong>

                            <br>

                            <small class="text-muted">
                                Highest positive creditor balances
                            </small>

                        </div>

                        <div class="card-body">

                            <?php if(!empty($topOutstanding)):?>

                                <div class="creditor-chart-container">
                                    <canvas id="creditorBalanceChart"></canvas>
                                </div>

                            <?php else:?>

                                <div class="text-center text-muted p-4">
                                    No outstanding creditor balances to display.
                                </div>

                            <?php endif;?>

                        </div>

                    </div>

                </div>


                <!-- MONTHLY MOVEMENT -->
                <div class="col-lg-6 p-2">

                    <div class="card creditor-chart-card shadow-sm">

                        <div class="card-header bg-white">

                            <strong>
                                <i class="fa fa-line-chart mr-2"></i>
                                Monthly Creditor Movement - <?=$year;?>
                            </strong>

                            <br>

                            <small class="text-muted">
                                Monthly credit and debit movement from the creditor ledger
                            </small>

                        </div>

                        <div class="card-body">

                            <div class="creditor-chart-container">
                                <canvas id="creditorMonthlyChart"></canvas>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- CREDITOR REGISTER -->
            <div class="row mx-1">

                <div class="col-12 p-2">

                    <div class="card shadow-sm border-0">

                        <div class="card-header bg-white">

                            <div class="row align-items-center">

                                <div class="col-md-7">

                                    <strong>
                                        <i class="fa fa-table mr-2"></i>
                                        Creditor Accounts
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        Balances and recorded ledger movement for each creditor
                                    </small>

                                </div>


                                <div class="col-md-5 mt-2 mt-md-0">

                                    <input
                                        type="text"
                                        id="creditorSearch"
                                        class="form-control form-control-sm creditor-search float-md-right"
                                        placeholder="Search creditor...">

                                </div>

                            </div>

                        </div>


                        <div class="table-responsive">

                            <table class="table table-sm table-striped table-hover creditor-table mb-0">

                                <thead>

                                    <tr>
                                        <th>#</th>
                                        <th>Creditor</th>
                                        <th class="text-right">Ledger Credit</th>
                                        <th class="text-right">Ledger Debit</th>
                                        <th class="text-right">Account Balance</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-right">Action</th>
                                    </tr>

                                </thead>


                                <tbody id="creditorTableBody">

                                <?php if(empty($creditors)):?>

                                    <tr>

                                        <td
                                            colspan="7"
                                            class="text-center text-muted p-4">
                                            No creditors found.
                                        </td>

                                    </tr>

                                <?php else:?>

                                    <?php $count = 1;?>

                                    <?php foreach($creditors as $r):?>

                                        <?php
                                            $balance = (float)$r['balance'];

                                            if($balance > 0)
                                            {
                                                $status = 'Outstanding';
                                                $statusClass = 'badge-danger';
                                            }
                                            elseif($balance < 0)
                                            {
                                                $status = 'Negative';
                                                $statusClass = 'badge-primary';
                                            }
                                            else
                                            {
                                                $status = 'Cleared';
                                                $statusClass = 'badge-success';
                                            }
                                        ?>

                                        <tr class="hover hover-hide-content">

                                            <td>
                                                <?=$count++;?>
                                            </td>


                                            <td>

                                                <strong>
                                                    <?=htmlspecialchars($r['name'] ?? '');?>
                                                </strong>

                                            </td>


                                            <td class="text-right text-warning">
                                                <?=number_format($r['credits'],0);?>
                                            </td>


                                            <td class="text-right text-success">
                                                <?=number_format($r['debits'],0);?>
                                            </td>


                                            <td class="text-right">

                                                <strong class="<?=($balance > 0 ? 'text-danger' : ($balance < 0 ? 'text-primary' : 'text-success'));?>">
                                                    <?=number_format($balance,0);?>
                                                </strong>

                                            </td>


                                            <td class="text-center">

                                                <span class="badge <?=$statusClass;?>">
                                                    <?=$status;?>
                                                </span>

                                            </td>


                                            <td class="text-right">

                                                <?php if(hasRole(['owner','partner','staff'])):?>

                                                    <span class="hover-display text-sms">

                                                        <?php if(hasRole(['owner','partner'])):?>

                                                            <button
                                                                class="btn btn-sm btn-outline-info edit-creditor text-muted"
                                                                data-id="<?=$r['id'];?>"
                                                                data-title="<?=htmlspecialchars($r['name'] ?? '',ENT_QUOTES);?>"
                                                                title="Edit Creditor">

                                                                <i class="fa fa-edit"></i>

                                                            </button>


                                                            <button
                                                                class="btn btn-sm btn-outline-danger delete-creditor"
                                                                data-id="<?=$r['id'];?>"
                                                                title="Delete Creditor">

                                                                <i class="fa fa-trash"></i>

                                                            </button>

                                                        <?php endif;?>


                                                        <button
                                                            class="btn btn-sm btn-outline-info view-creditor text-muted"
                                                            data-id="<?=$r['id'];?>"
                                                            data-title="<?=htmlspecialchars($r['name'] ?? '',ENT_QUOTES);?>"
                                                            title="View Transactions">

                                                            <i class="fa fa-eye"></i>

                                                        </button>

                                                    </span>

                                                <?php endif;?>

                                            </td>

                                        </tr>

                                    <?php endforeach;?>

                                <?php endif;?>

                                </tbody>


                                <?php if(!empty($creditors)):?>

                                    <tfoot>

                                        <tr class="font-weight-bold">

                                            <td colspan="2" class="text-right">
                                                TOTAL
                                            </td>

                                            <td class="text-right text-warning">
                                                <?=number_format($totalCreditMovement,0);?>
                                            </td>

                                            <td class="text-right text-success">
                                                <?=number_format($totalDebitMovement,0);?>
                                            </td>

                                            <td class="text-right text-danger">
                                                <?=number_format($totalOutstanding,0);?>
                                            </td>

                                            <td colspan="2"></td>

                                        </tr>

                                    </tfoot>

                                <?php endif;?>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

            <!-- side modal for a cash in -->
            <div class="p-0 bg-white side-modal-tall absolute border shadow" id='side-modal-creditor'>
                <div class="side-modal-header bg-success">
                    <h3 class="side-modal-title text-white"></h3>
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
                            beforeSend:function(){
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
                $(document).on('click','.saveCreditor',function(){
                    submitSingleForm("newCreditorForm", "../books/save/index.php");
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
                            window.location.reload();
                        })
                        .catch(err => {
                            console.error("AJAX error:", err);
                        });
                    });
                }

                // view creditor details
                $(document).on('click','.view-creditor',function(){
                    $('#central-modal').show();
                    var title = $(this).data('title')+" Transactions";
                    $('.central-modal-title').html(title);
                    var id = $(this).data('id');

                    $.ajax({
                        url:'save/index.php',
                        data:{
                            creditor_id:id,
                            action:'creditor-details'
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

                // view creditor details
                $(document).on('click','.edit-creditor',function(){
                    $('#side-modal-creditor').show();
                    var title = $(this).data('title')+" Edit";
                    $('.side-modal-title').html(title);
                    var id = $(this).data('id');

                    $.ajax({
                        url:'save/index.php',
                        data:{
                            creditor_id:id,
                            action:'creditor-edit'
                        },
                        beforeSend:function(){
                            $('.side-modal-content').html("<center><h3>Loading...</h3></center>");
                        },
                        success:function(res){
                            $('.side-modal-content').html(res);
                        },
                        error:function(err){
                            $('.side-modal-content').html("<center><h3>!!! Error Loading data</h3></center>");
                        }
                    });
                });

                // delete creditor
                $(document).on('click','.delete-creditor',function(){
                    var id = $(this).data('id');
                    xdialog.confirm('Confirm to delete creditor?',function(){
                        $.ajax({
                            url:'save/index.php',
                            data:{
                                action:'deletecreditor',
                                id:id
                            },
                            beforeSend:function(){
                                xdialog.startSpin();
                            },
                            success:function(res){
                                xdialog.stopSpin();
                                xdialog.info("creditor deleted successfully");
                                window.location.reload();
                            },
                            error:function(err){
                                xdialog.info("Error removing creditor");
                            }
                        });
                    });
                });

                // attach items to the creditor
                $(document).on('click','.creditor-attach-item',function(){
                    var id = $(this).data('id');
                    var book_id = '<?=$book->id;?>';
                    $('.side-modal-title').html("ATTACH ITEMS TO creditor");
                    $('#side-modal-creditor').show();
                    $.ajax({
                        url:'save/index.php',
                        data:{
                            action:'findItems',
                            id:id,
                            book_id: book_id
                        },
                        success:function(res){
                            $('.side-modal-content').html(res);
                        },
                        error:function(err){
                            xdialog.info("Error loading items");
                        }
                    });
                });

                // transfer credit transaction to another creditor
                $(document).on('click','.transfer-credit',function(){
                    var id = $(this).data('id');
                    var transid = $(this).data('trans_id');

                    // fetch creditors from the database
                    $.ajax({
                        url:'save/index.php',
                        data:{
                            action:'fetchCreditors',
                            trans_id: transid
                        },
                        beforeSend:function(){
                            xdialog.startSpin();
                        },
                        success:function(res){
                            xdialog.stopSpin();

                            var options = "<div class='p-2'>"+
                                "<h4>Select Creditor to transfer to:</h4>"+
                                "<select id='creditorId' class='form-control mb-2'>"+
                                    res + // options come from server
                                "</select>"+
                            "</div>";

                            xdialog.confirm(options,function(){
                                var creditor_id = $('#creditorId').val();
                                $.ajax({
                                    url:'save/index.php',
                                    method:'POST',
                                    data:{
                                        creditor_id:creditor_id,
                                        trans_id:transid,
                                        id:id,
                                        action:'transferTrans'
                                    },
                                    beforeSend:function(){
                                        xdialog.startSpin();
                                    },
                                    success:function(res){
                                        xdialog.stopSpin();
                                        xdialog.info('Transaction completed successfully');
                                    },
                                    error:function(){
                                        xdialog.error("Error completing transaction");
                                    }
                                });
                            });
                        },
                        error:function(err){
                            xdialog.error("Error fetching creditors!!!!");
                        }
                    });                    
                });


                /*
                |--------------------------------------------------------------------------
                | CREDITOR DASHBOARD CHARTS
                |--------------------------------------------------------------------------
                */
                const creditorBalanceLabels =
                    <?=json_encode(
                        array_map(
                            function($row)
                            {
                                return $row['name'];
                            },
                            $topOutstanding
                        ),
                        JSON_HEX_TAG |
                        JSON_HEX_APOS |
                        JSON_HEX_AMP |
                        JSON_HEX_QUOT
                    );?>;

                const creditorBalanceValues =
                    <?=json_encode(
                        array_map(
                            function($row)
                            {
                                return (float)$row['balance'];
                            },
                            $topOutstanding
                        )
                    );?>;


                const creditorBalanceCanvas =
                    document.getElementById('creditorBalanceChart');

                if(
                    creditorBalanceCanvas &&
                    typeof Chart !== 'undefined'
                )
                {
                    new Chart(
                        creditorBalanceCanvas,
                        {
                            type:'bar',

                            data:{
                                labels:creditorBalanceLabels,

                                datasets:[
                                    {
                                        label:'Outstanding Balance',
                                        data:creditorBalanceValues,
                                        borderWidth:1
                                    }
                                ]
                            },

                            options:{
                                indexAxis:'y',
                                responsive:true,
                                maintainAspectRatio:false,

                                scales:{
                                    x:{
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
                                        display:false
                                    },

                                    tooltip:{
                                        callbacks:{
                                            label:function(context)
                                            {
                                                return 'Balance: ' +
                                                    Number(context.raw).toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    );
                }


                const creditorMonthlyCanvas =
                    document.getElementById('creditorMonthlyChart');

                if(
                    creditorMonthlyCanvas &&
                    typeof Chart !== 'undefined'
                )
                {
                    new Chart(
                        creditorMonthlyCanvas,
                        {
                            type:'line',

                            data:{
                                labels:<?=json_encode($monthlyLabels);?>,

                                datasets:[
                                    {
                                        label:'Credits',
                                        data:<?=json_encode($monthlyCredits);?>,
                                        borderWidth:2,
                                        tension:.25
                                    },
                                    {
                                        label:'Debits',
                                        data:<?=json_encode($monthlyDebits);?>,
                                        borderWidth:2,
                                        tension:.25
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
                                                    Number(context.raw).toLocaleString();
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
                | CREDITOR SEARCH
                |--------------------------------------------------------------------------
                */
                $(document).on('keyup','#creditorSearch',function(){

                    var value =
                        $(this).val().toLowerCase();

                    $('#creditorTableBody tr').each(function(){

                        var rowText =
                            $(this).text().toLowerCase();

                        $(this).toggle(
                            rowText.indexOf(value) !== -1
                        );

                    });

                });

            </script>
        <?php
    }else{
        redirect('../');
    }

?>