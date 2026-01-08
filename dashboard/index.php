<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Dashboard');
        // fetch selected business
            $id_enc = request('bkid');
            $id = encryptor('decrypt',$id_enc);
            $business = bookFind($id);
        ?>
            <div class="container-fluid">
                <div class="row mx-1">
                    <div class="col p-2 inline-block">
                        <a href="../books/?bkid=<?=$id_enc;?>" class="nav-link">Books</a><i class="fa fa-angle-right"></i>
                        <a class="nav-link">Dashboard</a>
                    </div>
                </div>
                <hr>
                <div class="row mx-1">
                    <div class="col p-2">
                        <h3>Dashboard</h3>
                    </div>
                </div>
                <div class="row mx-1">
                    <div class="col p-2  m-1 border rounded-3">
                        <h3 class="p-2 border-bottom">CASH SUMMARIES</h3>
                        <?php
                            // get cashins and cashouts
                            $query = "SELECT SUM(credit_amount) as total_cashin,sum(debit_amount) as total_cashout,(SUM(credit_amount) - SUM(debit_amount)) AS balance FROM cashbook_transactions WHERE book_id = ?";
                            $data = prepared_statements($query,'i',[$business->id]);
                            $row = $data->fetch_assoc();
                        ?>
                        <div class="row mx-1">
                            <div class="col p-4 border rounded-3 m-1">
                                <strong>CASHIN: </strong><span class='right'><?=number_format($row['total_cashin'],0);?>/=</span>
                            </div>
                            <div class="col p-4 border rounded-3 m-1">
                                <strong>CASHOUT: </strong><span class='right'><?=number_format($row['total_cashout'],0);?>/=</span>
                            </div>
                            <div class="col p-4 border rounded-3 m-1">
                                <strong>BALANCE: </strong><span class='right'><?=number_format($row['balance'],0);?>/=</span>
                            </div>
                        </div>
                        <script>
                            const cashData = {
                                cashin: "<?= (int)$row['total_cashin']; ?>",
                                cashout: "<?= (int)$row['total_cashout']; ?>",
                                balance: "<?= (int)$row['balance']; ?>"
                            };
                        </script>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <canvas id='cashChart' width="200" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col p-4 border rounded-3 m-1">
                        <h3 class="p-2 border-bottom">MONTHLY SUMMARIES <?=date('Y');;?></h3>
                        <div class="p-2">
                            <?php
                                $year = date('Y');
                                $query = "
                                    SELECT 
                                        MONTH(created_at) AS month,
                                        SUM(credit_amount) AS cashin,
                                        SUM(debit_amount) AS cashout
                                    FROM cashbook_transactions
                                    WHERE book_id = ?
                                    GROUP BY MONTH(created_at)
                                    ORDER BY YEAR(created_at)
                                ";

                                $data = prepared_statements($query, 'i', [$business->id]);

                                $months = [];
                                $cashins = [];
                                $cashouts = [];

                                while ($row = $data->fetch_assoc()) {
                                    $months[]   = date('M', mktime(0, 0, 0, $row['month'], 1));
                                    $cashins[]  = (int)$row['cashin'];
                                    $cashouts[] = (int)$row['cashout'];
                                }
                            ?>
                            
                            <script>
                                const monthlyLabels  = <?= json_encode($months); ?>;
                                const monthlyCashin  = <?= json_encode($cashins); ?>;
                                const monthlyCashout = <?= json_encode($cashouts); ?>;
                            </script>
                            <canvas id="monthlyCashChart" width="200" height="300"></canvas>
                        </div>
                    </div>
                    <div class="col p-0 border rounded-3 m-1">
                        <!-- <h3 class="p-2 border-bottom">CATEGORY SUMMARIES</h3> -->
                        <div class="row mx-1">
                            <div class="col p-4 bg-secondary m-1 border rounded-3">
                                <em>Coming Soon...</em>
                            </div>
                            <div class="col p-4 bg-secondary m-1 border rounded-3">
                                <em>Coming Soon...</em>
                            </div>
                        </div>
                        <div class="row mx-1">
                            <div class="col p-4 bg-secondary m-1 border rounded-3">
                                <em>Coming Soon...</em>
                            </div>
                            <div class="col p-4 bg-secondary m-1 border rounded-3">
                                <em>Coming Soon...</em>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
            pageFooter();
        ?>
            <script>
                const ctx = document.getElementById('cashChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Cash In', 'Cash Out', 'Balance'],
                        datasets: [{
                            label: 'Cash Summary',
                            data: [
                                cashData.cashin,
                                cashData.cashout,
                                cashData.balance
                            ],
                            backgroundColor: [
                                'rgba(40, 167, 69, 0.7)',   // green - cashin
                                'rgba(220, 53, 69, 0.7)',   // red - cashout
                                'rgba(0, 123, 255, 0.7)'    // blue - balance
                            ],
                            borderColor: [
                                'rgba(40, 167, 69, 1)',
                                'rgba(220, 53, 69, 1)',
                                'rgba(0, 123, 255, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y.toLocaleString() + '/=';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });

                // monthly summaries
                const month = document.getElementById('monthlyCashChart').getContext('2d');
                new Chart(month, {
                    type: 'bar',
                    data: {
                        labels: monthlyLabels,
                        datasets: [
                            {
                                label: 'Cash In',
                                data: monthlyCashin,
                                backgroundColor: 'rgba(40, 167, 69, 0.7)'
                            },
                            {
                                label: 'Cash Out',
                                data: monthlyCashout,
                                backgroundColor: 'rgba(220, 53, 69, 0.7)'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' +
                                            context.parsed.y.toLocaleString() + '/=';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: value => value.toLocaleString()
                                }
                            }
                        }
                    }
                });
            </script>

        <?php
    }else
    {
        redirect('../');
    }
?>