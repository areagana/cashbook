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
| PAGE
|--------------------------------------------------------------------------
*/

pageHeader('Routes');


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
| ROUTES + CUSTOMERS + CUSTOMER BALANCES
|--------------------------------------------------------------------------
|
| Important:
|
| cashbook_customers.route_id
|      links customer -> route
|
| cashbook_customer_balances.customer_id
|      gives current customer balance
|
| This gives us:
|
| - number of customers per route
| - total customer balance per route
|
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        cr.*,

        crm.name AS manager,

        COUNT(DISTINCT cc.id) AS customers,

        COALESCE(
            SUM(
                DISTINCT CASE
                    WHEN ccb.id IS NOT NULL
                    THEN ccb.balance
                    ELSE 0
                END
            ),
            0
        ) AS route_balance

    FROM cashbook_routes cr


    LEFT JOIN cashbook_route_managers crm

        ON crm.id = cr.managers


    LEFT JOIN cashbook_customers cc

        ON cc.route_id = cr.id

        AND cc.book_id = cr.book_id


    LEFT JOIN cashbook_customer_balances ccb

        ON ccb.customer_id = cc.id


    WHERE cr.book_id = ?


    GROUP BY

        cr.id,
        cr.name,
        cr.details,
        cr.managers,
        crm.name


    ORDER BY cr.name ASC

";


$res = prepared_statements(
    $sql,
    'i',
    [$book->id]
);


/*
|--------------------------------------------------------------------------
| STORE ROUTES
|--------------------------------------------------------------------------
|
| We store results first so we can calculate dashboard totals
| before rendering the table.
|
*/

$routes = [];

$totalRoutes = 0;

$totalCustomers = 0;

$totalRouteBalance = 0;

$routesWithBalances = 0;


if($res)
{
    while($row = $res->fetch_assoc())
    {
        $row['customers'] =
            (int)($row['customers'] ?? 0);


        $row['route_balance'] =
            (float)($row['route_balance'] ?? 0);


        $routes[] = $row;


        /*
        |--------------------------------------------------------------------------
        | DASHBOARD TOTALS
        |--------------------------------------------------------------------------
        */

        $totalRoutes++;


        $totalCustomers +=
            $row['customers'];


        $totalRouteBalance +=
            $row['route_balance'];


        if($row['route_balance'] > 0)
        {
            $routesWithBalances++;
        }
    }
}

?>


