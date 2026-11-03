<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Purchases');

        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));

        /*
        ======================================================================
        PURCHASE SUMMARY
        ======================================================================
        We classify a purchase as CREDIT when creditor_id is a positive value.
        Everything without a creditor is treated as CASH.

        If your purchases table uses another column to identify credit
        purchases, change $purchaseCreditColumn below.
        ======================================================================
        */

        // Period and item filters. All queries remain scoped to the selected book.
        $from = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
        $to = isset($_GET['to']) ? trim((string)$_GET['to']) : '';
        $selectedItem = isset($_GET['item_id']) ? max(0, (int)$_GET['item_id']) : 0;
        $validDate = static function ($value) {
            if ($value === '') return true;
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            return $date && $date->format('Y-m-d') === $value;
        };
        if (!$validDate($from) || !$validDate($to) || ($from && $to && $from > $to)) {
            $from = $to = '';
        }
        $periodWhere = '';
        $periodTypes = 'i';
        $periodParams = [(int)$book->id];
        if ($from !== '') { $periodWhere .= ' AND cp.created_at >= ?'; $periodTypes .= 's'; $periodParams[] = $from . ' 00:00:00'; }
        if ($to !== '') { $periodWhere .= ' AND cp.created_at < DATE_ADD(?, INTERVAL 1 DAY)'; $periodTypes .= 's'; $periodParams[] = $to; }
        $itemWhere = $selectedItem ? ' AND cp.item_id = ?' : '';
        $historyTypes = $periodTypes . ($selectedItem ? 'i' : '');
        $historyParams = $periodParams;
        if ($selectedItem) $historyParams[] = $selectedItem;
        $filterQuery = ['bsid' => $bsid];
        if ($from !== '') $filterQuery['from'] = $from;
        if ($to !== '') $filterQuery['to'] = $to;
        $itemNames = [];
        $itemsResult = prepared_statements('SELECT id,name FROM cashbook_items WHERE book_id = ? ORDER BY name', 'i', [(int)$book->id]);
        if ($itemsResult) while ($ir = $itemsResult->fetch_assoc()) $itemNames[(int)$ir['id']] = $ir['name'];
        if ($selectedItem && !isset($itemNames[$selectedItem])) { $selectedItem = 0; $itemWhere = ''; $historyTypes = $periodTypes; $historyParams = $periodParams; }

        $purchaseCreditColumn = 'creditor_id';

        // Detect whether creditor_id exists so the page does not fail on an
        // older database that may not yet have the column.
        $columnExists = false;
        $columnCheck = $server->query("SHOW COLUMNS FROM cashbook_purchases LIKE 'creditor_id'");

        if($columnCheck && $columnCheck->num_rows > 0)
        {
            $columnExists = true;
        }

        /*
        If creditor_id is not available yet, all purchases are treated as cash
        until the creditor field is added to cashbook_purchases.
        */
        $creditCondition = $columnExists
            ? "COALESCE(cp.`{$purchaseCreditColumn}`,0) > 0"
            : "0 = 1";

        $cashCondition = $columnExists
            ? "COALESCE(cp.`{$purchaseCreditColumn}`,0) <= 0"
            : "1 = 1";

        /*
        ======================================================================
        SUMMARY CARDS
        ======================================================================
        */
        $summarySql = "
            SELECT
                COUNT(*) AS total_entries,
                COALESCE(SUM(cp.total),0) AS total_purchases,
                COALESCE(SUM(CASE WHEN {$cashCondition} THEN cp.total ELSE 0 END),0) AS cash_purchases,
                COALESCE(SUM(CASE WHEN {$creditCondition} THEN cp.total ELSE 0 END),0) AS credit_purchases,
                COALESCE(SUM(cp.quantity),0) AS total_quantity
            FROM cashbook_purchases cp
            WHERE cp.book_id = ? {$periodWhere}
        ";

        $summaryRes = prepared_statements($summarySql,$periodTypes,$periodParams);
        $summary = $summaryRes ? $summaryRes->fetch_assoc() : [];

        $totalEntries = (int)($summary['total_entries'] ?? 0);
        $totalPurchases = (float)($summary['total_purchases'] ?? 0);
        $cashPurchases = (float)($summary['cash_purchases'] ?? 0);
        $creditPurchases = (float)($summary['credit_purchases'] ?? 0);
        $totalQuantity = (float)($summary['total_quantity'] ?? 0);

        $cashPercentage = $totalPurchases > 0
            ? ($cashPurchases / $totalPurchases) * 100
            : 0;

        $creditPercentage = $totalPurchases > 0
            ? ($creditPurchases / $totalPurchases) * 100
            : 0;

        /*
        ======================================================================
        ITEM SUMMARY
        ======================================================================
        */
        $itemSql = "
            SELECT
                ci.id,
                ci.name,
                ci.units,
                COALESCE(SUM(cp.quantity),0) AS quantity,
                COALESCE(SUM(cp.total),0) AS total,
                COALESCE(SUM(CASE WHEN {$cashCondition} THEN cp.total ELSE 0 END),0) AS cash_total,
                COALESCE(SUM(CASE WHEN {$creditCondition} THEN cp.total ELSE 0 END),0) AS credit_total
            FROM cashbook_purchases cp
            INNER JOIN cashbook_items ci ON ci.id = cp.item_id
            WHERE cp.book_id = ? {$periodWhere}
            GROUP BY ci.id, ci.name, ci.units
            ORDER BY total DESC, ci.name ASC
        ";

        $itemRes = prepared_statements($itemSql,$periodTypes,$periodParams);
        $itemSummary = [];
        $chartLabels = [];
        $chartCash = [];
        $chartCredit = [];

        if($itemRes)
        {
            while($item = $itemRes->fetch_assoc())
            {
                $itemSummary[] = $item;
                $chartLabels[] = $item['name'];
                $chartCash[] = (float)$item['cash_total'];
                $chartCredit[] = (float)$item['credit_total'];
            }
        }

        /*
        ======================================================================
        MONTHLY SUMMARY
        ======================================================================
        */
        $monthlySql = "
            SELECT
                DATE_FORMAT(cp.created_at,'%Y-%m') AS month_key,
                DATE_FORMAT(cp.created_at,'%b %Y') AS month_name,
                COALESCE(SUM(cp.total),0) AS total,
                COALESCE(SUM(CASE WHEN {$cashCondition} THEN cp.total ELSE 0 END),0) AS cash_total,
                COALESCE(SUM(CASE WHEN {$creditCondition} THEN cp.total ELSE 0 END),0) AS credit_total
            FROM cashbook_purchases cp
            WHERE cp.book_id = ? {$periodWhere}
            GROUP BY DATE_FORMAT(cp.created_at,'%Y-%m'), DATE_FORMAT(cp.created_at,'%b %Y')
            ORDER BY month_key ASC
        ";

        $monthlyRes = prepared_statements($monthlySql,$periodTypes,$periodParams);
        $monthLabels = [];
        $monthCash = [];
        $monthCredit = [];

        if($monthlyRes)
        {
            while($month = $monthlyRes->fetch_assoc())
            {
                $monthLabels[] = $month['month_name'];
                $monthCash[] = (float)$month['cash_total'];
                $monthCredit[] = (float)$month['credit_total'];
            }
        }

        /*
        ======================================================================
        PURCHASE HISTORY
        ======================================================================
        */
        $sql = "
            SELECT
                cp.*,
                ci.name,
                ci.units,
                ci.details
            FROM cashbook_purchases cp
            INNER JOIN cashbook_items ci ON ci.id = cp.item_id
            WHERE cp.book_id = ? {$periodWhere} {$itemWhere}
            ORDER BY cp.created_at DESC, cp.id DESC
        ";

        $historyTotalSql = "SELECT COALESCE(SUM(cp.total),0) AS amount, COALESCE(SUM(cp.quantity),0) AS quantity FROM cashbook_purchases cp WHERE cp.book_id = ? {$periodWhere} {$itemWhere}";
        $historyTotalsResult = prepared_statements($historyTotalSql,$historyTypes,$historyParams);
        $historyTotals = $historyTotalsResult ? $historyTotalsResult->fetch_assoc() : ['amount'=>0,'quantity'=>0];
        $res = prepared_statements($sql,$historyTypes,$historyParams);

        $money = function($value) {
            return number_format((float)$value,0);
        };

        $quantity = function($value) {
            return rtrim(rtrim(number_format((float)$value,2), '0'), '.');
        };
