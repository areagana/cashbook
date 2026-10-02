<?php
require_once(__DIR__.'/../assets/functions.php');

if (!hasRole(['superadministrator'])) {
    redirect('../');
    exit;
}

pageHeader('Super Admin Dashboard');
$year = (int)date('Y');

function saRows($result): array {
    $rows = [];
    if ($result && is_object($result)) while ($row = $result->fetch_assoc()) $rows[] = $row;
    return $rows;
}
function saScalar($sql, $key = 'total', $types = '', $params = [], $default = 0) {
    $result = $types !== '' ? prepared_statements($sql, $types, $params) : mysqli_query($GLOBALS['server'], $sql);
    if (!$result || !is_object($result)) return $default;
    $row = $result->fetch_assoc();
    return $row[$key] ?? $default;
}
function saField(array $row, array $keys, $default = '') {
    foreach ($keys as $key) if (isset($row[$key]) && $row[$key] !== '') return $row[$key];
    return $default;
}
function saDate($value): string {
    if (!$value) return '—';
    $t = strtotime($value);
    return $t ? date('d M Y', $t) : '—';
}

/* =========================================================
   PLATFORM SUMMARY
   ========================================================= */
$totalBusinesses = (int)saScalar("SELECT COUNT(*) AS total FROM cashbook_business_profile");
$totalBooks = (int)saScalar("SELECT COUNT(*) AS total FROM cashbook_books");
$totalUsers = (int)saScalar("SELECT COUNT(*) AS total FROM cashbook_users");
$totalTransactions = (int)saScalar("SELECT COUNT(*) AS total FROM cashbook_transactions");
$activeBooks30 = (int)saScalar("SELECT COUNT(DISTINCT book_id) AS total FROM cashbook_transactions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$newBusinessesMonth = (int)saScalar("SELECT COUNT(*) AS total FROM cashbook_business_profile WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$newBooksMonth = (int)saScalar("SELECT COUNT(*) AS total FROM cashbook_books WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$newUsersMonth = (int)saScalar("SELECT COUNT(*) AS total FROM cashbook_users WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$transactionsToday = (int)saScalar("SELECT COUNT(*) AS total FROM cashbook_transactions WHERE DATE(created_at) = CURDATE()");
$dormantBooks = max(0, $totalBooks - $activeBooks30);

/* =========================================================
   MONTHLY PLATFORM GROWTH
   ========================================================= */
$bookGrowth = saRows(mysqli_query($server, "SELECT MONTH(created_at) month_no, COUNT(*) total FROM cashbook_books WHERE YEAR(created_at)={$year} GROUP BY MONTH(created_at)"));
$userGrowth = saRows(mysqli_query($server, "SELECT MONTH(created_at) month_no, COUNT(*) total FROM cashbook_users WHERE YEAR(created_at)={$year} GROUP BY MONTH(created_at)"));
$txGrowth   = saRows(mysqli_query($server, "SELECT MONTH(created_at) month_no, COUNT(*) total FROM cashbook_transactions WHERE YEAR(created_at)={$year} GROUP BY MONTH(created_at)"));

$mapBooks=[]; $mapUsers=[]; $mapTx=[];
foreach($bookGrowth as $r) $mapBooks[(int)$r['month_no']] = (int)$r['total'];
foreach($userGrowth as $r) $mapUsers[(int)$r['month_no']] = (int)$r['total'];
foreach($txGrowth as $r) $mapTx[(int)$r['month_no']] = (int)$r['total'];
$months=[]; $monthlyBooks=[]; $monthlyUsers=[]; $monthlyTx=[];
for($m=1;$m<=12;$m++) {
    $months[] = date('M', mktime(0,0,0,$m,1));
    $monthlyBooks[] = $mapBooks[$m] ?? 0;
    $monthlyUsers[] = $mapUsers[$m] ?? 0;
    $monthlyTx[] = $mapTx[$m] ?? 0;
}

/* =========================================================
   SUPPORT / OPERATIONS DATA
   We SELECT b.* so this page remains tolerant of extra book fields.
   ========================================================= */
$businesses = saRows(mysqli_query($server, "SELECT bp.*,
        COALESCE(bs.book_count,0) AS book_count,
        COALESCE(us.user_count,0) AS user_count,
        bs.latest_book
    FROM cashbook_business_profile bp
    LEFT JOIN (
        SELECT business_id, COUNT(*) AS book_count, MAX(created_at) AS latest_book
        FROM cashbook_books
        GROUP BY business_id
    ) bs ON bs.business_id = bp.id
    LEFT JOIN (
        SELECT business_id, COUNT(*) AS user_count
        FROM cashbook_users
        GROUP BY business_id
    ) us ON us.business_id = bp.id
    ORDER BY bp.id DESC
    LIMIT 20"));

$recentBooks = saRows(mysqli_query($server, "SELECT b.*, COALESCE(x.transactions,0) transactions, x.last_activity
    FROM cashbook_books b
    LEFT JOIN (
        SELECT book_id, COUNT(*) transactions, MAX(created_at) last_activity
        FROM cashbook_transactions GROUP BY book_id
    ) x ON x.book_id=b.id
    ORDER BY b.created_at DESC LIMIT 10"));

$activeBooks = saRows(mysqli_query($server, "SELECT b.*, COUNT(t.id) transactions, MAX(t.created_at) last_activity
    FROM cashbook_books b
    INNER JOIN cashbook_transactions t ON t.book_id=b.id
    GROUP BY b.id
    ORDER BY transactions DESC, last_activity DESC LIMIT 8"));

$recentUsers = saRows(mysqli_query($server, "SELECT * FROM cashbook_users ORDER BY created_at DESC LIMIT 10"));
?>

<style>
.sa-dashboard{background:#f6f8fb;min-height:100vh;padding-bottom:35px}.sa-hero{background:linear-gradient(135deg,#111827,#1f4b99);color:#fff;padding:26px;border-radius:10px;margin-bottom:18px;box-shadow:0 8px 24px rgba(15,23,42,.12)}
.sa-hero h2{font-weight:700;margin:0 0 5px}.sa-hero p{margin:0;color:rgba(255,255,255,.78)}.sa-card{background:#fff;border:1px solid #e7ebf0;border-radius:8px;box-shadow:0 3px 12px rgba(15,23,42,.04)}
.metric-card{padding:18px;height:100%}.metric-label{text-transform:uppercase;font-size:11px;letter-spacing:.65px;font-weight:700;color:#778195}.metric-value{font-size:26px;font-weight:750;color:#172033;margin:5px 0}.metric-sub{font-size:12px;color:#8a94a6}.metric-icon{width:42px;height:42px;display:flex;align-items:center;justify-content:center;background:#eef4ff;color:#1f5ebd;font-size:18px}
.section-title{font-weight:700;color:#202938;margin:0}.section-sub{font-size:12px;color:#8a94a6;margin-top:3px}.chart-box{height:285px}.mini-chart{height:245px}.sa-badge{display:inline-block;padding:4px 8px;background:#edf3ff;color:#2456a6;font-size:11px;font-weight:700}.status-live{color:#087f5b}.status-dormant{color:#b45309}.support-btn{border-radius:0}.table thead th{font-size:11px;text-transform:uppercase;letter-spacing:.45px;color:#788397;border-top:0}.table td{vertical-align:middle}.avatar{width:34px;height:34px;background:#edf3ff;color:#2456a6;display:inline-flex;align-items:center;justify-content:center;font-weight:700;margin-right:8px}.search-box{max-width:300px}.quick-link{display:block;padding:13px 14px;border-bottom:1px solid #edf0f4;color:#25324a;text-decoration:none}.quick-link:last-child{border-bottom:0}.quick-link:hover{background:#f8fafc;text-decoration:none}.empty-state{padding:30px;text-align:center;color:#8a94a6}
@media(max-width:767px){.sa-hero{padding:20px}.metric-value{font-size:22px}.chart-box{height:240px}}
</style>

<div class="container-fluid sa-dashboard">
<div class="container-fluid px-lg-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between py-3">
        <div><strong>System Administration</strong> <span class="text-muted">/ Overview</span></div>
        <div class="text-muted small"><i class="fa fa-calendar"></i> <?=date('l, d M Y');?></div>
    </div>

    <div class="sa-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2><i class="fa fa-shield"></i> KashHub Super Admin</h2>
                <p>Platform overview, book monitoring, user support and operational activity.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="../businesses/" class="btn btn-sm btn-outline-light support-btn"><i class="fa fa-building"></i> Businesses</a>
                <a href="../books/" class="btn btn-sm btn-outline-light support-btn"><i class="fa fa-book"></i> All Books</a>
                <a href="../users/" class="btn btn-sm btn-outline-light support-btn"><i class="fa fa-users"></i> All Users</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3"><div class="sa-card metric-card"><div class="d-flex justify-content-between"><div><div class="metric-label">Businesses</div><div class="metric-value"><?=number_format($totalBusinesses);?></div><div class="metric-sub">+<?=number_format($newBusinessesMonth);?> added this month</div></div><div class="metric-icon"><i class="fa fa-building"></i></div></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="sa-card metric-card"><div class="d-flex justify-content-between"><div><div class="metric-label">Total Books</div><div class="metric-value"><?=number_format($totalBooks);?></div><div class="metric-sub">Across all businesses</div></div><div class="metric-icon"><i class="fa fa-book"></i></div></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="sa-card metric-card"><div class="d-flex justify-content-between"><div><div class="metric-label">Total Users</div><div class="metric-value"><?=number_format($totalUsers);?></div><div class="metric-sub">+<?=number_format($newUsersMonth);?> registered this month</div></div><div class="metric-icon"><i class="fa fa-users"></i></div></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="sa-card metric-card"><div class="d-flex justify-content-between"><div><div class="metric-label">Active Books</div><div class="metric-value status-live"><?=number_format($activeBooks30);?></div><div class="metric-sub">Activity in the last 30 days</div></div><div class="metric-icon"><i class="fa fa-line-chart"></i></div></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="sa-card metric-card"><div class="metric-label">Dormant / No recent activity</div><div class="metric-value status-dormant"><?=number_format($dormantBooks);?></div><div class="metric-sub">Books without transaction activity in the last 30 days</div></div></div>
        <div class="col-md-4"><div class="sa-card metric-card"><div class="metric-label">Transactions</div><div class="metric-value"><?=number_format($totalTransactions);?></div><div class="metric-sub"><?=number_format($transactionsToday);?> recorded today</div></div></div>
        <div class="col-md-4"><div class="sa-card metric-card"><div class="metric-label">New Users This Month</div><div class="metric-value"><?=number_format($newUsersMonth);?></div><div class="metric-sub">Recently registered accounts</div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8"><div class="sa-card p-3"><div class="d-flex justify-content-between mb-3"><div><h5 class="section-title">Platform Growth</h5><div class="section-sub">Books and users created during <?=$year;?></div></div><span class="sa-badge">Annual trend</span></div><div class="chart-box"><canvas id="growthChart"></canvas></div></div></div>
        <div class="col-xl-4"><div class="sa-card p-3 h-100"><div class="mb-3"><h5 class="section-title">Platform Activity</h5><div class="section-sub">Monthly transaction volume</div></div><div class="mini-chart"><canvas id="activityChart"></canvas></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="sa-card p-3 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div><h5 class="section-title">Business Directory</h5><div class="section-sub">Businesses on KashHub with book and user totals — no financial records exposed here</div></div>
                    <input type="search" id="businessSearch" class="form-control form-control-sm search-box" placeholder="Search businesses...">
                </div>
                <div class="table-responsive">
                    <table class="table mb-0" id="businessTable">
                        <thead><tr><th>Business</th><th>Contact</th><th>Books</th><th>Users</th><th>Latest Book</th><th class="text-end">Manage</th></tr></thead>
                        <tbody>
                        <?php if($businesses): foreach($businesses as $biz):
                            $businessId=(int)($biz['id']??0);
                            $businessName=saField($biz,['name','business_name'],'Business #'.$businessId);
                            $email=saField($biz,['email'],'');
                            $contact=saField($biz,['contact1','contact2'],''); ?>
                            <tr>
                                <td><strong><?=htmlspecialchars($businessName);?></strong><div class="small text-muted">Business #<?=$businessId;?><?=!empty($biz['reg_no'])?' · '.htmlspecialchars($biz['reg_no']):'';?></div></td>
                                <td><?=htmlspecialchars($contact ?: '—');?><div class="small text-muted"><?=htmlspecialchars($email);?></div></td>
                                <td><span class="sa-badge"><?=number_format((int)($biz['book_count']??0));?> book<?=((int)($biz['book_count']??0)===1?'':'s');?></span></td>
                                <td><?=number_format((int)($biz['user_count']??0));?></td>
                                <td><?=saDate($biz['latest_book']??null);?></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary support-btn" href="../businesses/?id=<?=urlencode(encryptor('encrypt',$businessId));?>"><i class="fa fa-building"></i> View</a></td>
                            </tr>
                        <?php endforeach; else:?><tr><td colspan="6" class="empty-state">No businesses found.</td></tr><?php endif;?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="sa-card h-100">
                <div class="p-3"><h5 class="section-title">Quick Administration</h5><div class="section-sub">Common support actions</div></div>
                <a class="quick-link" href="../businesses/"><i class="fa fa-building fa-fw"></i> Browse businesses <i class="fa fa-angle-right float-end"></i></a>
                <a class="quick-link" href="../books/"><i class="fa fa-book fa-fw"></i> Browse all books <i class="fa fa-angle-right float-end"></i></a>
                <a class="quick-link" href="../users/"><i class="fa fa-users fa-fw"></i> Manage users <i class="fa fa-angle-right float-end"></i></a>
                <a class="quick-link" href="../logs/"><i class="fa fa-history fa-fw"></i> System activity / logs <i class="fa fa-angle-right float-end"></i></a>
                <a class="quick-link" href="../support/"><i class="fa fa-life-ring fa-fw"></i> Support requests <i class="fa fa-angle-right float-end"></i></a>
                <div class="p-3 small text-muted">Keep support access auditable. When an administrator opens a user's book, record who accessed it, when, and the reason for support.</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7"><div class="sa-card p-3"><div class="mb-3"><h5 class="section-title">Most Active Books</h5><div class="section-sub">Books with the highest transaction counts</div></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Book</th><th>Transactions</th><th>Last Activity</th><th></th></tr></thead><tbody>
        <?php if($activeBooks): foreach($activeBooks as $b): $bid=(int)$b['id']; $bn=saField($b,['name','book_name','title'],'Book #'.$bid);?><tr><td><strong><?=htmlspecialchars($bn);?></strong></td><td><?=number_format((int)$b['transactions']);?></td><td><?=saDate($b['last_activity']);?></td><td class="text-end"><a href="../books/?bkid=<?=urlencode(encryptor('encrypt',$bid));?>" class="btn btn-sm btn-outline-secondary support-btn">View</a></td></tr><?php endforeach; else:?><tr><td colspan="4" class="empty-state">No activity found.</td></tr><?php endif;?>
        </tbody></table></div></div></div>

        <div class="col-xl-5"><div class="sa-card p-3"><div class="mb-3"><h5 class="section-title">Newest Users</h5><div class="section-sub">Recently registered accounts requiring onboarding/support</div></div>
        <?php if($recentUsers): foreach($recentUsers as $u): $uid=(int)($u['id']??0); $un=saField($u,['name','full_name','username'],'User #'.$uid); $email=saField($u,['email','username'],''); ?>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div class="d-flex align-items-center"><span class="avatar"><?=htmlspecialchars(strtoupper(substr($un,0,1)));?></span><div><strong><?=htmlspecialchars($un);?></strong><div class="small text-muted"><?=htmlspecialchars($email);?> · <?=saDate(saField($u,['created_at','date_created']));?></div></div></div>
                <a href="../users/?id=<?=urlencode(encryptor('encrypt',$uid));?>" class="btn btn-sm btn-outline-secondary support-btn">Support</a>
            </div>
        <?php endforeach; else:?><div class="empty-state">No users found.</div><?php endif;?>
        </div></div>
    </div>
</div>
</div>

<?php pageFooter(); ?>
<script>
const saData = {
    labels: <?=json_encode($months);?>,
    books: <?=json_encode($monthlyBooks);?>,
    users: <?=json_encode($monthlyUsers);?>,
    transactions: <?=json_encode($monthlyTx);?>
};
new Chart(document.getElementById('growthChart'), {
    type:'bar', data:{labels:saData.labels,datasets:[
        {label:'New Books',data:saData.books,backgroundColor:'rgba(37,99,235,.75)',borderRadius:5},
        {label:'New Users',data:saData.users,backgroundColor:'rgba(15,118,110,.72)',borderRadius:5}
    ]}, options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}
});
new Chart(document.getElementById('activityChart'), {
    type:'line', data:{labels:saData.labels,datasets:[{label:'Transactions',data:saData.transactions,borderColor:'rgba(37,99,235,.9)',backgroundColor:'rgba(37,99,235,.12)',fill:true,tension:.35}]},
    options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}
});
document.getElementById('businessSearch')?.addEventListener('input', function(){
    const q=this.value.toLowerCase().trim();
    document.querySelectorAll('#businessTable tbody tr').forEach(row=>{ row.style.display=row.innerText.toLowerCase().includes(q)?'':'none'; });
});
</script>
