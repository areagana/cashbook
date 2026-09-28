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
    | ROUTE DETAILS
    |--------------------------------------------------------------------------
    */

    case 'route-details':


        /*
        |--------------------------------------------------------------------------
        | ROUTE ID
        |--------------------------------------------------------------------------
        */

        $id = (int)request('route_id');


        /*
        |--------------------------------------------------------------------------
        | FIND ROUTE
        |--------------------------------------------------------------------------
        */

        $route = routeFind($id);


        if(!$route)
        {
            ?>

                <div class="alert alert-warning m-3">

                    <strong>Route not found.</strong>

                    The selected route could not be loaded.

                </div>

            <?php

            break;
        }


        /*
        |--------------------------------------------------------------------------
        | ROUTE CUSTOMERS + CURRENT BALANCE
        |--------------------------------------------------------------------------
        |
        | cashbook_customers.route_id
        |      identifies customers on this route
        |
        | cashbook_customer_balances.customer_id
        |      stores the customer's current balance
        |
        | This is the SAME balance source we used on the routes dashboard.
        |
        |--------------------------------------------------------------------------
        */

        $sql = "

            SELECT

                cc.*,

                COALESCE(
                    ccb.balance,
                    0
                ) AS customer_balance

            FROM cashbook_customers cc


            LEFT JOIN cashbook_customer_balances ccb

                ON ccb.customer_id = cc.id


            WHERE cc.route_id = ?


            ORDER BY

                customer_balance DESC,

                cc.name ASC

        ";


        $res = prepared_statements(
            $sql,
            'i',
            [$id]
        );


        /*
        |--------------------------------------------------------------------------
        | STORE CUSTOMERS
        |--------------------------------------------------------------------------
        |
        | We first store them so we can calculate totals before printing.
        |
        */

        $customers = [];

        $totalBalance = 0;

        $totalCustomers = 0;

        $customersWithBalance = 0;

        $customersCleared = 0;


        if($res)
        {

            while($row = $res->fetch_assoc())
            {

                $balance =
                    (float)(
                        $row['customer_balance']
                        ?? 0
                    );


                $row['customer_balance'] =
                    $balance;


                $customers[] =
                    $row;


                $totalCustomers++;


                $totalBalance +=
                    $balance;


                if($balance > 0)
                {

                    $customersWithBalance++;

                }
                else
                {

                    $customersCleared++;

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | ROUTE MANAGER
        |--------------------------------------------------------------------------
        */

        $routeManager =
            isset($route->manager) &&
            trim($route->manager) != ''
            ? $route->manager
            : 'Not Assigned';


        /*
        |--------------------------------------------------------------------------
        | PRINT DATE
        |--------------------------------------------------------------------------
        */

        $statementDate =
            date(
                'd M Y'
            );


        ?>


        <style>

            /*
            |--------------------------------------------------------------------------
            | ROUTE STATEMENT
            |--------------------------------------------------------------------------
            */

            .route-statement
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

            .route-statement-toolbar
            {
                display: flex;

                align-items: center;

                justify-content: space-between;

                gap: 15px;

                padding: 14px 18px;

                border-bottom:
                    1px solid #e5e9ed;

                background: #ffffff;
            }


            .route-statement-toolbar h3
            {
                margin: 0;

                color: #172b3a;

                font-size: 16px;

                font-weight: 700;
            }


            .route-statement-toolbar p
            {
                margin: 3px 0 0;

                color: #8492a6;

                font-size: 11px;
            }


            /*
            |--------------------------------------------------------------------------
            | PRINTABLE DOCUMENT
            |--------------------------------------------------------------------------
            */

            .route-printable
            {
                padding: 25px;

                background: #ffffff;
            }


            /*
            |--------------------------------------------------------------------------
            | DOCUMENT HEADER
            |--------------------------------------------------------------------------
            */

            .route-document-header
            {
                display: flex;

                justify-content: space-between;

                gap: 20px;

                padding-bottom: 18px;

                margin-bottom: 18px;

                border-bottom:
                    2px solid #172b3a;
            }


            .route-document-title
            {
                color: #172b3a;
            }


            .route-document-title h2
            {
                margin:
                    0 0 5px;

                font-size: 21px;

                font-weight: 700;

                text-transform: uppercase;
            }


            .route-document-title p
            {
                margin: 0;

                color: #718096;

                font-size: 11px;
            }


            .route-document-meta
            {
                min-width: 190px;

                text-align: right;

                font-size: 11px;

                color: #718096;
            }


            .route-document-meta strong
            {
                color: #172b3a;
            }


            /*
            |--------------------------------------------------------------------------
            | ROUTE INFORMATION
            |--------------------------------------------------------------------------
            */

            .route-info-grid
            {
                display: grid;

                grid-template-columns:
                    repeat(2,1fr);

                gap: 10px;

                margin-bottom: 18px;
            }


            .route-info-item
            {
                padding: 10px 12px;

                background: #f8fafb;

                border:
                    1px solid #edf1f4;
            }


            .route-info-label
            {
                display: block;

                margin-bottom: 3px;

                color: #8492a6;

                font-size: 9px;

                font-weight: 700;

                letter-spacing: .5px;

                text-transform: uppercase;
            }


            .route-info-value
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

            .route-balance-summary
            {
                display: grid;

                grid-template-columns:
                    repeat(4,1fr);

                gap: 10px;

                margin-bottom: 20px;
            }


            .route-balance-card
            {
                padding: 12px;

                border:
                    1px solid #e5eaee;

                background: #ffffff;
            }


            .route-balance-card span
            {
                display: block;

                margin-bottom: 3px;

                color: #8492a6;

                font-size: 9px;

                font-weight: 700;

                text-transform: uppercase;
            }


            .route-balance-card strong
            {
                color: #172b3a;

                font-size: 16px;
            }


            .route-balance-card.balance strong
            {
                color: #d97706;
            }


            /*
            |--------------------------------------------------------------------------
            | TABLE
            |--------------------------------------------------------------------------
            */

            .route-customer-table
            {
                width: 100%;

                margin: 0;

                border-collapse: collapse;
            }


            .route-customer-table thead th
            {
                padding:
                    9px 8px;

                border:
                    1px solid #dfe5e9;

                background: #f3f6f8;

                color: #4a5568;

                font-size: 9px;

                font-weight: 700;

                letter-spacing: .4px;

                text-align: left;

                text-transform: uppercase;
            }


            .route-customer-table tbody td
            {
                padding:
                    9px 8px;

                border:
                    1px solid #e5eaee;

                color: #4a5568;

                font-size: 11px;
            }


            .route-customer-table tbody tr:nth-child(even)
            {
                background: #fbfcfd;
            }


            .route-customer-table .customer-name
            {
                color: #172b3a;

                font-weight: 700;
            }


            .route-customer-table .amount
            {
                text-align: right;

                white-space: nowrap;

                font-weight: 700;
            }


            .route-customer-table .owing
            {
                color: #c96b13;
            }


            .route-customer-table .cleared
            {
                color: #087f5b;
            }


            /*
            |--------------------------------------------------------------------------
            | TOTAL ROW
            |--------------------------------------------------------------------------
            */

            .route-customer-table tfoot td
            {
                padding:
                    10px 8px;

                border:
                    1px solid #cfd8df;

                background: #f3f6f8;

                color: #172b3a;

                font-size: 11px;

                font-weight: 700;
            }


            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            .balance-status
            {
                display: inline-block;

                padding:
                    3px 7px;

                font-size: 9px;

                font-weight: 700;

                text-transform: uppercase;
            }


            .balance-status.owing
            {
                background: #fff7ed;

                color: #c96b13;
            }


            .balance-status.cleared
            {
                background: #ecfdf5;

                color: #087f5b;
            }


            /*
            |--------------------------------------------------------------------------
            | FOOTER
            |--------------------------------------------------------------------------
            */

            .route-statement-footer
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

                .route-printable
                {
                    padding: 15px;
                }


                .route-document-header
                {
                    flex-direction:
                        column;
                }


                .route-document-meta
                {
                    text-align: left;
                }


                .route-info-grid
                {
                    grid-template-columns:
                        1fr;
                }


                .route-balance-summary
                {
                    grid-template-columns:
                        repeat(2,1fr);
                }


                .route-table-container
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
                    background: #ffffff !important;
                }


                /*
                |--------------------------------------------------------------------------
                | HIDE APPLICATION UI
                |--------------------------------------------------------------------------
                */

                body *
                {
                    visibility: hidden;
                }


                #printable,
                #printable *
                {
                    visibility: visible;
                }


                #printable
                {
                    position: absolute;

                    left: 0;

                    top: 0;

                    width: 100%;

                    padding: 0 !important;

                    margin: 0 !important;

                    background: #ffffff;
                }


                /*
                |--------------------------------------------------------------------------
                | DOCUMENT
                |--------------------------------------------------------------------------
                */

                .route-printable
                {
                    padding: 0 !important;
                }


                .route-document-header
                {
                    page-break-inside:
                        avoid;
                }


                .route-balance-summary
                {
                    page-break-inside:
                        avoid;
                }


                /*
                |--------------------------------------------------------------------------
                | TABLE
                |--------------------------------------------------------------------------
                */

                .route-customer-table
                {
                    width: 100%;

                    font-size: 10px;
                }


                .route-customer-table thead
                {
                    display:
                        table-header-group;
                }


                .route-customer-table tfoot
                {
                    display:
                        table-footer-group;
                }


                .route-customer-table tr
                {
                    page-break-inside:
                        avoid;
                }


                /*
                |--------------------------------------------------------------------------
                | REMOVE SCREEN EFFECTS
                |--------------------------------------------------------------------------
                */

                .route-info-item,
                .route-balance-card
                {
                    box-shadow: none;
                }


                /*
                |--------------------------------------------------------------------------
                | PAGE
                |--------------------------------------------------------------------------
                */

                @page
                {
                    size: A4 portrait;

                    margin: 12mm;
                }

            }

        </style>



        <div class="route-statement">


            <!-- ============================================================
                 SCREEN TOOLBAR
            ============================================================= -->

            <div class="route-statement-toolbar">


                <div>


                    <h3>

                        <i
                            class="
                                fa
                                fa-route
                                text-success
                                mr-1
                            "
                        ></i>

                        Route Customer Balances

                    </h3>


                    <p>

                        Review outstanding balances for every
                        customer assigned to this route.

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
                 PRINTABLE
            ============================================================= -->

            <div
                id="routePrintable"
                class="route-printable"
            >


                <!-- ========================================================
                     DOCUMENT HEADER
                ========================================================= -->

                <div class="route-document-header">


                    <div class="route-document-title">


                        <h2>

                            Route Customer Balance Statement

                        </h2>


                        <p>

                            Customer balances grouped under
                            the selected business route.

                        </p>


                    </div>



                    <div class="route-document-meta">


                        <div>

                            Statement Date:

                            <strong>

                                <?=$statementDate;?>

                            </strong>

                        </div>


                        <div>

                            Route ID:

                            <strong>

                                #<?=$id;?>

                            </strong>

                        </div>


                    </div>


                </div>



                <!-- ========================================================
                     ROUTE INFORMATION
                ========================================================= -->

                <div class="route-info-grid">


                    <div class="route-info-item">


                        <span class="route-info-label">

                            Route

                        </span>


                        <div class="route-info-value">

                            <?=htmlspecialchars(
                                $route->name
                            );?>

                        </div>


                    </div>



                    <div class="route-info-item">


                        <span class="route-info-label">

                            Route Manager

                        </span>


                        <div class="route-info-value">

                            <?=htmlspecialchars(
                                $routeManager
                            );?>

                        </div>


                    </div>


                </div>



                <!-- ========================================================
                     ROUTE SUMMARY
                ========================================================= -->

                <div class="route-balance-summary">


                    <!-- CUSTOMERS -->

                    <div class="route-balance-card">


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

                    <div class="route-balance-card">


                        <span>

                            Customers Owing

                        </span>


                        <strong>

                            <?=number_format(
                                $customersWithBalance
                            );?>

                        </strong>


                    </div>



                    <!-- CLEARED -->

                    <div class="route-balance-card">


                        <span>

                            Cleared / Credit

                        </span>


                        <strong>

                            <?=number_format(
                                $customersCleared
                            );?>

                        </strong>


                    </div>



                    <!-- BALANCE -->

                    <div
                        class="
                            route-balance-card
                            balance
                        "
                    >


                        <span>

                            Route Balance

                        </span>


                        <strong>

                            <?=number_format(
                                $totalBalance,
                                0
                            );?>/=

                        </strong>


                    </div>


                </div>



                <!-- ========================================================
                     CUSTOMER BALANCE TABLE
                ========================================================= -->

                <div class="route-table-container">


                    <table class="route-customer-table">


                        <thead>


                            <tr>


                                <th style="width:35px;">

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
                                        width:130px;
                                    "
                                >

                                    Balance

                                </th>


                            </tr>


                        </thead>



                        <tbody>


                            <?php
                            if(
                                count($customers) > 0
                            ):
                            ?>


                                <?php

                                $t = 0;

                                foreach(
                                    $customers
                                    as $customer
                                ):

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

                                            <?=++$t;?>

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
                                                    balance-status
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


                            <?php else: ?>


                                <tr>


                                    <td
                                        colspan="6"
                                        style="
                                            padding:30px;
                                            text-align:center;
                                            color:#8492a6;
                                        "
                                    >

                                        No customers are currently
                                        assigned to this route.

                                    </td>


                                </tr>


                            <?php endif; ?>


                        </tbody>



                        <?php
                        if(
                            count($customers) > 0
                        ):
                        ?>


                            <tfoot>


                                <tr>


                                    <td
                                        colspan="5"
                                        style="
                                            text-align:right;
                                        "
                                    >

                                        TOTAL ROUTE BALANCE

                                    </td>


                                    <td
                                        style="
                                            text-align:right;
                                        "
                                    >

                                        <?=number_format(
                                            $totalBalance,
                                            0
                                        );?>/=

                                    </td>


                                </tr>


                            </tfoot>


                        <?php endif; ?>


                    </table>


                </div>



                <!-- ========================================================
                     PRINT FOOTER
                ========================================================= -->

                <div class="route-statement-footer">


                    <div>

                        Route:
                        <?=htmlspecialchars(
                            $route->name
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
        </script>


        <?php

    break;



    /*
    |--------------------------------------------------------------------------
    | EDIT CUSTOMER
    |--------------------------------------------------------------------------
    */

    case 'edit-customer':


        /*
        |--------------------------------------------------------------------------
        | Keep your existing edit-customer functionality here.
        |--------------------------------------------------------------------------
        */


    break;

}

?>