?>
<style>
    .purchase-stat-card {
        min-height: 135px;
        border-radius: 8px;
    }

    .purchase-stat-icon {
        font-size: 35px;
        opacity: .16;
    }

    .purchase-chart-card {
        border: 0;
        border-radius: 8px;
    }

    .purchase-chart-wrap {
        position: relative;
        height: 350px;
    }

    .purchase-summary-table th,
    .purchase-summary-table td {
        vertical-align: middle;
    }

    .purchase-filter {
        max-width: 320px;
    }

    .purchase-type-badge {
        min-width: 70px;
    }

    @media(max-width: 767px)
    {
        .purchase-chart-wrap {
            height: 280px;
        }

        .purchase-filter {
            max-width: 100%;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3">

    <!-- Breadcrumb -->
    <div class="row mx-0">
        <div class="col p-2 d-flex align-items-center flex-wrap">
            <a href="../books/?bkid=<?=$bsid;?>" class="nav-link px-1">Books</a>
            <i class="fa fa-angle-right text-muted mx-1"></i>
            <span class="nav-link px-1 text-muted">Purchases</span>
        </div>
    </div>

    <!-- Header -->
    <div class="card shadow-sm border-0">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h3 class="mb-1">
                        <i class="fa fa-shopping-cart text-success mr-2"></i>
                        BOOK PURCHASES
                    </h3>
                    <small class="text-muted">
                        Monitor total purchases, cash purchases and credit purchases.
                    </small>
                </div>

                <?php if(hasRole(['owner','partner','superadministrator'])):?>
                    <button
                        class="btn btn-success btn-flat btn-click mt-2 mt-md-0"
                        data-title="record purchase"
                        data-section="purchase">
                        <i class="fa fa-plus-circle mr-1"></i>
                        Purchase
                    </button>
                <?php endif;?>
            </div>
        </div>
    </div>

    <!-- Period selection and item statement -->
    <div class="card shadow-sm border-0 mt-3 no-print">
        <div class="card-body">
            <form method="get" id="purchasePeriodForm" class="form-row align-items-end">
                <input type="hidden" name="bsid" value="<?=htmlspecialchars((string)$bsid, ENT_QUOTES);?>">
                <div class="col-md-3 mb-2"><label for="purchaseFrom">From date</label><input id="purchaseFrom" type="date" name="from" class="form-control" value="<?=htmlspecialchars($from,ENT_QUOTES);?>"></div>
                <div class="col-md-3 mb-2"><label for="purchaseTo">To date</label><input id="purchaseTo" type="date" name="to" class="form-control" value="<?=htmlspecialchars($to,ENT_QUOTES);?>"></div>
                <div class="col-md-3 mb-2"><label for="purchaseItem">Item purchase statement</label><select id="purchaseItem" name="item_id" class="form-control"><option value="0">All items</option><?php foreach ($itemNames as $itemId => $itemName): ?><option value="<?=$itemId;?>" <?=$selectedItem === $itemId ? 'selected' : '';?>><?=htmlspecialchars($itemName);?></option><?php endforeach; ?></select></div>
                <div class="col-md-3 mb-2"><button class="btn btn-success" type="submit"><i class="fa fa-filter"></i> View Report</button> <a class="btn btn-outline-secondary" href="?<?=htmlspecialchars(http_build_query(['bsid'=>$bsid]));?>">Reset</a> <button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="fa fa-print"></i> Print</button></div>
            </form>
            <small class="text-muted">View Report opens a full-width period report without leaving this page. Existing page filters remain available through the URL.</small>
        </div>
    </div>
    <?php if ($selectedItem): ?>
    <div class="alert alert-info mt-3 mb-0"><strong>Item purchase statement:</strong> <?=htmlspecialchars($itemNames[$selectedItem]);?> &mdash; <?=htmlspecialchars($from ?: 'Beginning');?> to <?=htmlspecialchars($to ?: 'Latest');?>. The purchase history below is limited to this item.</div>
    <?php endif; ?>

    <!-- Summary cards -->
    <div class="row mt-3">

        <div class="col-lg-3 col-md-6 col-6 mb-3">
            <div class="card purchase-stat-card shadow-sm border-left border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase">Total Purchases</small>
                            <h4 class="mt-2 mb-0 text-primary"><?=$money($totalPurchases);?></h4>
                        </div>
                        <div class="purchase-stat-icon text-primary">
                            <i class="fa fa-shopping-cart"></i>
                        </div>
                    </div>
                    <small class="text-muted"><?=$totalEntries;?> purchase entries</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6 mb-3">
            <div class="card purchase-stat-card shadow-sm border-left border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase">Cash Purchases</small>
                            <h4 class="mt-2 mb-0 text-success"><?=$money($cashPurchases);?></h4>
                        </div>
                        <div class="purchase-stat-icon text-success">
                            <i class="fa fa-money"></i>
                        </div>
                    </div>
                    <small class="text-muted"><?=number_format($cashPercentage,1);?>% of total purchases</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6 mb-3">
            <div class="card purchase-stat-card shadow-sm border-left border-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase">Credit Purchases</small>
                            <h4 class="mt-2 mb-0 text-warning"><?=$money($creditPurchases);?></h4>
                        </div>
                        <div class="purchase-stat-icon text-warning">
                            <i class="fa fa-credit-card"></i>
                        </div>
                    </div>
                    <small class="text-muted"><?=number_format($creditPercentage,1);?>% of total purchases</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6 mb-3">
            <div class="card purchase-stat-card shadow-sm border-left border-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase">Quantity Purchased</small>
                            <h4 class="mt-2 mb-0 text-info"><?=$quantity($totalQuantity);?></h4>
                        </div>
                        <div class="purchase-stat-icon text-info">
                            <i class="fa fa-cubes"></i>
                        </div>
                    </div>
                    <small class="text-muted">Across all purchase entries</small>
                </div>
            </div>
        </div>

    </div>

    <!-- Financial split -->
    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card purchase-chart-card shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong>
                        <i class="fa fa-bar-chart mr-2"></i>
                        Cash vs Credit Purchases by Item
                    </strong>
                </div>
                <div class="card-body">
                    <div class="purchase-chart-wrap">
                        <canvas id="purchaseCashCreditChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card purchase-chart-card shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong>
                        <i class="fa fa-pie-chart mr-2"></i>
                        Purchase Funding
                    </strong>
                </div>
                <div class="card-body">
                    <div class="purchase-chart-wrap">
                        <canvas id="purchaseFundingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly trend -->
    <div class="row">
        <div class="col-6 mb-3">
            <div class="card purchase-chart-card shadow-sm">
                <div class="card-header bg-white">
                    <strong>
                        <i class="fa fa-line-chart mr-2"></i>
                        Monthly Purchase Trend
                    </strong>
                </div>
                <div class="card-body">
                    <div class="purchase-chart-wrap">
                        <canvas id="purchaseMonthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 mb-3">
            <!-- Item summary -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white">
                    <strong>
                        <i class="fa fa-cubes mr-2"></i>
                        PURCHASE SUMMARY BY ITEM
                    </strong>
                </div>

                <div class="table-responsive p-2">
                    <table class="table table-sm table-striped table-hover purchase-summary-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Units</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Cash</th>
                                <th class="text-right">Credit</th>
                                <th class="text-right">Total</th>
                                <th class="text-center no-print">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($itemSummary)):?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted p-4">
                                        No purchase records found.
                                    </td>
                                </tr>
                            <?php else:?>
                                <?php $n = 1; foreach($itemSummary as $item):?>
                                    <tr>
                                        <td><?=$n++;?></td>
                                        <td>
                                            <strong><?=htmlspecialchars($item['name']);?></strong>
                                        </td>
                                        <td><?=htmlspecialchars($item['units']);?></td>
                                        <td class="text-right">
                                            <?=$quantity($item['quantity']);?>
                                        </td>
                                        <td class="text-right text-success">
                                            <?=$money($item['cash_total']);?>
                                        </td>
                                        <td class="text-right text-danger">
                                            <?=$money($item['credit_total']);?>
                                        </td>
                                        <td class="text-right font-weight-bold">
                                            <?=$money($item['total']);?>
                                        </td>
                                        <td class="text-center no-print"><button type="button" class="btn btn-sm btn-outline-primary view-item-purchases" data-item-id="<?=(int)$item['id'];?>" data-item-name="<?=htmlspecialchars($item['name'], ENT_QUOTES);?>"><i class="fa fa-eye"></i> View</button></td>
                                    </tr>
                                <?php endforeach;?>
                            <?php endif;?>
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold bg-light">
                                <td colspan="3" class="text-right">TOTAL</td>
                                <td class="text-right"><?=$quantity($totalQuantity);?></td>
                                <td class="text-right text-success"><?=$money($cashPurchases);?></td>
                                <td class="text-right text-danger"><?=$money($creditPurchases);?></td>
                                <td class="text-right"><?=$money($totalPurchases);?></td>
                                <td class="no-print"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase history -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <strong>
                        <i class="fa fa-history mr-2"></i>
                        PURCHASE HISTORY
                    </strong>
                    <div class="small text-muted"><?= $selectedItem ? 'Item purchase statement (filtered)' : 'Detailed purchase records for the selected period'; ?></div>
                </div>

                <input
                    type="text"
                    id="purchaseSearch"
                    class="form-control form-control-sm purchase-filter mt-2 mt-md-0"
                    placeholder="Search item or purchase...">
            </div>
        </div>

        <div class="table-responsive p-3">
            <table class="table table-sm table-striped table-hover dataTable mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="text-right">Qty</th>
                        <th>Units</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Amount</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="purchaseHistoryBody">
                    <?php if($res): while($r = $res->fetch_assoc()):
                        $isCredit = $columnExists && !empty($r['creditor_id']) && (int)$r['creditor_id'] > 0;
                    ?>
                        <tr>
                            <td><?=date_format(date_create($r['created_at']),'d-m-Y');?></td>
                            <td>
                                <strong><?=htmlspecialchars($r['name']);?></strong>
                                <?php if(!empty($r['details'])):?>
                                    <div class="small text-muted">
                                        <?=htmlspecialchars($r['details']);?>
                                    </div>
                                <?php endif;?>
                            </td>
                            <td>
                                <?php if($isCredit):?>
                                    <span class="badge badge-warning purchase-type-badge">CREDIT</span>
                                <?php else:?>
                                    <span class="badge badge-success purchase-type-badge">CASH</span>
                                <?php endif;?>
                            </td>
                            <td class="text-right"><?=$quantity($r['quantity']);?></td>
                            <td><?=htmlspecialchars($r['units']);?></td>
                            <td class="text-right"><?=$money($r['unit_price']);?></td>
                            <td class="text-right font-weight-bold"><?=$money($r['total']);?></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary view-item-purchases" data-item-id="<?=(int)$r['item_id'];?>" data-item-name="<?=htmlspecialchars($r['name'], ENT_QUOTES);?>"><i class="fa fa-eye"></i> View</button>
                                <?php if(hasRole(['owner','partner','superadministrator'])):?>
                                    <span class="hover-display text-sms">
                                        <!-- Existing edit/delete controls intentionally remain disabled,
                                             as in the original page. -->
                                    </span>
                                <?php else:?>
                                    <span class="text-muted">—</span>
                                <?php endif;?>
                            </td>
                        </tr>
                    <?php endwhile; endif;?>
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="6" class="text-right">TOTAL</td>
                        <td class="text-right"><?=$money($historyTotals['amount']);?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>



<!-- Central report modal, independent of purchase entry side modal -->
<div class="modal fade" id="purchaseReportModal" tabindex="-1" role="dialog" aria-labelledby="purchaseReportTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document" style="max-width:95vw">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="purchaseReportTitle">Purchase Report</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body" style="max-height:72vh;overflow:auto" id="purchaseReportBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-outline-primary" id="printPurchaseReport"><i class="fa fa-print"></i> Print</button>
        <a href="#" class="btn btn-success" id="downloadPurchasePdf" target="_blank" rel="noopener"><i class="fa fa-file-pdf-o"></i> Download PDF</a>
      </div>
    </div>
  </div>
</div>

<!-- Shared KashHub side modal: purchase entry and item statements -->
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


    /* Period report uses a wide central modal, not the purchase-entry side modal. */
    $('#purchasePeriodForm').on('submit', function (event) {
        event.preventDefault();
        var from = $('#purchaseFrom').val(), to = $('#purchaseTo').val();
        if (!from || !to || from > to) {
            alert('Please select valid From and To dates.'); return;
        }
        var params = {purchase_report:'1', bsid:<?=json_encode((string)$bsid);?>,
            from:from, to:to, item_id:$('#purchaseItem').val() || 0};
        var url = 'pdf.php?' + $.param(params);
        $('#purchaseReportTitle').text('Purchases: ' + from + ' to ' + to);
        $('#purchaseReportBody').html('<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-2x text-success"></i><p>Loading purchase report...</p></div>');
        $('#downloadPurchasePdf').attr('href',url+'&format=pdf');
        $('#purchaseReportModal').modal('show');
        $.ajax({url:url,method:'GET',success:function(html){
            $('#purchaseReportBody').html(html);
        },error:function(xhr){
            $('#purchaseReportBody').text('Unable to load report ('+xhr.status+'): '+(xhr.responseText || 'Please try again.'));
        }});
    });
    $('#printPurchaseReport').on('click', function () {
        var content = $('#purchaseReportBody .purchase-statement-print');
        if (!content.length) return;
        var styles = $('#purchaseReportBody style').first().prop('outerHTML') || '';
        var win = window.open('', '_blank', 'width=1100,height=800');
        if (!win) { alert('Allow pop-ups to print this report.'); return; }
        win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Purchase Report</title>'+styles+'<style>@page{size:A4 landscape;margin:12mm}</style></head><body>'+content.prop('outerHTML')+'</body></html>');
        win.document.close(); win.focus(); win.print();
    });

    /* Item purchase statement uses the existing KashHub side modal. */
    $(document).on('click', '.view-item-purchases', function () {
        var itemId = Number($(this).data('item-id'));
        if (!itemId) return;
        var $modal = $('#side-modal-cashin');
        $modal.show();
        $modal.find('.side-modal-title').text('ITEM PURCHASE STATEMENT');
        $modal.find('.side-modal-content').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x text-success"></i><p>Loading statement...</p></div>');
        $.ajax({
            url: 'pdf.php',
            method: 'GET',
            data: {purchase_statement:'1', bsid: <?=json_encode((string)$bsid);?>, item_id:itemId, from:<?=json_encode($from);?>, to:<?=json_encode($to);?>},
            success: function (html) {
                var pdfUrl = 'pdf.php?' + $.param({purchase_statement:'1',format:'pdf',bsid:<?=json_encode((string)$bsid);?>,item_id:itemId,from:<?=json_encode($from);?>,to:<?=json_encode($to);?>});
                $modal.find('.side-modal-content').html('<div class="p-3"><div class="text-right mb-2"><button type="button" class="btn btn-sm btn-outline-success print-item-statement"><i class="fa fa-print"></i> Print Statement</button> <a class="btn btn-sm btn-success" href="' + pdfUrl + '" target="_blank" rel="noopener"><i class="fa fa-file-pdf-o"></i> Download PDF</a></div>' + html + '</div>');
            },
            error: function (xhr) {
                $modal.find('.side-modal-content').html('<div class="alert alert-danger m-3">Unable to load statement (' + xhr.status + '). Please try again.</div>');
            }
        });
    });
    $(document).on('click', '.print-item-statement', function () {
        var statement = $('#side-modal-cashin .purchase-statement-print');
        if (!statement.length) return;
        var styles = $('#side-modal-cashin .side-modal-content style').first().prop('outerHTML') || '';
        var win = window.open('', '_blank', 'width=1050,height=750');
        if (!win) { alert('Please allow pop-ups to print the statement.'); return; }
        win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Item Purchase Statement</title>' + styles + '<style>@page{size:A4 landscape;margin:12mm}body{margin:0;padding:8px}</style></head><body>' + statement.prop('outerHTML') + '</body></html>');
        win.document.close();
        win.focus();
        win.print();
    });

    /* ================================================================
       EXISTING PURCHASE MODAL FUNCTIONALITY
       ================================================================ */
    $(document).on('click','.btn-click',function(){
        var title = $(this).data('title') || '';
        title = title.toUpperCase();

        $('#side-modal-cashin').show();
        $('.side-modal-title').html(title);

        var section = $(this).data('section') || '';
        var id = $(this).data('id') || '';

        fetchData(section,id);
    });

    function fetchData(section,id)
    {
        var book_id = "<?=$book->id;?>";

        if(section !== '')
        {
            $.ajax({
                url:'../books/save/index.php',
                data:{
                    section:section,
                    book_id:book_id,
                    action:'fetchForm',
                    item_id:id
                },
                beforeSend:function(){
                    $('.side-modal-content').html(
                        "<div class='text-center p-4'>" +
                        "<i class='fa fa-spinner fa-spin fa-2x text-success'></i>" +
                        "<div class='mt-2'>Loading...</div>" +
                        "</div>"
                    );
                },
                success:function(res){
                    $('.side-modal-content').html(res);
                },
                error:function(){
                    $('.side-modal-content').html(
                        "<div class='text-center text-danger p-4'>" +
                        "Error Loading data!!" +
                        "</div>"
                    );
                }
            });
        }
    }

    /* ================================================================
       SAVE PURCHASE
       ================================================================ */
    $(document).on('click','.savePurchase',function(){
        submitSingleForm("newPurchaseForm", "../books/save/index.php");
    });

    function submitSingleForm(formId, backendUrl)
    {
        const form = document.getElementById(formId);

        if(!form)
        {
            console.error("Form not found:", formId);
            return;
        }

        if(form.dataset.submitting === '1')
        {
            return;
        }

        form.dataset.submitting = '1';
        xdialog.startSpin();

        const formData = new FormData(form);

        fetch(backendUrl, {
            method:'POST',
            body:formData
        })
        .then(function(res){
            return res.text();
        })
        .then(function(){
            window.location.reload();
        })
        .catch(function(err){
            console.error('AJAX error:',err);
            form.dataset.submitting = '0';

            if(typeof xdialog !== 'undefined' && xdialog.stopSpin)
            {
                xdialog.stopSpin();
            }
        });
    }

    /* ================================================================
       SEARCH PURCHASE HISTORY
       ================================================================ */
    $(document).on('keyup','#purchaseSearch',function(){
        var value = $(this).val().toLowerCase();

        $('#purchaseHistoryBody tr').each(function(){
            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) !== -1
            );
        });
    });

    /* ================================================================
       CHARTS
       ================================================================ */
    $(function(){

        if(typeof Chart === 'undefined')
        {
            return;
        }

        var itemLabels = <?=json_encode($chartLabels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);?>;
        var itemCash = <?=json_encode($chartCash);?>;
        var itemCredit = <?=json_encode($chartCredit);?>;

        var monthLabels = <?=json_encode($monthLabels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);?>;
        var monthCash = <?=json_encode($monthCash);?>;
        var monthCredit = <?=json_encode($monthCredit);?>;

        /* ------------------------------------------------------------
           CASH VS CREDIT BY ITEM
           ------------------------------------------------------------ */
        var itemCanvas = document.getElementById('purchaseCashCreditChart');

        if(itemCanvas)
        {
            new Chart(itemCanvas.getContext('2d'),{
                type:'bar',
                data:{
                    labels:itemLabels,
                    datasets:[
                        {
                            label:'Cash Purchases',
                            data:itemCash,
                            borderWidth:1
                        },
                        {
                            label:'Credit Purchases',
                            data:itemCredit,
                            borderWidth:1
                        }
                    ]
                },
                options:{
                    responsive:true,
                    maintainAspectRatio:false,
                    scales:{
                        y:{beginAtZero:true}
                    }
                }
            });
        }

        /* ------------------------------------------------------------
           FUNDING MIX
           ------------------------------------------------------------ */
        var fundingCanvas = document.getElementById('purchaseFundingChart');

        if(fundingCanvas)
        {
            new Chart(fundingCanvas.getContext('2d'),{
                type:'doughnut',
                data:{
                    labels:['Cash Purchases','Credit Purchases'],
                    datasets:[{
                        data:[
                            <?=json_encode($cashPurchases);?>,
                            <?=json_encode($creditPurchases);?>
                        ],
                        borderWidth:1
                    }]
                },
                options:{
                    responsive:true,
                    maintainAspectRatio:false,
                    plugins:{legend:{position:'bottom'}}
                }
            });
        }

        /* ------------------------------------------------------------
           MONTHLY TREND
           ------------------------------------------------------------ */
        var monthlyCanvas = document.getElementById('purchaseMonthlyChart');

        if(monthlyCanvas)
        {
            new Chart(monthlyCanvas.getContext('2d'),{
                type:'line',
                data:{
                    labels:monthLabels,
                    datasets:[
                        {
                            label:'Cash Purchases',
                            data:monthCash,
                            borderWidth:2,
                            fill:false
                        },
                        {
                            label:'Credit Purchases',
                            data:monthCredit,
                            borderWidth:2,
                            fill:false
                        }
                    ]
                },
                options:{
                    responsive:true,
                    maintainAspectRatio:false,
                    scales:{
                        y:{beginAtZero:true}
                    }
                }
            });
        }
    });

    // calculate amount when quantity and rate have been put
    $(document).on('blur','#purchase_rate,#purchase_quantity',function(){
        var rate = $('#purchase_rate').val();
        var qty = $('#purchase_quantity').val();
        var amt = qty * rate;
        $('#purchase_amount').val(amt);
    });

    // control creditor selection based on the transaction type
    $(document).on('change','#purchase_type',function(){
        var type = $(this).val();
        // check type to display creditor
        if(type =='credit_purchase')
        {
            $('.purchase-creditor').show('500');
        }else{
            $('.purchase-creditor').hide('500');
        }

    });
</script>

<?php
    }
    else
    {
        redirect('../');
    }
?>
