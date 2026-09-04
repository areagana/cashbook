    <?php
        require_once('../assets/functions.php');
        if(isVerified())
        {
            pageHeader('Book-details');
            $id = request('bkid');
            $id = encryptor('decrypt',$id);
            $book = bookFind($id);
            setBook($book);
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
                <div class="col-md-3 p-2">
                    <h2 class="p-0 text-left"><?=$book->name;?></h2>
                </div>
                <div class="col-md-3 p-3">
                    <a href='../stock/?bkid=<?=request('bkid');?>' class="btn btn-sm btn-flat btn-primary"><i class="fa fa-stock"></i> Book Stock</a>
                </div>
                <?php if(hasRole(['owner','partner'])):?>
                        <div class="col p-3">
                            <button class="btn btn-sm btn-flat btn-outline-success btn-click" data-title='add category' data-section='category'><i class="fa fa-plus-circle"></i> Category</button>
                            <button class="btn btn-sm btn-flat btn-outline-primary btn-click" data-title='add customer' data-section='customer'><i class="fa fa-plus-circle"></i> Customer</button>
                            <button class="btn btn-sm btn-flat btn-outline-info btn-click" data-title='add member' data-section='member'><i class="fa fa-plus-circle"></i> Members</button>
                            <button class="btn btn-sm btn-flat btn-outline-secondary btn-click" data-title='add item' data-section='item'><i class="fa fa-plus-circle"></i> Items</button>
                            <button class="btn btn-sm btn-flat btn-outline-success btn-click" data-title='add payment mode' data-section='paymode'><i class="fa fa-plus-circle"></i> Payment Modes</button>
                        </div>
                <?php endif;?>
            </div>
            <hr>
            <div class="row mx-1 mt-2">
                <div class="col p-2 border rounded-3 main-display">
                    <div class="row mx-1">
                        <div class="col p-2 text-right">
                            <a href="../report/?bkid=<?=encryptor('encrypt',$book->id);?>" class="btn btn-flat btn-outline-secondary right mx-2 hover">REPORT</a>
                            <button class="btn btn-flat btn-danger right mx-2 cash-out btn-click hover" data-title='add cashout' data-section='cashout'>CASH Out</button>
                            <button class="btn btn-flat btn-primary right mx-2 cash-in btn-click hover" data-title='add cashin' data-section='cashin'>CASH IN</button>
                        </div>
                    </div>
                    <div class="row m-1">
                        <div class="col p-2 border m-1 book-dash border-dark">
                            <?php
                                $stmti = "SELECT SUM(amount) as cashin 
                                            FROM cashbook_cashins 
                                            WHERE book_id = ? 
                                            AND created_at < DATE_FORMAT(NOW(), '%Y-%m-%d')";
                                $resi = prepared_statements($stmti,'i',[$id]);
                                $rwi = $resi->fetch_assoc();
                                $cashinbbf = $rwi['cashin'];

                                // cashout
                                $stmto = "SELECT SUM(amount) as cashout 
                                            FROM cashbook_cashouts 
                                            WHERE book_id = ? 
                                            AND DATE(created_at) < DATE_FORMAT(NOW(), '%Y-%m-%d')";
                                $reso = prepared_statements($stmto,'i',[$id]);
                                $rwo = $reso->fetch_assoc();
                                $cashoutbbf = $rwo['cashout'];

                                // get bbf
                                $bbf = $cashinbbf - $cashoutbbf;

                            ?>
                            <div class="p-2 text-dark">BBF: <span class="right"><strong><?=number_format($bbf,0);?></strong></span></div>
                        </div>
                        <div class="col p-2 border m-1 book-dash border-primary">
                            <?php
                                $stmt = "SELECT SUM(amount) as cashin 
                                            FROM cashbook_cashins 
                                            WHERE book_id = ? 
                                            AND DATE(created_at) = DATE_FORMAT(NOW(), '%Y-%m-%d')";
                                $res = prepared_statements($stmt,'i',[$id]);
                                $rw = $res->fetch_assoc();
                                $cashin = $rw['cashin'];
                            ?>
                            <div class="p-2 text-primary">CASHIN: <span class="right"><?=number_format($cashin,0);?></span></div>
                        </div>
                        <div class="col p-2 border m-1 book-dash border-danger">
                            <?php
                                $stmtt = "SELECT SUM(amount) as cashout 
                                            FROM cashbook_cashouts 
                                            WHERE book_id = ? 
                                            AND DATE(created_at) = DATE_FORMAT(NOW(), '%Y-%m-%d')";
                                $ress = prepared_statements($stmtt,'i',[$id]);
                                $rws = $ress->fetch_assoc();
                                $cashout = $rws['cashout'];
                            ?>
                            <div class="p-2 text-danger">CASHOUT: <span class="right"><?=number_format($cashout,0);?></span></div>
                        </div>
                        <div class="col p-2 border m-1 book-dash border-dark">
                            <div class="p-2"><strong>BALANCE:</strong> <span class="right"><strong><?= number_format(($bbf + $cashin-$cashout),0);?></span></strong></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row mx-1">
                        <div class="col p-2">
                            <div class="text-muted">TRANSACTIONS</div>
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
                    <hr>
                    <div class="row mx-1">
                        <?php
                            $stmt_ = "SELECT ct.credit_amount as credits, ct.debit_amount as debits,cc.name as category,ct.id,ct.created_at,ct.details,cu.name as customer FROM cashbook_transactions ct 
                                    LEFT JOIN cashbook_categories cc ON cc.id = ct.category_id
                                    LEFT JOIn cashbook_customers cu ON cu.id = ct.customer_id
                                WHERE ct.book_id = ? AND DATE(ct.created_at) = DATE(now()) ORDER BY ct.id desc";
                            $res_ = prepared_statements($stmt_,'i',[$id]);
                            $t = 0;
                        ?>
                        <div class="col p-2 table-responsive transaction-table-wrapper">
                            <h4 class='text-center'>RECENT TRANSACTIONS (TODAY)</h4>
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
                                <?php while($r = $res_->fetch_assoc()):?>
                                    <tr class='transaction-details hover hover-hide-content '>
                                        <td><?=++$t;?></td>
                                        <td><?=$r['created_at'];?></td>
                                        <td><?=$r['category'];?></td>
                                        <td><?=$r['customer'];?></td>
                                        <td><?=$r['details'];?></td>
                                        <td class ="<?=$r['credits'] > 0 ? " text-primary" : "";?>"><?=number_format($r['credits'],0);?></td>
                                        <td class ="<?=$r['debits'] > 0 ? " text-danger" : "";?>"><?=($r['credits'] == 0 || empty($r['credits'])) ? number_format($r['debits'],0) : "";?></td>
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
            
            // 🔴 VALIDATION CHECK
            if(!form.checkValidity()) 
            {
                form.reportValidity(); // shows browser messages
                xdialog.stopSpin();
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

        // lock customer selection
        document.addEventListener("change", function(e){
            if(e.target && e.target.id == "transaction_type")
            {

                let type = e.target.value;
                let paymode = document.getElementById("paymode_id");
                let customer = document.getElementById("customer_id");

                if(!paymode || !customer) return;

                paymode.disabled = false;
                customer.required = false;

                if(type == 'credit_sale'){
                    paymode.value = "";
                    paymode.disabled = true;
                    customer.setAttribute("required", true);
                }

                if(type == 'payment'){
                    customer.setAttribute("required", true);
                    paymode.setAttribute("required", true);
                }
            }
        });

        // load customer invoices on data entry for cashin
        $(document).on('change','#customer_id',function(){
            var customer_id = $(this).val();
            if(customer_id !='')
            {
                $.ajax({
                    url: 'save/index.php',
                    data: {
                        customer_id:customer_id,
                        action:'findCustomerInvoices'
                    },
                    beforeSend:function(){
                        $('#invoice_id').html("<option>Loading...</option>");
                    },
                    success: function (res) {
                        $('#invoice_id').html(res);
                    },
                    error: function (err) {
                        $('#invoice_id').html("<option>!!!Error Loading!!!</option>");
                    }
                })
            }
        });

        // control displkay of other form items
        $(document).on('blur','#inamount',function(){
            var amount = $(this).val();
            if(amount == '')
            {
                $('.inAmount-controlled').hide();
            }else{
                $('.inAmount-controlled').show();
            }
            
        });

        // lock invoice_id section if type is cash_sale
        $(document).on('change', '#transaction_type', function()
        {
            const val = $(this).val();

            // Transaction types that do not require an invoice
            const noInvoiceTypes = [
                'cash_sale',
                'credit_sale',
                'other_income'
            ];

            const invoiceField = $('#invoice_id');

            if(noInvoiceTypes.includes(val))
            {
                invoiceField
                    .prop('disabled', true)
                    .val('')
                    .trigger('change');
            }
            else
            {
                invoiceField.prop('disabled', false);
            }
        });
    </script>