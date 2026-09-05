<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
require_once(__DIR__.'/../assets/functions.php');

if (!isVerified()) {
    redirect('../');
    exit;
}

pageHeader('Cashbook Dashboard');

$id_enc   = request('bkid');
$id       = encryptor('decrypt', $id_enc);
$business = bookFind($id);
$book_id  = (int)($business->id ?? 0);
$year     = (int)date('Y');
$today    = date('Y-m-d');
$monthStart= date('Y-m-01');

function cbDashboardValue($result, $key, $default = 0)
{
    if (!$result) return $default;
    $row = $result->fetch_assoc();
    return $row[$key] ?? $default;
}

/* ===============================
   CORE CASH SUMMARY
================================ */
$coreSql = "SELECT
    COALESCE(SUM(credit_amount),0) AS total_cashin,
    COALESCE(SUM(debit_amount),0) AS total_cashout,
    COALESCE(SUM(credit_amount) - SUM(debit_amount),0) AS balance,
    COUNT(*) AS total_transactions,
    COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN credit_amount ELSE 0 END),0) AS today_cashin,
    COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN debit_amount ELSE 0 END),0) AS today_cashout,
    COALESCE(SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN credit_amount ELSE 0 END),0) AS month_cashin,
    COALESCE(SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN debit_amount ELSE 0 END),0) AS month_cashout
FROM cashbook_transactions
WHERE book_id = ?";
$coreResult = prepared_statements($coreSql, 'i', [$book_id]);
$core = $coreResult ? $coreResult->fetch_assoc() : [];

$totalCashin      = (float)($core['total_cashin'] ?? 0);
$totalCashout     = (float)($core['total_cashout'] ?? 0);
$currentBalance   = (float)($core['balance'] ?? 0);
$totalTransactions= (int)($core['total_transactions'] ?? 0);
$todayCashin      = (float)($core['today_cashin'] ?? 0);
$todayCashout     = (float)($core['today_cashout'] ?? 0);
$monthCashin      = (float)($core['month_cashin'] ?? 0);
$monthCashout     = (float)($core['month_cashout'] ?? 0);

/* ===============================
   MONTHLY TREND - CURRENT YEAR
================================ */
$monthlySql = "SELECT
    MONTH(created_at) AS month_no,
    COALESCE(SUM(credit_amount),0) AS cashin,
    COALESCE(SUM(debit_amount),0) AS cashout
FROM cashbook_transactions
WHERE book_id = ? AND YEAR(created_at) = ?
GROUP BY MONTH(created_at)
ORDER BY MONTH(created_at) ASC";
$monthlyResult = prepared_statements($monthlySql, 'ii', [$book_id, $year]);
$monthlyMap = [];
if ($monthlyResult) {
    while ($r = $monthlyResult->fetch_assoc()) {
        $monthlyMap[(int)$r['month_no']] = $r;
    }
}
$monthlyLabels = [];
$monthlyCashin = [];
$monthlyCashout = [];
for ($m = 1; $m <= 12; $m++) {
    $monthlyLabels[] = date('M', mktime(0,0,0,$m,1));
    $monthlyCashin[] = (float)($monthlyMap[$m]['cashin'] ?? 0);
    $monthlyCashout[] = (float)($monthlyMap[$m]['cashout'] ?? 0);
}

/* ===============================
   PAYMENT MODE SUMMARY
================================ */
$paymodeSql = "SELECT
    COALESCE(pm.name, CONCAT('Mode #', t.paymode_id)) AS paymode,
    COALESCE(SUM(t.credit_amount),0) AS cashin,
    COALESCE(SUM(t.debit_amount),0) AS cashout
FROM cashbook_transactions t
LEFT JOIN cashbook_paymodes pm ON pm.id = t.paymode_id
WHERE t.book_id = ?
GROUP BY t.paymode_id, pm.name
ORDER BY (SUM(t.credit_amount) + SUM(t.debit_amount)) DESC
LIMIT 6";
$paymodeResult = prepared_statements($paymodeSql, 'i', [$book_id]);
$paymodeLabels=[]; $paymodeIn=[]; $paymodeOut=[];
if ($paymodeResult) {
    while ($r = $paymodeResult->fetch_assoc()) {
        $paymodeLabels[] = $r['paymode'];
        $paymodeIn[] = (float)$r['cashin'];
        $paymodeOut[] = (float)$r['cashout'];
    }
}

/* ===============================
   CATEGORY SUMMARY
================================ */
$categorySql = "SELECT
    COALESCE(c.name, CONCAT('Category #', t.category_id)) AS category,
    COALESCE(SUM(t.credit_amount),0) AS cashin,
    COALESCE(SUM(t.debit_amount),0) AS cashout
