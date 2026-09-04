<?php
require_once(__dir__.'/../assets/functions.php');

if(isVerified())
{
    pageHeader('Customers');

    $bsid = request('bsid');
    $book = bookFind(encryptor('decrypt',$bsid));

    /*
    =========================================================
    CUSTOMER SUMMARY
    =========================================================
    */

    $summary_sql = "SELECT
            COUNT(*) AS total_customers,
             COALESCE(SUM(
                CASE
                    WHEN COALESCE(l.balance,0) > 0
                    THEN l.balance
                    ELSE 0
                END
            ),0) AS total_receivable,

            COUNT(
                CASE
                    WHEN COALESCE(l.balance,0) > 0
                    THEN 1
                END
            ) AS customers_with_balance,

            COUNT(
                CASE
                    WHEN COALESCE(l.balance,0) = 0
                    THEN 1
                END
            ) AS customers_zero_balance,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(l.balance,0) < 0
                    THEN ABS(l.balance)
                    ELSE 0
                END
            ),0) AS customer_credit

        FROM cashbook_customers c
        LEFT JOIN (
            SELECT
                cl.customer_id,
                cl.balance
            FROM cashbook_customer_ledger cl
            INNER JOIN (
                SELECT
                    customer_id,
                    MAX(id) AS max_id
                FROM cashbook_customer_ledger
                GROUP BY customer_id
            ) latest ON latest.max_id = cl.id
        ) l ON l.customer_id = c.id
        WHERE c.book_id = ?
    ";

    $summary_res = prepared_statements( $summary_sql,'i',[$book->id] );

    $summary = $summary_res->fetch_assoc();

    /*
    =========================================================
    CUSTOMER LIST
    =========================================================
    */

    $sql = "
        SELECT
            c.*,

            COALESCE(ci.items,0) AS items,

            cr.name AS route,

            crm.name AS route_manager,

            COALESCE(l.balance,0) AS balance

        FROM cashbook_customers c

        LEFT JOIN (
            SELECT
                customer_id,
                COUNT(DISTINCT item_id) AS items
            FROM cashbook_customer_items
            GROUP BY customer_id
        ) ci
            ON ci.customer_id = c.id

        LEFT JOIN (
            SELECT
                cl.customer_id,
                cl.balance
            FROM cashbook_customer_ledger cl

            INNER JOIN (
                SELECT
                    customer_id,
                    MAX(id) AS max_id
                FROM cashbook_customer_ledger
                GROUP BY customer_id
            ) latest
                ON latest.max_id = cl.id
        ) l
            ON l.customer_id = c.id

        LEFT JOIN cashbook_routes cr
            ON cr.id = c.route_id

        LEFT JOIN cashbook_route_managers crm
            ON crm.id = c.route_manager_id

        WHERE c.book_id = ?

        ORDER BY
            CASE
                WHEN COALESCE(l.balance,0) > 0 THEN 0
                ELSE 1
            END,
            COALESCE(l.balance,0) DESC,
            c.name ASC
    ";

    $res = prepared_statements(
        $sql,
        'i',
        [$book->id]
    );

    $customers = [];

    while($r = $res->fetch_assoc())
    {
        $customers[] = $r;
    }

    $total_customers       = (int)($summary['total_customers'] ?? 0);
    $total_receivable      = (float)($summary['total_receivable'] ?? 0);
    $customers_with_balance= (int)($summary['customers_with_balance'] ?? 0);
    $customers_zero_balance= (int)($summary['customers_zero_balance'] ?? 0);
    $customer_credit       = (float)($summary['customer_credit'] ?? 0);

    /*
    =========================================================
    HELPER FOR ESCAPING
    =========================================================
    */

    function customer_html($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
?>

<style>
    .customer-page {
        --customer-radius: 12px;
    }

    .customer-page .page-title {
        font-weight: 700;
        letter-spacing: -.3px;
    }

    .customer-page .breadcrumb-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .customer-page .breadcrumb-wrap a {
        text-decoration: none;
    }

    .customer-page .metric-card {
        border: 0;
        border-radius: var(--customer-radius);
        box-shadow: 0 4px 18px rgba(0,0,0,.06);
        overflow: hidden;
        height: 100%;
        background: #fff;
    }

    .customer-page .metric-card .card-body {
        padding: 18px;
    }

    .customer-page .metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
    }

    .customer-page .metric-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: #7b8490;
        margin-bottom: 5px;
    }

    .customer-page .metric-value {
        font-size: 25px;
        line-height: 1.1;
        font-weight: 800;
        margin: 0;
    }

    .customer-page .overview-card {
        border: 0;
        border-radius: var(--customer-radius);
        box-shadow: 0 4px 18px rgba(0,0,0,.06);
    }

    .customer-page .overview-total {
        font-size: 22px;
        font-weight: 800;
    }

    .customer-page .progress {
        height: 9px;
        border-radius: 20px;
        background: #edf0f3;
    }

    .customer-page .customer-table-card {
        border: 0;
        border-radius: var(--customer-radius);
        box-shadow: 0 4px 18px rgba(0,0,0,.06);
        overflow: hidden;
    }

    .customer-page .customer-table-card .card-header {
        background: #fff;
        border-bottom: 1px solid #edf0f3;
        padding: 17px 20px;
    }

    .customer-page .customer-table-card .card-body {
        padding: 0;
    }

    .customer-page #customerTable thead th {
        white-space: nowrap;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .45px;
        color: #6c757d;
        border-top: 0;
        background: #f8f9fa;
        padding: 12px 10px;
    }

    .customer-page #customerTable tbody td {
        vertical-align: middle;
        padding: 12px 10px;
    }

    .customer-page #customerTable tbody tr {
        transition: background .15s ease;
    }

    .customer-page #customerTable tbody tr:hover {
        background: #f8fbff;
    }

    .customer-page .customer-name {
        font-weight: 700;
        color: #252a2f;
    }

    .customer-page .customer-meta {
        font-size: 11px;
        color: #89919a;
        margin-top: 2px;
    }

    .customer-page .balance {
        font-weight: 800;
        white-space: nowrap;
    }

    .customer-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 20px;
        padding: 5px 9px;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }

    .customer-page .status-outstanding {
        color: #b42318;
        background: #fef3f2;
    }

    .customer-page .status-cleared {
        color: #667085;
        background: #f2f4f7;
    }

    .customer-page .status-credit {
        color: #067647;
        background: #ecfdf3;
    }

    .customer-page .action-btn {
        border-radius: 7px;
    }

    .customer-page .filter-box {
        position: relative;
    }

    .customer-page .filter-box i {
        position: absolute;
        left: 11px;
        top: 10px;
        color: #98a2b3;
        z-index: 2;
    }

    .customer-page .filter-box input {
        padding-left: 34px;
    }

    .customer-page .summary-note {
        color: #667085;
        font-size: 12px;
    }

    .customer-page .empty-customers {
        padding: 55px 20px;
        text-align: center;
        color: #7b8490;
    }

    @media(max-width: 767px)
    {
        .customer-page .metric-value {
            font-size: 21px;
        }

        .customer-page .customer-table-card .card-header {
            padding: 14px;
        }

        .customer-page .page-actions {
            width: 100%;
        }

        .customer-page .page-actions .btn {
            width: 100%;
        }
    }

    @media print
    {
        .customer-page .no-print,
        .customer-page .page-actions,
        .customer-page .action-column {
            display: none !important;
        }

        .customer-page .metric-card,
        .customer-page .overview-card,
        .customer-page .customer-table-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }

        body {
            background: #fff !important;
        }
    }
