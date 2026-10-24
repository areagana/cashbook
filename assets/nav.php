<?php
    function sideBar()
    {
        global $book;

        /*
        |--------------------------------------------------------------------------
        | GET BOOK ID
        |--------------------------------------------------------------------------
        */

        $encryptedBookId =
            $_REQUEST['bkid']
            ?? $_REQUEST['bsid']
            ?? $_SESSION['book_id']
            ?? null;

        if (empty($encryptedBookId)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | DECRYPT BOOK ID
        |--------------------------------------------------------------------------
        */

        $book_id = encryptor(
            'decrypt',
            $encryptedBookId
        );

        /*
        |--------------------------------------------------------------------------
        | FIND BOOK
        |--------------------------------------------------------------------------
        */

        $book = bookFind((int)$book_id);

        if (!$book) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | FIND BUSINESS THAT OWNS THIS BOOK
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | The company logo belongs to cashbook_business_profile,
        | NOT cashbook_books.
        |
        */

        $business = null;

        if (!empty($book->business_id)) {
            $business = businessFindId($book->business_id);
        }

        /*
        |--------------------------------------------------------------------------
        | BUSINESS INFORMATION
        |--------------------------------------------------------------------------
        */

        $businessName =
            !empty($business->name)
            ? $business->name
            : 'Business';

        $bookName =
            !empty($book->name)
            ? $book->name
            : 'Business Book';

        /*
        |--------------------------------------------------------------------------
        | BUSINESS LOGO
        |--------------------------------------------------------------------------
        */
        $businessLogo = businessLogo($business);
        /*
        |--------------------------------------------------------------------------
        | ENCRYPT BOOK ID ONCE
        |--------------------------------------------------------------------------
        */

        $bsid = encryptor(
            'encrypt',
            $book->id
        );

        ?>

        <!-- =============================================================
            MOBILE MENU
        ============================================================== -->

        <button id="mobile-menu-btn" type="button" aria-label="Open navigation" aria-expanded="false">
            <i class="fa fa-bars"></i>
        </button>

        <!-- =============================================================
            MOBILE OVERLAY
        ============================================================== -->

        <div id="sidebar-overlay"></div>

        <!-- =============================================================
            SIDEBAR
        ============================================================== -->

        <aside id="sidebar" class="expand">

            <!-- =========================================================
                SIDEBAR HEADER
            ========================================================== -->

            <div class="sidebar-header">

                <!-- DESKTOP TOGGLE -->

                <button id="toggle-btn" type="button" aria-label="Toggle navigation" aria-expanded="true">
                    <i class="fa fa-bars"></i>
                </button>

                <!-- =====================================================
                    COMPANY BRAND
                ====================================================== -->

                <div class="sidebar-logo">

                    <a href="../dashboard/?bkid=<?=$bsid;?>" class="sidebar-company-brand" title="<?=htmlspecialchars($businessName);?>">
                        <div class="sidebar-company-logo-wrap">
                            <img
                                src="<?=htmlspecialchars($businessLogo);?>"
                                alt="<?=htmlspecialchars($businessName);?>"
                                class="sidebar-company-logo"
                            >
                        </div>
                        <div class="sidebar-company-details">
                            <span class="sidebar-company-name">
                                <?=htmlspecialchars($businessName);?>
                            </span>
                            <span class="sidebar-book-name">
                                <?=htmlspecialchars($bookName);?>
                            </span>
                        </div>
                    </a>
                </div>

                <!-- MOBILE CLOSE -->

                <button id="mobile-close-btn" type="button" aria-label="Close navigation">
                    <i class="fa fa-times"></i>
                </button>

            </div>

            <!-- =========================================================
                NAVIGATION
            ========================================================== -->

            <ul class="sidebar-nav">

                <!-- DASHBOARD -->
                <?php if(khIsSuperAdmin()):?>
                    <li class="sidebar-item">
                        <a  href="../super/?bkid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-shield-alt"></i>
                            <span>Super Admin Dashboard</span>
                        </a>
                    </li>
                <?php endif;?>
                <?php if (khCan('dashboard.view',(int)$book->id)): ?>
                        <li class="sidebar-item">
                        <a  href="../dashboard/?bkid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php if (khCan('home.view',(int)$book->id)): ?>
                    <li class="sidebar-item">
                    <a href="../home/?bkid=<?=$bsid;?>" class="sidebar-link">
                        <i class="fa fa-building"></i>
                        <span>Home</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- CUSTOMERS -->

                <?php if (khCan('customers.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../customers/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-user-friends"></i>

                        <span>Customers</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- CREDITORS -->

                <?php if (khCan('creditors.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a href="../creditors/?bsid=<?=$bsid;?>" class="sidebar-link">
                        <i class="fa fa-hand-holding-usd"></i>
                        <span>Creditors</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- STOCK: expandable navigation -->
                <?php if (khCan('stock.view',(int)$book->id) || khCan('stocktake.view',(int)$book->id)): ?>
                <?php $stockMenuId = 'kh-stock-menu-1'; ?>
                <li class="sidebar-item kh-stock-nav">
                    <button type="button" class="sidebar-link kh-stock-toggle" aria-expanded="false" aria-controls="<?=$stockMenuId;?>">
                        <i class="fa fa-warehouse"></i>
                        <span>Stock</span>
                        <i class="fa fa-chevron-down kh-stock-chevron" aria-hidden="true"></i>
                    </button>
                    <ul id="<?=$stockMenuId;?>" class="kh-stock-submenu" hidden>
                        <?php if (khCan('stock.view',(int)$book->id)): ?><li><a href="../stock/?bkid=<?=$bsid;?>"><i class="fa fa-boxes"></i> Stock Overview</a></li><?php endif; ?>
                        <?php if (khCan('stocktake.view',(int)$book->id)): ?><li><a href="../stock/stocktake/?bkid=<?=$bsid;?>"><i class="fa fa-clipboard-check"></i> Stock Taking</a></li><?php endif; ?>
                    </ul>
                </li>

                <?php endif; ?>

                <!-- ITEMS -->

                <?php if (khCan('items.view',(int)$book->id)): ?>
                    <li class="sidebar-item">
                    <a href="../items/?bsid=<?=$bsid;?>" class="sidebar-link">
                        <i class="fa fa-box-open"></i>
                        <span>Items</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- PURCHASES -->

                <?php if (khCan('purchases.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../purchases/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-shopping-cart"></i>

                        <span>Purchases</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- USERS -->

                <?php if (khCan('users.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../members/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-users"></i>

                        <span>Users</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- ROLE MANAGEMENT: owner or superadministrator (transitional) -->
                <?php if (canManageBusinessRolesNav((int)($book->business_id ?? 0))): ?>
                    <li class="sidebar-item">
                        <a href="../roles/?business_id=<?= (int)$book->business_id; ?>" class="sidebar-link">
                            <i class="fa fa-user-shield"></i>
                            <span>Roles &amp; Access</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- CATEGORIES -->

                <?php if (khCan('categories.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../category/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-tags"></i>

                        <span>Categories</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- PAY MODES -->

                <?php if (khCan('paymodes.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../modes/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-handshake"></i>

                        <span>Pay Modes</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- INVOICES -->

                <?php if (khCan('invoices.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../invoices/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-file-invoice"></i>

                        <span>Invoices</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- REPORT -->

                <?php if (khCan('reports.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../report/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-chart-bar"></i>

                        <span>Reports</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- ROUTES -->

                <?php if (khCan('routes.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../routes/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-route"></i>

                        <span>Routes</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- ROUTE MANAGERS -->

                <?php if (khCan('route_managers.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../route_managers/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-users-cog"></i>

                        <span>Route Managers</span>
                    </a>

                </li>
                <?php endif; ?>

            </ul>

            <!-- =========================================================
                SIDEBAR FOOTER / KASHHUB BRAND
            ========================================================== -->

            <div class="sidebar-footer">

                <div class="sidebar-powered-by">

                    <span>Powered by</span>

                    <strong>KashHub</strong>

                </div>

                <a href="../?logout=1">

                    <i class="fa fa-sign-out-alt"></i>

                    <span>Logout</span>

                </a>

            </div>

        </aside>

        <?php
    }

    function sideBarLevel2()
    {
        global $book;

        /*
        |--------------------------------------------------------------------------
        | GET BOOK ID
        |--------------------------------------------------------------------------
        */

        $encryptedBookId =
            $_REQUEST['bkid']
            ?? $_REQUEST['bsid']
            ?? $_SESSION['book_id']
            ?? null;

        if (empty($encryptedBookId)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | DECRYPT BOOK ID
        |--------------------------------------------------------------------------
        */

        $book_id = encryptor(
            'decrypt',
            $encryptedBookId
        );

        /*
        |--------------------------------------------------------------------------
        | FIND BOOK
        |--------------------------------------------------------------------------
        */

        $book = bookFind((int)$book_id);

        if (!$book) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | FIND BUSINESS THAT OWNS THIS BOOK
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | The company logo belongs to cashbook_business_profile,
        | NOT cashbook_books.
        |
        */

        $business = null;

        if (!empty($book->business_id)) {
            $business = businessFindId($book->business_id);
        }

        /*
        |--------------------------------------------------------------------------
        | BUSINESS INFORMATION
        |--------------------------------------------------------------------------
        */

        $businessName =
            !empty($business->name)
            ? $business->name
            : 'Business';

        $bookName =
            !empty($book->name)
            ? $book->name
            : 'Business Book';

        /*
        |--------------------------------------------------------------------------
        | BUSINESS LOGO
        |--------------------------------------------------------------------------
        */

        $businessLogo = businessLogo($business);

        /*
        |--------------------------------------------------------------------------
        | ENCRYPT BOOK ID ONCE
        |--------------------------------------------------------------------------
        */

        $bsid = encryptor(
            'encrypt',
            $book->id
        );

        ?>

        <!-- =============================================================
            MOBILE MENU
        ============================================================== -->

        <button id="mobile-menu-btn" type="button" aria-label="Open navigation" aria-expanded="false">
            <i class="fa fa-bars"></i>
        </button>

        <!-- =============================================================
            MOBILE OVERLAY
        ============================================================== -->

        <div id="sidebar-overlay"></div>

        <!-- =============================================================
            SIDEBAR
        ============================================================== -->

        <aside id="sidebar" class="expand">

            <!-- =========================================================
                SIDEBAR HEADER
            ========================================================== -->

            <div class="sidebar-header">

                <!-- DESKTOP TOGGLE -->

                <button id="toggle-btn" type="button" aria-label="Toggle navigation" aria-expanded="true">
                    <i class="fa fa-bars"></i>
                </button>

                <!-- =====================================================
                    COMPANY BRAND
                ====================================================== -->

                <div class="sidebar-logo">

                    <a
                        href="../../dashboard/?bkid=<?=$bsid;?>"
                        class="sidebar-company-brand"
                        title="<?=htmlspecialchars($businessName);?>"
                    >

                        <div class="sidebar-company-logo-wrap">

                            <img
                                src="<?=htmlspecialchars($businessLogo);?>"
                                alt="<?=htmlspecialchars($businessName);?>"
                                class="sidebar-company-logo"
                            >

                        </div>

                        <div class="sidebar-company-details">

                            <span class="sidebar-company-name">
                                <?=htmlspecialchars($businessName);?>
                            </span>

                            <span class="sidebar-book-name">
                                <?=htmlspecialchars($bookName);?>
                            </span>

                        </div>

                    </a>

                </div>

                <!-- MOBILE CLOSE -->

                <button id="mobile-close-btn" type="button" aria-label="Close navigation">
                    <i class="fa fa-times"></i>
                </button>

            </div>

            <!-- =========================================================
                NAVIGATION
            ========================================================== -->

            <ul class="sidebar-nav">

                <!-- DASHBOARD -->
                <?php if(khIsSuperAdmin()):?>
                    <li class="sidebar-item">
                        <a  href="../../super/?bkid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-shield-alt"></i>
                            <span>Super Admin Dashboard</span>
                        </a>
                    </li>
                <?php endif;?>
                <?php if (khCan('dashboard.view',(int)$book->id)): ?>
                        <li class="sidebar-item">
                        <a  href="../../dashboard/?bkid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php if (khCan('home.view',(int)$book->id)): ?>
                    <li class="sidebar-item">
                    <a href="../../home/?bkid=<?=$bsid;?>" class="sidebar-link">
                        <i class="fa fa-building"></i>
                        <span>Home</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- CUSTOMERS -->

                <?php if (khCan('customers.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../../customers/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-user-friends"></i>

                        <span>Customers</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- CREDITORS -->

                <?php if (khCan('creditors.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../../creditors/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-hand-holding-usd"></i>
                        <span>Creditors</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- STOCK: expandable navigation -->
                <?php if (khCan('stock.view',(int)$book->id) || khCan('stocktake.view',(int)$book->id)): ?>
        <?php $stockMenuId = 'kh-stock-menu-2'; ?>
                <li class="sidebar-item kh-stock-nav">
                    <button type="button" class="sidebar-link kh-stock-toggle" aria-expanded="false" aria-controls="<?=$stockMenuId;?>">
                        <i class="fa fa-warehouse"></i>
                        <span>Stock</span>
                        <i class="fa fa-chevron-down kh-stock-chevron" aria-hidden="true"></i>
                    </button>
                    <ul id="<?=$stockMenuId;?>" class="kh-stock-submenu" hidden>
                        <?php if (khCan('stock.view',(int)$book->id)): ?><li><a href="../../stock/?bkid=<?=$bsid;?>"><i class="fa fa-boxes"></i> Stock Overview</a></li><?php endif; ?>
                        <?php if (khCan('stocktake.view',(int)$book->id)): ?><li><a href="../../stock/stocktake/?bkid=<?=$bsid;?>"><i class="fa fa-clipboard-check"></i> Stock Taking</a></li><?php endif; ?>
                    </ul>
                </li>

                <?php endif; ?>

                <!-- ITEMS -->

                <?php if (khCan('items.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../../items/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-box-open"></i>

                        <span>Items</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- PURCHASES -->

                <?php if (khCan('purchases.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../../purchases/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-shopping-cart"></i>

                        <span>Purchases</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- USERS -->

                <?php if (khCan('users.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../../members/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-users"></i>

                        <span>Users</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- ROLE MANAGEMENT: owner or superadministrator (transitional) -->
                <?php if (canManageBusinessRolesNav((int)($book->business_id ?? 0))): ?>
                    <li class="sidebar-item">
                        <a href="../../roles/?business_id=<?= (int)$book->business_id; ?>" class="sidebar-link">
                            <i class="fa fa-user-shield"></i>
                            <span>Roles &amp; Access</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- CATEGORIES -->

                <?php if (khCan('categories.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../../category/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-tags"></i>

                        <span>Categories</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- PAY MODES -->
                <?php if (khCan('paymodes.view',(int)$book->id)): ?>
                    <li class="sidebar-item">
                        <a href="../../modes/?bsid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-handshake"></i>
                            <span>Pay Modes</span>
                        </a>

                    </li>
                <?php endif; ?>

                <!-- INVOICES -->
                <?php if (khCan('invoices.view',(int)$book->id)): ?>
                    <li class="sidebar-item">
                        <a href="../../invoices/?bsid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-file-invoice"></i>
                            <span>Invoices</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- REPORT -->

                <?php if (khCan('reports.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../../report/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-chart-bar"></i>

                        <span>Reports</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- ROUTES -->

                <?php if (khCan('routes.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../../routes/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-route"></i>

                        <span>Routes</span>
                    </a>

                </li>
                <?php endif; ?>

                <!-- ROUTE MANAGERS -->

                <?php if (khCan('route_managers.view',(int)$book->id)): ?>
                    <li class="sidebar-item">

                    <a
                        href="../../route_managers/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-users-cog"></i>

                        <span>Route Managers</span>
                    </a>

                </li>
                <?php endif; ?>

            </ul>

            <!-- =========================================================
                SIDEBAR FOOTER / KASHHUB BRAND
            ========================================================== -->

            <div class="sidebar-footer">

                <div class="sidebar-powered-by">

                    <span>Powered by</span>

                    <strong>KashHub</strong>

                </div>

                <a href="../../?logout=1">

                    <i class="fa fa-sign-out-alt"></i>

                    <span>Logout</span>

                </a>

            </div>

        </aside>

        <?php
    }

    function superAdminSideBar()
    {
        /*
        |--------------------------------------------------------------------------
        | KASHHUB PLATFORM BRANDING
        |--------------------------------------------------------------------------
        | Super Admin represents KashHub itself.
        | Do not resolve a business or book logo here.
        */

        $kashHubLogo = '/cashbook/assets/images/kashhub/logo.png';

        ?>

        <!-- Mobile menu button -->
        <button id="mobile-menu-btn" type="button" aria-label="Open navigation" aria-expanded="false">
            <i class="fa fa-bars"></i>
        </button>

        <!-- Mobile overlay -->
        <div id="sidebar-overlay"></div>

        <!-- ============================================================
            SUPER ADMIN SIDEBAR
        ============================================================= -->
        <aside id="sidebar" class="expand">

            <!-- ========================================================
                HEADER
            ========================================================= -->
            <div class="sidebar-header">

                <!-- Desktop toggle -->
                <button id="toggle-btn" type="button" aria-label="Toggle navigation" aria-expanded="true">
                    <i class="fa fa-bars"></i>
                </button>

                <!-- KashHub Brand -->
                <div class="sidebar-logo">

                    <a href="../super/" class="sidebar-company-brand" title="KashHub Administration">

                        <div class="sidebar-company-logo-wrap">
                            <img
                                src="<?= htmlspecialchars($kashHubLogo); ?>"
                                alt="KashHub"
                                class="sidebar-company-logo"
                            >
                        </div>

                        <div class="sidebar-company-details">

                            <span class="sidebar-company-name">
                                KashHub
                            </span>

                            <span class="sidebar-book-name">
                                System Administration
                            </span>

                        </div>

                    </a>

                </div>

                <!-- Mobile close -->
                <button id="mobile-close-btn" type="button" aria-label="Close navigation">
                    <i class="fa fa-times"></i>
                </button>

            </div>

            <!-- ========================================================
                NAVIGATION
            ========================================================= -->
            <ul class="sidebar-nav">

                <li class="sidebar-item">
                    <a href="../super/" class="sidebar-link" title="Dashboard">
                        <i class="fa fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="../books/" class="sidebar-link" title="Books">
                        <i class="fa fa-book"></i>
                        <span>Books</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="../businesses/" class="sidebar-link" title="Businesses">
                        <i class="fa fa-building"></i>
                        <span>Businesses</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="../users/" class="sidebar-link" title="System Users">
                        <i class="fa fa-users"></i>
                        <span>System Users</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="../support/" class="sidebar-link" title="Support">
                        <i class="fa fa-headphones"></i>
                        <span>Support</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="../activity/" class="sidebar-link" title="System Activity">
                        <i class="fa fa-history"></i>
                        <span>System Activity</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="../settings/" class="sidebar-link" title="System Settings">
                        <i class="fa fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>

                <!-- SYSTEM ROLE MANAGEMENT -->
                <?php if (canManageSystemRolesNav()): ?>
                    <li class="sidebar-item">
                        <a href="../roles/" class="sidebar-link">
                            <i class="fa fa-user-shield"></i>
                            <span>System Roles</span>
                        </a>
                    </li>
                <?php endif; ?>

            </ul>

            <!-- ========================================================
                FOOTER
            ========================================================= -->
            <div class="sidebar-footer">

                <div class="sidebar-admin-context">

                    <div class="sidebar-admin-icon">
                        <i class="fa fa-shield-alt"></i>
                    </div>

                    <div class="sidebar-admin-details">
                        <span>Administration</span>
                        <strong>KashHub Platform</strong>
                    </div>

                </div>

                <a href="../?logout=1" class="sidebar-logout" title="Logout">
                    <i class="fa fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>

            </div>

        </aside>

        <?php
    }

    function businessSideBar()
    {
        /*
        |--------------------------------------------------------------------------
        | FIND CURRENT BUSINESS
        |--------------------------------------------------------------------------
        */

        $business = businessFind(auth());

        if (
            !$business &&
            isset(auth()->business_id) &&
            !empty(auth()->business_id)
        ) {
            $business = businessFindId(
                auth()->business_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | BUSINESS BRANDING
        |--------------------------------------------------------------------------
        */

        $businessName =
            !empty($business->name)
            ? $business->name
            : 'My Business';

        $businessLogo =
            businessLogo($business);

        $canEditBusiness = !empty($business->id) && (khIsSuperAdmin() || khOwnsBusiness((int)$business->id));
        ?>

        <!-- =============================================================
            MOBILE MENU BUTTON
        ============================================================== -->

        <button id="mobile-menu-btn" type="button" aria-label="Open navigation" aria-expanded="false">
            <i class="fa fa-bars"></i>
        </button>

        <!-- MOBILE OVERLAY -->

        <div id="sidebar-overlay"></div>

        <!-- =============================================================
            BUSINESS SIDEBAR
        ============================================================== -->

        <aside id="sidebar" class="expand">

            <!-- =========================================================
                HEADER
            ========================================================== -->

            <div class="sidebar-header">

                <!-- DESKTOP TOGGLE -->

                <button id="toggle-btn" type="button" aria-label="Toggle navigation" aria-expanded="true">
                    <i class="fa fa-bars"></i>
                </button>

                <!-- =====================================================
                    BUSINESS BRAND
                ====================================================== -->

                <div class="sidebar-logo">

                    <a
                        href="../"
                        class="sidebar-company-brand"
                        title="<?=htmlspecialchars($businessName);?>"
                    >

                        <div class="sidebar-company-logo-wrap">

                            <img
                                src="<?=htmlspecialchars($businessLogo);?>"
                                alt="<?=htmlspecialchars($businessName);?>"
                                class="sidebar-company-logo"
                            >

                        </div>

                        <div class="sidebar-company-details">

                            <span class="sidebar-company-name">
                                <?=htmlspecialchars($businessName);?>
                            </span>

                            <span class="sidebar-book-name">
                                KashHub Business
                            </span>

                        </div>

                    </a>

                </div>

                <!-- MOBILE CLOSE -->

                <button id="mobile-close-btn" type="button" aria-label="Close navigation">
                    <i class="fa fa-times"></i>
                </button>

            </div>

            <!-- =========================================================
                NAVIGATION
            ========================================================== -->

            <ul class="sidebar-nav">

                <!-- HOME -->

                <li class="sidebar-item">

                    <a href="../" class="sidebar-link">

                        <i class="fa fa-home"></i>

                        <span>Home</span>

                    </a>

                </li>

                <?php if ($canEditBusiness): ?>
                <!-- BUSINESS PROFILE -->

                <li class="sidebar-item">

                    <a
                        href="#"
                        class="sidebar-link new-profile"
                        data-id="<?=
                            !empty($business->id)
                            ? (int)$business->id
                            : '';
                        ?>"
                    >

                        <i class="fa fa-building"></i>

                        <span>Business Profile</span>

                    </a>

                </li>

                <!-- TEAM -->

                <li class="sidebar-item">
                    <a href="#" class="sidebar-link new-team">
                        <i class="fa fa-users"></i>
                        <span>Team</span>
                    </a>
                </li>

                <?php endif; ?>
                <!-- BUSINESS ROLE MANAGEMENT: owner or superadministrator (transitional) -->
                <?php if (!empty($business->id) && canManageBusinessRolesNav((int)$business->id)): ?>
                    <li class="sidebar-item">
                        <a href="../roles/?business_id=<?= (int)$business->id; ?>" class="sidebar-link">
                            <i class="fa fa-user-shield"></i>
                            <span>Roles &amp; Access</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($canEditBusiness): ?>
                <!-- SETTINGS -->

                <li class="sidebar-item">

                    <a href="#" class="sidebar-link new-settings">

                        <i class="fa fa-cog"></i>

                        <span>Settings</span>

                    </a>

                </li>

                <?php endif; ?>
            </ul>

            <!-- =========================================================
                FOOTER
            ========================================================== -->

            <div class="sidebar-footer">

                <!-- KASHHUB PLATFORM BRAND -->

                <div class="sidebar-powered-by">
                    <span>Powered by</span>
                    <strong>KashHub</strong>
                </div>

                <!-- LOGOUT -->
                <a href="../?logout=1">
                    <i class="fa fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <?php
    }
?>