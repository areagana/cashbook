<?php

require_once(__DIR__.'/../assets/functions.php');


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if(!isVerified())
{
    redirect('../');
    exit;
}


/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

pageHeader('Route Managers');


/*
|--------------------------------------------------------------------------
| BOOK
|--------------------------------------------------------------------------
*/

$bsid = request('bsid');

$book_id = encryptor(
    'decrypt',
    $bsid
);

$book = bookFind(
    $book_id
);


/*
|--------------------------------------------------------------------------
| VALIDATE BOOK
|--------------------------------------------------------------------------
*/

if(!$book)
{
    ?>

        <div class="container-fluid p-4">

            <div class="alert alert-warning">

                <strong>Book not found.</strong>

                The requested business book could not be loaded.

            </div>

        </div>

    <?php

    pageFooter();

    exit;
}


/*
|--------------------------------------------------------------------------
| ROUTE MANAGERS + ROUTES + CUSTOMERS + BALANCES
|--------------------------------------------------------------------------
|
| Relationship:
|
| route manager
|      ↓
| cashbook_routes.managers
|      ↓
| cashbook_customers.route_id
|      ↓
| cashbook_customer_balances.customer_id
|
| We use subqueries here to avoid balance multiplication when joins contain
| several routes/customers.
|
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        crm.*,


        /* -------------------------------------------------------------
           ROUTES MANAGED
        ------------------------------------------------------------- */

        (
            SELECT COUNT(*)

            FROM cashbook_routes cr

            WHERE cr.managers = crm.id
            AND cr.book_id = crm.book_id

        ) AS total_routes,


        /* -------------------------------------------------------------
           CUSTOMERS UNDER MANAGER'S ROUTES
        ------------------------------------------------------------- */

        (
            SELECT COUNT(DISTINCT cc.id)

            FROM cashbook_routes cr

            INNER JOIN cashbook_customers cc
                ON cc.route_id = cr.id
                AND cc.book_id = cr.book_id

            WHERE cr.managers = crm.id
            AND cr.book_id = crm.book_id

        ) AS total_customers,


        /* -------------------------------------------------------------
           TOTAL CUSTOMER BALANCE
        ------------------------------------------------------------- */

        (
            SELECT COALESCE(
                SUM(ccb.balance),
                0
            )

            FROM cashbook_routes cr

            INNER JOIN cashbook_customers cc
                ON cc.route_id = cr.id
                AND cc.book_id = cr.book_id

            LEFT JOIN cashbook_customer_balances ccb
                ON ccb.customer_id = cc.id

            WHERE cr.managers = crm.id
            AND cr.book_id = crm.book_id

        ) AS manager_balance,


        /* -------------------------------------------------------------
           CUSTOMERS OWING
        ------------------------------------------------------------- */

        (
            SELECT COUNT(DISTINCT cc.id)

            FROM cashbook_routes cr

            INNER JOIN cashbook_customers cc
                ON cc.route_id = cr.id
                AND cc.book_id = cr.book_id

            INNER JOIN cashbook_customer_balances ccb
                ON ccb.customer_id = cc.id

            WHERE cr.managers = crm.id
            AND cr.book_id = crm.book_id
            AND ccb.balance > 0

        ) AS customers_owing


    FROM cashbook_route_managers crm


    WHERE crm.book_id = ?


    ORDER BY crm.name ASC

";


$res = prepared_statements(
    $sql,
    'i',
    [$book->id]
);


/*
|--------------------------------------------------------------------------
| STORE MANAGERS / DASHBOARD TOTALS
|--------------------------------------------------------------------------
*/

$managers = [];

$totalManagers = 0;

$totalRoutes = 0;

$totalCustomers = 0;

$totalManagerBalance = 0;

$managersWithBalances = 0;


