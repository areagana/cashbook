<?php
require_once(__DIR__.'/../assets/functions.php');
if (isVerified())
{
    pageHeader('Home');
    $business = businessFind(auth());

    if (!$business && isset(auth()->business_id))
    {
        $business = businessFindId(auth()->business_id);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE BUSINESS
    |--------------------------------------------------------------------------
    */

    if (!$business)
    {
        ?>

            <div class="container-fluid p-4">

                <div class="alert alert-warning">

                    <h5>
                        <i class="fa fa-exclamation-triangle"></i>
                        Business Profile Not Found
                    </h5>

                    <p class="mb-0">
                        We could not find a business profile attached to your account.
                        Please contact your administrator.
                    </p>

                </div>

            </div>

        <?php

        pageFooter();

        exit;
    }
    // Profile editing is restricted to business administrators.
    // The save endpoint must independently enforce this permission and business scope.
    $canManageBusinessProfile = hasRole(['owner', 'superadministrator']);

    $books = [];

    $totalCredits = 0;
    $totalDebits = 0;
    $totalBalance = 0;


    /*
    |--------------------------------------------------------------------------
    | BOOK VISIBILITY — ENFORCED IN SQL
    |--------------------------------------------------------------------------
    | Owners, partners and superadministrators may see business books.
    | Other members may see only books explicitly mapped to their user ID.
    | An EXISTS clause avoids duplicate books when mappings are repeated.
    | Never use business membership alone as permission to list all books.
    */
    $canViewAllBusinessBooks = hasRole(['owner', 'partner', 'superadministrator']);
    $currentUserId = (int)auth()->id;
    $businessId = (int)$business->id;

    if ($canViewAllBusinessBooks)
    {
        $stmt = "SELECT b.* FROM cashbook_books b
                 WHERE b.business_id = ? ORDER BY b.id DESC";
        $bookResult = prepared_statements($stmt, 'i', [$businessId]);
    }
    else
    {
        $stmt = "SELECT b.* FROM cashbook_books b
                 WHERE b.business_id = ?
                   AND EXISTS (
                       SELECT 1 FROM cashbook_book_users bu
                       WHERE bu.book_id = b.id AND bu.user_id = ?
                   )
                 ORDER BY b.id DESC";
        $bookResult = prepared_statements($stmt, 'ii', [$businessId, $currentUserId]);
    }

    $cashins = "SELECT COALESCE(SUM(amount),0) AS credits FROM cashbook_cashins WHERE book_id = ?";
    $cashouts = "SELECT COALESCE(SUM(amount),0) AS debits FROM cashbook_cashouts WHERE book_id = ?";

    if ($bookResult)
    {
        while ($book = $bookResult->fetch_assoc())
        {
            $cashinResult = prepared_statements($cashins,'i',[$book['id']]);
            $cashinRow = $cashinResult ? $cashinResult->fetch_assoc() : ['credits' => 0];
            $credits = (float)($cashinRow['credits'] ?? 0);
            $cashoutResult = prepared_statements($cashouts,'i', [$book['id']]);
            $cashoutRow = $cashoutResult ? $cashoutResult->fetch_assoc() : ['debits' => 0];
            $debits = (float)($cashoutRow['debits'] ?? 0);

            $balance = $credits - $debits;

            $book['credits'] = $credits;
            $book['debits'] = $debits;
            $book['balance'] = $balance;

            /*
            |--------------------------------------------------------------------------
            | TOTALS
            |--------------------------------------------------------------------------
            */

            $totalCredits += $credits;
            $totalDebits += $debits;
            $totalBalance += $balance;

            $books[] = $book;
        }
    }

    $totalBooks = count($books);
    $canCreateBooks = hasRole(['owner', 'partner', 'superadministrator']);
    $firstBookUrl = $totalBooks > 0
        ? '../books/?bkid=' . urlencode(encryptor('encrypt', $books[0]['id']))
        : null;
    $firstDashboardUrl = $totalBooks > 0
        ? '../dashboard/?bkid=' . urlencode(encryptor('encrypt', $books[0]['id']))
        : null;
    $onboardingKey = 'kashhub-onboarding-' . $businessId . '-' . $currentUserId;
?>

<style>

    .business-home
    {
        padding: 20px 20px 40px;
    }


    .business-welcome
    {
        position: relative;
        overflow: hidden;
        padding: 30px;
        margin-bottom: 24px;
        background:
            linear-gradient(
                135deg,
                #0e2238 0%,
                #123b52 55%,
                #087f5b 100%
            );

        color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,.08);
    }


    .business-welcome::after
    {
        content: "";
        position: absolute;
        width: 220px;
        height: 220px;
        right: -80px;
        top: -100px;
        border-radius: 50%;
        background:rgba(255,255,255,.06);
    }


    .business-welcome::before
    {
        content: "";
        position: absolute;
        width: 150px;
        height: 150px;
        right: 100px;
        bottom: -100px;
        border-radius: 50%;
        background:  rgba(255,255,255,.04);
    }


    .welcome-content
    {
        position: relative;
        z-index: 2;
    }


    .welcome-label
    {
        margin-bottom: 6px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color:  rgba(255,255,255,.65);
    }


    .business-welcome h1
    {
        margin: 0 0 8px;
        font-size: 29px;
        font-weight: 700;
    }


    .business-welcome p
    {
        max-width: 720px;
        margin: 0;
        color: rgba(255,255,255,.78);
        font-size: 14px;
        line-height: 1.7;
    }


    /*
    |--------------------------------------------------------------------------
    | WELCOME ACTIONS
    |--------------------------------------------------------------------------
    */

    .welcome-actions
    {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }


    .welcome-action-btn
    {
        display: inline-flex;

        align-items: center;

        gap: 7px;

        padding: 9px 13px;

        border:
            1px solid rgba(255,255,255,.30);

        border-radius: 4px;

        background:
            rgba(255,255,255,.08);

        color: #ffffff;

        font-size: 12px;

        font-weight: 600;

        cursor: pointer;

        transition:
            background .2s ease,
            color .2s ease;
    }


    .welcome-action-btn:hover
    {
        background: #ffffff;

        color: #0e2238;
    }


    /*
    |--------------------------------------------------------------------------
    | SUMMARY CARDS
    |--------------------------------------------------------------------------
    */

    .business-summary-card
    {
        height: 100%;

        padding: 19px;

        background: #ffffff;

        border: 1px solid #e8edf2;

        border-radius: 8px;

        box-shadow:
            0 3px 14px rgba(15,23,42,.04);
    }


    .summary-top
    {
        display: flex;

        justify-content: space-between;

        align-items: center;
    }


    .summary-icon
    {
        width: 40px;

        height: 40px;

        display: flex;

        align-items: center;

        justify-content: center;

        border-radius: 7px;

        background: #eef8f5;

        color: #087f5b;

        font-size: 17px;
    }


    .summary-title
    {
        color: #718096;

        font-size: 11px;

        font-weight: 700;

        letter-spacing: .7px;

        text-transform: uppercase;
    }


    .summary-number
    {
        margin-top: 7px;

        color: #172b3a;

        font-size: 23px;

        font-weight: 700;
    }


    .summary-description
    {
        margin-top: 3px;

        color: #9aa5b1;

        font-size: 11px;
    }


    /*
    |--------------------------------------------------------------------------
    | MAIN CARDS
    |--------------------------------------------------------------------------
    */

    .business-panel
    {
        background: #ffffff;

        border: 1px solid #e8edf2;

        border-radius: 8px;

        box-shadow:
            0 3px 14px rgba(15,23,42,.04);

        overflow: hidden;
    }


    .business-panel-header
    {
        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 15px;

        padding: 18px 20px;

        border-bottom:
            1px solid #edf1f5;
    }


    .business-panel-header h2
    {
        margin: 0;

        color: #172b3a;

        font-size: 17px;

        font-weight: 700;
    }


    .business-panel-header p
    {
        margin: 4px 0 0;

        color: #8492a6;

        font-size: 12px;
    }


    /*
    |--------------------------------------------------------------------------
    | BOOK LIST
    |--------------------------------------------------------------------------
    */

    .business-book-list
    {
        padding: 5px 20px 15px;
    }


    .business-book
    {
        display: flex;

        align-items: center;

        gap: 15px;

        padding: 16px 0;

        border-bottom:
            1px solid #edf1f5;
    }


    .business-book:last-child
    {
        border-bottom: none;
    }


    .business-book-icon
    {
        width: 45px;

        height: 45px;

        min-width: 45px;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #edf9f5;

        color: #087f5b;

        border-radius: 7px;

        font-size: 17px;
    }


    .business-book-info
    {
        flex: 1;

        min-width: 0;
    }


    .business-book-name
    {
        color: #172b3a;

        font-size: 15px;

        font-weight: 700;
    }


    .business-book-description
    {
        margin-top: 3px;

        color: #8492a6;

        font-size: 11px;
    }


    /*
    |--------------------------------------------------------------------------
    | BOOK BALANCE
    |--------------------------------------------------------------------------
    */

    .business-book-balance
    {
        min-width: 155px;

        text-align: right;
    }


    .business-book-balance small
    {
        display: block;

        margin-bottom: 2px;

        color: #8492a6;

        font-size: 9px;

        font-weight: 700;

        letter-spacing: .6px;

        text-transform: uppercase;
    }


    .business-book-balance strong
    {
        font-size: 15px;
    }


    /*
    |--------------------------------------------------------------------------
    | BOOK ACTIONS
    |--------------------------------------------------------------------------
    */

    .business-book-actions
    {
        min-width: 185px;

        text-align: right;

        white-space: nowrap;
    }


    .business-book-actions .btn
    {
        margin-left: 4px;
    }


    /*
    |--------------------------------------------------------------------------
    | GUIDE PANEL
    |--------------------------------------------------------------------------
    */

    .business-guide
    {
        padding: 20px;
    }


    .business-guide-title
    {
        margin-bottom: 5px;

        color: #172b3a;

        font-size: 16px;

        font-weight: 700;
    }


    .business-guide-intro
    {
        margin-bottom: 12px;

        color: #8492a6;

        font-size: 12px;

        line-height: 1.6;
    }


    .business-guide-step
    {
        display: flex;

        gap: 11px;

        padding: 13px 0;

        border-bottom:
            1px solid #edf1f5;
    }


    .business-guide-step:last-child
    {
        border-bottom: none;
    }


    .guide-icon
    {
        width: 34px;

        height: 34px;

        min-width: 34px;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #f2f5f8;

        color: #087f5b;

        border-radius: 6px;
    }


    .business-guide-step strong
    {
        display: block;

        margin-bottom: 2px;

        color: #172b3a;

        font-size: 12px;
    }


    .business-guide-step span
    {
        color: #8492a6;

        font-size: 11px;

        line-height: 1.5;
    }


    /*
    |--------------------------------------------------------------------------
    | EMPTY STATE
    |--------------------------------------------------------------------------
    */

    .empty-business-books
    {
        padding: 50px 20px;

        text-align: center;
    }


    .empty-business-books .empty-icon
    {
        margin-bottom: 15px;

        color: #cbd5df;

        font-size: 42px;
    }


    .empty-business-books h3
    {
        margin-bottom: 7px;

        color: #34495e;

        font-size: 17px;
    }


    .empty-business-books p
    {
        max-width: 420px;

        margin:
            0 auto 18px;

        color: #8492a6;

        font-size: 12px;
    }


    /*
    |--------------------------------------------------------------------------
    | MOBILE
    |--------------------------------------------------------------------------
    */

    @media(max-width: 991px)
    {

        .business-home
        {
            padding:
                15px 12px 30px;
        }


        .business-welcome
        {
            padding: 23px;
        }


        .welcome-actions
        {
            justify-content:
                flex-start;

            margin-top: 20px;
        }

    }


    @media(max-width: 767px)
    {

        .business-welcome
        {
            padding: 20px;
        }


        .business-welcome h1
        {
            font-size: 23px;
        }


        .welcome-action-btn
        {
            padding: 8px 10px;
        }


        .business-panel-header
        {
            align-items:
                flex-start;

            flex-direction:
                column;
        }


        .business-book
        {
            align-items:
                flex-start;

            flex-wrap: wrap;
        }


        .business-book-info
        {
            width:
                calc(100% - 60px);
        }


        .business-book-balance
        {
            width: 100%;

            min-width: 0;

            padding-left: 60px;

            text-align: left;
        }


        .business-book-actions
        {
            width: 100%;

            min-width: 0;

            padding-left: 55px;

            text-align: left;
        }

    }


    @media(max-width: 480px)
    {

        .business-home
        {
            padding:
                10px 8px 25px;
        }


        .business-welcome
        {
            border-radius: 7px;
        }


        .business-summary-card,
        .business-panel
        {
            border-radius: 6px;
        }


        .business-book-actions
        {
            padding-left: 0;

            display: flex;

            gap: 5px;
        }


        .business-book-actions .btn
        {
            flex: 1;

            margin-left: 0;
        }


        .business-book-balance
        {
            padding-left: 0;
        }

    }



    /* ---------------------------------------------------------------
       KASHHUB GETTING STARTED / ONBOARDING
       --------------------------------------------------------------- */
    .kh-start { margin-bottom:24px; border:1px solid #dcece6; border-radius:12px; background:#fff; overflow:hidden; box-shadow:0 6px 20px rgba(15,23,42,.045); }
    .kh-start-head { padding:22px 24px; background:linear-gradient(110deg,#effbf5,#f5f9ff); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; }
    .kh-start-eyebrow { font-size:11px; font-weight:800; letter-spacing:1.1px; text-transform:uppercase; color:#087f5b; }
    .kh-start-head h2 { margin:6px 0; font-size:22px; font-weight:750; color:#142d3b; }
    .kh-start-head p { margin:0; max-width:660px; font-size:13px; line-height:1.6; color:#5b6e7b; }
    .kh-start-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .kh-start-body { padding:20px 24px 24px; }
    .kh-start-progress { display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:12px; font-weight:700; color:#536575; margin-bottom:8px; }
    .kh-progress-track { height:8px; background:#e7efec; border-radius:10px; overflow:hidden; margin-bottom:18px; }
    .kh-progress-fill { height:100%; width:0; background:#0a966a; transition:width .2s; }
    .kh-start-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .kh-start-step { padding:16px; border:1px solid #e5ebef; border-radius:9px; display:flex; flex-direction:column; gap:10px; min-width:0; }
    .kh-start-step-top { display:flex; justify-content:space-between; align-items:center; }
    .kh-start-icon { width:37px; height:37px; display:grid; place-items:center; border-radius:9px; background:#e9f8f1; color:#087f5b; }
    .kh-start-step h3 { font-size:14px; font-weight:750; margin:0; color:#172b3a; }
    .kh-start-step p { margin:0; color:#6c7c89; font-size:12px; line-height:1.55; flex:1; }
    .kh-start-step .btn { align-self:flex-start; font-size:12px; }
    .kh-step-check { display:flex; gap:5px; align-items:center; margin:0; font-size:11px; color:#667b85; cursor:pointer; }
    .kh-step-check input { accent-color:#087f5b; }
    .kh-tour-modal { display:none; }
    .kh-tour-body { padding:24px; }
    .kh-tour-page { display:none; }
    .kh-tour-page.active { display:block; }
    .kh-tour-count { color:#087f5b; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; }
    .kh-tour-page h3 { font-size:22px; margin:10px 0; color:#172b3a; }
    .kh-tour-page p { font-size:14px; line-height:1.75; color:#5b6e7b; }
    .kh-tour-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:24px; }
    @media(max-width:991px) { .kh-start-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:575px) { .kh-start-head,.kh-start-body { padding:17px; } .kh-start-grid { grid-template-columns:1fr; } .kh-start-head h2 { font-size:20px; } }

    /* BUSINESS PROFILE / BRAND IDENTITY */
    .profile-brand-card { background:#f7fafc; border:1px solid #e3eaf0; padding:18px; margin-bottom:18px; }
    .profile-section { border:1px solid #e8edf2; background:#fff; padding:18px; margin-bottom:16px; }
    .profile-section-title { display:flex; align-items:center; gap:9px; margin-bottom:15px; color:#172b3a; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
    .profile-section-title i { color:#087f5b; }
    .business-logo-uploader { display:flex; align-items:center; gap:18px; }
    .business-logo-frame { width:112px; height:112px; min-width:112px; display:flex; align-items:center; justify-content:center; background:#fff; border:1px dashed #b8c5d1; padding:10px; cursor:pointer; overflow:hidden; }
    .business-logo-frame img { width:100%; height:100%; object-fit:contain; }
    .business-logo-copy { flex:1; min-width:0; }
    .business-logo-copy h4 { margin:0 0 5px; color:#172b3a; font-size:15px; font-weight:700; }
    .business-logo-copy p { margin:0 0 10px; color:#8492a6; font-size:11px; line-height:1.6; }
    .logo-upload-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 11px; border:1px solid #087f5b; background:#fff; color:#087f5b; font-size:11px; font-weight:700; cursor:pointer; }
    .logo-upload-btn:hover { background:#087f5b; color:#fff; }
    .profile-field-help { display:block; margin-top:5px; color:#9aa5b1; font-size:10px; }
    .profile-save-bar { position:sticky; bottom:0; z-index:3; display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 -2px -2px; padding:14px 16px; background:rgba(255,255,255,.97); border-top:1px solid #e8edf2; box-shadow:0 -4px 14px rgba(15,23,42,.04); }
    .profile-save-note { color:#8492a6; font-size:10px; }
    @media(max-width:575px) { .business-logo-uploader { align-items:flex-start; flex-direction:column; } .business-logo-frame { width:96px; height:96px; min-width:96px; } .profile-save-bar { align-items:stretch; flex-direction:column; } .profile-save-bar .btn { width:100%; } }

</style>


<!-- ================================================================
     BUSINESS HOME
================================================================ -->

<div class="container-fluid business-home">


    <!-- ============================================================
         WELCOME
    ============================================================= -->

    <div class="business-welcome">

        <div class="row align-items-center">

            <div class="col-lg-7 welcome-content">

                <div class="welcome-label">
                    Business Workspace
                </div>


                <h1>
                    Welcome to
                    <?=htmlspecialchars($business->name);?>
                </h1>


                <p>
                    Your business books keep your financial and operational
                    records organised. Select a book below to continue managing
                    transactions, customers, stock, invoices and reports.
                </p>

            </div>


            <?php if($canManageBusinessProfile): ?>

                <div class="col-lg-5">

                    <div class="welcome-actions">


                        <button
                            type="button"
                            class="welcome-action-btn new-profile"
                            data-id="<?=$business->id;?>"
                        >

                            <i class="fa fa-building"></i>

                            Business Profile

                        </button>


                        <button
                            type="button"
                            class="welcome-action-btn new-settings"
                        >

                            <i class="fa fa-cog"></i>

                            Settings

                        </button>


                        <button
                            type="button"
                            class="welcome-action-btn new-team"
                        >

                            <i class="fa fa-user-plus"></i>

                            Add Team

                        </button>


                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>




    <!-- ============================================================
         NEW USER QUICK START - ROLE AWARE, NO EXTRA DATABASE TABLES
    ============================================================= -->
    <section class="kh-start" id="kashhubGettingStarted" data-storage-key="<?=htmlspecialchars($onboardingKey, ENT_QUOTES);?>">
        <div class="kh-start-head">
            <div>
                <div class="kh-start-eyebrow"><i class="fa fa-rocket mr-1"></i> Your KashHub journey starts here</div>
                <h2>Set up today. Stay in control every day.</h2>
                <p>From your first book to your first transaction and report, follow these simple steps to get value from KashHub right away.</p>
            </div>
            <div class="kh-start-actions">
                <button type="button" class="btn btn-success btn-flat" id="khStartTour"><i class="fa fa-play-circle mr-1"></i> Take a quick tour</button>
                <button type="button" class="btn btn-outline-secondary btn-flat" id="khToggleGuide" aria-expanded="true">Hide guide</button>
            </div>
        </div>
        <div class="kh-start-body" id="khGuideBody">
            <div class="kh-start-progress"><span>Your getting-started progress</span><span id="khProgressText">0 of 3 steps</span></div>
            <div class="kh-progress-track"><div class="kh-progress-fill" id="khProgressFill"></div></div>
            <div class="kh-start-grid">
                <?php if ($canCreateBooks): ?>
                <article class="kh-start-step">
                    <div class="kh-start-step-top"><div class="kh-start-icon"><i class="fa fa-book"></i></div><label class="kh-step-check"><input type="checkbox" class="kh-onboarding-check" data-step="book"> Done</label></div>
                    <h3>1. Create your business book</h3>
                    <p>Give your business activities a home. Use a separate book for each operation you want to manage independently.</p>
                    <button type="button" class="btn btn-sm btn-outline-success btn-flat new-book"><i class="fa fa-plus mr-1"></i> Add a book</button>
                </article>
                <?php else: ?>
                <article class="kh-start-step">
                    <div class="kh-start-step-top"><div class="kh-start-icon"><i class="fa fa-book"></i></div><label class="kh-step-check"><input type="checkbox" class="kh-onboarding-check" data-step="book"> Done</label></div>
                    <h3>1. Open your assigned book</h3>
                    <p>Your manager controls book access. Open an assigned book to work with the records you are authorized to manage.</p>
                    <?php if ($firstBookUrl): ?><a class="btn btn-sm btn-outline-success btn-flat" href="<?=htmlspecialchars($firstBookUrl, ENT_QUOTES);?>">Open book</a><?php else: ?><span class="small text-muted">Ask your manager to assign you a book.</span><?php endif; ?>
                </article>
                <?php endif; ?>
                <article class="kh-start-step">
                    <div class="kh-start-step-top"><div class="kh-start-icon"><i class="fa fa-exchange"></i></div><label class="kh-step-check"><input type="checkbox" class="kh-onboarding-check" data-step="transactions"> Done</label></div>
                    <h3>2. Record your daily activity</h3>
                    <p>Inside your book, record money received and spent. Use the available customer, item and stock tools as your role permits.</p>
                    <?php if ($firstBookUrl): ?><a class="btn btn-sm btn-outline-success btn-flat" href="<?=htmlspecialchars($firstBookUrl, ENT_QUOTES);?>">Go to book</a><?php else: ?><span class="small text-muted">Start after a book is available.</span><?php endif; ?>
                </article>
                <article class="kh-start-step">
                    <div class="kh-start-step-top"><div class="kh-start-icon"><i class="fa fa-line-chart"></i></div><label class="kh-step-check"><input type="checkbox" class="kh-onboarding-check" data-step="insights"> Done</label></div>
                    <h3>3. Follow your business performance</h3>
                    <p>Review the dashboard and available reports to understand cash movement, outstanding balances and trends.</p>
                    <?php if ($firstDashboardUrl): ?><a class="btn btn-sm btn-outline-success btn-flat" href="<?=htmlspecialchars($firstDashboardUrl, ENT_QUOTES);?>">View dashboard</a><?php else: ?><span class="small text-muted">Your dashboard is available after setup.</span><?php endif; ?>
                </article>
            </div>
            <div class="small text-muted mt-3"><i class="fa fa-lightbulb-o text-warning mr-1"></i> Tip: Mark each step complete as you go. Progress is saved in this browser, not in your business records.</div>
        </div>
    </section>

    <!-- ============================================================
         SUMMARY
    ============================================================= -->

    <div class="row">


        <!-- BOOKS -->

        <div class="col-md-6 col-xl-4 mb-3">

            <div class="business-summary-card">

                <div class="summary-top">

                    <div>

                        <div class="summary-title">
                            Business Books
                        </div>


                        <div class="summary-number">
                            <?=number_format($totalBooks);?>
                        </div>

                    </div>


                    <div class="summary-icon">

                        <i class="fa fa-book"></i>

                    </div>

                </div>


                <div class="summary-description">

                    Business books available to your account

                </div>

            </div>

        </div>



        <!-- BALANCE -->

        <div class="col-md-6 col-xl-4 mb-3">

            <div class="business-summary-card">

                <div class="summary-top">

                    <div>

                        <div class="summary-title">
                            Combined Balance
                        </div>


                        <div
                            class="summary-number
                            <?=$totalBalance < 0
                                ? 'text-danger'
                                : 'text-success';?>"
                        >

                            <?=number_format(
                                $totalBalance,
                                0
                            );?>/=

                        </div>

                    </div>


                    <div class="summary-icon">

                        <i class="fa fa-wallet"></i>

                    </div>

                </div>


                <div class="summary-description">

                    Cash in less cash out across accessible books

                </div>

            </div>

        </div>



        <!-- WORKSPACE -->

        <div class="col-md-6 col-xl-4 mb-3">

            <div class="business-summary-card">

                <div class="summary-top">

                    <div>

                        <div class="summary-title">
                            Workspace
                        </div>


                        <div
                            class="summary-number"
                            style="font-size:19px;"
                        >
                            Ready
                        </div>

                    </div>


                    <div class="summary-icon">

                        <i class="fa fa-check-circle"></i>

                    </div>

                </div>


                <div class="summary-description">

                    Select a book below to continue working

                </div>

            </div>

        </div>


    </div>



    <!-- ============================================================
         BOOKS + GUIDE
    ============================================================= -->

    <div class="row mt-2">


        <!-- ========================================================
             BUSINESS BOOKS
        ========================================================= -->

        <div class="col-xl-8 mb-4">

            <div class="business-panel">


                <div class="business-panel-header">

                    <div>

                        <h2>

                            <i
                                class="fa fa-book
                                text-success mr-2"
                            ></i>

                            Business Books

                        </h2>

                        <p>

                            Select the book you want to work with.

                        </p>

                    </div>


                    <?php
                    if(hasRole(['owner','partner','superadministrator'])):
                    ?>

                        <button
                            type="button" class="
                                btn
                                btn-sm
                                btn-success
                                btn-flat
                                new-book
                            "
                        >

                            <i
                                class="
                                    fa
                                    fa-plus-circle
                                    mr-1
                                "
                            ></i>

                            Add Book

                        </button>

                    <?php endif; ?>

                </div>



                <?php if($totalBooks > 0): ?>


                    <div class="business-book-list">


                        <?php foreach($books as $book): ?>


                            <?php

                            $encryptedBookId =
                                encryptor(
                                    'encrypt',
                                    $book['id']
                                );


                            $balanceClass =
                                $book['balance'] < 0
                                ? 'text-danger'
                                : 'text-success';

                            ?>


                            <div class="business-book">


                                <!-- ICON -->

                                <div class="business-book-icon">

                                    <i class="fa fa-book"></i>

                                </div>



                                <!-- DETAILS -->

                                <div class="business-book-info">


                                    <div class="business-book-name">

                                        <?=htmlspecialchars(
                                            $book['name']
                                        );?>

                                    </div>


                                    <div
                                        class="
                                            business-book-description
                                        "
                                    >

                                        <?php

                                        if(
                                            isset($book['details']) &&
                                            trim($book['details']) != ''
                                        )
                                        {

                                            echo htmlspecialchars(
                                                $book['details']
                                            );

                                        }
                                        else
                                        {

                                            echo
                                                'Open this book to manage '
                                                .'its business records.';

                                        }

                                        ?>

                                    </div>


                                </div>



                                <!-- BALANCE -->

                                <div class="business-book-balance">

                                    <small>
                                        Current Balance
                                    </small>


                                    <strong
                                        class="<?=$balanceClass;?>"
                                    >

                                        <?=number_format(
                                            $book['balance'],
                                            0
                                        );?>/=

                                    </strong>

                                </div>



                                <!-- ACTIONS -->

                                <div class="business-book-actions">


                                    <a
                                        href="
                                            ../dashboard/?bkid=<?=
                                            $encryptedBookId;
                                            ?>
                                        "
                                        class="
                                            btn
                                            btn-sm
                                            btn-outline-secondary
                                            btn-flat
                                        "
                                    >

                                        <i
                                            class="
                                                fa
                                                fa-info-circle
                                            "
                                        ></i>

                                        Dashboard

                                    </a>


                                    <a
                                        href="
                                            ../books/?bkid=<?=
                                            $encryptedBookId;
                                            ?>
                                            
                                        "
                                        class="
                                            btn
                                            btn-sm
                                            btn-success
                                            btn-flat
                                        "
                                    >

                                        Open

                                        <i
                                            class="
                                                fa
                                                fa-arrow-right
                                                ml-1
                                            "
                                        ></i>

                                    </a>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <!-- EMPTY STATE -->

                    <div class="empty-business-books">


                        <div class="empty-icon">

                            <i class="fa fa-book-open"></i>

                        </div>


                        <h3>
                            No Business Books Yet
                        </h3>


                        <p>

                            A business book is where your transactions,
                            customers, stock and other records are managed.

                            Create your first book to get started.

                        </p>


                        <?php
                        if(hasRole(['owner','partner','superadministrator'])):
                        ?>

                            <button
                                type="button"
                                class="
                                    btn
                                    btn-success
                                    btn-flat
                                    new-book
                                "
                            >

                                <i
                                    class="
                                        fa
                                        fa-plus-circle
                                        mr-1
                                    "
                                ></i>

                                Create First Book

                            </button>

                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </div>

        </div>



        <!-- ========================================================
             GETTING AROUND
        ========================================================= -->

        <div class="col-xl-4 mb-4">

            <div class="business-panel">

                <div class="business-guide">


                    <div class="business-guide-title">

                        <i
                            class="
                                fa
                                fa-compass
                                text-success
                                mr-2
                            "
                        ></i>

                        Getting Around

                    </div>


                    <div class="business-guide-intro">

                        Your business is organised into books.
                        Each book has its own operational records.

                    </div>



                    <!-- STEP 1 -->

                    <div class="business-guide-step">

                        <div class="guide-icon">

                            <i class="fa fa-book"></i>

                        </div>


                        <div>

                            <strong>
                                Choose a Book
                            </strong>

                            <span>

                                Select the business book whose
                                records you want to work with.

                            </span>

                        </div>

                    </div>



                    <!-- STEP 2 -->

                    <div class="business-guide-step">

                        <div class="guide-icon">

                            <i
                                class="
                                    fa
                                    fa-tachometer-alt
                                "
                            ></i>

                        </div>


                        <div>

                            <strong>
                                Open its Dashboard
                            </strong>

                            <span>

                                The dashboard gives you balances,
                                trends and recent activity.

                            </span>

                        </div>

                    </div>



                    <!-- STEP 3 -->

                    <div class="business-guide-step">

                        <div class="guide-icon">

                            <i class="fa fa-bars"></i>

                        </div>


                        <div>

                            <strong>
                                Use the Book Sidebar
                            </strong>

                            <span>

                                Access customers, creditors,
                                stock, purchases, invoices,
                                reports and other book tools.

                            </span>

                        </div>

                    </div>



                    <?php if($canManageBusinessProfile): ?>
                        <!-- STEP 4 -->

                        <div class="business-guide-step">

                            <div class="guide-icon">

                                <i class="fa fa-users"></i>

                            </div>


                            <div>

                                <strong>
                                    Manage Your Team
                                </strong>

                                <span>

                                    Add and manage the people who
                                    help operate your business.

                                </span>

                            </div>

                        </div>


                    <?php endif; ?>


                </div>

            </div>

        </div>


    </div>


</div>




<!-- ================================================================
     QUICK START TOUR - KASHHUB CUSTOM CENTRAL MODAL
================================================================ -->
<div class="p-0 bg-white central-modal absolute border shadow kh-tour-modal" id="khOnboardingTour" role="dialog" aria-modal="true" aria-labelledby="khTourTitle">
    <div class="central-modal-header bg-success">
        <h3 class="central-modal-title text-white" id="khTourTitle">Welcome to KashHub</h3>
        <button type="button" class="central-modal-close" id="khCloseTour" aria-label="Close guide">&times;</button>
    </div>
    <div class="central-modal-content kh-tour-body">
        <div class="kh-tour-page active" data-tour-page="0"><div class="kh-tour-count">Step 1 of 3</div><h3>Give every transaction a home</h3><p>A business book is your workspace for daily operations. Owners can create books; team members work only in books assigned to them.</p></div>
        <div class="kh-tour-page" data-tour-page="1"><div class="kh-tour-count">Step 2 of 3</div><h3>Capture what happens each day</h3><p>Record cash in and cash out as they happen. Where your book uses stock, customers and creditors, link records correctly so statements are easier to follow.</p></div>
        <div class="kh-tour-page" data-tour-page="2"><div class="kh-tour-count">Step 3 of 3</div><h3>Make informed decisions</h3><p>Use dashboards and statements to follow cash flow, customers, creditors and stock. Remember: a cash balance is not the same as profit.</p></div>
        <div class="kh-tour-footer">
            <button type="button" class="btn btn-outline-secondary btn-flat" id="khTourPrevious">Back</button>
            <button type="button" class="btn btn-success btn-flat" id="khTourNext">Next <i class="fa fa-arrow-right ml-1"></i></button>
        </div>
    </div>
</div>

<!-- ================================================================
     CREATE BUSINESS BOOK MODAL
================================================================ -->

<div
    class="
        p-2
        bg-white
        side-modal-tall
        absolute
        border
        shadow
    "
    id="side-modal-newbook"
>


    <div class="side-modal-header">


        <div>

            <h3
                class="
                    side-modal-title
                    text-dark
                    mb-1
                "
            >

                CREATE BUSINESS BOOK

            </h3>


            <small class="text-muted">

                Create a separate workspace for
                business records.

            </small>

        </div>


        <button
            type="button"
            class="side-modal-close"
        >
            &times;
        </button>


    </div>



    <div class="side-modal-content">


        <form
            action="../books/save/index.php"
            method="post"
        >


            <input
                type="hidden"
                name="business_id"
                value="<?=$business->id;?>"
            >



            <div class="form-group">


                <label for="book_title">

                    BOOK TITLE

                </label>


                <input
                    type="text"
                    name="book_title"
                    id="book_title"
                    class="form-control"
                    placeholder="
                        e.g. Main Business Book
                    "
                    required
                >


            </div>



            <div class="form-group">


                <label for="book_details">

                    BOOK DETAILS

                </label>


                <input
                    type="text"
                    name="book_details"
                    id="book_details"
                    class="form-control"
                    placeholder="
                        Brief description of this book
                    "
                >


            </div>



            <div class="text-right pt-2">


                <button
                    type="submit"
                    name="saveNewBook"
                    class="
                        btn
                        btn-flat
                        btn-success
                    "
                >

                    <i
                        class="
                            fa
                            fa-save
                            mr-1
                        "
                    ></i>

                    CREATE BOOK

                </button>


            </div>


        </form>


    </div>


</div>



<!-- ================================================================
     BUSINESS PROFILE / BRAND IDENTITY MODAL
================================================================ -->

<?php if ($canManageBusinessProfile): ?>
<div class="p-2 bg-white side-modal-tall absolute border shadow" id="side-modal-new-profile">
    <div class="side-modal-header">
        <div>
            <h3 class="side-modal-title text-dark mb-1">BUSINESS PROFILE</h3>
            <small class="text-muted">Manage the identity customers will see on KashHub documents and screens.</small>
        </div>
        <button type="button" class="side-modal-close">&times;</button>
    </div>

    <div class="side-modal-content">
        <form id="newBusinessForm" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="businessProfile">
            <input type="hidden" name="business_id" value="<?=$business->id;?>">

            <div class="profile-brand-card">
                <div class="profile-section-title mb-2">
                    <i class="fa fa-palette"></i> Business Branding
                </div>
                <div class="business-logo-uploader">
                    <label for="profile" class="business-logo-frame mb-0" title="Choose business logo">
                        <img
                            src="<?=!empty($business->profile) ? htmlspecialchars($business->profile) : '../images/profile.jpg';?>"
                            alt="<?=htmlspecialchars($business->name ?? 'Business');?> logo"
                            id="profilePreview"
                        >
                    </label>
                    <div class="business-logo-copy">
                        <h4>Your company logo</h4>
                        <p>This logo can be used in your workspace, receipts, invoices, statements and reports while KashHub remains the platform brand.</p>
                        <label for="profile" class="logo-upload-btn mb-0">
                            <i class="fa fa-upload"></i> Choose Logo
                        </label>
                        <input type="file" name="profile" id="profile" class="hidden" accept="image/png,image/jpeg,image/webp">
                        <span class="profile-field-help">PNG or WebP with a transparent background works best. Recommended square or wide logo, up to 2 MB.</span>
                    </div>
                </div>
            </div>

            <div class="profile-section">
                <div class="profile-section-title"><i class="fa fa-building"></i> Business Identity</div>
                <div class="form-group">
                    <label for="name">BUSINESS / COMPANY NAME</label>
                    <input type="text" name="name" id="name" value="<?=htmlspecialchars($business->name ?? '');?>" class="form-control" placeholder="e.g. FreshRays (U) Ltd" required>
                    <span class="profile-field-help">This is the official display name used throughout your KashHub workspace.</span>
                </div>
                <div class="form-group mb-0">
                    <label for="reg_no">REGISTRATION / TIN NUMBER</label>
                    <input type="text" name="reg_no" id="reg_no" value="<?=htmlspecialchars($business->reg_no ?? '');?>" class="form-control" placeholder="Business registration number or TIN">
                </div>
            </div>

            <div class="profile-section">
                <div class="profile-section-title"><i class="fa fa-address-card"></i> Contact Information</div>
                <div class="form-group">
                    <label for="email">BUSINESS EMAIL</label>
                    <input type="email" name="email" id="email" value="<?=htmlspecialchars($business->email ?? '');?>" class="form-control" placeholder="accounts@company.com">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="contact1">PRIMARY CONTACT</label>
                        <input type="text" name="contact1" id="contact1" value="<?=htmlspecialchars($business->contact1 ?? '');?>" class="form-control" placeholder="e.g. +256 700 000000">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="contact2">ALTERNATIVE CONTACT</label>
                        <input type="text" name="contact2" id="contact2" value="<?=htmlspecialchars($business->contact2 ?? '');?>" class="form-control" placeholder="Optional second number">
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label for="address">BUSINESS ADDRESS</label>
                    <textarea name="address" id="address" class="form-control" rows="2" placeholder="Physical or postal business address"><?=htmlspecialchars($business->address ?? '');?></textarea>
                    <span class="profile-field-help">This can appear beneath your company name on customer-facing documents.</span>
                </div>
            </div>

            <div class="profile-save-bar">
                <div class="profile-save-note"><i class="fa fa-info-circle mr-1"></i> Changes will update your business identity across KashHub.</div>
                <button type="submit" class="btn btn-flat btn-success saveNewBusiness">
                    <i class="fa fa-save mr-1"></i> SAVE BUSINESS PROFILE
                </button>
            </div>
        </form>
    </div>
</div>


<?php endif; // End restricted business profile modal ?>

<!-- ================================================================
     PAGE JAVASCRIPT
================================================================ -->
<?php
    pageFooter();
?>
<script>

document.addEventListener(
    'DOMContentLoaded',
    function ()
    {



        /* -----------------------------------------------------------
           ONBOARDING: BROWSER-LOCAL CHECKLIST + GUIDED TOUR
           ----------------------------------------------------------- */
        const gettingStarted = document.getElementById('kashhubGettingStarted');
        if (gettingStarted)
        {
            const key = gettingStarted.dataset.storageKey;
            const checks = Array.from(gettingStarted.querySelectorAll('.kh-onboarding-check'));
            let completed = [];
            try { completed = JSON.parse(localStorage.getItem(key) || '[]'); }
            catch (error) { completed = []; }
            if (!Array.isArray(completed)) completed = [];

            function updateOnboardingProgress()
            {
                const count = checks.filter(check => check.checked).length;
                document.getElementById('khProgressText').textContent = count + ' of ' + checks.length + ' steps';
                document.getElementById('khProgressFill').style.width = (count / checks.length * 100) + '%';
                try { localStorage.setItem(key, JSON.stringify(checks.filter(check => check.checked).map(check => check.dataset.step))); }
                catch (error) { /* Browser storage may be unavailable. */ }
            }
            checks.forEach(check => {
                check.checked = completed.includes(check.dataset.step);
                check.addEventListener('change', updateOnboardingProgress);
            });
            updateOnboardingProgress();

            const guideBody = document.getElementById('khGuideBody');
            const guideToggle = document.getElementById('khToggleGuide');
            guideToggle.addEventListener('click', function () {
                const hidden = guideBody.hidden = !guideBody.hidden;
                guideToggle.textContent = hidden ? 'Show guide' : 'Hide guide';
                guideToggle.setAttribute('aria-expanded', String(!hidden));
            });

            const tour = document.getElementById('khOnboardingTour');
            const pages = Array.from(tour.querySelectorAll('.kh-tour-page'));
            const back = document.getElementById('khTourPrevious');
            const next = document.getElementById('khTourNext');
            let page = 0;
            function renderTour()
            {
                pages.forEach((element, index) => element.classList.toggle('active', index === page));
                back.disabled = page === 0;
                next.textContent = page === pages.length - 1 ? 'Finish' : 'Next →';
            }
            document.getElementById('khStartTour').addEventListener('click', function () {
                page = 0;
                renderTour();
                tour.style.display = 'block';
            });
            document.getElementById('khCloseTour').addEventListener('click', function () {
                tour.style.display = 'none';
            });
            back.addEventListener('click', function () { if (page > 0) { page--; renderTour(); } });
            next.addEventListener('click', function () {
                if (page < pages.length - 1) { page++; renderTour(); }
                else { tour.style.display = 'none'; }
            });
            renderTour();
        }

        /*
        |--------------------------------------------------------------------------
        | OPEN CREATE BOOK
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('.new-book')
            .forEach(
                function (button)
                {

                    button.addEventListener(
                        'click',
                        function ()
                        {

                            const modal =
                                document.getElementById(
                                    'side-modal-newbook'
                                );


                            if (modal)
                            {
                                modal.style.display =
                                    'block';
                            }

                        }
                    );

                }
            );



        /*
        |--------------------------------------------------------------------------
        | OPEN BUSINESS PROFILE
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('.new-profile')
            .forEach(
                function (button)
                {

                    button.addEventListener(
                        'click',
                        function (event)
                        {

                            event.preventDefault();


                            const modal =
                                document.getElementById(
                                    'side-modal-new-profile'
                                );


                            if (modal)
                            {
                                modal.style.display =
                                    'block';
                            }

                        }
                    );

                }
            );



        /*
        |--------------------------------------------------------------------------
        | CLOSE SIDE MODALS
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('.side-modal-close')
            .forEach(
                function (button)
                {

                    button.addEventListener(
                        'click',
                        function ()
                        {

                            const modal =
                                button.closest(
                                    '.absolute'
                                );


                            if (modal)
                            {
                                modal.style.display =
                                    'none';
                            }

                        }
                    );

                }
            );



        /*
        |--------------------------------------------------------------------------
        | PROFILE IMAGE PREVIEW
        |--------------------------------------------------------------------------
        */

        const profileInput =
            document.getElementById(
                'profile'
            );


        const profilePreview =
            document.getElementById(
                'profilePreview'
            );


        if (
            profileInput &&
            profilePreview
        )
        {

            profileInput.addEventListener(
                'change',
                function ()
                {
                    const file = this.files && this.files[0] ? this.files[0] : null;

                    if (!file)
                    {
                        return;
                    }

                    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];

                    if (!allowedTypes.includes(file.type))
                    {
                        xdialog.alert('Please select a PNG, JPG or WebP logo.');
                        this.value = '';
                        return;
                    }

                    if (file.size > (2 * 1024 * 1024))
                    {
                        xdialog.alert('The business logo should not exceed 2 MB.');
                        this.value = '';
                        return;
                    }

                    const reader =
                        new FileReader();


                    reader.onload =
                        function (event)
                        {

                            profilePreview.src =
                                event.target.result;

                        };


                    reader.readAsDataURL(
                        this.files[0]
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | SAVE BUSINESS PROFILE
        |--------------------------------------------------------------------------
        */

        const businessForm =
            document.getElementById(
                'newBusinessForm'
            );


        if (businessForm)
        {

            businessForm.addEventListener(
                'submit',
                function (event)
                {

                    event.preventDefault();


                    const submitButton =
                        businessForm.querySelector(
                            'button[type="submit"]'
                        );


                    const formData =
                        new FormData(
                            businessForm
                        );


                    if (submitButton)
                    {

                        submitButton.disabled =
                            true;


                        submitButton.innerHTML =
                            '<i class="fa fa-spinner fa-spin mr-1"></i> SAVING...';

                    }



                    fetch(
                        'save/index.php',
                        {

                            method: 'POST',

                            body: formData

                        }
                    )

                    .then(
                        function (response)
                        {

                            if (!response.ok)
                            {

                                throw new Error(
                                    'HTTP ' +
                                    response.status
                                );

                            }


                            return response.text();

                        }
                    )

                    .then(
                        function ()
                        {

                            xdialog.alert(
                                'Business profile saved successfully.'
                            );


                            setTimeout(
                                function ()
                                {

                                    window.location.reload();

                                },
                                800
                            );

                        }
                    )

                    .catch(
                        function (error)
                        {

                            console.error(
                                'Business profile error:',
                                error
                            );


                            xdialog.alert(
                                'Unable to save the business profile. Please try again.'
                            );

                        }
                    )

                    .finally(
                        function ()
                        {

                            if (submitButton)
                            {

                                submitButton.disabled =
                                    false;


                                submitButton.innerHTML =
                                    '<i class="fa fa-save mr-1"></i> SAVE PROFILE';

                            }

                        }
                    );

                }
            );

        }


    }
);

</script>


<?php
}else{
     redirect('../');
    exit;
}
?>