</style>

<div class="customer-page">

    <div class="container-fluid">

        <!-- =====================================================
             BREADCRUMB / PAGE HEADER
        ====================================================== -->

        <div class="row mx-1">
            <div class="col p-2">

                <div class="breadcrumb-wrap">

                    <a href="../books/?bkid=<?=$bsid;?>" class="nav-link p-0">
                        <i class="fa fa-book"></i>
                        Books
                    </a>

                    <i class="fa fa-angle-right text-muted"></i>

                    <span class="text-muted">
                        Customers
                    </span>

                </div>

            </div>
        </div>

        <hr>

        <div class="row mx-1 align-items-center mb-2">

            <div class="col-md-8 p-2">

                <h3 class="page-title mb-1">
                    <?=customer_html(strtoupper($book->name));?> — CUSTOMER ACCOUNTS
                </h3>

                <div class="summary-note">
                    Customer balances, account status and transaction access.
                </div>

            </div>

            <div class="col-md-4 p-2 text-md-right page-actions">

                <?php if(hasRole(['owner','partner','staff'])): ?>

                    <button
                        type="button"
                        class="btn btn-sm btn-success btn-click"
                        data-title="Add Customer"
                        data-section="customer">

                        <i class="fa fa-plus-circle"></i>
                        Customer

                    </button>

                <?php endif; ?>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary ml-1"
                    onclick="window.print();">

                    <i class="fa fa-print"></i>
                    Print

                </button>

            </div>

        </div>

        <!-- =====================================================
             FINANCIAL METRICS
        ====================================================== -->

        <div class="row mx-1 mb-2">

            <div class="col-xl-3 col-md-6 p-2">
                <div class="metric-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <div class="metric-label">
                                    Total Customers
                                </div>

                                <div class="metric-value">
                                    <?=number_format($total_customers);?>
                                </div>
                            </div>

                            <div class="metric-icon bg-primary text-white">
                                <i class="fa fa-users"></i>
                            </div>

                        </div>

                        <div class="summary-note mt-2">
                            All customer accounts in this book
                        </div>

                    </div>

                </div>
            </div>


            <div class="col-xl-3 col-md-6 p-2">
                <div class="metric-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <div class="metric-label">
                                    Total Outstanding
                                </div>

                                <div class="metric-value text-danger">
                                    <?=number_format($total_receivable,0);?>
                                </div>
                            </div>

                            <div class="metric-icon bg-danger text-white">
                                <i class="fa fa-money"></i>
                            </div>

                        </div>

                        <div class="summary-note mt-2">
                            Money currently owed by customers
                        </div>

                    </div>

                </div>
            </div>


            <div class="col-xl-3 col-md-6 p-2">
                <div class="metric-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <div class="metric-label">
                                    Outstanding Accounts
                                </div>

                                <div class="metric-value text-warning">
                                    <?=number_format($customers_with_balance);?>
                                </div>
                            </div>

                            <div class="metric-icon bg-warning text-white">
                                <i class="fa fa-exclamation-circle"></i>
                            </div>

                        </div>

                        <div class="summary-note mt-2">
                            Customers with a positive balance
                        </div>

                    </div>

                </div>
            </div>


            <div class="col-xl-3 col-md-6 p-2">
                <div class="metric-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <div class="metric-label">
                                    Customer Credit
                                </div>

                                <div class="metric-value text-success">
                                    <?=number_format($customer_credit,0);?>
                                </div>
                            </div>

                            <div class="metric-icon bg-success text-white">
                                <i class="fa fa-check-circle"></i>
                            </div>

                        </div>

                        <div class="summary-note mt-2">
                            Customer credit / overpayments
                        </div>

                    </div>

                </div>
            </div>

        </div>


        <!-- =====================================================
             ACCOUNT OVERVIEW
        ====================================================== -->

        <div class="row mx-1 mb-2">

            <div class="col-lg-8 p-2">

                <div class="card overview-card h-100">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start mb-3">

                            <div>
                                <h5 class="mb-1">
                                    <i class="fa fa-bar-chart text-primary"></i>
                                    Receivables Overview
                                </h5>

                                <div class="summary-note">
                                    Distribution of customer accounts by current balance.
                                </div>
                            </div>

                            <div class="overview-total text-danger">
                                <?=number_format($total_receivable,0);?>
                            </div>

                        </div>

                        <?php
                            $account_total = max($total_customers, 1);
                            $outstanding_percent = ($customers_with_balance / $account_total) * 100;
                            $cleared_percent = ($customers_zero_balance / $account_total) * 100;
                        ?>

                        <div class="d-flex justify-content-between small mb-1">
                            <span>
                                Outstanding customers
                            </span>

                            <strong>
                                <?=number_format($outstanding_percent,1);?>%
                            </strong>
                        </div>

                        <div class="progress mb-3">
                            <div
                                class="progress-bar bg-danger"
                                style="width: <?=min(100,$outstanding_percent);?>%;">
                            </div>
                        </div>

                        <div class="row">

                            <div class="col-sm-6">

                                <div class="border rounded p-3">

                                    <div class="small text-muted">
                                        Owing
                                    </div>

                                    <div class="h5 font-weight-bold text-danger mb-0">
                                        <?=number_format($customers_with_balance);?>
                                    </div>

                                </div>

                            </div>

                            <div class="col-sm-6 mt-2 mt-sm-0">

                                <div class="border rounded p-3">

                                    <div class="small text-muted">
                                        Cleared
                                    </div>

                                    <div class="h5 font-weight-bold text-secondary mb-0">
                                        <?=number_format($customers_zero_balance);?>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-lg-4 p-2">

                <div class="card overview-card h-100">

                    <div class="card-body">

                        <h5 class="mb-1">
                            <i class="fa fa-info-circle text-info"></i>
                            Account Summary
                        </h5>

                        <div class="summary-note mb-3">
                            Quick financial position of your customer accounts.
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">
                                Customers
                            </span>
                            <strong>
                                <?=number_format($total_customers);?>
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">
                                Owing
                            </span>
                            <strong class="text-danger">
                                <?=number_format($customers_with_balance);?>
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">
                                Cleared
                            </span>
                            <strong>
                                <?=number_format($customers_zero_balance);?>
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">
                                Credit
                            </span>
                            <strong class="text-success">
                                <?=number_format($customer_credit,0);?>
                            </strong>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             CUSTOMER LIST
        ====================================================== -->

        <div class="row mx-1">

            <div class="col p-2">

                <div class="card customer-table-card">

                    <div class="card-header">

                        <div class="row align-items-center">

                            <div class="col-lg-6">

                                <h5 class="mb-1">
                                    <i class="fa fa-users text-primary"></i>
                                    Customer Accounts
                                </h5>

                                <div class="summary-note">
                                    <?=number_format($total_customers);?>
                                    customer accounts available.
                                    Open an account to view its transactions.
                                </div>

                            </div>

                            <div class="col-lg-6 mt-3 mt-lg-0 no-print">

                                <div class="filter-box">

                                    <i class="fa fa-search"></i>

                                    <input
                                        type="text"
                                        id="customerSearch"
                                        class="form-control form-control-sm"
                                        placeholder="Search customer, contact, route or manager...">

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body">

                        <?php if(empty($customers)): ?>

                            <div class="empty-customers">

                                <i class="fa fa-users fa-3x mb-3 text-muted"></i>

                                <h5>
                                    No customers found
                                </h5>

                                <p class="mb-3">
                                    There are currently no customers registered for this book.
                                </p>

                                <?php if(hasRole(['owner','partner','staff'])): ?>

                                    <button
                                        type="button"
                                        class="btn btn-success btn-click"
                                        data-title="Add Customer"
                                        data-section="customer">

                                        <i class="fa fa-plus"></i>
                                        Add First Customer

                                    </button>

                                <?php endif; ?>

                            </div>

                        <?php else: ?>

                            <div class="table-responsive">

                                <table
                                    class="table table-sm table-hover"
                                    id="customerTable">

                                    <thead>

                                        <tr>

                                            <th width="45">
                                                #
                                            </th>

                                            <th>
                                                Customer
                                            </th>

                                            <th>
                                                Contact
                                            </th>

                                            <th>
                                                Route
                                            </th>

                                            <th>
                                                Manager
                                            </th>

                                            <th class="text-center">
                                                Items
                                            </th>

                                            <th class="text-right">
                                                Balance
                                            </th>

                                            <th class="text-center">
                                                Status
                                            </th>

                                            <th class="text-right action-column">
                                                Action
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                    <?php foreach($customers as $index => $r): ?>

                                        <?php

                                            $balance = (float)$r['balance'];

                                            if($balance > 0)
                                            {
                                                $status = 'Outstanding';
                                                $status_class = 'status-outstanding';
                                                $status_icon = 'fa-exclamation-circle';
                                            }
                                            elseif($balance < 0)
                                            {
                                                $status = 'Credit';
                                                $status_class = 'status-credit';
                                                $status_icon = 'fa-check-circle';
                                            }
                                            else
                                            {
                                                $status = 'Cleared';
                                                $status_class = 'status-cleared';
                                                $status_icon = 'fa-minus-circle';
                                            }

                                        ?>

                                        <tr
                                            data-customer-id="<?=customer_html($r['id']);?>"
                                            data-balance="<?=$balance;?>">

                                            <td>
                                                <?=$index + 1;?>
                                            </td>

                                            <td>

                                                <div class="customer-name">
                                                    <?=customer_html($r['name']);?>
                                                </div>

                                                <?php if(isset($r['id'])): ?>

                                                    <div class="customer-meta">
                                                        Account #<?=customer_html($r['id']);?>
                                                    </div>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <?php if(!empty($r['contact'])): ?>

                                                    <span>
                                                        <i class="fa fa-phone text-muted"></i>
                                                        <?=customer_html($r['contact']);?>
                                                    </span>

                                                <?php else: ?>

                                                    <span class="text-muted">
                                                        —
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <?php if(!empty($r['route'])): ?>

                                                    <span class="badge badge-light border">
                                                        <?=customer_html($r['route']);?>
                                                    </span>

                                                <?php else: ?>

                                                    <span class="text-muted">
                                                        —
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <?php if(!empty($r['route_manager'])): ?>

                                                    <?=customer_html($r['route_manager']);?>

                                                <?php else: ?>

                                                    <span class="text-muted">
                                                        —
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td class="text-center">

                                                <span class="badge badge-info">
                                                    <?=number_format((int)$r['items']);?>
                                                </span>

                                            </td>

                                            <td class="text-right">

                                                <?php if($balance > 0): ?>

                                                    <span class="balance text-danger">
                                                        <?=number_format($balance,0);?>
                                                    </span>

                                                <?php elseif($balance < 0): ?>

                                                    <span class="balance text-success">
                                                        <?=number_format(abs($balance),0);?>
                                                    </span>

                                                    <small class="d-block text-success">
                                                        Credit
                                                    </small>

                                                <?php else: ?>

                                                    <span class="balance text-muted">
                                                        0
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td class="text-center">

                                                <span class="status-pill <?=$status_class;?>">

                                                    <i class="fa <?=$status_icon;?>"></i>

                                                    <?=$status;?>

                                                </span>

                                            </td>

                                            <td class="text-right action-column">

                                                <div class="btn-group">

                                                    <?php if(hasRole(['owner','partner','staff'])): ?>

                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-primary action-btn view-customer"
                                                            data-id="<?=customer_html($r['id']);?>"
                                                            data-title="<?=customer_html($r['name']);?>"
                                                            title="View customer account">

                                                            <i class="fa fa-eye"></i>
                                                            <span class="d-none d-xl-inline">
                                                                Account
                                                            </span>

                                                        </button>

                                                    <?php endif; ?>


                                                    <?php if(hasRole(['owner','partner'])): ?>

                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-info action-btn edit-customer"
                                                            data-id="<?=customer_html($r['id']);?>"
                                                            data-title="<?=customer_html($r['name']);?>"
                                                            title="Edit customer">

                                                            <i class="fa fa-edit"></i>

                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger action-btn delete-customer"
                                                            data-id="<?=customer_html($r['id']);?>"
                                                            title="Delete customer">

                                                            <i class="fa fa-trash"></i>

                                                        </button>

                                                    <?php endif; ?>


                                                    <?php if(hasRole(['owner','partner','staff'])): ?>

                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-secondary action-btn customer-attach-item"
                                                            data-id="<?=customer_html($r['id']);?>"
                                                            title="Attach items">

                                                            <i class="fa fa-plus"></i>

                                                        </button>

                                                    <?php endif; ?>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         CUSTOMER SIDE MODAL
    ====================================================== -->

    <div
        class="p-0 bg-white side-modal-tall absolute border shadow"
        id="side-modal-customer"
        style="display:none;">

        <div class="side-modal-header bg-success">

            <h3 class="side-modal-title text-white"></h3>

            <button
                type="button"
                class="side-modal-close">
                &times;
            </button>

        </div>

        <div class="side-modal-content"></div>

    </div>


    <!-- =====================================================
         CENTRAL CUSTOMER ACCOUNT MODAL
    ====================================================== -->

    <div
        class="p-0 bg-white central-modal absolute border shadow"
        id="central-modal"
        style="display:none;">

        <div class="central-modal-header bg-success">

            <h3 class="central-modal-title"></h3>

            <button
                type="button"
                class="central-modal-close">
                &times;
            </button>

        </div>

        <div class="central-modal-content"></div>

    </div>