if($res)
{

    while($row = $res->fetch_assoc())
    {

        $row['total_routes'] =
            (int)($row['total_routes'] ?? 0);


        $row['total_customers'] =
            (int)($row['total_customers'] ?? 0);


        $row['manager_balance'] =
            (float)($row['manager_balance'] ?? 0);


        $row['customers_owing'] =
            (int)($row['customers_owing'] ?? 0);


        $managers[] =
            $row;


        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        $totalManagers++;


        $totalRoutes +=
            $row['total_routes'];


        $totalCustomers +=
            $row['total_customers'];


        $totalManagerBalance +=
            $row['manager_balance'];


        if($row['manager_balance'] > 0)
        {
            $managersWithBalances++;
        }

    }

}

?>


<style>

    /*
    |--------------------------------------------------------------------------
    | PAGE
    |--------------------------------------------------------------------------
    */

    .route-managers-page
    {
        padding:
            18px 18px 40px;
    }


    /*
    |--------------------------------------------------------------------------
    | BREADCRUMB
    |--------------------------------------------------------------------------
    */

    .rm-breadcrumb
    {
        display: flex;

        align-items: center;

        gap: 8px;

        margin-bottom: 15px;

        color: #718096;

        font-size: 12px;
    }


    .rm-breadcrumb a
    {
        color: #087f5b;

        text-decoration: none;
    }


    .rm-breadcrumb a:hover
    {
        text-decoration: underline;
    }


    /*
    |--------------------------------------------------------------------------
    | HEADER
    |--------------------------------------------------------------------------
    */

    .rm-header
    {
        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 20px;

        padding: 22px;

        margin-bottom: 20px;

        background:
            linear-gradient(
                135deg,
                #0e2238 0%,
                #123b52 60%,
                #087f5b 100%
            );

        color: #ffffff;

        border-radius: 10px;

        box-shadow:
            0 5px 18px rgba(0,0,0,.07);
    }


    .rm-header-label
    {
        margin-bottom: 5px;

        color:
            rgba(255,255,255,.60);

        font-size: 10px;

        font-weight: 700;

        letter-spacing: 1px;

        text-transform: uppercase;
    }


    .rm-header h1
    {
        margin: 0;

        font-size: 24px;

        font-weight: 700;
    }


    .rm-header p
    {
        margin:
            6px 0 0;

        color:
            rgba(255,255,255,.75);

        font-size: 12px;
    }


    .rm-header .btn
    {
        white-space: nowrap;

        border-color:
            rgba(255,255,255,.60);

        color: #ffffff;
    }


    .rm-header .btn:hover
    {
        background: #ffffff;

        color: #087f5b;
    }


    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    .rm-summary-card
    {
        height: 100%;

        padding: 18px;

        background: #ffffff;

        border:
            1px solid #e7edf2;

        border-radius: 8px;

        box-shadow:
            0 3px 13px rgba(15,23,42,.04);
    }


    .rm-summary-top
    {
        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 10px;
    }


    .rm-summary-label
    {
        color: #718096;

        font-size: 10px;

        font-weight: 700;

        letter-spacing: .7px;

        text-transform: uppercase;
    }


    .rm-summary-value
    {
        margin-top: 6px;

        color: #172b3a;

        font-size: 22px;

        font-weight: 700;
    }


    .rm-summary-note
    {
        margin-top: 3px;

        color: #9aa5b1;

        font-size: 10px;
    }


    .rm-summary-icon
    {
        width: 39px;

        height: 39px;

        min-width: 39px;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #eef8f5;

        color: #087f5b;

        border-radius: 7px;

        font-size: 16px;
    }


    /*
    |--------------------------------------------------------------------------
    | MAIN PANEL
    |--------------------------------------------------------------------------
    */

    .rm-panel
    {
        margin-top: 8px;

        background: #ffffff;

        border:
            1px solid #e7edf2;

        border-radius: 8px;

        box-shadow:
            0 3px 13px rgba(15,23,42,.04);

        overflow: hidden;
    }


    .rm-panel-header
    {
        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 15px;

        padding:
            17px 20px;

        border-bottom:
            1px solid #edf1f5;
    }


    .rm-panel-header h2
    {
        margin: 0;

        color: #172b3a;

        font-size: 16px;

        font-weight: 700;
    }


    .rm-panel-header p
    {
        margin:
            4px 0 0;

        color: #8492a6;

        font-size: 11px;
    }


    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    .rm-table-wrap
    {
        padding: 15px;

        overflow-x: auto;
    }


    .rm-table
    {
        width: 100%;

        margin-bottom: 0;
    }


    .rm-table thead th
    {
        padding:
            11px 9px;

        border-top: 0;

        border-bottom:
            1px solid #dde5eb;

        color: #718096;

        font-size: 9px;

        font-weight: 700;

        letter-spacing: .5px;

        text-transform: uppercase;

        white-space: nowrap;
    }


    .rm-table tbody td
    {
        padding:
            13px 9px;

        vertical-align: middle;

        border-top:
            1px solid #edf1f5;

        color: #4a5568;

        font-size: 11px;
    }


    /*
    |--------------------------------------------------------------------------
    | MANAGER
    |--------------------------------------------------------------------------
    */

    .rm-manager
    {
        display: flex;

        align-items: center;

        gap: 9px;
    }


    .rm-manager-avatar
    {
        width: 34px;

        height: 34px;

        min-width: 34px;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #eef8f5;

        color: #087f5b;

        border-radius: 50%;

        font-size: 12px;

        font-weight: 700;
    }


    .rm-manager-name
    {
        color: #172b3a;

        font-weight: 700;
    }


    .rm-manager-sub
    {
        margin-top: 2px;

        color: #9aa5b1;

        font-size: 9px;
    }


    /*
    |--------------------------------------------------------------------------
    | COUNTERS
    |--------------------------------------------------------------------------
    */

    .rm-counter
    {
        display: inline-flex;

        align-items: center;

        gap: 5px;

        padding:
            4px 8px;

        background: #f3f6f8;

        border-radius: 4px;

        color: #4a5568;

        font-weight: 600;
    }


    /*
    |--------------------------------------------------------------------------
    | BALANCE
    |--------------------------------------------------------------------------
    */

    .rm-balance
    {
        font-weight: 700;

        white-space: nowrap;
    }


    .rm-balance.owing
    {
        color: #d97706;
    }


    .rm-balance.cleared
    {
        color: #087f5b;
    }


    .rm-balance.zero
    {
        color: #718096;
    }


    /*
    |--------------------------------------------------------------------------
    | EMPTY
    |--------------------------------------------------------------------------
    */

    .rm-empty
    {
        padding:
            45px 20px;

        text-align: center;
    }


    .rm-empty i
    {
        margin-bottom: 12px;

        color: #cbd5df;

        font-size: 38px;
    }


    .rm-empty h3
    {
        color: #34495e;

        font-size: 16px;
    }


    .rm-empty p
    {
        color: #8492a6;

        font-size: 12px;
    }


    /*
    |--------------------------------------------------------------------------
    | MOBILE
    |--------------------------------------------------------------------------
    */

    @media(max-width: 767px)
    {

        .route-managers-page
        {
            padding:
                12px 8px 30px;
        }


        .rm-header
        {
            align-items:
                flex-start;

            flex-direction:
                column;

            padding: 18px;
        }


        .rm-header h1
        {
            font-size: 20px;
        }


        .rm-panel-header
        {
            align-items:
                flex-start;

            flex-direction:
                column;
        }


        .rm-table-wrap
        {
            padding: 8px;
        }

    }

