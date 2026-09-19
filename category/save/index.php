<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner','staff']))
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
            switch($action)
            {
                case 'category-details':

                    $id = request('category_id');

                    /*
                    |--------------------------------------------------------------------------
                    | GET CATEGORY
                    |--------------------------------------------------------------------------
                    */
                    $stmt = "SELECT * FROM cashbook_categories WHERE id = ?";
                    $catRes = prepared_statements($stmt, 'i', [$id]);
                    $category = $catRes->fetch_assoc();

                    /*
                    |--------------------------------------------------------------------------
                    | FETCH CATEGORY TRANSACTIONS
                    |--------------------------------------------------------------------------
                    |
                    | cashbook_transactions:
                    |   credit_amount = CASH IN
                    |   debit_amount  = CASH OUT
                    |
                    */
                    $sql = "SELECT  ct.*,pm.name AS paymode_name,c.name AS customer_name FROM cashbook_transactions ct
                                LEFT JOIN cashbook_paymodes pm ON ct.paymode_id = pm.id
                                LEFT JOIN cashbook_customers c ON ct.customer_id = c.id
                            WHERE ct.category_id = ?
                            ORDER BY ct.created_at ASC, ct.id ASC";

                    $res = prepared_statements($sql, 'i', [$id]);
                    ?>

                    <div class="row mx-1">

                        <div class="col p-2">

                            <strong>
                                CATEGORY:
                            </strong>

                            <?= htmlspecialchars($category['name'] ?? 'Unknown Category'); ?>

                        </div>

                        <div class="col p-2 text-right">

                            <button
                                class="btn btn-sm btn-outline-danger btn-flat"
                                onclick="printMe('printable-div')">

                                <i class="fa fa-print"></i>
                                Print

                            </button>

                        </div>

                    </div>


                    <div class="p-2" id="printable-div">

                        <!-- =========================================================
                            CATEGORY HEADER
                        ========================================================== -->

                        <div class="row mx-1">

                            <div class="col p-2">

                                <h3>
                                    <strong>
                                        Category:
                                    </strong>

                                    <?= htmlspecialchars($category['name'] ?? 'Unknown Category'); ?>

                                </h3>

                            </div>


                            <div class="col p-2">

                                <div class="border p-3 rounded">

                                    <?php

                                    /*
                                    |--------------------------------------------------------------------------
                                    | CATEGORY TOTALS
                                    |--------------------------------------------------------------------------
                                    */

                                    $totalCashIn = 0;
                                    $totalCashOut = 0;

                                    $totalsSql = "
                                        SELECT
                                            COALESCE(SUM(credit_amount), 0) AS cashin,
                                            COALESCE(SUM(debit_amount), 0) AS cashout
                                        FROM cashbook_transactions
                                        WHERE category_id = ?
                                    ";

                                    $totalsRes = prepared_statements(
                                        $totalsSql,
                                        'i',
                                        [$id]
                                    );

                                    $totals = $totalsRes->fetch_assoc();

                                    $totalCashIn  = (float)($totals['cashin'] ?? 0);
                                    $totalCashOut = (float)($totals['cashout'] ?? 0);

                                    $categoryBalance = $totalCashIn - $totalCashOut;

                                    ?>

                                    <div class="row">

                                        <div class="col">
                                            <strong>Cash In</strong><br>
                                            <?= number_format($totalCashIn, 0); ?>
                                        </div>

                                        <div class="col">
                                            <strong>Cash Out</strong><br>
                                            <?= number_format($totalCashOut, 0); ?>
                                        </div>

                                        <div class="col">
                                            <strong>Balance</strong><br>
                                            <?= number_format($categoryBalance, 0); ?>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <hr>


                        <!-- =========================================================
                            TRANSACTION STATEMENT
                        ========================================================== -->

                        <div class="row mx-1">

                            <div class="col p-2">

                                <h4 class="text-center">
                                    <strong>
                                        CATEGORY TRANSACTION STATEMENT
                                    </strong>
                                </h4>


                                <table class="table table-striped table-bordered dataTable">

                                    <thead>

                                        <tr>

                                            <th>Date</th>

                                            <th>Trans ID</th>

                                            <th>Type</th>

                                            <th>Details</th>

                                            <th>Customer</th>

                                            <th>Pay Mode</th>

                                            <th>Cash In</th>

                                            <th>Cash Out</th>

                                            <th>Balance</th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                    <?php

                                    if ($res && $res->num_rows > 0):

                                        $runningBalance = 0;

                                        $totalIn = 0;
                                        $totalOut = 0;

                                        while ($r = $res->fetch_assoc()):

                                            $cashIn  = (float)($r['credit_amount'] ?? 0);
                                            $cashOut = (float)($r['debit_amount'] ?? 0);

                                            $totalIn += $cashIn;
                                            $totalOut += $cashOut;

                                            /*
                                            |--------------------------------------------------------------------------
                                            | RUNNING CATEGORY BALANCE
                                            |--------------------------------------------------------------------------
                                            */

                                            $runningBalance += $cashIn - $cashOut;

                                    ?>

                                        <tr>

                                            <!-- DATE -->
                                            <td>

                                                <?= !empty($r['created_at'])
                                                    ? date(
                                                        'd-m-Y',
                                                        strtotime($r['created_at'])
                                                    )
                                                    : '';
                                                ?>

                                            </td>


                                            <!-- TRANSACTION ID -->
                                            <td>

                                                <?= htmlspecialchars(
                                                    $r['id'] ?? ''
                                                ); ?>

                                            </td>


                                            <!-- TYPE -->
                                            <td>

                                                <?= htmlspecialchars(
                                                    $r['type'] ?? ''
                                                ); ?>

                                            </td>


                                            <!-- DETAILS -->
                                            <td>

                                                <?= htmlspecialchars(
                                                    $r['details'] ?? ''
                                                ); ?>

                                            </td>


                                            <!-- CUSTOMER -->
                                            <td>

                                                <?= htmlspecialchars(
                                                    $r['customer_name'] ?? ''
                                                ); ?>

                                            </td>


                                            <!-- PAYMENT MODE -->
                                            <td>

                                                <?= htmlspecialchars(
                                                    $r['paymode_name'] ?? ''
                                                ); ?>

                                            </td>


                                            <!-- CASH IN -->
                                            <td class="text-right">

                                                <?= $cashIn > 0
                                                    ? number_format($cashIn, 0)
                                                    : '';
                                                ?>

                                            </td>


                                            <!-- CASH OUT -->
                                            <td class="text-right">

                                                <?= $cashOut > 0
                                                    ? number_format($cashOut, 0)
                                                    : '';
                                                ?>

                                            </td>


                                            <!-- RUNNING BALANCE -->
                                            <td class="text-right">

                                                <?= number_format(
                                                    $runningBalance,
                                                    0
                                                ); ?>

                                            </td>

                                        </tr>

                                    <?php

                                        endwhile;

                                    ?>

                                        <!-- =================================================
                                            TOTAL
                                        ================================================== -->

                                        <tr>

                                            <th colspan="6" class="text-right">
                                                TOTAL
                                            </th>

                                            <th class="text-right">
                                                <?= number_format($totalIn, 0); ?>
                                            </th>

                                            <th class="text-right">
                                                <?= number_format($totalOut, 0); ?>
                                            </th>

                                            <th class="text-right">
                                                <?= number_format(
                                                    $totalIn - $totalOut,
                                                    0
                                                ); ?>
                                            </th>

                                        </tr>


                                        <!-- =================================================
                                            BALANCE
                                        ================================================== -->

                                        <tr>

                                            <th colspan="8" class="text-right">
                                                CATEGORY BALANCE
                                            </th>

                                            <th class="text-right">

                                                <?= number_format(
                                                    $runningBalance,
                                                    0
                                                ); ?>

                                            </th>

                                        </tr>


                                    <?php else: ?>

                                        <tr>

                                            <td colspan="9" class="text-center">

                                                No transactions found for this category.

                                            </td>

                                        </tr>

                                    <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                <?php

                break;
                
                
            }
        }
    }else{
        redirect('../');
    }
?>