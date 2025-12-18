<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner']))
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
            switch($action)
            {
                case 'Customer-details':
                        $id = request('customer_id');

                        // fetch transactions
                        $sql = "SELECT * FROM cashbook_transactions WHERE customer_id = ? ORDER BY created_at desc";
                        $res = prepared_statements($sql,'i',[$id]);
                        $credits = [];
                        $debits =[];
                    ?>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if($res->num_rows > 0):?>
                                <?php while($r = $res->fetch_assoc()): $credits[] = $r['credit_amount']; $debits[] = $r['debit_amount']; ?>
                                    <tr>
                                        <td><?=$r['created_at'];?></td>
                                        <td><?=$r['type'];?></td>
                                        <td><?=$r['details'];?></td>
                                        <td><?=(!empty($r['credit_amount'])) ? number_format($r['credit_amount'],0) : number_format(-$r['debit_amount'],0);?></td>
                                        <td></td>
                                    </tr>
                                <?php endwhile;?>
                            <?php else:?>
                                <tr>
                                    <td colspan='5'><center>No Transactions found</center></td>
                                </tr>
                            <?php endif;?>
                            <tr>
                                <th colspan='3'>BALANCE</th>
                                <th><?=number_format((array_sum($credits) - array_sum($debits)),0);?></th>
                            </tr>
                            </tbody>
                        </table>
                    <?php
                    break;
                case'edit-customer':


                    break;
            }
        }
    }else{
        redirect('../');
    }
?>