<?php

require_once(__DIR__.'/../assets/functions.php');


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (!isVerified())
{
    redirect('../');
    exit;
}


/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
|
| pageHeader() will determine which sidebar should be displayed.
|
*/

pageHeader('Home');

/*
|--------------------------------------------------------------------------
| BUSINESS
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| BUSINESS BOOKS
|--------------------------------------------------------------------------
*/

$books = [];

$totalCredits = 0;
$totalDebits = 0;
$totalBalance = 0;


$stmt = "
    SELECT *
    FROM cashbook_books
    WHERE business_id = ?
    ORDER BY id DESC
";


$bookResult = prepared_statements(
    $stmt,
    'i',
    [$business->id]
);


/*
|--------------------------------------------------------------------------
| BOOK BALANCES
|--------------------------------------------------------------------------
*/

$cashins = "
    SELECT
        COALESCE(SUM(amount),0) AS credits
    FROM cashbook_cashins
    WHERE book_id = ?
";


$cashouts = "
    SELECT
        COALESCE(SUM(amount),0) AS debits
    FROM cashbook_cashouts
    WHERE book_id = ?
";


if ($bookResult)
{
    while ($book = $bookResult->fetch_assoc())
    {

        /*
        |--------------------------------------------------------------------------
        | CASH IN
        |--------------------------------------------------------------------------
        */

        $cashinResult = prepared_statements(
            $cashins,
            'i',
            [$book['id']]
        );


        $cashinRow = $cashinResult
            ? $cashinResult->fetch_assoc()
            : ['credits' => 0];


        $credits = (float)($cashinRow['credits'] ?? 0);


        /*
        |--------------------------------------------------------------------------
        | CASH OUT
        |--------------------------------------------------------------------------
        */

        $cashoutResult = prepared_statements(
            $cashouts,
            'i',
            [$book['id']]
        );


        $cashoutRow = $cashoutResult
            ? $cashoutResult->fetch_assoc()
            : ['debits' => 0];


        $debits = (float)($cashoutRow['debits'] ?? 0);


        /*
        |--------------------------------------------------------------------------
        | BALANCE
        |--------------------------------------------------------------------------
        */

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

?>


<style>

    /*
    |--------------------------------------------------------------------------
    | BUSINESS HOME
    |--------------------------------------------------------------------------
    */

    .business-home
    {
        padding: 20px 20px 40px;
    }


    /*
    |--------------------------------------------------------------------------
    | WELCOME HEADER
    |--------------------------------------------------------------------------
    */

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

        box-shadow:
            0 8px 25px rgba(0,0,0,.08);
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

        background:
            rgba(255,255,255,.06);
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

        background:
            rgba(255,255,255,.04);
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

        color:
            rgba(255,255,255,.65);
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

        color:
            rgba(255,255,255,.78);

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


            <?php if(hasRole(['owner','superadministrator'])): ?>

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

                    Active books attached to this business

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

                    Cash in less cash out across all books

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
                            type="button"
                            class="
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



                    <?php if(hasRole(['owner','superadministrator'])): ?>
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

/*
|--------------------------------------------------------------------------
| PAGE FOOTER
|--------------------------------------------------------------------------
|
| Important:
| Do NOT load jQuery, Bootstrap, FontAwesome, custom.js or script.js again
| on this page. pageFooter() handles our global assets and their versioning.
|
*/

?>