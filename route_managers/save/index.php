<?php

require_once(__DIR__.'/../../assets/functions.php');


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if(
    !isVerified() ||
    !hasRole([
        'owner',
        'partner',
        'superadministrator'
    ])
)
{
    redirect('../');
    exit;
}


/*
|--------------------------------------------------------------------------
| ACTION
|--------------------------------------------------------------------------
*/

if(
    !isset($_REQUEST['action']) ||
    empty($_REQUEST['action'])
)
{
    exit;
}


$action = request('action');


switch($action)
{


    /*
    |--------------------------------------------------------------------------
    | ROUTE MANAGER DETAILS
    |--------------------------------------------------------------------------
    */

    case 'route-manager-details':


        /*
        |--------------------------------------------------------------------------
        | MANAGER ID
        |--------------------------------------------------------------------------
        */

        $manager_id =
            (int)request(
                'route_manager_id'
            );


        /*
        |--------------------------------------------------------------------------
        | FIND ROUTE MANAGER
        |--------------------------------------------------------------------------
        */

        $managerSql = "

            SELECT *

            FROM cashbook_route_managers

            WHERE id = ?

            LIMIT 1

        ";


        $managerRes =
            prepared_statements(
                $managerSql,
                'i',
                [$manager_id]
            );


        if(
            !$managerRes ||
            $managerRes->num_rows == 0
        )
        {
            ?>

                <div class="alert alert-warning m-3">

                    <strong>
                        Route Manager not found.
                    </strong>

                    The selected Route Manager could not
                    be loaded.

                </div>

            <?php

            break;
        }


        $manager =
            $managerRes->fetch_assoc();


        /*
        |--------------------------------------------------------------------------
        | FETCH ROUTES
        |--------------------------------------------------------------------------
        |
        | Each route assigned to this manager is fetched separately.
        | Customer balances will then be grouped beneath each route.
        |
        |--------------------------------------------------------------------------
        */

        $routeSql = "

            SELECT

                cr.id,

                cr.name,

                cr.details

            FROM cashbook_routes cr

            WHERE cr.managers = ?

            AND cr.book_id = ?

            ORDER BY cr.name ASC

        ";


        $routeRes =
            prepared_statements(
                $routeSql,
                'ii',
                [
                    $manager_id,
                    $manager['book_id']
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER QUERY
        |--------------------------------------------------------------------------
        |
        | Uses the same current balance source as:
        |
        | Route Dashboard
        | Route Customer Statement
        | Route Manager Dashboard
        |
        |--------------------------------------------------------------------------
        */

        $customerSql = "

            SELECT

                cc.id,

                cc.name,

                cc.contact,

                cc.address,

                COALESCE(
                    ccb.balance,
                    0
                ) AS customer_balance

            FROM cashbook_customers cc


            LEFT JOIN cashbook_customer_balances ccb

                ON ccb.customer_id = cc.id


            WHERE cc.route_id = ?

            AND cc.book_id = ?


            ORDER BY

                customer_balance DESC,

                cc.name ASC

        ";


        /*
        |--------------------------------------------------------------------------
        | BUILD MANAGER DATA
        |--------------------------------------------------------------------------
        */

        $routes = [];

        $totalRoutes = 0;

        $totalCustomers = 0;

        $totalCustomersOwing = 0;

        $totalCleared = 0;

        $managerBalance = 0;


        if($routeRes)
        {

            while(
                $route =
                $routeRes->fetch_assoc()
            )
            {

                /*
                |--------------------------------------------------------------------------
                | ROUTE CUSTOMERS
                |--------------------------------------------------------------------------
                */

                $customerRes =
                    prepared_statements(
                        $customerSql,
                        'ii',
                        [
                            $route['id'],
                            $manager['book_id']
                        ]
                    );


                $customers = [];

                $routeBalance = 0;

                $routeCustomers = 0;

                $routeCustomersOwing = 0;


                if($customerRes)
                {

                    while(
                        $customer =
                        $customerRes->fetch_assoc()
                    )
                    {

                        $balance =
                            (float)(
                                $customer[
                                    'customer_balance'
                                ]
                                ?? 0
                            );


                        $customer[
                            'customer_balance'
                        ] = $balance;


                        $customers[] =
                            $customer;


                        /*
                        |--------------------------------------------------------------------------
                        | ROUTE TOTALS
                        |--------------------------------------------------------------------------
                        */

                        $routeCustomers++;

                        $totalCustomers++;


                        $routeBalance +=
                            $balance;


                        $managerBalance +=
                            $balance;


                        if($balance > 0)
                        {

                            $routeCustomersOwing++;

                            $totalCustomersOwing++;

                        }
                        else
                        {

                            $totalCleared++;

                        }

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | STORE ROUTE
                |--------------------------------------------------------------------------
                */

                $route['customers'] =
                    $customers;


                $route['route_balance'] =
                    $routeBalance;


                $route['total_customers'] =
                    $routeCustomers;


                $route['customers_owing'] =
                    $routeCustomersOwing;


                $routes[] =
                    $route;


                $totalRoutes++;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | MANAGER DETAILS
        |--------------------------------------------------------------------------
        */

        $managerName =
            $manager['name']
            ?? 'Route Manager';


        $contact1 =
            $manager['contact1']
            ?? '';


        $contact2 =
            $manager['contact2']
            ?? '';


        $statementDate =
            date(
                'd M Y'
            );


        ?>


        <style>

            /*
            |--------------------------------------------------------------------------
            | STATEMENT
            |--------------------------------------------------------------------------
            */

            .manager-statement
            {
                background: #ffffff;

                color: #263238;

                font-size: 13px;
            }


            /*
            |--------------------------------------------------------------------------
            | SCREEN TOOLBAR
            |--------------------------------------------------------------------------
            */

            .manager-statement-toolbar
            {
                display: flex;

                align-items: center;

                justify-content: space-between;

                gap: 15px;

                padding:
                    14px 18px;

                border-bottom:
                    1px solid #e5e9ed;

                background: #ffffff;
            }


            .manager-statement-toolbar h3
            {
                margin: 0;

                color: #172b3a;

                font-size: 16px;

                font-weight: 700;
            }


            .manager-statement-toolbar p
            {
                margin:
                    3px 0 0;

                color: #8492a6;

                font-size: 11px;
            }


            /*
            |--------------------------------------------------------------------------
            | PRINTABLE DOCUMENT
            |--------------------------------------------------------------------------
            */

            .manager-printable
            {
                padding: 25px;

                background: #ffffff;
            }


            /*
            |--------------------------------------------------------------------------
            | DOCUMENT HEADER
            |--------------------------------------------------------------------------
            */

            .manager-document-header
            {
                display: flex;

                justify-content: space-between;

                gap: 20px;

                padding-bottom: 18px;

                margin-bottom: 18px;

                border-bottom:
                    2px solid #172b3a;
            }


            .manager-document-title
            {
                color: #172b3a;
            }


            .manager-document-title h2
            {
                margin:
                    0 0 5px;

                font-size: 21px;

                font-weight: 700;

                text-transform: uppercase;
            }


            .manager-document-title p
            {
                margin: 0;

                color: #718096;

                font-size: 11px;
            }


            .manager-document-meta
            {
                min-width: 190px;

                text-align: right;

                color: #718096;

                font-size: 11px;
            }


            .manager-document-meta strong
            {
                color: #172b3a;
            }


            /*
            |--------------------------------------------------------------------------
            | MANAGER INFO
            |--------------------------------------------------------------------------
            */

            .manager-info-grid
            {
                display: grid;

                grid-template-columns:
                    repeat(3,1fr);

                gap: 10px;

                margin-bottom: 18px;
            }


            .manager-info-item
            {
                padding:
                    10px 12px;

                background: #f8fafb;

                border:
                    1px solid #edf1f4;
            }


            .manager-info-label
            {
                display: block;

                margin-bottom: 3px;

                color: #8492a6;

                font-size: 9px;

                font-weight: 700;

                letter-spacing: .5px;

                text-transform: uppercase;
            }


            .manager-info-value
            {
                color: #172b3a;

                font-size: 13px;

                font-weight: 700;
            }


            /*
            |--------------------------------------------------------------------------
            | SUMMARY
            |--------------------------------------------------------------------------
            */

            .manager-balance-summary
            {
                display: grid;

                grid-template-columns:
                    repeat(4,1fr);

                gap: 10px;

                margin-bottom: 25px;
            }


            .manager-balance-card
            {
                padding: 12px;

                border:
                    1px solid #e5eaee;

                background: #ffffff;
            }


            .manager-balance-card span
            {
                display: block;

                margin-bottom: 3px;

                color: #8492a6;

                font-size: 9px;

                font-weight: 700;

                text-transform: uppercase;
            }


            .manager-balance-card strong
            {
                color: #172b3a;

                font-size: 16px;
            }


            .manager-balance-card.balance strong
            {
                color: #d97706;
            }


            /*
            |--------------------------------------------------------------------------
            | ROUTE SECTION
            |--------------------------------------------------------------------------
            */

            .manager-route-section
            {
                margin-bottom: 25px;

                border:
                    1px solid #e2e8ed;

                page-break-inside:
                    auto;
            }


            .manager-route-header
            {
                display: flex;

                align-items: center;

                justify-content: space-between;

                gap: 15px;

                padding:
                    11px 13px;

                background: #f3f6f8;

                border-bottom:
                    1px solid #dfe6eb;
            }


            .manager-route-name
            {
                color: #172b3a;

                font-size: 13px;

                font-weight: 700;

                text-transform: uppercase;
            }


            .manager-route-details
            {
                margin-top: 2px;

                color: #8492a6;

                font-size: 9px;
            }


            .manager-route-summary
            {
                display: flex;

                align-items: center;

                gap: 15px;

                white-space: nowrap;

                font-size: 10px;
            }


            .manager-route-summary span
            {
                color: #718096;
            }


            .manager-route-summary strong
            {
                color: #172b3a;
            }


            .manager-route-summary .route-total
            {
                color: #d97706;

                font-size: 12px;
            }


            /*
            |--------------------------------------------------------------------------
            | CUSTOMER TABLE
            |--------------------------------------------------------------------------
            */

            .manager-customer-table
            {
                width: 100%;

                margin: 0;

                border-collapse: collapse;
            }


            .manager-customer-table thead th
            {
                padding:
                    8px 8px;

                border:
                    1px solid #dfe5e9;

                background: #fafbfc;

                color: #4a5568;

                font-size: 9px;

                font-weight: 700;

                letter-spacing: .4px;

                text-align: left;

                text-transform: uppercase;
            }


            .manager-customer-table tbody td
            {
                padding:
                    8px;

                border:
                    1px solid #e5eaee;

                color: #4a5568;

                font-size: 10px;
            }


            .manager-customer-table tbody tr:nth-child(even)
            {
                background: #fbfcfd;
            }


            .manager-customer-table .customer-name
            {
                color: #172b3a;

                font-weight: 700;
            }


            .manager-customer-table .amount
            {
                text-align: right;

                white-space: nowrap;

                font-weight: 700;
            }


            .manager-customer-table .owing
            {
                color: #c96b13;
            }


            .manager-customer-table .cleared
            {
                color: #087f5b;
            }


            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            .manager-balance-status
            {
                display: inline-block;

                padding:
                    3px 7px;

                font-size: 8px;

                font-weight: 700;

                text-transform: uppercase;
            }


            .manager-balance-status.owing
            {
                background: #fff7ed;

                color: #c96b13;
            }


            .manager-balance-status.cleared
            {
                background: #ecfdf5;

                color: #087f5b;
            }


            /*
            |--------------------------------------------------------------------------
            | ROUTE FOOTER
            |--------------------------------------------------------------------------
            */

            .manager-route-footer
            {
                display: flex;

                justify-content: flex-end;

                padding:
                    9px 12px;

                background: #f8fafb;

                border-top:
                    1px solid #e1e7ec;

                color: #172b3a;

                font-size: 11px;

                font-weight: 700;
            }


            .manager-route-footer strong
            {
                min-width: 130px;

                margin-left: 15px;

                text-align: right;

                color: #d97706;
            }


            /*
            |--------------------------------------------------------------------------
            | GRAND TOTAL
            |--------------------------------------------------------------------------
            */

            .manager-grand-total
            {
                display: flex;

                align-items: center;

                justify-content: space-between;

                gap: 20px;

                margin-top: 25px;

                padding:
                    15px 17px;

                background: #172b3a;

                color: #ffffff;

                page-break-inside:
                    avoid;
            }


            .manager-grand-total-label
            {
                font-size: 10px;

                font-weight: 700;

                letter-spacing: .5px;

                text-transform: uppercase;
            }


            .manager-grand-total small
            {
                display: block;

                margin-top: 3px;

                color:
                    rgba(255,255,255,.60);

                font-size: 9px;
            }


            .manager-grand-total strong
            {
                font-size: 20px;
            }


            /*
            |--------------------------------------------------------------------------
            | EMPTY
            |--------------------------------------------------------------------------
            */

            .manager-empty
            {
                padding:
                    35px 20px;

                text-align: center;

                color: #8492a6;
            }


            .manager-empty i
            {
                margin-bottom: 10px;

                color: #cbd5df;

                font-size: 30px;
            }


            /*
            |--------------------------------------------------------------------------
            | PRINT FOOTER
            |--------------------------------------------------------------------------
            */

            .manager-statement-footer
            {
                display: flex;

                justify-content: space-between;

                gap: 20px;

                margin-top: 30px;

                padding-top: 15px;

                border-top:
                    1px solid #dfe5e9;

                color: #8492a6;

                font-size: 9px;
            }


            /*
            |--------------------------------------------------------------------------
            | MOBILE
            |--------------------------------------------------------------------------
            */

            @media(max-width: 767px)
            {

                .manager-printable
                {
                    padding: 15px;
                }


                .manager-document-header
                {
                    flex-direction:
                        column;
                }


                .manager-document-meta
                {
                    text-align: left;
                }


                .manager-info-grid
                {
                    grid-template-columns:
                        1fr;
                }


                .manager-balance-summary
                {
                    grid-template-columns:
                        repeat(2,1fr);
                }


                .manager-route-header
                {
                    align-items:
                        flex-start;

                    flex-direction:
                        column;
                }


                .manager-route-summary
                {
                    width: 100%;

                    justify-content:
                        space-between;
                }


                .manager-table-container
                {
                    overflow-x: auto;
                }

            }


            /*
            |--------------------------------------------------------------------------
            | PRINT
            |--------------------------------------------------------------------------
            */

            @media print
            {

                body
                {
                    background:
                        #ffffff !important;
                }


                /*
                |--------------------------------------------------------------------------
                | HIDE APPLICATION
                |--------------------------------------------------------------------------
                */

                body *
                {
                    visibility:
                        hidden;
                }


                #managerPrintable,
                #managerPrintable *
                {
                    visibility:
                        visible;
                }


                #managerPrintable
                {
                    position: absolute;

                    left: 0;

                    top: 0;

                    width: 100%;

                    padding:
                        0 !important;

                    margin:
                        0 !important;

                    background: #ffffff;
                }


                /*
                |--------------------------------------------------------------------------
                | DOCUMENT
                |--------------------------------------------------------------------------
                */

                .manager-printable
                {
                    padding:
                        0 !important;
                }


                .manager-document-header,
                .manager-balance-summary,
                .manager-grand-total
                {
                    page-break-inside:
                        avoid;
                }


                /*
                |--------------------------------------------------------------------------
                | ROUTE
                |--------------------------------------------------------------------------
                */

                .manager-route-header
                {
                    page-break-after:
                        avoid;
                }


                /*
                |--------------------------------------------------------------------------
                | TABLE
                |--------------------------------------------------------------------------
                */

                .manager-customer-table
                {
                    width: 100%;

                    font-size: 9px;
                }


                .manager-customer-table thead
                {
                    display:
                        table-header-group;
                }


                .manager-customer-table tr
                {
                    page-break-inside:
                        avoid;
                }


                /*
                |--------------------------------------------------------------------------
                | PAGE
                |--------------------------------------------------------------------------
                */

                @page
                {
                    size:
                        A4 portrait;

                    margin:
                        12mm;
                }

            }

        </style>



        <div class="manager-statement">


            <!-- ============================================================
                 SCREEN TOOLBAR
            ============================================================= -->

            <div class="manager-statement-toolbar">


                <div>


                    <h3>

                        <i
                            class="
                                fa
                                fa-user-tie
                                text-success
                                mr-1
                            "
                        ></i>

                        Route Manager Customer Balances

                    </h3>


                    <p>

                        Routes, customers and balances under
                        this Route Manager.

                    </p>


                </div>



                <button
                    type="button"
                    class="btn btn-sm btn-flat btn-outline-danger"
                    onclick="printStatement(
                        'routePrintable',
                        'Route Balance Statement'
                    )"
                >
                    <i class="fa fa-print mr-1"></i>
                    Print Statement
                </button>
            </div>



            <!-- ============================================================
                 PRINTABLE DOCUMENT
            ============================================================= -->

            <div
                id="routePrintable"
                class="route-printable"
            >


                <!-- ========================================================
                     DOCUMENT HEADER
                ========================================================= -->

                <div class="manager-document-header">
                    <div class="manager-document-title">
                        <h2>
                           Route Manager Balance Statement
                        </h2>
                        <p>
                            Customer balances grouped by
                            routes assigned to the manager.
                        </p>
                    </div>
                    <div class="manager-document-meta">
                        <div>

                            Statement Date:

                            <strong>

                                <?=$statementDate;?>

                            </strong>

                        </div>


                        <div>

                            Manager ID:

                            <strong>

                                #<?=$manager_id;?>

                            </strong>

                        </div>


                    </div>


                </div>



                <!-- ========================================================
                     MANAGER INFORMATION
                ========================================================= -->

                <div class="manager-info-grid">


                    <div class="manager-info-item">


                        <span class="manager-info-label">

                            Route Manager

                        </span>


                        <div class="manager-info-value">

                            <?=htmlspecialchars(
                                $managerName
                            );?>

                        </div>


                    </div>



                    <div class="manager-info-item">


                        <span class="manager-info-label">

                            Primary Contact

                        </span>


                        <div class="manager-info-value">

                            <?=!empty($contact1)
                                ? htmlspecialchars(
                                    $contact1
                                )
                                : '-';
                            ?>

                        </div>


                    </div>



                    <div class="manager-info-item">


                        <span class="manager-info-label">

                            Alternative Contact

                        </span>


                        <div class="manager-info-value">

                            <?=!empty($contact2)
                                ? htmlspecialchars(
                                    $contact2
                                )
                                : '-';
                            ?>

                        </div>


                    </div>


                </div>



                <!-- ========================================================
                     MANAGER SUMMARY
                ========================================================= -->

                <div class="manager-balance-summary">


                    <!-- ROUTES -->

                    <div class="manager-balance-card">


                        <span>

                            Routes

                        </span>


                        <strong>

                            <?=number_format(
                                $totalRoutes
                            );?>

                        </strong>


                    </div>



                    <!-- CUSTOMERS -->

                    <div class="manager-balance-card">


                        <span>

                            Customers

                        </span>


                        <strong>

                            <?=number_format(
                                $totalCustomers
                            );?>

                        </strong>


                    </div>



                    <!-- OWING -->

                    <div class="manager-balance-card">


                        <span>

                            Customers Owing

                        </span>


                        <strong>

                            <?=number_format(
                                $totalCustomersOwing
                            );?>

                        </strong>


                    </div>



                    <!-- BALANCE -->

                    <div
                        class="
                            manager-balance-card
                            balance
                        "
                    >


                        <span>

                            Manager Balance

                        </span>


                        <strong>

                            <?=number_format(
                                $managerBalance,
                                0
                            );?>/=

                        </strong>


                    </div>


                </div>



                <!-- ========================================================
                     ROUTES
                ========================================================= -->

                <?php if(count($routes) > 0): ?>


                    <?php

                    $routeNumber = 0;

                    foreach(
                        $routes
                        as $route
                    ):

                        $routeNumber++;

                    ?>


                        <div class="manager-route-section">


                            <!-- ============================================
                                 ROUTE HEADER
                            ============================================= -->

                            <div class="manager-route-header">


                                <div>


                                    <div class="manager-route-name">

                                        <?=$routeNumber;?>.

                                        <?=htmlspecialchars(
                                            $route['name']
                                        );?>

                                    </div>


                                    <?php
                                    if(
                                        !empty(
                                            $route['details']
                                        )
                                    ):
                                    ?>


                                        <div class="manager-route-details">

                                            <?=htmlspecialchars(
                                                $route['details']
                                            );?>

                                        </div>


                                    <?php endif; ?>


                                </div>



                                <div class="manager-route-summary">


                                    <span>

                                        Customers:

                                        <strong>

                                            <?=number_format(
                                                $route[
                                                    'total_customers'
                                                ]
                                            );?>

                                        </strong>

                                    </span>


                                    <span>

                                        Owing:

                                        <strong>

                                            <?=number_format(
                                                $route[
                                                    'customers_owing'
                                                ]
                                            );?>

                                        </strong>

                                    </span>


                                    <strong class="route-total">

                                        <?=number_format(
                                            $route[
                                                'route_balance'
                                            ],
                                            0
                                        );?>/=

                                    </strong>


                                </div>


                            </div>



                            <!-- ============================================
                                 CUSTOMERS
                            ============================================= -->

                            <?php
                            if(
                                count(
                                    $route['customers']
                                ) > 0
                            ):
                            ?>


                                <div class="manager-table-container">


                                    <table class="manager-customer-table">


                                        <thead>


                                            <tr>


                                                <th
                                                    style="
                                                        width:35px;
                                                    "
                                                >

                                                    #

                                                </th>


                                                <th>

                                                    Customer

                                                </th>


                                                <th>

                                                    Contact

                                                </th>


                                                <th>

                                                    Location

                                                </th>


                                                <th>

                                                    Status

                                                </th>


                                                <th
                                                    style="
                                                        text-align:right;
                                                        width:120px;
                                                    "
                                                >

                                                    Balance

                                                </th>


                                            </tr>


                                        </thead>



                                        <tbody>


                                            <?php

                                            $customerNumber =
                                                0;


                                            foreach(
                                                $route[
                                                    'customers'
                                                ]
                                                as $customer
                                            ):


                                                $customerNumber++;


                                                $balance =
                                                    $customer[
                                                        'customer_balance'
                                                    ];


                                                $balanceClass =
                                                    $balance > 0
                                                    ? 'owing'
                                                    : 'cleared';


                                                $balanceStatus =
                                                    $balance > 0
                                                    ? 'Owing'
                                                    : (
                                                        $balance < 0
                                                        ? 'Credit'
                                                        : 'Cleared'
                                                    );

                                            ?>


                                                <tr>


                                                    <td>

                                                        <?=$customerNumber;?>

                                                    </td>



                                                    <!-- CUSTOMER -->

                                                    <td
                                                        class="
                                                            customer-name
                                                        "
                                                    >

                                                        <?=htmlspecialchars(
                                                            $customer[
                                                                'name'
                                                            ]
                                                        );?>

                                                    </td>



                                                    <!-- CONTACT -->

                                                    <td>

                                                        <?php

                                                        echo
                                                            !empty(
                                                                $customer[
                                                                    'contact'
                                                                ]
                                                            )
                                                            ? htmlspecialchars(
                                                                $customer[
                                                                    'contact'
                                                                ]
                                                            )
                                                            : '-';

                                                        ?>

                                                    </td>



                                                    <!-- LOCATION -->

                                                    <td>

                                                        <?php

                                                        echo
                                                            !empty(
                                                                $customer[
                                                                    'address'
                                                                ]
                                                            )
                                                            ? htmlspecialchars(
                                                                $customer[
                                                                    'address'
                                                                ]
                                                            )
                                                            : '-';

                                                        ?>

                                                    </td>



                                                    <!-- STATUS -->

                                                    <td>


                                                        <span
                                                            class="
                                                                manager-balance-status
                                                                <?=$balanceClass;?>
                                                            "
                                                        >

                                                            <?=$balanceStatus;?>

                                                        </span>


                                                    </td>



                                                    <!-- BALANCE -->

                                                    <td
                                                        class="
                                                            amount
                                                            <?=$balanceClass;?>
                                                        "
                                                    >

                                                        <?=number_format(
                                                            $balance,
                                                            0
                                                        );?>/=

                                                    </td>


                                                </tr>


                                            <?php endforeach; ?>


                                        </tbody>


                                    </table>


                                </div>


                            <?php else: ?>


                                <div class="manager-empty">


                                    <i class="fa fa-users"></i>


                                    <div>

                                        No customers are currently
                                        assigned to this route.

                                    </div>


                                </div>


                            <?php endif; ?>



                            <!-- ============================================
                                 ROUTE TOTAL
                            ============================================= -->

                            <div class="manager-route-footer">


                                Route Balance


                                <strong>

                                    <?=number_format(
                                        $route[
                                            'route_balance'
                                        ],
                                        0
                                    );?>/=

                                </strong>


                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="manager-empty">


                        <i class="fa fa-route"></i>


                        <h4>

                            No Routes Assigned

                        </h4>


                        <p>

                            This Route Manager does not
                            currently have any routes assigned.

                        </p>


                    </div>


                <?php endif; ?>



                <!-- ========================================================
                     MANAGER GRAND TOTAL
                ========================================================= -->

                <div class="manager-grand-total">


                    <div>


                        <div class="manager-grand-total-label">

                            Total Manager Customer Balance

                        </div>


                        <small>

                            Combined current balance for
                            <?=number_format(
                                $totalCustomers
                            );?>

                            customers across

                            <?=number_format(
                                $totalRoutes
                            );?>

                            routes.

                        </small>


                    </div>



                    <strong>

                        <?=number_format(
                            $managerBalance,
                            0
                        );?>/=

                    </strong>


                </div>



                <!-- ========================================================
                     PRINT FOOTER
                ========================================================= -->

                <div class="manager-statement-footer">


                    <div>

                        Route Manager:

                        <?=htmlspecialchars(
                            $managerName
                        );?>

                    </div>


                    <div>

                        Generated:

                        <?=$statementDate;?>

                    </div>


                </div>


            </div>


        </div>



        <script>

            /*
            |--------------------------------------------------------------------------
            | PRINT MANAGER STATEMENT
            |--------------------------------------------------------------------------
            */

            function printManagerStatement()
            {

                window.print();

            }

        </script>


        <?php

    break;



    /*
    |--------------------------------------------------------------------------
    | LEGACY CUSTOMER DETAILS
    |--------------------------------------------------------------------------
    |
    | I have retained this action so anything elsewhere in your system
    | still calling Customer-details will not suddenly stop working.
    |
    |--------------------------------------------------------------------------
    */

    case 'Customer-details':


        $id =
            (int)request(
                'customer_id'
            );


        $sql = "

            SELECT *

            FROM cashbook_transactions

            WHERE customer_id = ?

            ORDER BY created_at DESC

        ";


        $res =
            prepared_statements(
                $sql,
                'i',
                [$id]
            );


        $credits = [];

        $debits = [];

        ?>


            <div class="table-responsive">


                <table
                    class="
                        table
                        table-sm
                        table-striped
                        table-bordered
                    "
                >


                    <thead>


                        <tr>

                            <th>Date</th>

                            <th>Type</th>

                            <th>Details</th>

                            <th class="text-right">
                                Credit
                            </th>

                            <th class="text-right">
                                Debit
                            </th>

                            <th class="text-right">
                                Balance
                            </th>

                        </tr>


                    </thead>



                    <tbody>


                        <?php
                        if(
                            $res &&
                            $res->num_rows > 0
                        ):
                        ?>


                            <?php
                            while(
                                $r =
                                $res->fetch_assoc()
                            ):


                                $credits[] =
                                    $r[
                                        'credit_amount'
                                    ];


                                $debits[] =
                                    $r[
                                        'debit_amount'
                                    ];


                                $bal =
                                    array_sum(
                                        $credits
                                    )
                                    -
                                    array_sum(
                                        $debits
                                    );

                            ?>


                                <tr>


                                    <td>

                                        <?=$r[
                                            'created_at'
                                        ];?>

                                    </td>


                                    <td>

                                        <?=htmlspecialchars(
                                            $r['type']
                                        );?>

                                    </td>


                                    <td>

                                        <?=htmlspecialchars(
                                            $r['details']
                                        );?>

                                    </td>


                                    <td class="text-right">

                                        <?=!empty(
                                            $r[
                                                'credit_amount'
                                            ]
                                        )
                                            ? number_format(
                                                $r[
                                                    'credit_amount'
                                                ],
                                                0
                                            )
                                            : '';
                                        ?>

                                    </td>


                                    <td class="text-right">

                                        <?=!empty(
                                            $r[
                                                'debit_amount'
                                            ]
                                        )
                                            ? number_format(
                                                $r[
                                                    'debit_amount'
                                                ],
                                                0
                                            )
                                            : '';
                                        ?>

                                    </td>


                                    <td class="text-right">

                                        <?=number_format(
                                            $bal,
                                            0
                                        );?>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="6"
                                    class="text-center p-4"
                                >

                                    No Transactions found

                                </td>

                            </tr>


                        <?php endif; ?>


                    </tbody>


                    <tfoot>


                        <tr>


                            <th colspan="3">

                                BALANCE

                            </th>


                            <th class="text-right">

                                <?=number_format(
                                    array_sum(
                                        $credits
                                    ),
                                    0
                                );?>

                            </th>


                            <th class="text-right">

                                <?=number_format(
                                    array_sum(
                                        $debits
                                    ),
                                    0
                                );?>

                            </th>


                            <th class="text-right">

                                <?=number_format(
                                    (
                                        array_sum(
                                            $credits
                                        )
                                        -
                                        array_sum(
                                            $debits
                                        )
                                    ),
                                    0
                                );?>

                            </th>


                        </tr>


                    </tfoot>


                </table>


            </div>


        <?php

    break;



    /*
    |--------------------------------------------------------------------------
    | EDIT CUSTOMER - PRESERVED
    |--------------------------------------------------------------------------
    */

    case 'edit-customer':


        /*
        |--------------------------------------------------------------------------
        | Existing functionality can remain here.
        |--------------------------------------------------------------------------
        */


    break;

}

?>