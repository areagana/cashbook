<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Reports');
        $bsid = request('bkid');
        $book = bookFind(encryptor('decrypt',$bsid));
    ?>
            <div class="container">
                <div class="row mx-1">
                    <div class="col p-2 inline-block">
                        <a href="../books/?bkid=<?=$bsid;?>" class="nav-link">Books</a><i class="fa fa-angle-right"></i>
                        <a class="nav-link">REPORTS</a>
                    </div>
                </div>
                <hr>
                <div class="row mx-1">
                    <div class="col p-2">
                        <h3 class="p-2">FILTER TO VIEW REPORTS</h3>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <div class="p-2 input-group">
                                    <?php
                                        $sqld = "SELECT distinct DATE(created_at) as date FROM cashbook_transactions WHERE book_id =? order by DATE(created_at) desc";
                                        $dats = prepared_statements($sqld,'i',[$book->id]);
                                    ?>
                                    <input type="date" name="filter-date" id="filter-date" data-type='min_date' max='<?=date('Y-m-d');?>' class="form-control filter-item">
                                    <input type="date" name="filter-date" id="filter-date" data-type='max_date' max='<?=date('Y-m-d');?>' class="form-control filter-item">
                                    <?php
                                        $sqlm = "SELECT distinct MONTH(created_at) as month,YEAR(created_at) as year FROM cashbook_transactions WHERE book_id =? order by MONTH(created_at) asc";
                                        $months = prepared_statements($sqlm,'i',[$book->id]);
                                        $month_names =[
                                            1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',
                                            8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'
                                        ];
                                    ?>
                                    <select name="filter-month" id="filter-month" data-type='month' class="form-control filter-item">
                                        <option value='' hidden><i class="fa fa-filter"></i> By Month</option>
                                        <?php while($rm = $months->fetch_assoc()):?>
                                            <option value="<?=$rm['month'];?>"><?=$month_names[$rm['month']];?> <?=$rm['year'];?></option>
                                        <?php endwhile;?>
                                    </select>

                                    <select name="filter-type" id="filter-type" data-type='type' class="form-control filter-item">
                                        <option value=''><i class="fa fa-filter"></i> By Type</option>
                                        <option value="credit">Cashin</option>
                                        <option value="debit">Cashout</option>
                                    </select>
                                    <?php
                                        $sql = "SELECT * FROM cashbook_categories WHERE book_id =? order by name asc";
                                        $cats = prepared_statements($sql,'i',[$book->id]);
                                    ?>
                                    <select name="filter-category" id="filter-category" data-type='category' class="form-control filter-item">
                                        <option value=''><i class="fa fa-filter"></i> By Category</option>
                                        <?php while($rc = $cats->fetch_assoc()):?>
                                            <option value="<?=$rc['id'];?>"><?=$rc['name'];?></option>
                                        <?php endwhile;?>
                                    </select>
                                    <?php
                                        $sqlc = "SELECT * FROM cashbook_customers WHERE book_id =? order by name asc";
                                        $cust = prepared_statements($sqlc,'i',[$book->id]);
                                    ?>
                                    <select name="filter-customer" id="filter-customer" data-type='customer' class="form-control filter-item">
                                        <option value=''><i class="fa fa-filter"></i> By Customer</option>
                                        <?php while($rcu = $cust->fetch_assoc()):?>
                                            <option value="<?=$rcu['id'];?>"><?=$rcu['name'];?></option>
                                        <?php endwhile;?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="p-2 report-view border-bottom">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Customer</th>
                                        <th>Details</th>
                                        <th>Credit</th>
                                        <th>Debit</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody class='transactions-tbody'>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
             <!-- side modal for a cash in -->
            <div class="p-0 bg-white side-modal-tall absolute border shadow" id='side-modal-cashin'>
                <div class="side-modal-header bg-success">
                    <h3 class="side-modal-title text-white"></h3>
                    <button type='button' class='side-modal-close'>&times;</button>
                </div>
                <div class="side-modal-content">
                    
                </div>
            </div>

            <!-- central modal -->
            <div class="p-0 bg-white central-modal absolute border shadow" id='central-modal'>
                <div class="central-modal-header bg-success">
                    <h3 class="central-modal-title"></h3>
                    <button type='button' class='central-modal-close'>&times;</button>
                </div>
                <div class="central-modal-content">
                    
                </div>
            </div>
        <?php
            pageFooter();
        ?>
            <script>
                // click button to show the modal
                $(document).on('click','.btn-click',function(){
                    var title = $(this).data('title');
                    title = title.toUpperCase();
                    $('.side-modal-tall').show();
                    $('.side-modal-title').html(title);
                    // display data in the side modal
                    var category = $(this).data('section');
                    var id = $(this).data('id');
                    fetchData(category,id);
                });

                function fetchData(sect,id)
                {
                    var book_id ="<?=$book->id;?>";
                    if(sect !='')
                    {
                        $.ajax({
                            url:'../books/save/index.php',
                            data:{
                                section:sect,
                                book_id:book_id,
                                action:'fetchForm',
                                route_id:id
                            },
                            beforesend:function(){
                                $('.side-modal-content').html("<h3 class='text-center'>Loading...</h3>");
                            },
                            success:function(res){
                                $('.side-modal-content').html(res);
                            },
                            error:function(err){
                                $('.side-modal-content').html("<h3 class='text-center'>Error Loading data!!</h3>");
                            }
                        });
                    }
                }

                // Call the function for your form
                $(document).on('click','.saveRoute',function(){
                    submitSingleForm("newRouteForm", "../books/save/index.php");
                });

                function submitSingleForm(formId, backendUrl) 
                {
                    const form = document.getElementById(formId);
                    xdialog.startSpin();
                    if (!form) {
                        console.error("Form not found:", formId);
                        return;
                    }

                    // Attach submit listener once
                    form.addEventListener("submit", function(e) {
                        e.preventDefault(); // prevent default page reload
                        const formData = new FormData(form);
                        console.log(formData);
                        fetch(backendUrl, {
                            method: "POST",
                            body: formData
                        })
                        .then(res => res.text()) // or .json() if backend returns JSON
                        .then(response => {
                            // Optionally show response below form
                            let responseDiv = document.getElementById("response_" + formId);
                            if (!responseDiv) {
                                responseDiv = document.createElement("div");
                                responseDiv.id = "response_" + formId;
                                form.appendChild(responseDiv);
                            }
                            // console.log(response);
                            xdialog.stopSpin();
                            window.location.reload();
                        })
                        .catch(err => {
                            console.error("AJAX error:", err);
                        });
                    });
                }

                // filter transactions based on type
                $(document).on('change', '.filter-item', function () {
                    var book_id ="<?=$book->id;?>";
                    let filters = {
                        action: 'transactionFilter',
                        book_id:book_id
                    };

                    $('.filter-item').each(function () {
                        let key = $(this).data('type');
                        let val = $(this).val();

                        if (val && val !== '') {
                            filters[key] = val;
                        }
                    });
                    //send request to database

                    $.ajax({
                        url: '../books/save/index.php',
                        type: 'POST',
                        data: filters,
                        beforeSend:function(){
                            $('.transactions-tbody').html(
                                "<tr><td colspan='10' align='center'>Loading...</td></tr>"
                            );
                        },
                        success: function (res) {
                            $('.transactions-tbody').html(res);
                        },
                        error: function (err) {
                            $('.transactions-tbody').html(
                                "<tr><td colspan='10'><center>Failed to load data</center></td></tr>"
                            );
                        }
                    });
                });

            </script>
        <?php
    }else{
        redirect('../');
    }

?>