</div>


<script>
(function(){

    /*
    =========================================================
    CUSTOMER SEARCH
    =========================================================
    */

    $('#customerSearch').on('keyup', function(){

        const value = $(this).val().toLowerCase().trim();

        $('#customerTable tbody tr').each(function(){

            const rowText = $(this).text().toLowerCase();

            $(this).toggle(rowText.indexOf(value) !== -1);

        });

    });


    /*
    =========================================================
    ADD CUSTOMER
    =========================================================
    */

    $(document).on('click','.btn-click',function(){

        const title = $(this).data('title') || '';
        const category = $(this).data('section') || '';

        $('.side-modal-tall').show();

        $('.side-modal-title').html(
            $('<div>').text(title.toUpperCase()).html()
        );

        $('.side-modal-content').html(`
            <div class="text-center p-5">
                <i class="fa fa-spinner fa-spin fa-2x text-success"></i>
                <div class="mt-2">Loading...</div>
            </div>
        `);

        fetchData(category);

    });


    /*
    =========================================================
    FETCH FORM
    =========================================================
    */

    function fetchData(section)
    {
        const book_id = <?=json_encode($book->id);?>;

        if(!section)
        {
            return;
        }

        $.ajax({

            url:'../books/save/index.php',

            type:'POST',

            data:{
                section:section,
                book_id:book_id,
                action:'fetchForm'
            },

            beforeSend:function(){

                $('.side-modal-content').html(`
                    <div class="text-center p-5">
                        <i class="fa fa-spinner fa-spin fa-2x text-success"></i>
                        <div class="mt-2">Loading form...</div>
                    </div>
                `);

            },

            success:function(res){

                $('.side-modal-content').html(res);

            },

            error:function(xhr){

                console.error(xhr.responseText);

                $('.side-modal-content').html(`
                    <div class="alert alert-danger m-3">
                        <i class="fa fa-exclamation-triangle"></i>
                        Error loading form.
                    </div>
                `);

            }

        });
    }


    /*
    =========================================================
    SAVE CUSTOMER
    =========================================================
    */

    $(document).on('click','.saveCustomer',function(e){

        e.preventDefault();

        const form = document.getElementById('newCustomerForm');

        if(!form)
        {
            console.error('newCustomerForm was not found.');
            return;
        }

        const formData = new FormData(form);
        const button = $(this);

        $.ajax({

            url:'../books/save/index.php',

            type:'POST',

            data:formData,

            processData:false,

            contentType:false,

            beforeSend:function(){

                button.prop('disabled',true);
                xdialog.startSpin();

            },

            success:function(response){

                xdialog.stopSpin();

                window.location.reload();

            },

            error:function(xhr){

                xdialog.stopSpin();

                button.prop('disabled',false);

                console.error(xhr.responseText);

                xdialog.info('Error saving customer.');

            }

        });

    });


    /*
    =========================================================
    VIEW CUSTOMER ACCOUNT
    =========================================================
    */

    $(document).on('click','.view-customer',function(){

        const id = $(this).data('id');
        const title = $(this).data('title') || 'Customer';

        $('#central-modal').show();

        $('.central-modal-title').html(
            '<i class="fa fa-user-circle"></i> ' +
            $('<div>').text(title).html() +
            ' — ACCOUNT'
        );

        $('.central-modal-content').html(`
            <div class="text-center p-5">
                <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                <div class="mt-2">Loading customer account...</div>
            </div>
        `);

        $.ajax({

            url:'save/index.php',

            type:'POST',

            data:{
                customer_id:id,
                action:'Customer-details'
            },

            success:function(res){

                $('.central-modal-content').html(res);

            },

            error:function(xhr){

                console.error(xhr.responseText);

                $('.central-modal-content').html(`
                    <div class="alert alert-danger m-3">
                        <i class="fa fa-exclamation-triangle"></i>
                        Unable to load this customer's account.
                    </div>
                `);

            }

        });

    });


    /*
    =========================================================
    EDIT CUSTOMER
    =========================================================
    */

    $(document).on('click','.edit-customer',function(){

        const id = $(this).data('id');
        const title = $(this).data('title') || 'Customer';

        $('#side-modal-customer').show();

        $('.side-modal-title').html(
            $('<div>').text(title + ' Edit').html()
        );

        $('.side-modal-content').html(`
            <div class="text-center p-5">
                <i class="fa fa-spinner fa-spin fa-2x text-success"></i>
                <div class="mt-2">Loading customer...</div>
            </div>
        `);

        $.ajax({

            url:'save/index.php',

            type:'POST',

            data:{
                customer_id:id,
                action:'Customer-edit'
            },

            success:function(res){

                $('.side-modal-content').html(res);

            },

            error:function(xhr){

                console.error(xhr.responseText);

                $('.side-modal-content').html(`
                    <div class="alert alert-danger m-3">
                        Unable to load customer details.
                    </div>
                `);

            }

        });

    });


    /*
    =========================================================
    DELETE CUSTOMER
    =========================================================
    */

    $(document).on('click','.delete-customer',function(){

        const id = $(this).data('id');

        xdialog.confirm(
            'Confirm to delete this customer?',
            function(){

                $.ajax({

                    url:'save/index.php',

                    type:'POST',

                    data:{
                        action:'deleteCustomer',
                        id:id
                    },

                    beforeSend:function(){

                        xdialog.startSpin();

                    },

                    success:function(res){

                        xdialog.stopSpin();

                        xdialog.info(
                            'Customer deleted successfully',
                            function(){
                                window.location.reload();
                            }
                        );

                    },

                    error:function(xhr){

                        xdialog.stopSpin();

                        console.error(xhr.responseText);

                        xdialog.info(
                            'Error removing customer.'
                        );

                    }

                });

            }
        );

    });


    /*
    =========================================================
    ATTACH ITEMS
    =========================================================
    */

    $(document).on('click','.customer-attach-item',function(){

        const id = $(this).data('id');
        const book_id = <?=json_encode($book->id);?>;

        $('#side-modal-customer').show();

        $('.side-modal-title').html(
            'ATTACH ITEMS TO CUSTOMER'
        );

        $('.side-modal-content').html(`
            <div class="text-center p-5">
                <i class="fa fa-spinner fa-spin fa-2x text-success"></i>
                <div class="mt-2">Loading items...</div>
            </div>
        `);

        $.ajax({

            url:'save/index.php',

            type:'POST',

            data:{
                action:'findItems',
                id:id,
                book_id:book_id
            },

            success:function(res){

                $('.side-modal-content').html(res);

            },

            error:function(xhr){

                console.error(xhr.responseText);

                $('.side-modal-content').html(`
                    <div class="alert alert-danger m-3">
                        Unable to load customer items.
                    </div>
                `);

            }

        });

    });


    /*
    =========================================================
    CLOSE MODALS
    =========================================================
    */

    $(document).on('click','.side-modal-close',function(){

        $(this).closest('.side-modal-tall').hide();

    });

    $(document).on('click','.central-modal-close',function(){

        $(this).closest('.central-modal').hide();

    });

})();
</script>

<?php
    pageFooter();

}
else
{
    redirect('../');
}
?>