FROM cashbook_transactions t
LEFT JOIN cashbook_categories c ON c.id = t.category_id
WHERE t.book_id = ?
GROUP BY t.category_id, c.name
ORDER BY (SUM(t.credit_amount) + SUM(t.debit_amount)) DESC
LIMIT 8";
$categoryResult = prepared_statements($categorySql, 'i', [$book_id]);
$categories = [];
if ($categoryResult) while ($r = $categoryResult->fetch_assoc()) $categories[] = $r;

/* ===============================
   RECENT TRANSACTIONS
================================ */
$recentSql = "SELECT id, type, details, credit_amount, debit_amount, created_at, paymode_id, category_id
FROM cashbook_transactions
WHERE book_id = ?
ORDER BY created_at DESC, id DESC
LIMIT 8";
$recentResult = prepared_statements($recentSql, 'i', [$book_id]);
$recentTransactions = [];
if ($recentResult) while ($r = $recentResult->fetch_assoc()) $recentTransactions[] = $r;

/* ===============================
   TOP CUSTOMERS / RECEIVABLES
================================ */
$customersSql = "SELECT ccb.customer_id, cc.name AS customer_name, ccb.balance FROM cashbook_customer_balances ccb
                INNER JOIN cashbook_customers cc ON cc.id = ccb.customer_id
                WHERE ccb.book_id = ? AND ccb.balance > 0
                ORDER BY ccb.balance DESC";
$customersResult = prepared_statements($customersSql, 'i', [$book_id]);
$topCustomers = [];
$totalDebtors = 0;
if ($customersResult) while ($r = $customersResult->fetch_assoc()) {
    $topCustomers[] = $r;
    $totalDebtors += $r['balance'];
}


?>