<style>

    /*
    |--------------------------------------------------------------------------
    | ROUTES PAGE
    |--------------------------------------------------------------------------
    */

    .routes-page
    {
        padding:
            18px 18px 40px;
    }


    /*
    |--------------------------------------------------------------------------
    | BREADCRUMB
    |--------------------------------------------------------------------------
    */

    .route-breadcrumb
    {
        display: flex;

        align-items: center;

        gap: 8px;

        margin-bottom: 15px;

        color: #718096;

        font-size: 12px;
    }


    .route-breadcrumb a
    {
        color: #087f5b;

        text-decoration: none;
    }


    .route-breadcrumb a:hover
    {
        text-decoration: underline;
    }


    /*
    |--------------------------------------------------------------------------
    | PAGE HEADER
    |--------------------------------------------------------------------------
    */

    .route-header
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


    .route-header-label
    {
        margin-bottom: 5px;

        color:
            rgba(255,255,255,.60);

        font-size: 10px;

        font-weight: 700;

        letter-spacing: 1px;

        text-transform: uppercase;
    }


    .route-header h1
    {
        margin: 0;

        font-size: 24px;

        font-weight: 700;
    }


    .route-header p
    {
        margin:
            6px 0 0;

        color:
            rgba(255,255,255,.75);

        font-size: 12px;
    }


    .route-header .btn
    {
        white-space: nowrap;

        border-color:
            rgba(255,255,255,.60);

        color: #ffffff;
    }


    .route-header .btn:hover
    {
        background: #ffffff;

        color: #087f5b;
    }


    /*
    |--------------------------------------------------------------------------
    | SUMMARY CARDS
    |--------------------------------------------------------------------------
    */

    .route-summary-card
    {
        position: relative;

        height: 100%;

        padding: 18px;

        background: #ffffff;

        border:
            1px solid #e7edf2;

        border-radius: 8px;

        box-shadow:
            0 3px 13px rgba(15,23,42,.04);
    }


    .route-summary-top
    {
        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 10px;
    }


    .route-summary-label
    {
        color: #718096;

        font-size: 10px;

        font-weight: 700;

        letter-spacing: .7px;

        text-transform: uppercase;
    }


    .route-summary-value
    {
        margin-top: 6px;

        color: #172b3a;

        font-size: 22px;

        font-weight: 700;
    }


    .route-summary-note
    {
        margin-top: 3px;

        color: #9aa5b1;

        font-size: 10px;
    }


    .route-summary-icon
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
    | ROUTE PANEL
    |--------------------------------------------------------------------------
    */

    .route-panel
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


    .route-panel-header
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


    .route-panel-header h2
    {
        margin: 0;

        color: #172b3a;

        font-size: 16px;

        font-weight: 700;
    }


    .route-panel-header p
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

    .route-table-wrap
    {
        padding: 15px;

        overflow-x: auto;
    }


    .route-table
    {
        margin-bottom: 0;

        width: 100%;
    }


    .route-table thead th
    {
        padding:
            11px 10px;

        border-top: 0;

        border-bottom:
            1px solid #dde5eb;

        color: #718096;

        font-size: 10px;

        font-weight: 700;

        letter-spacing: .5px;

        text-transform: uppercase;

        white-space: nowrap;
    }


    .route-table tbody td
    {
        padding:
            13px 10px;

        vertical-align: middle;

        border-top:
            1px solid #edf1f5;

        color: #4a5568;

        font-size: 12px;
    }


    .route-name
    {
        color: #172b3a;

        font-weight: 700;
    }


    .route-manager
    {
        display: inline-flex;

        align-items: center;

        gap: 6px;
    }


    .route-manager-icon
    {
        width: 26px;

        height: 26px;

        display: inline-flex;

        align-items: center;

        justify-content: center;

        background: #f1f5f8;

        color: #607080;

        border-radius: 50%;

        font-size: 10px;
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER COUNT
    |--------------------------------------------------------------------------
    */

    .customer-count
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
    | BALANCES
    |--------------------------------------------------------------------------
    */

    .route-balance
    {
        font-weight: 700;

        white-space: nowrap;
    }


    .route-balance-positive
    {
        color: #d97706;
    }


    .route-balance-zero
    {
        color: #718096;
    }


    .route-balance-negative
    {
        color: #087f5b;
    }


    /*
    |--------------------------------------------------------------------------
    | EMPTY STATE
    |--------------------------------------------------------------------------
    */

    .route-empty
    {
        padding:
            45px 20px;

        text-align: center;
    }


    .route-empty i
    {
        margin-bottom: 12px;

        color: #cbd5df;

        font-size: 38px;
    }


    .route-empty h3
    {
        color: #34495e;

        font-size: 16px;
    }


    .route-empty p
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

        .routes-page
        {
            padding:
                12px 8px 30px;
        }


        .route-header
        {
            align-items:
                flex-start;

            flex-direction:
                column;

            padding: 18px;
        }


        .route-header h1
        {
            font-size: 20px;
        }


        .route-panel-header
        {
            align-items:
                flex-start;

            flex-direction:
                column;
        }


        .route-table-wrap
        {
            padding: 8px;
        }

    }

</style>



<!-- ================================================================
     ROUTES PAGE
================================================================ -->