</style>



<!-- ================================================================
     PAGE
================================================================ -->

<div class="container-fluid route-managers-page">


    <!-- ============================================================
         BREADCRUMB
    ============================================================= -->

    <div class="rm-breadcrumb">


        <a href="../books/?bkid=<?=$bsid;?>">

            <i class="fa fa-book"></i>

            Books

        </a>


        <i class="fa fa-angle-right"></i>


        <span>

            Route Managers

        </span>


    </div>



    <!-- ============================================================
         HEADER
    ============================================================= -->

    <div class="rm-header">


        <div>


            <div class="rm-header-label">

                Customer Distribution

            </div>


            <h1>

                <?=htmlspecialchars(
                    strToUpper($book->name)
                );?>

                - Route Managers

            </h1>


            <p>

                Manage route managers and monitor the routes,
                customers and outstanding balances under their responsibility.

            </p>


        </div>



        <?php
        if(
            hasRole([
                'owner',
                'partner',
                'superadministrator'
            ])
        ):
        ?>


            <button
                type="button"
                class="
                    btn
                    btn-sm
                    btn-outline-light
                    btn-flat
                    btn-click
                "
                data-title="Add Route Manager"
                data-section="route_manager"
            >

                <i class="fa fa-plus-circle mr-1"></i>

                Route Manager

            </button>


        <?php endif; ?>


    </div>



    <!-- ============================================================
         SUMMARY
    ============================================================= -->

    <div class="row">


        <!-- MANAGERS -->

        <div class="col-6 col-lg-3 mb-3">


            <div class="rm-summary-card">


                <div class="rm-summary-top">


                    <div>


                        <div class="rm-summary-label">

                            Route Managers

                        </div>


                        <div class="rm-summary-value">

                            <?=number_format(
                                $totalManagers
                            );?>

                        </div>


                    </div>


                    <div class="rm-summary-icon">

                        <i class="fa fa-user-tie"></i>

                    </div>


                </div>


                <div class="rm-summary-note">

                    Registered managers

                </div>


            </div>


        </div>



        <!-- ROUTES -->

        <div class="col-6 col-lg-3 mb-3">


            <div class="rm-summary-card">


                <div class="rm-summary-top">


                    <div>


                        <div class="rm-summary-label">

                            Managed Routes

                        </div>


                        <div class="rm-summary-value">

                            <?=number_format(
                                $totalRoutes
                            );?>

                        </div>


                    </div>


                    <div class="rm-summary-icon">

                        <i class="fa fa-route"></i>

                    </div>


                </div>


                <div class="rm-summary-note">

                    Routes assigned to managers

                </div>


            </div>


        </div>



        <!-- CUSTOMERS -->

        <div class="col-6 col-lg-3 mb-3">


            <div class="rm-summary-card">


                <div class="rm-summary-top">


                    <div>


                        <div class="rm-summary-label">

                            Managed Customers

                        </div>


                        <div class="rm-summary-value">

                            <?=number_format(
                                $totalCustomers
                            );?>

                        </div>


                    </div>


                    <div class="rm-summary-icon">

                        <i class="fa fa-users"></i>

                    </div>


                </div>


                <div class="rm-summary-note">

                    Customers across managed routes

                </div>


            </div>


        </div>



        <!-- BALANCE -->

        <div class="col-6 col-lg-3 mb-3">


            <div class="rm-summary-card">


                <div class="rm-summary-top">


                    <div>


                        <div class="rm-summary-label">

                            Customer Balances

                        </div>


                        <div
                            class="
                                rm-summary-value
                                <?=$totalManagerBalance > 0
                                    ? 'text-warning'
                                    : 'text-success';?>
                            "
                        >

                            <?=number_format(
                                $totalManagerBalance,
                                0
                            );?>/=

                        </div>


                    </div>


                    <div class="rm-summary-icon">

                        <i class="fa fa-wallet"></i>

                    </div>


                </div>


                <div class="rm-summary-note">

                    Balance across all managed customers

                </div>


            </div>


        </div>


    </div>



    <!-- ============================================================
         MANAGER REGISTER
    ============================================================= -->

    <div class="rm-panel">


        <div class="rm-panel-header">


            <div>


                <h2>

                    <i
                        class="
                            fa
                            fa-user-tie
                            text-success
                            mr-2
                        "
                    ></i>

                    Route Manager Register

                </h2>


                <p>

                    View each manager's assigned routes,
                    customers and combined customer balance.

                </p>


            </div>



            <?php
            if(
                hasRole([
                    'owner',
                    'partner',
                    'superadministrator'
                ])
            ):
            ?>


                <button
                    type="button"
                    class="
                        btn
                        btn-sm
                        btn-success
                        btn-flat
                        btn-click
                    "
                    data-title="Add Route Manager"
                    data-section="route_manager"
                >

                    <i class="fa fa-plus-circle mr-1"></i>

                    Route Manager

                </button>


            <?php endif; ?>


        </div>



        <?php if(count($managers) > 0): ?>


            <div class="rm-table-wrap">


                <table
                    class="
                        table
                        table-sm
                        rm-table
                        dataTable
                    "
                >


                    <thead>


                        <tr>


                            <th style="width:40px;">

                                #

                            </th>


                            <th>

                                Manager

                            </th>


                            <th>

                                Contact

                            </th>


                            <th class="text-center">

                                Routes

                            </th>


                            <th class="text-center">

                                Customers

                            </th>


                            <th class="text-center">

                                Owing

                            </th>


                            <th class="text-right">

                                Balance

                            </th>


                            <th class="text-right">

                                Action

                            </th>


                        </tr>


                    </thead>



                    <tbody>


                        <?php

                        $s = 1;

                        foreach(
                            $managers
                            as $r
                        ):


                            /*
                            |--------------------------------------------------------------------------
                            | BALANCE CLASS
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $r['manager_balance'] > 0
                            )
                            {

                                $balanceClass =
                                    'owing';

                            }
                            elseif(
                                $r['manager_balance'] < 0
                            )
                            {

                                $balanceClass =
                                    'cleared';

                            }
                            else
                            {

                                $balanceClass =
                                    'zero';

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | INITIALS
                            |--------------------------------------------------------------------------
                            */

                            $initial =
                                strtoupper(
                                    substr(
                                        trim(
                                            $r['name']
                                        ),
                                        0,
                                        1
                                    )
                                );

                        ?>


                            <tr
                                class="
                                    hover
                                    hover-hide-content
                                "
                            >


                                <td>

                                    <?=$s++;?>

                                </td>



                                <!-- MANAGER -->

                                <td>


                                    <div class="rm-manager">


                                        <div class="rm-manager-avatar">

                                            <?=htmlspecialchars(
                                                $initial
                                            );?>

                                        </div>


                                        <div>


                                            <div class="rm-manager-name">

                                                <?=htmlspecialchars(
                                                    $r['name']
                                                );?>

                                            </div>


                                            <div class="rm-manager-sub">

                                                Route Manager

                                            </div>


                                        </div>


                                    </div>


                                </td>



                                <!-- CONTACT -->

                                <td>


                                    <?php

                                    if(
                                        !empty(
                                            $r['contact1']
                                        )
                                    )
                                    {

                                        echo htmlspecialchars(
                                            $r['contact1']
                                        );

                                    }
                                    else
                                    {

                                        echo
                                            '<span class="text-muted">-</span>';

                                    }

                                    ?>


                                    <?php
                                    if(
                                        !empty(
                                            $r['contact2']
                                        )
                                    ):
                                    ?>


                                        <div class="text-muted mt-1">

                                            <?=htmlspecialchars(
                                                $r['contact2']
                                            );?>

                                        </div>


                                    <?php endif; ?>


                                </td>



                                <!-- ROUTES -->

                                <td class="text-center">


                                    <span class="rm-counter">


                                        <i
                                            class="
                                                fa
                                                fa-route
                                                text-muted
                                            "
                                        ></i>


                                        <?=number_format(
                                            $r['total_routes']
                                        );?>


                                    </span>


                                </td>



                                <!-- CUSTOMERS -->

                                <td class="text-center">


                                    <span class="rm-counter">


                                        <i
                                            class="
                                                fa
                                                fa-users
                                                text-muted
                                            "
                                        ></i>


                                        <?=number_format(
                                            $r['total_customers']
                                        );?>


                                    </span>


                                </td>



                                <!-- OWING -->

                                <td class="text-center">


                                    <?php
                                    if(
                                        $r['customers_owing'] > 0
                                    ):
                                    ?>


                                        <span
                                            class="
                                                badge
                                                badge-warning
                                            "
                                        >

                                            <?=number_format(
                                                $r['customers_owing']
                                            );?>

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="
                                                badge
                                                badge-light
                                            "
                                        >

                                            0

                                        </span>


                                    <?php endif; ?>


                                </td>



                                <!-- BALANCE -->

                                <td
                                    class="
                                        text-right
                                        rm-balance
                                        <?=$balanceClass;?>
                                    "
                                >


                                    <?=number_format(
                                        $r['manager_balance'],
                                        0
                                    );?>/=


                                </td>



                                <!-- ACTION -->

                                <td class="text-right">


                                    <?php
                                    if(
                                        hasRole([
                                            'owner',
                                            'partner',
                                            'superadministrator'
                                        ])
                                    ):
                                    ?>


                                        <span
                                            class="
                                                hover-display
                                                text-sm
                                            "
                                        >


                                            <?php
                                            if(
                                                hasRole([
                                                    'owner',
                                                    'superadministrator'
                                                ])
                                            ):
                                            ?>


                                                <!-- EDIT -->

                                                <button
                                                    type="button"
                                                    class="
                                                        btn
                                                        btn-sm
                                                        btn-outline-info
                                                        edit-route-manager
                                                        text-muted
                                                        btn-click
                                                    "
                                                    data-section="route-manager-edit"
                                                    data-title="Edit Route Manager"
                                                    data-id="<?=$r['id'];?>"
                                                    title="Edit Manager"
                                                >

                                                    <i class="fa fa-edit"></i>

                                                </button>



                                                <!-- DELETE -->

                                                <button
                                                    type="button"
                                                    class="
                                                        btn
                                                        btn-sm
                                                        btn-outline-danger
                                                        delete-route-manager
                                                    "
                                                    data-id="<?=$r['id'];?>"
                                                    title="Delete Manager"
                                                >

                                                    <i class="fa fa-trash"></i>

                                                </button>


                                            <?php endif; ?>



                                            <!-- VIEW -->

                                            <button
                                                type="button"
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-secondary
                                                    view-route-manager
                                                "
                                                data-id="<?=$r['id'];?>"
                                                data-title="<?=
                                                    htmlspecialchars(
                                                        $r['name'],
                                                        ENT_QUOTES
                                                    );
                                                ?>"
                                                title="View Manager Details"
                                            >

                                                <i class="fa fa-eye"></i>

                                            </button>


                                        </span>


                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <!-- ====================================================
                 EMPTY STATE
            ===================================================== -->

            <div class="rm-empty">


                <i class="fa fa-user-tie"></i>


                <h3>

                    No Route Managers Yet

                </h3>


                <p>

                    Create route managers and assign routes
                    so that customers and their balances can
                    be monitored by manager.

                </p>


                <?php
                if(
                    hasRole([
                        'owner',
                        'partner',
                        'superadministrator'
                    ])
                ):
                ?>


                    <button
                        type="button"
                        class="
                            btn
                            btn-sm
                            btn-success
                            btn-flat
                            btn-click
                        "
                        data-title="Add Route Manager"
                        data-section="route_manager"
                    >

                        <i class="fa fa-plus-circle mr-1"></i>

                        Create Route Manager

                    </button>


                <?php endif; ?>


            </div>


        <?php endif; ?>


    </div>


