    <?php
        require_once('../assets/functions.php');
        if(isVerified())
        {
            $id = request('bkid');
            $id = encryptor('decrypt',$id);
            $book = bookFind($id);
            if (!$book) { http_response_code(404); exit('Book not found.'); }
            $bookId = (int)$book->id;
            $scope = khTransactionScope($bookId);
            $canCreate = khCan('transactions.create', $bookId);
            $canEdit = khCan('transactions.edit', $bookId);
            $canDelete = khCan('transactions.delete', $bookId);
            $canSummary = khCan('transactions.summary', $bookId);
            $canReport = khCan('reports.view', $bookId);
            $canDashboard = khCan('dashboard.view', $bookId);
            $canManageRoles = khCan('roles.manage', $bookId);
            $canManageItems = khCan('items.view', $bookId);
            $canManageCustomers = khCan('customers.view', $bookId);
            $canManageCategories = khCan('categories.view', $bookId);
            $canManagePaymodes = khCan('paymodes.view', $bookId);
            $canStock = khCan('stock.view', $bookId);
            if ($scope === 'none' && !$canCreate) {
                $bookToken = rawurlencode((string)request('bkid'));
                $destinations = [
                    ['stock.view', '../stock/?bkid='],
                    ['stocktake.view', '../stock/stocktake/?bkid='],
                    ['invoices.view', '../invoices/?bsid='],
                    ['customers.view', '../customers/?bkid='],
                    ['reports.view', '../report/?bkid='],
                    ['dashboard.view', '../dashboard/?bkid=']
                ];
                foreach ($destinations as [$permission, $url]) {
                    if (khCan($permission, $bookId)) {
                        header('Location: '.$url.$bookToken);
                        exit;
                    }
                }
                http_response_code(403);
                exit('No authorized modules are assigned for this book.');
            }
            setBook($book);
            pageHeader('Book-details');

            /*
            |--------------------------------------------------------------------------
            | BUSINESS PROFILE / LOGO
            |--------------------------------------------------------------------------
            | The logo belongs to the business profile, not the cashbook.
            | Reuse the same helpers already used elsewhere in KashHub.
            */
            $business = null;
            if($book && !empty($book->business_id)){
                $business = businessFindId($book->business_id);
            }

            // Fallback to the authenticated user's business where necessary.
            if(!$business){
                $business = businessFind(auth());
            }

            $businessName = (!empty($business->name)) ? $business->name : 'Business';
            $businessLogo = businessLogo($business);
    ?>
            <style>
                :root{--kh-primary:#2563eb;--kh-primary-dark:#1d4ed8;--kh-success:#16a34a;--kh-danger:#dc2626;--kh-text:#172033;--kh-muted:#6b7280;--kh-border:#e5e7eb;--kh-bg:#f5f7fb;--kh-soft:#f8fafc}
                body{background:var(--kh-bg)!important;color:var(--kh-text)}
                .kh-book-page{max-width:1450px;margin:auto;padding:22px 15px 45px}.kh-book-page *{box-sizing:border-box}
                .kh-header,.kh-quick,.main-display{background:#fff;border:1px solid var(--kh-border)!important;border-radius:0!important}
                .kh-header{padding:20px 22px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:18px}.kh-title{display:flex;align-items:center;gap:13px;min-width:0}.kh-business-logo{width:52px;height:52px;flex:0 0 52px;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid var(--kh-border);overflow:hidden}.kh-business-logo img{width:100%;height:100%;object-fit:contain;padding:4px;display:block}.kh-title h2{margin:0;font-size:21px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.kh-title p{margin:4px 0 0;color:var(--kh-muted);font-size:16px}.kh-header-actions,.kh-actions{display:flex;gap:7px;flex-wrap:wrap}.kh-header .btn,.kh-quick .btn,.kh-cashbar .btn{border-radius:0!important;font-size:12px;font-weight:600;min-height:38px;display:inline-flex;align-items:center;justify-content:center;gap:6px}
                .kh-quick{padding:14px 18px;margin-bottom:14px}.kh-quick-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}.kh-quick-head strong{font-size:13px}.kh-quick-head span{font-size:18px;color:var(--kh-muted)}
                .main-display{padding:0!important;overflow:visible}.kh-cashbar{padding:16px 18px;border-bottom:1px solid var(--kh-border);display:flex;align-items:center;justify-content:space-between;gap:12px}.kh-cashbar h5{margin:0;font-size:15px;font-weight:700}.kh-cashbar small{font-size:18px;color:var(--kh-muted)}.kh-cash-actions{display:flex;gap:7px;flex-wrap:wrap}.kh-cash-actions .btn{min-width:105px}
                .kh-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:16px 18px;border-bottom:1px solid var(--kh-border)}.book-dash{position:relative;margin:0!important;padding:15px!important;min-height:100px;background:#fff;border:1px solid var(--kh-border)!important;border-radius:0!important}.book-dash:before{content:'';position:absolute;left:-1px;top:-1px;bottom:-1px;width:4px;background:#64748b}.book-dash.in:before{background:var(--kh-success)}.book-dash.out:before{background:var(--kh-danger)}.book-dash.balance:before{background:var(--kh-primary)}.kh-stat-icon{width:30px;height:30px;display:flex;align-items:center;justify-content:center;background:var(--kh-soft);margin-bottom:10px;color:#64748b}.in .kh-stat-icon{background:#f0fdf4;color:var(--kh-success)}.out .kh-stat-icon{background:#fef2f2;color:var(--kh-danger)}.balance .kh-stat-icon{background:#eff6ff;color:var(--kh-primary)}.kh-stat-label{font-size:10px;font-weight:700;color:var(--kh-muted);text-transform:uppercase;letter-spacing:.4px}.kh-stat-value{font-size:19px;font-weight:700;margin-top:3px;color:var(--kh-text)}
                .transaction-report-toolbar{padding:18px 18px 12px;display:flex;align-items:center;justify-content:space-between;gap:12px}.transaction-report-toolbar h5{font-size:15px;font-weight:700}.transaction-report-toolbar small{font-size:18px}.transaction-report-actions{display:flex;gap:7px}.transaction-report-actions .btn{border-radius:0!important;font-size:18px;font-weight:700;min-height:35px}.filter-active-count{display:inline-flex;min-width:18px;height:18px;align-items:center;justify-content:center;margin-left:4px;background:#fff;color:var(--kh-primary);font-size:10px}
                .transaction-table-wrapper{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--kh-border);background:#fff}.transaction-table-wrapper table{min-width:900px;margin:0!important;font-size:18px}.transaction-table-wrapper thead th{background:#f8fafc;color:#475569;border-top:0!important;border-bottom:1px solid var(--kh-border)!important;padding:10px!important;font-size:10px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap}.transaction-table-wrapper tbody td{padding:10px!important;vertical-align:middle!important;border-top:1px solid #f0f2f5!important}.transaction-table-wrapper tbody tr:hover{background:#f8fbff!important}.transaction-table-wrapper .btn{border-radius:0!important}.mobile-table-hint{display:none;font-size:18px;color:var(--kh-muted);background:#f8fafc;border:1px solid var(--kh-border);border-bottom:0;padding:8px 10px}
                .transaction-filter-overlay{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:1040;opacity:0;visibility:hidden;transition:.2s}.transaction-filter-overlay.open{opacity:1;visibility:visible}.transaction-filter-drawer{position:fixed;right:-390px;top:0;width:370px;max-width:92vw;height:100vh;background:#fff;z-index:1050;display:flex;flex-direction:column;box-shadow:-10px 0 30px rgba(15,23,42,.12);transition:right .25s}.transaction-filter-drawer.open{right:0}.filter-drawer-header{min-height:74px;padding:17px 19px;border-bottom:1px solid var(--kh-border);display:flex;justify-content:space-between;align-items:center}.filter-drawer-header h5{margin:0;font-size:15px;font-weight:700}.filter-drawer-header small{font-size:18px;color:var(--kh-muted)}.filter-drawer-close{width:34px;height:34px;border:1px solid var(--kh-border);background:#fff;font-size:22px}.filter-drawer-body{padding:19px;overflow-y:auto;flex:1}.filter-group{margin-bottom:16px}.filter-group label{display:block;font-size:18px;font-weight:700;color:#374151;margin-bottom:6px}.filter-group .form-control{border-radius:0!important;height:41px;border:1px solid #d8dde5;box-shadow:none!important;font-size:13px}.filter-group .form-control:focus{border-color:var(--kh-primary)}.filter-drawer-footer{border-top:1px solid var(--kh-border);padding:14px 19px;display:flex;justify-content:space-between;gap:8px}.filter-drawer-footer .btn{border-radius:0!important;min-height:38px;font-size:12px;font-weight:600}body.filter-drawer-open{overflow:hidden}
                .side-modal-tall{position:fixed!important;top:0!important;right:0!important;height:100vh!important;width:460px!important;max-width:95vw!important;z-index:1060!important;border:0!important;border-left:1px solid var(--kh-border)!important;border-radius:0!important;box-shadow:-12px 0 35px rgba(15,23,42,.16)!important;overflow-y:auto!important}.side-modal-header{min-height:68px;padding:14px 19px!important;display:flex!important;align-items:center!important;justify-content:space-between!important;background:var(--kh-primary)!important}.side-modal-title{margin:0!important;font-size:15px!important;font-weight:700!important}.side-modal-close{width:34px;height:34px;border:1px solid rgba(255,255,255,.4);background:transparent;color:#fff;font-size:22px}.side-modal-content{padding:19px!important}.side-modal-content .form-control,.side-modal-content .btn{border-radius:0!important}
                @media(max-width:991.98px){.kh-summary{grid-template-columns:repeat(2,1fr)}}
                @media(max-width:767.98px){.kh-book-page{padding:12px 8px 30px}.kh-header{padding:15px;display:block}.kh-business-logo{width:46px;height:46px;flex-basis:46px}.kh-title h2{font-size:18px}.kh-header-actions{margin-top:13px}.kh-header-actions .btn{flex:1}.kh-quick{padding:13px}.kh-actions{display:grid;grid-template-columns:repeat(2,1fr)}.kh-actions .btn{margin:0!important}.kh-cashbar{display:block;padding:14px}.kh-cash-actions{margin-top:12px;display:grid;grid-template-columns:1fr 1fr}.kh-cash-actions .btn{width:100%;margin:0!important}.kh-cash-actions .report-btn{grid-column:1/-1}.kh-summary{grid-template-columns:1fr 1fr;gap:8px;padding:11px}.book-dash{min-height:94px;padding:12px!important}.kh-stat-value{font-size:17px}.transaction-report-toolbar{padding:14px 12px 10px;align-items:flex-start}.transaction-report-toolbar small{display:none}.mobile-table-hint{display:block}.side-modal-tall{width:100%!important;max-width:100%!important}}
                @media(max-width:420px){.kh-summary{grid-template-columns:1fr}.kh-actions{grid-template-columns:1fr 1fr}}
            </style>

            <div class="kh-book-page">
                <div class="kh-header">
                    <div class="kh-title">
                        <div class="kh-business-logo">
                            <img src="<?=htmlspecialchars($businessLogo,ENT_QUOTES,'UTF-8');?>" alt="<?=htmlspecialchars($businessName,ENT_QUOTES,'UTF-8');?>">
                        </div>
                        <div>
                            <h2><?=htmlspecialchars($book->name,ENT_QUOTES,'UTF-8');?></h2>
                            <p>
                                <strong><?=htmlspecialchars($businessName,ENT_QUOTES,'UTF-8');?></strong>
                                &nbsp;&bull;&nbsp;
                                <i class="far fa-calendar-alt mr-1"></i><?=date('l, d M Y');?>
                                &nbsp;&bull;&nbsp; Manage transactions and daily activity
                            </p>
                        </div>
                    </div>
                    <div class="kh-header-actions"><?php if($canDashboard):?><a href="../dashboard/?bkid=<?=request('bkid');?>" class="btn btn-primary btn-flat"><i class="fas fa-chart-line"></i> Dashboard</a><?php endif; if($canReport):?><a href="../report/?bkid=<?=request('bkid');?>" class="btn btn-outline-secondary btn-flat"><i class="fas fa-chart-bar"></i> Reports</a><?php endif;?></div>
                </div>
                <?php if($canStock || $canManageCategories || $canManageCustomers || $canManageRoles || $canManageItems || $canManagePaymodes):?>
                <div class="kh-quick">
                    <div class="kh-quick-head"><strong><i class="fas fa-bolt mr-1 text-primary"></i> Quick Actions</strong><span>Manage this cashbook</span></div><div class="kh-actions">
                        <?php if($canStock):?><a href="../stock/?bkid=<?=request('bkid');?>" class="btn btn-outline-primary"><i class="fas fa-boxes"></i> Stock</a><?php endif;?>
                        <?php if($canManageCategories):?><?php if($canManagePaymodes):?><button class="btn btn-outline-success btn-click" data-title="add category" data-section="category"><i class="fas fa-tags"></i> Category</button><?php endif;?><?php endif;?>
                        <?php if($canManageCustomers):?><button class="btn btn-outline-primary btn-click" data-title="add customer" data-section="customer"><i class="fas fa-user-plus"></i> Customer</button><?php endif;?>
                        <?php if($canManageRoles):?><button class="btn btn-outline-info btn-click" data-title="add member" data-section="member"><i class="fas fa-users"></i> Members</button><?php endif;?>
                        <?php if($canManageItems):?><button class="btn btn-outline-secondary btn-click" data-title="add item" data-section="item"><i class="fas fa-box"></i> Items</button><?php endif;?>
                        <button class="btn btn-outline-success btn-click" data-title="add payment mode" data-section="paymode"><i class="fas fa-wallet"></i> Payment Modes</button>
                        <span class="right border">
                        <?php
                            if(isset($_SESSION['entry_date']) && !empty($_SESSION['entry_date'])){
                                $entryDate = $_SESSION['entry_date'];
                                ?>
                                    <div class="p-2">
                                        <strong>Entry Date:</strong> <?=htmlspecialchars($entryDate,ENT_QUOTES,'UTF-8');?>
                                        <button class="btn btn-sm btn-outline-danger ml-2 resetEntryDate" id="resetEntryDate"><i class="fas fa-times"></i> Reset</button>  
                                    </div>
                                <?php
                            }
                        ?>
                        </span>
                    </div>
                </div>
                <?php endif;?>
                <div class="main-display">
                    <div class="kh-cashbar"><div><h5><i class="fas fa-exchange-alt mr-1 text-primary"></i> Today's Cashbook</h5><small>Record money coming in or going out</small>
                </div>
                <div class="kh-cash-actions">
                    <?php if($canCreate):?><button class="btn btn-success cash-in btn-click" data-title="add cashin" data-section="cashin"><i class="fas fa-arrow-down"></i> CASH IN</button>
                    <button class="btn btn-danger cash-out btn-click" data-title="add cashout" data-section="cashout"><i class="fas fa-arrow-up"></i> CASH OUT</button>
                    <?php endif; if($canReport):?><a href="../report/?bkid=<?=encryptor('encrypt',$book->id);?>" class="btn btn-outline-secondary report-btn"><i class="fas fa-file-alt"></i> REPORT</a>
                    <?php endif; if($canCreate):?><button class="btn btn-outline-primary entry-date"><i class="fas fa-arrow-up"></i> ENTRY DATE</button><?php endif;?>
                </div>
            </div>
                    <?php if($canSummary):?>
                    <?php
                        $resi=prepared_statements("SELECT SUM(amount) cashin FROM cashbook_cashins WHERE book_id=? AND created_at<DATE_FORMAT(NOW(),'%Y-%m-%d')",'i',[$id]);$rwi=$resi->fetch_assoc();$cashinbbf=(float)($rwi['cashin']??0);
                        $reso=prepared_statements("SELECT SUM(amount) cashout FROM cashbook_cashouts WHERE book_id=? AND DATE(created_at)<DATE_FORMAT(NOW(),'%Y-%m-%d')",'i',[$id]);$rwo=$reso->fetch_assoc();$cashoutbbf=(float)($rwo['cashout']??0);$bbf=$cashinbbf-$cashoutbbf;
                        $res=prepared_statements("SELECT SUM(amount) cashin FROM cashbook_cashins WHERE book_id=? AND DATE(created_at)=DATE_FORMAT(NOW(),'%Y-%m-%d')",'i',[$id]);$rw=$res->fetch_assoc();$cashin=(float)($rw['cashin']??0);
                        $ress=prepared_statements("SELECT SUM(amount) cashout FROM cashbook_cashouts WHERE book_id=? AND DATE(created_at)=DATE_FORMAT(NOW(),'%Y-%m-%d')",'i',[$id]);$rws=$ress->fetch_assoc();$cashout=(float)($rws['cashout']??0);
                    ?>
                    <div class="kh-summary">
                        <div class="book-dash"><div class="kh-stat-icon"><i class="fas fa-history"></i></div><div class="kh-stat-label">Balance Brought Forward</div><div class="kh-stat-value"><?=number_format($bbf,0);?></div></div>
                        <div class="book-dash in"><div class="kh-stat-icon"><i class="fas fa-arrow-down"></i></div><div class="kh-stat-label">Today's Cash In</div><div class="kh-stat-value"><?=number_format($cashin,0);?></div></div>
                        <div class="book-dash out"><div class="kh-stat-icon"><i class="fas fa-arrow-up"></i></div><div class="kh-stat-label">Today's Cash Out</div><div class="kh-stat-value"><?=number_format($cashout,0);?></div></div>
                        <div class="book-dash balance"><div class="kh-stat-icon"><i class="fas fa-wallet"></i></div><div class="kh-stat-label">Current Balance</div><div class="kh-stat-value"><?=number_format($bbf+$cashin-$cashout,0);?></div></div>
                    </div>
                    <?php endif;?>
                    <!-- TRANSACTION REPORT TOOLBAR -->
                    <div class="transaction-report-toolbar">
                        <div>
                            <h5 class="mb-0"><i class="fa fa-list-alt mr-1"></i> TRANSACTIONS</h5>
                            <small class="text-muted">Recent activity and filtered transaction report</small>
                        </div>
                        <div class="transaction-report-actions">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="resetFilters" title="Reset Filters">
                                <i class="fa fa-undo"></i><span class="d-none d-md-inline"> Reset</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" id="openTransactionFilters">
                                <i class="fa fa-filter"></i> FILTER
                                <span class="filter-active-count" style="display:none">0</span>
                            </button>
                        </div>
                    </div>

                    <!-- FILTER DRAWER OVERLAY -->
                    <div class="transaction-filter-overlay" id="transactionFilterOverlay"></div>

                    <!-- FILTER DRAWER -->
                    <aside class="transaction-filter-drawer" id="transactionFilterDrawer" aria-hidden="true">
                        <div class="filter-drawer-header">
                            <div>
                                <h5><i class="fa fa-filter mr-1"></i> Filter Transactions</h5>
                                <small>Choose one or more filters</small>
                            </div>
                            <button type="button" class="filter-drawer-close" id="closeTransactionFilters" aria-label="Close filters">&times;</button>
                        </div>

                        <div class="filter-drawer-body">
                            <div class="filter-group">
                                <label for="filter-date-from">From Date</label>
                                <input type="date" name="filter-date-from" id="filter-date-from"
                                    data-type="min_date" max="<?=date('Y-m-d');?>" class="form-control filter-item">
                            </div>

                            <div class="filter-group">
                                <label for="filter-date-to">To Date</label>
                                <input type="date" name="filter-date-to" id="filter-date-to"
                                    data-type="max_date" max="<?=date('Y-m-d');?>" class="form-control filter-item">
                            </div>

                            <?php
                                $sqlm="SELECT DISTINCT MONTH(created_at) month,YEAR(created_at) year
                                       FROM cashbook_transactions WHERE book_id=?".
                                       ($scope==='own' ? " AND user_id=?" : "").
                                       " ORDER BY YEAR(created_at) DESC,MONTH(created_at) DESC";
                                $months=prepared_statements($sqlm,$scope==='own'?'ii':'i',
                                    $scope==='own'?[$bookId,(int)auth()->id]:[$bookId]);
                                $month_names=[1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                                    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                            ?>
                            <div class="filter-group">
                                <label for="filter-month">Month</label>
                                <select name="filter-month" id="filter-month" data-type="month" class="form-control filter-item">
                                    <option value="">All Months</option>
                                    <?php while($rm=$months->fetch_assoc()):?>
                                        <option value="<?=$rm['month'];?>"><?=$month_names[$rm['month']];?> <?=$rm['year'];?></option>
                                    <?php endwhile;?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="filter-type">Transaction Type</label>
                                <select name="filter-type" id="filter-type" data-type="type" class="form-control filter-item">
                                    <option value="">All Types</option>
                                    <option value="credit">Cash In</option>
                                    <option value="debit">Cash Out</option>
                                </select>
                            </div>

                            <?php
                                $cats=prepared_statements("SELECT * FROM cashbook_categories WHERE book_id=? ORDER BY name ASC",'i',[$book->id]);
                            ?>
                            <div class="filter-group">
                                <label for="filter-category">Category</label>
                                <select name="filter-category" id="filter-category" data-type="category" class="form-control filter-item">
                                    <option value="">All Categories</option>
                                    <?php while($rc=$cats->fetch_assoc()):?>
                                        <option value="<?=$rc['id'];?>"><?=htmlspecialchars($rc['name']);?></option>
                                    <?php endwhile;?>
                                </select>
                            </div>

                            <?php
                                $cust=prepared_statements("SELECT * FROM cashbook_customers WHERE book_id=? ORDER BY name ASC",'i',[$book->id]);
                            ?>
                            <div class="filter-group">
                                <label for="filter-customer">Customer</label>
                                <select name="filter-customer" id="filter-customer" data-type="customer" class="form-control filter-item">
                                    <option value="">All Customers</option>
                                    <?php while($rcu=$cust->fetch_assoc()):?>
                                        <option value="<?=$rcu['id'];?>"><?=htmlspecialchars($rcu['name']);?></option>
                                    <?php endwhile;?>
                                </select>
                            </div>
                        </div>

                        <div class="filter-drawer-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearDrawerFilters"><i class="fa fa-times"></i> Clear</button>
                            <button type="button" class="btn btn-sm btn-primary" id="applyTransactionFilters"><i class="fa fa-check"></i> Apply Filters</button>
                        </div>
                    </aside>
                    <div class="row mx-1">
                        <?php
                            $stmt_ = "SELECT ct.credit_amount as credits, ct.debit_amount as debits,cc.name as category,ct.id,ct.user_id,ct.created_at,ct.details,cu.name as customer FROM cashbook_transactions ct 
                                    LEFT JOIN cashbook_categories cc ON cc.id = ct.category_id
                                    LEFT JOIn cashbook_customers cu ON cu.id = ct.customer_id
                                WHERE ct.book_id = ?".($scope==='own' ? " AND ct.user_id=?" : "").
                                " ORDER BY ct.id DESC LIMIT 20";
                            $res_ = prepared_statements($stmt_,$scope==='own'?'ii':'i',
                                $scope==='own'?[$bookId,(int)auth()->id]:[$bookId]);
                            $t = 0;
                            $cashin = 0;
                            $cashout = 0;
                        ?>
                        <div class="col p-2">
                            <h4 class="text-center">RECENT TRANSACTIONS (LAST 20)</h4>
                            <div class="mobile-table-hint"><i class="fa fa-arrows-h mr-1"></i> Swipe left/right to view Credit, Debit and Actions</div>
                            <div class="transaction-table-wrapper">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Customer</th>
                                        <th>Details</th>
                                        <th>Credit</th>
                                        <th>Debit</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody class='transactions-tbody'>
                                <?php while($r = $res_->fetch_assoc()):
                                    
                                    $cashin += $r['credits'];
                                    $debit = ($r['credits'] == 0 || empty($r['credits'])) ? $r['debits'] : 0;
                                    $cashout += $debit;
                                    ?>
                                    <tr class='transaction-details hover hover-hide-content '>
                                        <td><?=++$t;?></td>
                                        <td><?=date_format(date_create($r['created_at']), 'Y-m-d');?></td>
                                        <td><?=$r['category'];?></td>
                                        <td><?=$r['customer'];?></td>
                                        <td><?=$r['details'];?></td>
                                        <td class ="<?=$r['credits'] > 0 ? " text-primary" : "";?>"><?=number_format($r['credits'],0);?></td>
                                        <td class ="<?=$r['debits'] > 0 ? " text-danger" : "";?>"><?=number_format($debit,0);?></td>
                                        <td>
                                            <?php if(($canEdit || $canDelete) && ($scope==='all' || (int)$r['user_id']===(int)auth()->id)):?>
                                                <span class="hover-display text-sms">
                                                    <?php if($canEdit):?><button class="btn btn-sm btn-outline-info edit-trans text-muted" data-id="<?=$r['id'];?>" data-type="<?=($r['credits'] > 0) ? 'credit':'debit';?>"><i class="fa fa-edit"></i></button>
                                                    <?php endif; if($canDelete):?><button class="btn btn-sm btn-outline-danger delete-trans" data-id="<?=$r['id'];?>" data-type="<?=($r['credits'] > 0) ? 'credit':'debit';?>"><i class="fa fa-trash"></i></button><?php endif;?> 
                                                </span>
                                            <?php endif;?>
                                        </td>
                                    </tr>
                                <?php endwhile;?>
                                <tr>
                                    <th>TOTALS</th>
                                    <th colspan="4" class="text-right"></th>
                                    <th class="text-right"><?=number_format($cashin,0);?></th>
                                    <th class="text-right"><?=number_format($cashout,0);?></th>
                                    <th></th>
                                </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            </div><!-- /.kh-book-page -->

            <!-- side modal for a cash in -->
            <div class="p-0 bg-white side-modal-tall absolute border shadow" id='side-modal-cashin'>
                <div class="side-modal-header bg-success">
                    <h3 class="side-modal-title text-white"></h3>
                    <button type='button' class='side-modal-close'>&times;</button>
                </div>
                <div class="side-modal-content">
                    
                </div>
            </div>
    <?php
            pageFooter();
        }else{
            redirect('../');
        }
    ?>
    <script>
        function toggleMenu(){
            document.getElementById("navLinks").classList.toggle("active");
        }
        $(document).on('click','.cash-in',function(){
            $('#side-modal-cashin').show();
        });
        $(document).on('click','.cash-out',function(){
            $('#side-modal-cashout').show();
        });
        $(document).on('click','.side-modal-close',function(){
            $(this).closest('.side-modal-tall').hide();
        });

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
            var book_id = "<?=encryptor('decrypt',request('bkid'));?>";
            if(sect !='')
            {
                $.ajax({
                    url:'save/index.php',
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

        function submitSingleForm(formId, backendUrl) 
        {
            const form = document.getElementById(formId);
            xdialog.startSpin();
            if (!form) {
                console.error("Form not found:", formId);
                return;
            }
            
            // 🔴 VALIDATION CHECK
            if(!form.checkValidity()) 
            {
                form.reportValidity(); // shows browser messages
                xdialog.stopSpin();
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
                .then(async res => { const body = await res.text(); if (!res.ok) throw new Error(body); return body; })
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
                    xdialog.stopSpin();
                    xdialog.warn(err.message || 'Request failed');
                    console.error("AJAX error:", err);
                });
            });
        }

        // Call the function for your form
        $(document).on('click','.saveCategory',function(){
            submitSingleForm("newCategoryForm", "save/index.php");
        });
        // Call the function for your form
        $(document).on('click','.saveUser',function(){
            submitSingleForm("newUserForm", "save/index.php");
        });

        // Call the function for your form
        $(document).on('click','.saveItem',function(){
            submitSingleForm("newItemForm", "save/index.php");
        });
        // Call the function for your form
        $(document).on('click','.saveCashin',function(){
            submitSingleForm("newCashinForm", "save/index.php");
        });

        // Call the function for your form
        $(document).on('click','.saveCashout',function(){
            submitSingleForm("newCashoutForm", "save/index.php");
        });

        // Call the function for your form
        $(document).on('click','.savePaymode',function(){
            submitSingleForm("newPaymodeForm", "save/index.php");
        });
        // Call the function for your form
        $(document).on('click','.saveCustomer',function(){
            submitSingleForm("newCustomerForm", "save/index.php");
        });

        // edit transaction
        $(document).on('click','.edit-trans',function(){
            var id = $(this).data('id');
            var type = $(this).data('type');
            if(id !='')
            {
                $('.side-modal-tall').show();
                $('.side-modal-title').html('EDIT TRANSACTION');
                $.ajax({
                    url:'save/index.php',
                    data:{
                        id:id,
                        action:'editTransaction',
                        type:type
                    },
                    beforeSend:function(){
                         $('.side-modal-content').html("<h3 class='text-center'>Loading...</h3>");
                    },
                    success:function(res){
                         $('.side-modal-content').html(res);
                    },
                    error:function(err){
                        $('.side-modal-content').html(err);
                    }
                });
            }
        });

        // delete transaction #delete-trans
        $(document).on('click','.delete-trans',function(){
            var id = $(this).data('id');
            var type = $(this).data('type');
            if(id !='')
            {
                xdialog.confirm("Continue to delete transaction?",function(){
                    $.ajax({
                    url:'save/index.php',
                    data:{
                        id:id,
                        action:'DeleteTransaction',
                        type:type
                    },
                    success:function(res){
                         xdialog.info('Transaction successful');
                         console.log(res);
                        // window.location.reload();
                    },
                    error:function(err){
                        xdialog.warn('Transaction failed');
                        console.log(err);
                    }
                });
                });                
            }
        });
        
        // transaction filter drawer
        function openTransactionFilters(){
            $('#transactionFilterDrawer,#transactionFilterOverlay').addClass('open');
            $('#transactionFilterDrawer').attr('aria-hidden','false');
            $('body').addClass('filter-drawer-open');
        }
        function closeTransactionFilters(){
            $('#transactionFilterDrawer,#transactionFilterOverlay').removeClass('open');
            $('#transactionFilterDrawer').attr('aria-hidden','true');
            $('body').removeClass('filter-drawer-open');
        }
        function updateFilterCount(){
            let count=0;
            $('.filter-item').each(function(){if($(this).val()) count++;});
            $('.filter-active-count').text(count).toggle(count>0);
        }
        function loadFilteredTransactions(){
            let filters={action:'transactionFilter',book_id:"<?=$book->id;?>"};
            $('.filter-item').each(function(){let val=$(this).val();if(val) filters[$(this).data('type')]=val;});
            $.ajax({
                url:'save/index.php',type:'POST',data:filters,
                beforeSend:function(){
                    $('.transactions-tbody').html("<tr><td colspan='8' class='text-center p-4'><i class='fa fa-spinner fa-spin'></i> Loading transactions...</td></tr>");
                },
                success:function(res){$('.transactions-tbody').html(res);updateFilterCount();},
                error:function(){
                    $('.transactions-tbody').html("<tr><td colspan='8' class='text-center text-danger p-4'><i class='fa fa-exclamation-triangle'></i> Failed to load transactions</td></tr>");
                }
            });
        }

        $(document).on('click','#openTransactionFilters',openTransactionFilters);
        $(document).on('click','#closeTransactionFilters,#transactionFilterOverlay',closeTransactionFilters);
        $(document).on('click','#applyTransactionFilters',function(){loadFilteredTransactions();closeTransactionFilters();});
        $(document).on('click','#clearDrawerFilters,#resetFilters',function(){
            $('.filter-item').val('');updateFilterCount();loadFilteredTransactions();
            if(this.id==='clearDrawerFilters') closeTransactionFilters();
        });
        $(document).on('keydown',function(e){if(e.key==='Escape') closeTransactionFilters();});

        // lock customer selection
        document.addEventListener("change", function(e){
            if(e.target && e.target.id == "transaction_type")
            {

                let type = e.target.value;
                let paymode = document.getElementById("paymode_id");
                let customer = document.getElementById("customer_id");

                if(!paymode || !customer) return;

                paymode.disabled = false;
                customer.required = false;

                if(type == 'credit_sale'){
                    paymode.value = "";
                    paymode.disabled = true;
                    customer.setAttribute("required", true);
                }

                if(type == 'payment'){
                    customer.setAttribute("required", true);
                    paymode.setAttribute("required", true);
                }

                if(type == 'creditorInjection')
                {
                    $('.income-creditor').show();
                }
            }
        });

        // load customer invoices on data entry for cashin
        $(document).on('change','#customer_id',function(){
            var customer_id = $(this).val();
            if(customer_id !='')
            {
                $.ajax({
                    url: 'save/index.php',
                    data: {
                        customer_id:customer_id,
                        action:'findCustomerInvoices'
                    },
                    beforeSend:function(){
                        $('#invoice_id').html("<option>Loading...</option>");
                    },
                    success: function (res) {
                        $('#invoice_id').html(res);
                    },
                    error: function (err) {
                        $('#invoice_id').html("<option>!!!Error Loading!!!</option>");
                    }
                })
            }
        });

        // control displkay of other form items
        $(document).on('blur','#inamount',function(){
            var amount = $(this).val();
            if(amount == '')
            {
                $('.inAmount-controlled').hide();
            }else{
                $('.inAmount-controlled').show();
            }
            
        });

        // lock invoice_id section if type is cash_sale
        $(document).on('change', '#transaction_type', function()
        {
            const val = $(this).val();

            // Transaction types that do not require an invoice
            const noInvoiceTypes = [
                'cash_sale',
                'credit_sale',
                'other_income'
            ];

            const invoiceField = $('#invoice_id');

            if(noInvoiceTypes.includes(val))
            {
                invoiceField
                    .prop('disabled', true)
                    .val('')
                    .trigger('change');
            }
            else
            {
                invoiceField.prop('disabled', false);
            }
        });

        // lock and display some points of the form
        $(document).on('change','#expense_type',function(){
            var type = $(this).val();
            if(type !='')
            {
                if(type == 'purchase')
                {
                    $('.expense-customer').hide();
                    $('.purchase-expense').show();
                }else if(type == 'expense'){
                    $('.expense-customer').hide();
                    $('.purchase-expense').hide();
                }else if(type == 'creditorPayment'){
                    $('.expense-customer').hide();
                    $('.purchase-expense').hide();
                    $('.expense-creditor').show();
                }else{
                    $('.expense-customer').show();
                    $('.purchase-expense').hide();
                }
            }
        });

        // set data entry date to avoid over selecting date
        $(document).on('click','.entry-date',function(){
            const option = "<input type='date' class='form-control' id='entry_date' name='entry_date' max='<?=date('Y-m-d');?>'>";
            xdialog.confirm("Select the date for data entry (YYYY-MM-DD):"+ option,function(){
                var date = $('#entry_date').val();
                if(date !='')
                {
                    $.ajax({
                        url:'save/index.php',
                        data:{
                            action:'setEntryDate',
                            entry_date:date
                        },
                        success:function(res){
                            xdialog.info('Entry date set to '+date);
                            window.location.reload();
                        },
                        error:function(err){
                            xdialog.warn('Failed to set entry date');
                        }
                    });
                }else{
                    xdialog.warn('Please select a date');
                }
            });
        });
        $(document).on('click','.resetEntryDate',function(){
            xdialog.confirm("Reset entry date to default? - Today",function(){
                $.ajax({
                    url:'save/index.php',
                    data:{
                        action:'ResetsetEntryDate',
                    },
                    success:function(res){
                        xdialog.info('Entry date reset to default');
                        window.location.reload();
                    },
                    error:function(err){
                        xdialog.warn('Failed to set entry date');
                    }
                });
            });
        })
    </script>