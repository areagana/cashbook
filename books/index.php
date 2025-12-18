    <?php
        require_once('../assets/functions.php');
        if(isVerified())
        {
            pageHeader('Book-details');
            $id = request('bkid');
            $id = encryptor('decrypt',$id);
            $book = bookFind($id);
    ?>
            <style>
                @media screen AND (max-width:768px)
                {
                    .side-displays{
                        display:none;
                    }
                    .main-display{
                        width:100% !important;
                    }
                }
                .transaction-table-wrapper {
                    max-height: 400px;   /* choose your height */
                    overflow-y: auto;    /* vertical scroll */
                    overflow-x: hidden; /* optional */
                }
                .transaction-table-wrapper thead th {
                    position: sticky;
                    top: 0;
                    background: #fff;
                    z-index: 2;
                }
            </style>
            <div class="row mx-1">
                <div class="col-md-1">
                    <a href="../" class="nav-link"><i class="fa fa-arrow-left"></i> Back</a>
                </div>
                <div class="col-md-5 p-2">
                    <h2 class="p-0 text-left"><?=$book->name;?></h2>
                </div>
                <?php if(hasRole(['owner','partner'])):?>
                    <div class="col p-3">
                        <button class="btn btn-sm btn-flat btn-outline-success btn-click" data-title='add category' data-section='category'><i class="fa fa-plus-circle"></i> Category</button>
                        <button class="btn btn-sm btn-flat btn-outline-success btn-click" data-title='add customer' data-section='customer'><i class="fa fa-plus-circle"></i> Customer</button>
                        <button class="btn btn-sm btn-flat btn-outline-success btn-click" data-title='add member' data-section='member'><i class="fa fa-plus-circle"></i> Members</button>
                        <button class="btn btn-sm btn-flat btn-outline-success btn-click" data-title='add item' data-section='item'><i class="fa fa-plus-circle"></i> Items</button>
                        <button class="btn btn-sm btn-flat btn-outline-success btn-click" data-title='add payment mode' data-section='paymode'><i class="fa fa-plus-circle"></i> Payment Modes</button>
                    </div>
                <?php endif;?>
            </div>
            <hr>

            <div class="row mx-1 mt-2">
                <div class="col p-2 side-displays">
                    <div class="p-2 border rounded-3 m-1">
                        <h3 class="border-bottom rounded-3 p-2">MEMBERS</h3>
                        <div class="p-2 text-center">
                            <?php
                                $sqlu = "SELECT DISTINCT user_id FROM cashbook_book_users WHERE book_id = ?";
                                $rspu = prepared_statements($sqlu,'i',[$book->id]);
                                echo $rspu->num_rows;
                            ?>
                        </div>
                    </div>
                    <div class="p-2 border rounded-3 m-1">
                        <h3 class="border-bottom rounded-3 p-2">TRANSACTIONS</h3>
                        <div class="p-2 text-center">
                            <?php
                                $sqlt = "SELECT count(id) as transactions FROM cashbook_transactions WHERE book_id = ?";
                                $rspt = prepared_statements($sqlt,'i',[$book->id]);
                                $rwt = $rspt->fetch_assoc();
                                echo $rwt['transactions'];
                            ?>
                        </div>
                    </div>
                    <div class="p-2 border rounded-3 m-1">
                        <h3 class="border-bottom rounded-3 p-2">ITEMS</h3>
                        <div class="p-2 text-center">
                            <?php
                                $sqli = "SELECT * FROM cashbook_items WHERE book_id = ?";
                                $rsi = prepared_statements($sqli,'i',[$book->id]);
                                echo $rsi->num_rows;
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-7 p-2 border rounded-3 main-display">
                    <div class="row mx-1">
                        <div class="col p-2 bg-success cash-in text-center hover text-white btn-click" data-title='add cashin' data-section='cashin'>
                            CASHIN
                        </div>
                        <div class="col p-2 bg-danger cash-out text-center hover text-white btn-click" data-title='add cashout' data-section='cashout'>
                            CASHOUT
                        </div>
                    </div>
                    <div class="row m-1">
                        <div class="col p-2 border m-1">
                            <?php
                                $stmt = "SELECT sum(amount) as cashin FROM cashbook_cashins WHERE book_id = ?";
                                $res = prepared_statements($stmt,'i',[$id]);
                                $rw = $res->fetch_assoc();
                            ?>
                            <h3 class="p-2 text-muted">CASHIN: <span class="right"><?=number_format($rw['cashin'],0);?></span></h3>
                        </div>
                        <div class="col p-2 border m-1">
                            <?php
                                $stmtt = "SELECT sum(amount) as cashout FROM cashbook_cashouts WHERE book_id = ?";
                                $ress = prepared_statements($stmtt,'i',[$id]);
                                $rws = $ress->fetch_assoc();
                            ?>
                            <h3 class="p-2 text-muted">CASHOUT: <span class="right"><?=number_format($rws['cashout'],0);?></span></h3>
                        </div>
                        <div class="col p-2 border m-1">
                            <?php
                                $stmtt = "SELECT sum(amount) as cashout FROM cashbook_cashouts WHERE book_id = ?";
                                $ress = prepared_statements($stmtt,'i',[$id]);
                                $rws = $ress->fetch_assoc();
                            ?>
                            <h3 class="p-2"><strong>BALANCE:</strong> <span class="right"><?=number_format(($rw['cashin']-$rws['cashout']),0);?></span></h3>
                        </div>
                    </div>
                    <hr>
                    <div class="row mx-1">
                        <div class="col p-2">
                            <h4 class="text-muted">TRANSACTIONS</h4>
                        </div>
                        <div class="col p-2 text-muted">
                            <i class="fa fa-filter"></i>  FILTER TRANSACTIONS:
                        </div>
                        <div class="col p-2">
                            <button class="btn btn-sm btn-outline-secondary right" id="resetFilters"> <i class="fa fa-undo"></i> <i class="fa fa-filter"></i> Reset</button>
                        </div>
                        <div class="p-2 input-group">
                            <?php
                                $sqld = "SELECT distinct DATE(created_at) as date FROM cashbook_transactions WHERE book_id =? order by DATE(created_at) desc";
                                $dats = prepared_statements($sqld,'i',[$book->id]);
                            ?>
                            <select name="filter-date" id="filter-date" data-type='date' class="form-control filter-item">
                                <option value=''><i class="fa fa-filter"></i> By Date</option>
                                <?php while($rd = $dats->fetch_assoc()):?>
                                    <option value="<?=$rd['date'];?>"><?=date('d-m-Y', strtotime($rd['date']));?></option>
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
                        </div>
                    </div>
                    <hr>
                    <div class="row mx-1">
                        <?php
                            $stmt_ = "SELECT ct.credit_amount as credits, ct.debit_amount as debits,cc.name as category,ct.id,ct.created_at,ct.details,cu.name as customer FROM cashbook_transactions ct 
                                    LEFT JOIN cashbook_categories cc ON cc.id = ct.category_id
                                    LEFT JOIn cashbook_customers cu ON cu.id = ct.customer_id
                                WHERE ct.book_id = ? ORDER BY created_at desc";
                            $res_ = prepared_statements($stmt_,'i',[$id]);
                            $t = 0;
                        ?>
                        <div class="col p-2 table-responsive transaction-table-wrapper">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Details</th>
                                        <!-- <th>Customer</th> -->
                                        <th>Credit</th>
                                        <th>Debit</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody class='transactions-tbody'>
                                <?php while($r = $res_->fetch_assoc()):?>
                                    <tr class='transaction-details hover hover-hide-content'>
                                        <td><?=++$t;?></td>
                                        <td><?=$r['created_at'];?></td>
                                        <td><?=$r['category'];?></td>
                                        <td><?=$r['details'];?></td>
                                        <td><?//=$r['customer'];?></td>
                                        <td><?=number_format($r['credits'],0);?></td>
                                        <td><?=number_format($r['debits'],0);?></td>
                                        <td>
                                            <?php if(hasRole(['owner','partner'])):?>
                                                <span class="hover-display text-sms">
                                                    <button class="btn btn-sm btn-outline-info edit-trans text-muted" data-id="<?=$r['id'];?>" data-type="<?=($r['credits'] > 0) ? 'credit':'debit';?>"><i class="fa fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger delete-trans" data-id="<?=$r['id'];?>" data-type="<?=($r['credits'] > 0) ? 'credit':'debit';?>"><i class="fa fa-trash"></i></button> 
                                                </span>
                                            <?php endif;?>
                                        </td>
                                    </tr>
                                <?php endwhile;?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col p-2 side-displays">
                    <div class="p-2 border rounded-3 m-1 text-center">
                        <h3 class="border-bottom rounded-3 p-2">CATEGORIES</h3>
                        <div class="p-2 text-center">
                            <?php
                                $sqlc = "SELECT * FROM cashbook_categories WHERE book_id = ?";
                                $rsc = prepared_statements($sqlc,'i',[$book->id]);
                                echo $rsc->num_rows;
                            ?>
                        </div>
                        <hr>
                        <a href="../category/?bsid=<?=encryptor('encrypt',$book->id);?>" class="nav-link">View</a>
                    </div>

                    <div class="p-2 border rounded-3 m-1 text-center">
                        <h3 class="border-bottom rounded-3 p-2">PAYMODES</h3>
                        <div class="p-2 text-center">
                            <?php
                                $sqlp = "SELECT * FROM cashbook_paymodes WHERE book_id = ?";
                                $rsp = prepared_statements($sqlp,'i',[$book->id]);
                                echo $rsp->num_rows;
                            ?>
                        </div>
                        <hr>
                        <a href="../modes/?bsid=<?=encryptor('encrypt',$book->id);?>" class="nav-link">View</a>
                    </div>
                    <div class="p-2 border rounded-3 m-1 text-center">
                        <h3 class="border-bottom rounded-3 p-2">CUSTOMERS</h3>
                        <div class="p-2 text-center">
                            <?php
                                $sqlcu = "SELECT * FROM cashbook_customers WHERE book_id = ?";
                                $rspcu = prepared_statements($sqlcu,'i',[$book->id]);
                                echo $rspcu->num_rows;
                            ?>
                        </div>
                        <hr>
                        <a href="../customers/?bsid=<?=encryptor('encrypt',$book->id);?>" class="nav-link">View</a>
                    </div>
                </div>
            </div>
            
            
            <!-- side modal for a cash in -->
            <div class="p-2 bg-white side-modal-tall absolute border shadow" id='side-modal-cashin'>
                <div class="side-modal-header">
                    <h3 class="side-modal-title text-dark"></h3>
                    <button type='button' class='side-modal-close'>&times;</button>
                </div>
                <div class="side-modal-content">
                    
                </div>
            </div>
    <?php
            pageFooter();
        }else{
            redirect('../');
        }
    ?>
    <script>
        function toggleMenu(){
            document.getElementById("navLinks").classList.toggle("active");
        }
        $(document).on('click','.cash-in',function(){
            $('#side-modal-cashin').show();
        });
        $(document).on('click','.cash-out',function(){
            $('#side-modal-cashout').show();
        });

        // click button to show the modal
        $(document).on('click','.btn-click',function(){
            var title = $(this).data('title');
            title = title.toUpperCase();
            $('.side-modal-tall').show();
            $('.side-modal-title').html(title);
            // display data in the side modal
            var category = $(this).data('section');
            fetchData(category);
        });

        function fetchData(sect)
        {
            var book_id = "<?=encryptor('decrypt',request('bkid'));?>";
            if(sect !='')
            {
                $.ajax({
                    url:'save/index.php',
                    data:{
                        section:sect,
                        book_id:book_id,
                        action:'fetchForm'
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
                    window.location.reload();
                })
                .catch(err => {
                    console.error("AJAX error:", err);
                });
            });
        }

        // Call the function for your form
        $(document).on('click','.saveCategory',function(){
            submitSingleForm("newCategoryForm", "save/index.php");
        });
        // Call the function for your form
        $(document).on('click','.saveUser',function(){
            submitSingleForm("newUserForm", "save/index.php");
        });

        // Call the function for your form
        $(document).on('click','.saveItem',function(){
            submitSingleForm("newItemForm", "save/index.php");
        });
        // Call the function for your form
        $(document).on('click','.saveCashin',function(){
            submitSingleForm("newCashinForm", "save/index.php");
        });

        // Call the function for your form
        $(document).on('click','.saveCashout',function(){
            submitSingleForm("newCashoutForm", "save/index.php");
        });

        // Call the function for your form
        $(document).on('click','.savePaymode',function(){
            submitSingleForm("newPaymodeForm", "save/index.php");
        });
        // Call the function for your form
        $(document).on('click','.saveCustomer',function(){
            submitSingleForm("newCustomerForm", "save/index.php");
        });

        // edit transaction
        $(document).on('click','.edit-trans',function(){
            var id = $(this).data('id');
            var type = $(this).data('type');
            if(id !='')
            {
                $('.side-modal-tall').show();
                $('.side-modal-title').html('EDIT TRANSACTION');
                $.ajax({
                    url:'save/index.php',
                    data:{
                        id:id,
                        action:'editTransaction',
                        type:type
                    },
                    beforeSend:function(){
                         $('.side-modal-content').html("<h3 class='text-center'>Loading...</h3>");
                    },
                    success:function(res){
                         $('.side-modal-content').html(res);
                    },
                    error:function(err){
                        $('.side-modal-content').html(err);
                    }
                });
            }
        });

        // delete transaction #delete-trans
        $(document).on('click','.delete-trans',function(){
            var id = $(this).data('id');
            var type = $(this).data('type');
            if(id !='')
            {
                xdialog.confirm("Continue to delete transaction?",function(){
                    $.ajax({
                    url:'save/index.php',
                    data:{
                        id:id,
                        action:'DeleteTransaction',
                        type:type
                    },
                    success:function(res){
                         xdialog.info('Transaction successful');
                        window.location.reload();
                    },
                    error:function(err){
                        xdialog.warn('Transaction failed');
                    }
                });
                });                
            }
        });
        
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
                url: 'save/index.php',
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
        // reset filter items
        $('#resetFilters').on('click', function () {
            $('.filter-item').val('');
            $('.filter-item').trigger('change');
        });
    </script>