</div>



<!-- ================================================================
     SIDE MODAL - CREATE / EDIT
================================================================ -->

<div
    class="
        p-0
        bg-white
        side-modal-tall
        absolute
        border
        shadow
    "
    id="side-modal-cashin"
>


    <div class="side-modal-header bg-success">


        <h3
            class="
                side-modal-title
                text-white
            "
        ></h3>


        <button
            type="button"
            class="side-modal-close"
        >

            &times;

        </button>


    </div>


    <div class="side-modal-content">

    </div>


</div>



<!-- ================================================================
     CENTRAL MODAL - DETAILS
================================================================ -->

<div
    class="
        p-0
        bg-white
        central-modal
        absolute
        border
        shadow
    "
    id="central-modal"
>


    <div class="central-modal-header bg-success">


        <h3 class="central-modal-title">

        </h3>


        <button
            type="button"
            class="central-modal-close"
        >

            &times;

        </button>


    </div>


    <div class="central-modal-content">

    </div>


</div>



<?php

/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
|
| Load the normal page footer BEFORE page-specific JavaScript.
|
| This preserves jQuery, xdialog and the global Cashbook scripts.
|
*/

pageFooter();

?>


<script>

/*
|--------------------------------------------------------------------------
| CREATE / EDIT MANAGER
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.btn-click',
    function(e)
    {

        e.preventDefault();


        var title =
            $(this).data('title')
            ||
            'Route Manager';


        title =
            String(title)
            .toUpperCase();


        var section =
            $(this).data('section');


        var id =
            $(this).data('id')
            ||
            '';


        /*
        |--------------------------------------------------------------------------
        | OPEN MODAL
        |--------------------------------------------------------------------------
        */

        $('#side-modal-cashin')
            .show();


        $('#side-modal-cashin .side-modal-title')
            .html(title);


        /*
        |--------------------------------------------------------------------------
        | FETCH FORM
        |--------------------------------------------------------------------------
        */

        fetchManagerForm(
            section,
            id
        );

    }
);