<div class="container-fluid routes-page">


    <!-- ============================================================
         BREADCRUMB
    ============================================================= -->

    <div class="route-breadcrumb">

        <a href="../books/?bkid=<?=$bsid;?>">

            <i class="fa fa-book"></i>

            Books

        </a>


        <i class="fa fa-angle-right"></i>


        <span>

            Routes

        </span>

    </div>



    <!-- ============================================================
         HEADER
    ============================================================= -->

    <div class="route-header">


        <div>


            <div class="route-header-label">

                Customer Distribution

            </div>


            <h1>

                <?=htmlspecialchars(
                    strToUpper($book->name)
                );?>

                - Routes

            </h1>


            <p>

                Organise customers by route, assign route managers
                and monitor outstanding customer balances.

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
                data-title="Add Route"
                data-section="route"
            >

                <i class="fa fa-plus-circle mr-1"></i>

                Create Route

            </button>


        <?php endif; ?>


    </div>



    <!-- ============================================================
         DASHBOARD SUMMARY
    ============================================================= -->

    <div class="row">


        <!-- TOTAL ROUTES -->

        <div class="col-6 col-lg-3 mb-3">

            <div class="route-summary-card">


                <div class="route-summary-top">


                    <div>


                        <div class="route-summary-label">

                            Total Routes

                        </div>


                        <div class="route-summary-value">

                            <?=number_format(
                                $totalRoutes
                            );?>

                        </div>


                    </div>


                    <div class="route-summary-icon">

                        <i class="fa fa-route"></i>

                    </div>


                </div>


                <div class="route-summary-note">

                    Routes registered in this book

                </div>


            </div>

        </div>



        <!-- TOTAL CUSTOMERS -->

        <div class="col-6 col-lg-3 mb-3">

            <div class="route-summary-card">


                <div class="route-summary-top">


                    <div>


                        <div class="route-summary-label">

                            Route Customers

                        </div>


                        <div class="route-summary-value">

                            <?=number_format(
                                $totalCustomers
                            );?>

                        </div>


                    </div>


                    <div class="route-summary-icon">

                        <i class="fa fa-users"></i>

                    </div>


                </div>


                <div class="route-summary-note">

                    Customers assigned to routes

                </div>


            </div>

        </div>



        <!-- ROUTE BALANCES -->

        <div class="col-6 col-lg-3 mb-3">

            <div class="route-summary-card">


                <div class="route-summary-top">


                    <div>


                        <div class="route-summary-label">

                            Route Balances

                        </div>


                        <div
                            class="
                                route-summary-value
                                <?=$totalRouteBalance > 0
                                    ? 'text-warning'
                                    : 'text-success';?>
                            "
                        >

                            <?=number_format(
                                $totalRouteBalance,
                                0
                            );?>/=

                        </div>


                    </div>


                    <div class="route-summary-icon">

                        <i class="fa fa-wallet"></i>

                    </div>


                </div>


                <div class="route-summary-note">

                    Total balance of all route customers

                </div>


            </div>

        </div>



        <!-- ROUTES WITH BALANCES -->

        <div class="col-6 col-lg-3 mb-3">

            <div class="route-summary-card">


                <div class="route-summary-top">


                    <div>


                        <div class="route-summary-label">

                            Routes Owing

                        </div>


                        <div class="route-summary-value">

                            <?=number_format(
                                $routesWithBalances
                            );?>

                        </div>


                    </div>


                    <div class="route-summary-icon">

                        <i
                            class="
                                fa
                                fa-exclamation-circle
                            "
                        ></i>

                    </div>


                </div>


                <div class="route-summary-note">

                    Routes with positive balances

                </div>


            </div>

        </div>


    </div>



    <!-- ============================================================
         ROUTE REGISTER
    ============================================================= -->

    <div class="route-panel">


        <div class="route-panel-header">


            <div>


                <h2>

                    <i
                        class="
                            fa
                            fa-route
                            text-success
                            mr-2
                        "
                    ></i>

                    Route Register

                </h2>


                <p>

                    Customers and outstanding balances
                    grouped by their assigned routes.

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
                    data-title="Add Route"
                    data-section="route"
                >

                    <i
                        class="
                            fa
                            fa-plus-circle
                            mr-1
                        "
                    ></i>

                    Create Route

                </button>


            <?php endif; ?>


        </div>



        <?php if(count($routes) > 0): ?>


            <div class="route-table-wrap">


                <table
                    class="
                        table
                        table-sm
                        route-table
                        dataTable
                    "
                >


                    <thead>


                        <tr>


                            <th style="width:45px;">

                                #

                            </th>


                            <th>

                                Route

                            </th>


                            <th>

                                Details

                            </th>


                            <th>

                                Manager

                            </th>


                            <th class="text-center">

                                Customers

                            </th>


                            <th class="text-right">

                                Route Balance

                            </th>


                            <th class="text-right">

                                Action

                            </th>


                        </tr>


                    </thead>



                    <tbody>


                        <?php

                        $s = 1;

                        foreach($routes as $r):


                            /*
                            |--------------------------------------------------------------------------
                            | BALANCE CLASS
                            |--------------------------------------------------------------------------
                            */

                            if($r['route_balance'] > 0)
                            {
                                $balanceClass =
                                    'route-balance-positive';
                            }
                            elseif($r['route_balance'] < 0)
                            {
                                $balanceClass =
                                    'route-balance-negative';
                            }
                            else
                            {
                                $balanceClass =
                                    'route-balance-zero';
                            }

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



                                <!-- ROUTE -->

                                <td>


                                    <div class="route-name">

                                        <?=htmlspecialchars(
                                            $r['name']
                                        );?>

                                    </div>


                                </td>



                                <!-- DETAILS -->

                                <td>


                                    <?php

                                    if(
                                        isset($r['details']) &&
                                        trim($r['details']) != ''
                                    )
                                    {

                                        echo htmlspecialchars(
                                            $r['details']
                                        );

                                    }
                                    else
                                    {

                                        echo
                                            '<span class="text-muted">'
                                            .'No details'
                                            .'</span>';

                                    }

                                    ?>


                                </td>



                                <!-- MANAGER -->

                                <td>


                                    <?php
                                    if(
                                        isset($r['manager']) &&
                                        trim($r['manager']) != ''
                                    ):
                                    ?>


                                        <span class="route-manager">


                                            <span
                                                class="
                                                    route-manager-icon
                                                "
                                            >

                                                <i
                                                    class="
                                                        fa
                                                        fa-user
                                                    "
                                                ></i>

                                            </span>


                                            <?=htmlspecialchars(
                                                $r['manager']
                                            );?>


                                        </span>


                                    <?php else: ?>


                                        <span class="text-muted">

                                            Not assigned

                                        </span>


                                    <?php endif; ?>


                                </td>



                                <!-- CUSTOMERS -->

                                <td class="text-center">


                                    <span class="customer-count">


                                        <i
                                            class="
                                                fa
                                                fa-users
                                                text-muted
                                            "
                                        ></i>


                                        <?=number_format(
                                            $r['customers']
                                        );?>


                                    </span>


                                </td>



                                <!-- BALANCE -->

                                <td
                                    class="
                                        text-right
                                        route-balance
                                        <?=$balanceClass;?>
                                    "
                                >


                                    <?=number_format(
                                        $r['route_balance'],
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
                                                        edit-route
                                                        text-muted
                                                        btn-click
                                                    "
                                                    data-title="Edit Route"
                                                    data-section="route-edit"
                                                    data-id="<?=$r['id'];?>"
                                                    title="Edit Route"
                                                >

                                                    <i
                                                        class="
                                                            fa
                                                            fa-edit
                                                        "
                                                    ></i>

                                                </button>



                                                <!-- DELETE -->

                                                <button
                                                    type="button"
                                                    class="
                                                        btn
                                                        btn-sm
                                                        btn-outline-danger
                                                        delete-route
                                                    "
                                                    data-id="<?=$r['id'];?>"
                                                    title="Delete Route"
                                                >

                                                    <i
                                                        class="
                                                            fa
                                                            fa-trash
                                                        "
                                                    ></i>

                                                </button>


                                            <?php endif; ?>



                                            <!-- VIEW -->

                                            <button
                                                type="button"
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-secondary
                                                    view-route
                                                "
                                                data-id="<?=$r['id'];?>"
                                                data-title="<?=
                                                    htmlspecialchars(
                                                        $r['name'],
                                                        ENT_QUOTES
                                                    );
                                                ?>"
                                                title="View Route"
                                            >

                                                <i
                                                    class="
                                                        fa
                                                        fa-eye
                                                    "
                                                ></i>

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

            <div class="route-empty">


                <i class="fa fa-route"></i>


                <h3>

                    No Routes Created Yet

                </h3>


                <p>

                    Create routes to organise customers,
                    assign route managers and monitor
                    customer balances.

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
                            btn-success
                            btn-sm
                            btn-flat
                            btn-click
                        "
                        data-title="Add Route"
                        data-section="route"
                    >

                        <i
                            class="
                                fa
                                fa-plus-circle
                                mr-1
                            "
                        ></i>

                        Create First Route

                    </button>


                <?php endif; ?>


            </div>


        <?php endif; ?>


    </div>


</div>



<!-- ================================================================
     SIDE MODAL
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
     CENTRAL MODAL
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
    pageFooter();
?>
<script>

    /*
    |--------------------------------------------------------------------------
    | OPEN CREATE / EDIT ROUTE
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-click',
        function()
        {

            var title =
                $(this).data('title');


            if(title)
            {
                title =
                    String(title).toUpperCase();
            }


            $('.side-modal-tall').show();


            $('.side-modal-title').html(
                title
            );


            var category =
                $(this).data('section');


            var id =
                $(this).data('id');


            fetchData(
                category,
                id
            );

        }
    );



    /*
    |--------------------------------------------------------------------------
    | FETCH ROUTE FORM
    |--------------------------------------------------------------------------
    */

    function fetchData(
        sect,
        id
    )
    {

        var book_id =
            "<?=$book->id;?>";


        if(sect != '')
        {

            $.ajax({

                url:
                    '../books/save/index.php',

                data:
                {

                    section:
                        sect,

                    book_id:
                        book_id,

                    action:
                        'fetchForm',

                    route_id:
                        id

                },


                beforeSend:
                    function()
                    {

                        $('.side-modal-content')
                            .html(
                                "<div class='p-5 text-center'>"
                                +"<i class='fa fa-spinner fa-spin fa-2x text-success'></i>"
                                +"<p class='mt-3 text-muted'>Loading...</p>"
                                +"</div>"
                            );

                    },


                success:
                    function(res)
                    {

                        $('.side-modal-content')
                            .html(res);

                    },


                error:
                    function(err)
                    {

                        console.error(
                            err
                        );


                        $('.side-modal-content')
                            .html(
                                "<div class='alert alert-danger m-3'>"
                                +"Error loading route information."
                                +"</div>"
                            );

                    }

            });

        }

    }

    /*
|--------------------------------------------------------------------------
| PRINT ROUTE / MANAGER STATEMENTS
|--------------------------------------------------------------------------
|
| This function lives on the parent page, NOT inside AJAX-loaded content.
|
| printableId:
|   - routePrintable
|   - managerPrintable
|
|--------------------------------------------------------------------------
*/

    /*
    |--------------------------------------------------------------------------
    | SAVE ROUTE
    |--------------------------------------------------------------------------
    |
    | Keep the existing dynamic-form functionality.
    |
    */

    $(document).on('click','.saveRoute',function(){
        submitSingleForm('newRouteForm','../books/save/index.php');
    });

    /*
    |--------------------------------------------------------------------------
    | SUBMIT DYNAMIC FORM
    |--------------------------------------------------------------------------
    */

    function submitSingleForm(
        formId,
        backendUrl
    )
    {

        const form =
            document.getElementById(
                formId
            );


        if(!form)
        {

            console.error(
                'Form not found:',
                formId
            );

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Avoid attaching duplicate submit handlers
        |--------------------------------------------------------------------------
        */

        if(
            form.dataset.submitAttached ===
            '1'
        )
        {

            form.requestSubmit();

            return;

        }


        form.dataset.submitAttached =
            '1';


        form.addEventListener(
            'submit',
            function(e)
            {

                e.preventDefault();


                const formData =
                    new FormData(
                        form
                    );


                xdialog.startSpin();


                fetch(
                    backendUrl,
                    {

                        method:
                            'POST',

                        body:
                            formData

                    }
                )

                .then(
                    res =>
                        res.text()
                )

                .then(
                    response =>
                    {

                        xdialog.stopSpin();


                        window.location.reload();

                    }
                )

                .catch(
                    err =>
                    {

                        xdialog.stopSpin();


                        console.error(
                            'AJAX error:',
                            err
                        );


                        xdialog.alert(
                            'Unable to save the route. Please try again.'
                        );

                    }
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Trigger submit after handler has been attached
        |--------------------------------------------------------------------------
        */

        form.requestSubmit();

    }



    /*
    |--------------------------------------------------------------------------
    | VIEW ROUTE
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.view-route',
        function()
        {

            $('#central-modal').show();


            var title =
                $(this).data('title')
                + ' Details';


            $('.central-modal-title')
                .html(title);


            var id =
                $(this).data('id');


            $.ajax({

                url:
                    'save/index.php',

                data:
                {

                    route_id:
                        id,

                    action:
                        'route-details'

                },


                beforeSend:
                    function()
                    {

                        $('.central-modal-content')
                            .html(
                                "<div class='p-5 text-center'>"
                                +"<i class='fa fa-spinner fa-spin fa-2x text-success'></i>"
                                +"<p class='mt-3 text-muted'>Loading route details...</p>"
                                +"</div>"
                            );

                    },


                success:
                    function(res)
                    {

                        $('.central-modal-content')
                            .html(res);

                    },


                error:
                    function(err)
                    {

                        console.error(
                            err
                        );


                        $('.central-modal-content')
                            .html(
                                "<div class='alert alert-danger m-3'>"
                                +"Unable to load route details."
                                +"</div>"
                            );

                    }

            });

        }
    );

    // delete route
    $(document).on('click','.delete-route', function(){
        var routeId = $(this).data('id'); 
        xdialog.confirm('Are you sure you want to delete this route? This action cannot be undone.',function(){
            $.ajax({
                url: 'save/index.php',
                type: 'POST',
                data: {
                    action: 'deleteRoute',
                    route_id: routeId
                },
                success: function(response)
                {
                    xdialog.alert('Route deleted successfully.');
                    window.location.reload();
                },
                error: function(xhr, status, error)
                {
                    console.error('Error deleting route:', error);
                    alert('An error occurred while deleting the route. Please try again.');
                }
            });
        });
    });
</script>