<style>
:root{--cb-primary:#0f766e;--cb-dark:#0f172a;--cb-muted:#64748b;--cb-border:#e2e8f0;--cb-bg:#f8fafc;}
.cb-dashboard{background:var(--cb-bg);min-height:100vh;padding:10px 0 35px}.cb-card{background:#fff;border:1px solid var(--cb-border);border-radius:18px;box-shadow:0 6px 24px rgba(15,23,42,.05)}
.cb-hero{background:linear-gradient(135deg,#0f172a,#0f766e);color:#fff;border-radius:20px;padding:25px;margin-bottom:18px}.cb-hero h2{margin:0;font-weight:700}.cb-hero p{margin:6px 0 0;opacity:.8}.metric-card{padding:18px;height:100%}.metric-icon{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:20px;background:#f1f5f9;color:var(--cb-primary)}.metric-label{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--cb-muted);font-weight:700}.metric-value{font-size:25px;font-weight:800;color:var(--cb-dark);margin-top:5px}.metric-sub{font-size:12px;color:var(--cb-muted);margin-top:6px}.section-title{font-size:16px;font-weight:800;color:var(--cb-dark);margin:0}.section-sub{font-size:12px;color:var(--cb-muted)}.chart-box{height:310px}.mini-chart{height:260px}.table>thead th{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--cb-muted);border-top:0}.badge-cb{padding:7px 10px;border-radius:20px;background:#f1f5f9;color:#334155;font-size:11px}.quick-btn{border:1px solid var(--cb-border);background:#fff;border-radius:12px;padding:11px 14px;color:var(--cb-dark);font-weight:600;text-decoration:none;display:inline-block;margin:3px}.quick-btn:hover{background:#f8fafc;color:var(--cb-primary)}.amount-in{color:#15803d;font-weight:700}.amount-out{color:#dc2626;font-weight:700}.balance-positive{color:#15803d}.balance-negative{color:#dc2626}.category-row{padding:11px 0;border-bottom:1px solid var(--cb-border)}.category-row:last-child{border-bottom:0}.progress{height:7px;border-radius:20px}.empty-state{padding:35px;text-align:center;color:var(--cb-muted)}
</style>

<div class="container-fluid cb-dashboard">
    <div class="container-fluid px-lg-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
            <div class="py-2">
                <a href="../books/?bkid=<?=$id_enc;?>" class="text-decoration-none text-muted">Books</a>
                <span class="mx-2 text-muted">/</span><span class="text-muted">Dashboard</span>
            </div>
            <div class="text-muted small"><i class="fa fa-calendar"></i> <?=date('l, d M Y');?></div>
        </div>

        <div class="cb-hero">
            <div class="row align-items-center">
                <div class="col-lg-8"><h2><i class="fa fa-line-chart me-2"></i><?=htmlspecialchars($business->name ?? 'Cashbook');?> Dashboard</h2><p>Live financial overview, cash movement, transaction trends and management insights.</p></div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3"><div class="cb-card metric-card"><div class="d-flex justify-content-between"><div><div class="metric-label">Total Cash In</div><div class="metric-value amount-in"><?=number_format($totalCashin,0);?>/=</div><div class="metric-sub">All recorded income</div></div><div class="metric-icon"><i class="fa fa-arrow-down"></i></div></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="cb-card metric-card"><div class="d-flex justify-content-between"><div><div class="metric-label">Total Cash Out</div><div class="metric-value amount-out"><?=number_format($totalCashout,0);?>/=</div><div class="metric-sub">All recorded expenditure</div></div><div class="metric-icon"><i class="fa fa-arrow-up"></i></div></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="cb-card metric-card"><div class="d-flex justify-content-between"><div><div class="metric-label">Available Balance</div><div class="metric-value <?=$currentBalance < 0 ? 'balance-negative':'balance-positive';?>"><?=number_format($currentBalance,0);?>/=</div><div class="metric-sub">Cash in minus cash out</div></div><div class="metric-icon"><i class="fa fa-wallet"></i></div></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="cb-card metric-card"><div class="d-flex justify-content-between"><div><div class="metric-label">Total Debt</div><div class="metric-value balance-positive"><?=number_format($totalDebtors,0);?>/=</div><div class="metric-sub">Amount with Customers</div></div><div class="metric-icon"><a href="../customers/?bsid=<?=encryptor("encrypt", $business->id);?>"><i class="fa fa-eye"></i></a></div></div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-2"><div class="cb-card metric-card"><div class="metric-label">Today's Cash In</div><div class="metric-value amount-in"><?=number_format($todayCashin,0);?>/=</div></div></div>
            <div class="col-md-2"><div class="cb-card metric-card"><div class="metric-label">Today's Cash Out</div><div class="metric-value amount-out"><?=number_format($todayCashout,0);?>/=</div></div></div>
            <div class="col-md-2"><div class="cb-card metric-card"><div class="metric-label">Today's Balance</div><div class="metric-value amount-in"><?=number_format($todayCashin - $todayCashout,0);?>/=</div></div></div>
            <div class="col-md-2"><div class="cb-card metric-card"><div class="metric-label">This Month In</div><div class="metric-value amount-in"><?=number_format($monthCashin,0);?>/=</div></div></div>
            <div class="col-md-2"><div class="cb-card metric-card"><div class="metric-label">This Month Net</div><div class="metric-value <?=($monthCashin-$monthCashout)<0?'balance-negative':'balance-positive';?>"><?=number_format($monthCashin-$monthCashout,0);?>/=</div></div></div>
            <div class="col-md-2"><div class="cb-card metric-card"><div class="metric-label">Transactions</div><div class="metric-value"></div><div class="metric-value"><?=number_format($totalTransactions);?></div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8"><div class="cb-card p-3"><div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="section-title">Monthly Cash Movement</h5><div class="section-sub">Cash in versus cash out for <?=$year;?></div></div><span class="badge-cb">Annual Trend</span></div><div class="chart-box"><canvas id="monthlyCashChart"></canvas></div></div></div>
            <div class="col-xl-4"><div class="cb-card p-3 h-100"><div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="section-title">Cash Position</h5><div class="section-sub">Overall financial distribution</div></div></div><div class="mini-chart"><canvas id="cashPositionChart"></canvas></div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-6"><div class="cb-card p-3 h-100"><div class="mb-3"><h5 class="section-title">Payment Mode Performance</h5><div class="section-sub">Cash movement by payment method</div></div><div class="mini-chart"><canvas id="paymodeChart"></canvas></div></div></div>
            <div class="col-xl-6"><div class="cb-card p-3 h-100"><div class="mb-3"><h5 class="section-title">Category Performance</h5><div class="section-sub">Largest income and expense categories</div></div>
            <?php if($categories): $p = 0; foreach($categories as $cat): if($p >= 4) break; $activity=(float)$cat['cashin']+(float)$cat['cashout']; $maxActivity=max(array_map(fn($x)=>(float)$x['cashin']+(float)$x['cashout'],$categories)); $percent=$maxActivity>0?($activity/$maxActivity)*100:0; ?>
                <div class="category-row"><div class="d-flex justify-content-between"><strong><?=htmlspecialchars($cat['category']);?></strong><small><?=number_format($activity,0);?>/=</small></div><div class="progress mt-2"><div class="progress-bar" role="progressbar" style="width: <?=$percent;?>%"></div></div><div class="d-flex justify-content-between mt-2 small"><span class="amount-in">In: <?=number_format($cat['cashin'],0);?></span><span class="amount-out">Out: <?=number_format($cat['cashout'],0);?></span></div></div>
            <?php $p++;endforeach; else: ?><div class="empty-state">No category activity found.</div><?php endif; ?>
            </div></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8"><div class="cb-card p-3 h-100"><div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="section-title">Recent Transactions</h5><div class="section-sub">Latest recorded cash movements</div></div><a href="../transactions/?bkid=<?=$id_enc;?>" class="btn btn-sm btn-outline-secondary">View All</a></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Date</th><th>Details</th><th>Type</th><th class="text-end">Cash In</th><th class="text-end">Cash Out</th></tr></thead><tbody>
            <?php if($recentTransactions): foreach($recentTransactions as $tx): ?><tr><td class="small text-muted"><?=date('d M Y',strtotime($tx['created_at']));?></td><td><strong><?=htmlspecialchars($tx['details'] ?: 'No details');?></strong></td><td><span class="badge-cb"><?=htmlspecialchars($tx['type']);?></span></td><td class="text-end amount-in"><?= $tx['credit_amount']>0 ? number_format($tx['credit_amount'],0).' /=' : '-';?></td><td class="text-end amount-out"><?= $tx['debit_amount']>0 ? number_format($tx['debit_amount'],0).' /=' : '-';?></td></tr><?php endforeach; else: ?><tr><td colspan="5" class="empty-state">No transactions found.</td></tr><?php endif; ?>
            </tbody></table></div></div></div>
            <div class="col-xl-4"><div class="cb-card p-3 h-100"><h4 class="section-title">Customer Balances</h4><div class="section-sub mb-3">Outstanding balances from latest ledger positions</div><hr><?php if($topCustomers): $k=0; foreach($topCustomers as $customer): if($k >= 7) break;?><div class="d-flex justify-content-between align-items-center py-2 border-bottom"><div><strong><?=$customer['customer_name'];?></strong><div class="small text-muted">Current outstanding balance</div></div><strong class="amount-out"><?=number_format($customer['balance'],0);?>/=</strong></div><?php $k++; endforeach; else: ?><div class="empty-state">No outstanding customer balances found.</div><?php endif; ?></div></div>
        </div>
    </div>
</div>
<?php pageFooter(); ?>
<script>
const dashboardData = {
    monthlyLabels: <?=json_encode($monthlyLabels);?>,
    monthlyCashin: <?=json_encode($monthlyCashin);?>,
    monthlyCashout: <?=json_encode($monthlyCashout);?>,
    paymodeLabels: <?=json_encode($paymodeLabels);?>,
    paymodeIn: <?=json_encode($paymodeIn);?>,
    paymodeOut: <?=json_encode($paymodeOut);?>,
    cashin: <?=$totalCashin;?>,
    cashout: <?=$totalCashout;?>,
    balance: <?=$currentBalance;?>
};

new Chart(document.getElementById('monthlyCashChart'), {
    type: 'bar', data: {labels: dashboardData.monthlyLabels, datasets: [
        {label:'Cash In', data:dashboardData.monthlyCashin, backgroundColor:'rgba(15,118,110,.75)', borderRadius:7},
        {label:'Cash Out', data:dashboardData.monthlyCashout, backgroundColor:'rgba(220,38,38,.70)', borderRadius:7}
    ]}, options:{responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false}, scales:{y:{beginAtZero:true,ticks:{callback:v=>Number(v).toLocaleString()}}}, plugins:{tooltip:{callbacks:{label:c=>`${c.dataset.label}: ${Number(c.parsed.y).toLocaleString()}/=`}}}}
});

new Chart(document.getElementById('cashPositionChart'), {
    type:'doughnut', data:{labels:['Cash In','Cash Out','Net Balance'],datasets:[{data:[dashboardData.cashin,dashboardData.cashout,Math.abs(dashboardData.balance)],backgroundColor:['rgba(15,118,110,.8)','rgba(220,38,38,.75)','rgba(59,130,246,.75)'],borderWidth:0}]}, options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'},tooltip:{callbacks:{label:c=>`${c.label}: ${Number(c.parsed).toLocaleString()}/=`}}}}
});

new Chart(document.getElementById('paymodeChart'), {
    type:'bar', data:{labels:dashboardData.paymodeLabels,datasets:[{label:'Cash In',data:dashboardData.paymodeIn,backgroundColor:'rgba(15,118,110,.75)',borderRadius:6},{label:'Cash Out',data:dashboardData.paymodeOut,backgroundColor:'rgba(220,38,38,.7)',borderRadius:6}]}, options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',scales:{x:{beginAtZero:true,ticks:{callback:v=>Number(v).toLocaleString()}}},plugins:{tooltip:{callbacks:{label:c=>`${c.dataset.label}: ${Number(c.parsed.x).toLocaleString()}/=`}}}}
});
</script>