/*
|--------------------------------------------------------------------------
| FETCH CREATE / EDIT FORM
|--------------------------------------------------------------------------
*/

function fetchManagerForm(
    section,
    id
)
{

    var book_id =
        "<?=$book->id;?>";


    if(!section)
    {
        return;
    }


    $.ajax({

        url:
            '../books/save/index.php',


        type:
            'GET',


        data:
        {

            section:
                section,

            book_id:
                book_id,

            action:
                'fetchForm',

            route_manager_id:
                id

        },


        beforeSend:
            function()
            {

                $('#side-modal-cashin .side-modal-content')
                    .html(
                        '<div class="p-5 text-center">'
                        +
                        '<i class="fa fa-spinner fa-spin fa-2x text-success"></i>'
                        +
                        '<p class="mt-3 text-muted">'
                        +
                        'Loading manager form...'
                        +
                        '</p>'
                        +
                        '</div>'
                    );

            },


        success:
            function(response)
            {

                $('#side-modal-cashin .side-modal-content')
                    .html(
                        response
                    );

            },


        error:
            function(xhr)
            {

                console.error(
                    'Manager form error:',
                    xhr.responseText
                );


                $('#side-modal-cashin .side-modal-content')
                    .html(
                        '<div class="alert alert-danger m-3">'
                        +
                        '<strong>Unable to load manager form.</strong>'
                        +
                        '</div>'
                    );

            }

    });

}



