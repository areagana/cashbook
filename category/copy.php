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
                            <a href="../report/?bkid=<?=$id_enc;?>" class="btn btn-sm btn-outline-light">Generate Report</a>
                            <a href="../books/?bkid=<?=$id_enc;?>" class="btn btn-sm btn-outline-light">Transact</a>
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
                    <div class="col-md-2"><div class="cb-card metric-card"><div class="metric-label">This Month Out</div><div class="metric-value amount-out"><?=number_format($monthCashout,0);?>/=</div></div></div>
                    <div class="col-md-2"><div class="cb-card metric-card"><div class="metric-label">This Month Net</div><div class="metric-value <?=($monthCashin-$monthCashout) < 0 ? 'balance-negative':'balance-positive';?>"><?=number_format($monthCashin-$monthCashout,0);?>/=</div></div></div>
                    <!-- <div class="col-md-2"><div class="cb-card metric-card"><div class="metric-label">Transactions</div><div class="metric-value"></div><div class="metric-value"><?=number_format($totalTransactions);?></div></div></div> -->
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-xl-8"><div class="cb-card p-3"><div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="section-title">Monthly Cash Movement</h5><div class="section-sub">Cash in versus cash out for <?=$year;?></div></div><span class="badge-cb">Annual Trend</span></div><div class="chart-box"><canvas id="monthlyCashChart"></canvas></div></div></div>
                    <div class="col-xl-4"><div class="cb-card p-3 h-100"><div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="section-title">Cash Position</h5><div class="section-sub">Overall financial distribution</div></div></div><div class="mini-chart"><canvas id="cashPositionChart"></canvas></div></div></div>
                </div>
            </div>
        </div>