/*
|--------------------------------------------------------------------------
| SAVE ROUTE MANAGER
|--------------------------------------------------------------------------
|
| The form is dynamically loaded.
|
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.saveRouteManager',
    function(e)
    {

        e.preventDefault();


        var form =
            document.getElementById(
                'newRouteManagerForm'
            );


        if(!form)
        {

            console.error(
                'newRouteManagerForm not found'
            );


            xdialog.alert(
                'Route Manager form could not be found.'
            );


            return;

        }


        saveRouteManagerForm(
            form
        );

    }
);



/*
|--------------------------------------------------------------------------
| FORM SUBMIT
|--------------------------------------------------------------------------
*/

$(document).on(
    'submit',
    '#newRouteManagerForm',
    function(e)
    {

        e.preventDefault();


        saveRouteManagerForm(
            this
        );

    }
);



/*
|--------------------------------------------------------------------------
| SAVE FUNCTION
|--------------------------------------------------------------------------
*/

function saveRouteManagerForm(
    form
)
{

    /*
    |--------------------------------------------------------------------------
    | PREVENT DOUBLE SUBMISSION
    |--------------------------------------------------------------------------
    */

    if(
        form.dataset.saving ===
        '1'
    )
    {
        return;
    }


    form.dataset.saving =
        '1';


    var formData =
        new FormData(
            form
        );


    xdialog.startSpin();


    fetch(
        '../books/save/index.php',
        {

            method:
                'POST',

            body:
                formData

        }
    )

    .then(
        function(response)
        {

            if(!response.ok)
            {

                throw new Error(
                    'HTTP '
                    +
                    response.status
                );

            }


            return response.text();

        }
    )

    .then(
        function(response)
        {

            console.log(
                'Route Manager response:',
                response
            );


            xdialog.stopSpin();


            window.location.reload();

        }
    )

    .catch(
        function(error)
        {

            form.dataset.saving =
                '0';


            xdialog.stopSpin();


            console.error(
                'Route Manager save error:',
                error
            );


            xdialog.alert(
                'Unable to save the Route Manager.'
            );

        }
    );

}



/*
|--------------------------------------------------------------------------
| VIEW ROUTE MANAGER
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| The old page was incorrectly sending:
|
| customer_id
| action = Customer-details
|
| We now send the actual route manager ID.
|
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.view-route-manager',
    function(e)
    {

        e.preventDefault();


        var id =
            $(this).data('id');


        var title =
            $(this).data('title')
            ||
            'Route Manager';


        /*
        |--------------------------------------------------------------------------
        | OPEN CENTRAL MODAL
        |--------------------------------------------------------------------------
        */

        $('#central-modal')
            .show();


        $('#central-modal .central-modal-title')
            .html(
                title
                +
                ' - Details'
            );


        /*
        |--------------------------------------------------------------------------
        | FETCH MANAGER DETAILS
        |--------------------------------------------------------------------------
        */

        $.ajax({

            url:
                'save/index.php',


            type:
                'GET',


            data:
            {

                route_manager_id:
                    id,

                action:
                    'route-manager-details'

            },


            beforeSend:
                function()
                {

                    $('#central-modal .central-modal-content')
                        .html(
                            '<div class="p-5 text-center">'
                            +
                            '<i class="fa fa-spinner fa-spin fa-2x text-success"></i>'
                            +
                            '<p class="mt-3 text-muted">'
                            +
                            'Loading manager details...'
                            +
                            '</p>'
                            +
                            '</div>'
                        );

                },


            success:
                function(response)
                {

                    $('#central-modal .central-modal-content')
                        .html(
                            response
                        );

                },


            error:
                function(xhr)
                {

                    console.error(
                        'Manager details error:',
                        xhr.responseText
                    );


                    $('#central-modal .central-modal-content')
                        .html(
                            '<div class="alert alert-danger m-3">'
                            +
                            'Unable to load manager details.'
                            +
                            '</div>'
                        );

                }

        });

    }
);



/*
|--------------------------------------------------------------------------
| CLOSE SIDE MODAL
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.side-modal-close',
    function()
    {

        $('#side-modal-cashin')
            .hide();

    }
);



/*
|--------------------------------------------------------------------------
| CLOSE CENTRAL MODAL
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.central-modal-close',
    function()
    {

        $('#central-modal')
            .hide();

    }
);

</script>