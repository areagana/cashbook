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
            <div class="row mx-1">
                <div class="col-md-3 p-2">
                    <h2 class="p-0 text-left"><?=$book->name;?></h2>
                </div>
                <div class="col-md-3 p-3">
                    <a href='../dashboard/?bkid=<?=request('bkid');?>' class="btn btn-sm btn-flat btn-primary"><i class="fa fa-stock"></i> DASHBOARD</a>
                    <a href='../report/?bkid=<?=request('bkid');?>' class="btn btn-sm btn-flat btn-secondary"><i class="fa fa-stock"></i> REPORT</a>
                </div>
                <?php if(hasRole(['owner','partner','superadministrator'])):?>
                        <div class="col p-3">
                            <a href='../stock/?bkid=<?=request('bkid');?>' class="btn btn-sm btn-flat btn-primary"><i class="fa fa-stock"></i> Book Stock</a>
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
                    <!-- TRANSACTION REPORT TOOLBAR -->
                    <div class="transaction-report-toolbar">
                        <div>
                            <h5 class="mb-0"><i class="fa fa-list-alt mr-1"></i> TRANSACTIONS</h5>
                            <small class="text-muted">Recent activity and filtered transaction report</small>
                        </div>
                        <div class="transaction-report-actions">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="resetFilters" title="Reset Filters">
                                <i class="fa fa-undo"></i><span class="d-none d-md-inline"> Reset</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" id="openTransactionFilters">
                                <i class="fa fa-filter"></i> FILTER
                                <span class="filter-active-count" style="display:none">0</span>
                            </button>
                        </div>
                    </div>

                    <!-- FILTER DRAWER OVERLAY -->
                    <div class="transaction-filter-overlay" id="transactionFilterOverlay"></div>

                    <!-- FILTER DRAWER -->
                    <aside class="transaction-filter-drawer" id="transactionFilterDrawer" aria-hidden="true">
                        <div class="filter-drawer-header">
                            <div>
                                <h5><i class="fa fa-filter mr-1"></i> Filter Transactions</h5>
                                <small>Choose one or more filters</small>
                            </div>
                            <button type="button" class="filter-drawer-close" id="closeTransactionFilters" aria-label="Close filters">&times;</button>
                        </div>

                        <div class="filter-drawer-body">
                            <div class="filter-group">
                                <label for="filter-date-from">From Date</label>
                                <input type="date" name="filter-date-from" id="filter-date-from"
                                    data-type="min_date" max="<?=date('Y-m-d');?>" class="form-control filter-item">
                            </div>

                            <div class="filter-group">
                                <label for="filter-date-to">To Date</label>
                                <input type="date" name="filter-date-to" id="filter-date-to"
                                    data-type="max_date" max="<?=date('Y-m-d');?>" class="form-control filter-item">
                            </div>

                            <?php
                                $sqlm="SELECT DISTINCT MONTH(created_at) month,YEAR(created_at) year
                                       FROM cashbook_transactions WHERE book_id=? ORDER BY YEAR(created_at) DESC,MONTH(created_at) DESC";
                                $months=prepared_statements($sqlm,'i',[$book->id]);
                                $month_names=[1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                                    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                            ?>
                            <div class="filter-group">
                                <label for="filter-month">Month</label>
                                <select name="filter-month" id="filter-month" data-type="month" class="form-control filter-item">
                                    <option value="">All Months</option>
                                    <?php while($rm=$months->fetch_assoc()):?>
                                        <option value="<?=$rm['month'];?>"><?=$month_names[$rm['month']];?> <?=$rm['year'];?></option>
                                    <?php endwhile;?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="filter-type">Transaction Type</label>
                                <select name="filter-type" id="filter-type" data-type="type" class="form-control filter-item">
                                    <option value="">All Types</option>
                                    <option value="credit">Cash In</option>
                                    <option value="debit">Cash Out</option>
                                </select>
                            </div>

                            <?php
                                $cats=prepared_statements("SELECT * FROM cashbook_categories WHERE book_id=? ORDER BY name ASC",'i',[$book->id]);
                            ?>
                            <div class="filter-group">
                                <label for="filter-category">Category</label>
                                <select name="filter-category" id="filter-category" data-type="category" class="form-control filter-item">
                                    <option value="">All Categories</option>
                                    <?php while($rc=$cats->fetch_assoc()):?>
                                        <option value="<?=$rc['id'];?>"><?=htmlspecialchars($rc['name']);?></option>
                                    <?php endwhile;?>
                                </select>
                            </div>

                            <?php
                                $cust=prepared_statements("SELECT * FROM cashbook_customers WHERE book_id=? ORDER BY name ASC",'i',[$book->id]);
                            ?>
                            <div class="filter-group">
                                <label for="filter-customer">Customer</label>
                                <select name="filter-customer" id="filter-customer" data-type="customer" class="form-control filter-item">
                                    <option value="">All Customers</option>
                                    <?php while($rcu=$cust->fetch_assoc()):?>
                                        <option value="<?=$rcu['id'];?>"><?=htmlspecialchars($rcu['name']);?></option>
                                    <?php endwhile;?>
                                </select>
                            </div>
                        </div>

                        <div class="filter-drawer-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearDrawerFilters"><i class="fa fa-times"></i> Clear</button>
                            <button type="button" class="btn btn-sm btn-primary" id="applyTransactionFilters"><i class="fa fa-check"></i> Apply Filters</button>
                        </div>
                    </aside>
                    <div class="row mx-1">
                        <?php
                            $stmt_ = "SELECT ct.credit_amount as credits, ct.debit_amount as debits,cc.name as category,ct.id,ct.created_at,ct.details,cu.name as customer FROM cashbook_transactions ct 
                                    LEFT JOIN cashbook_categories cc ON cc.id = ct.category_id
                                    LEFT JOIn cashbook_customers cu ON cu.id = ct.customer_id
                                WHERE ct.book_id = ? ORDER BY ct.id DESC LIMIT 20";
                            $res_ = prepared_statements($stmt_,'i',[$id]);
                            $t = 0;
                            $cashin = 0;
                            $cashout = 0;
                        ?>
                        <div class="col p-2">
                            <h4 class="text-center">RECENT TRANSACTIONS (LAST 20)</h4>
                            <div class="mobile-table-hint"><i class="fa fa-arrows-h mr-1"></i> Swipe left/right to view Credit, Debit and Actions</div>
                            <div class="transaction-table-wrapper">
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
                                <?php while($r = $res_->fetch_assoc()):
                                    
                                    $cashin += $r['credits'];
                                    $debit = ($r['credits'] == 0 || empty($r['credits'])) ? $r['debits'] : 0;
                                    $cashout += $debit;
                                    ?>
                                    <tr class='transaction-details hover hover-hide-content '>
                                        <td><?=++$t;?></td>
                                        <td><?=date_format(date_create($r['created_at']), 'Y-m-d');?></td>
                                        <td><?=$r['category'];?></td>
                                        <td><?=$r['customer'];?></td>
                                        <td><?=$r['details'];?></td>
                                        <td class ="<?=$r['credits'] > 0 ? " text-primary" : "";?>"><?=number_format($r['credits'],0);?></td>
                                        <td class ="<?=$r['debits'] > 0 ? " text-danger" : "";?>"><?=number_format($debit,0);?></td>
                                        <td>
                                            <?php if(hasRole(['owner','partner','superadministrator'])):?>
                                                <span class="hover-display text-sms">
                                                    <button class="btn btn-sm btn-outline-info edit-trans text-muted" data-id="<?=$r['id'];?>" data-type="<?=($r['credits'] > 0) ? 'credit':'debit';?>"><i class="fa fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger delete-trans" data-id="<?=$r['id'];?>" data-type="<?=($r['credits'] > 0) ? 'credit':'debit';?>"><i class="fa fa-trash"></i></button> 
                                                </span>
                                            <?php endif;?>
                                        </td>
                                    </tr>
                                <?php endwhile;?>
                                <tr>
                                    <th>TOTALS</th>
                                    <th colspan="4" class="text-right"></th>
                                    <th class="text-right"><?=number_format($cashin,0);?></th>
                                    <th class="text-right"><?=number_format($cashout,0);?></th>
                                    <th></th>
                                </tr>
                                </tbody>
                            </table>
                            </div>
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
        
        // transaction filter drawer
        function openTransactionFilters(){
            $('#transactionFilterDrawer,#transactionFilterOverlay').addClass('open');
            $('#transactionFilterDrawer').attr('aria-hidden','false');
            $('body').addClass('filter-drawer-open');
        }
        function closeTransactionFilters(){
            $('#transactionFilterDrawer,#transactionFilterOverlay').removeClass('open');
            $('#transactionFilterDrawer').attr('aria-hidden','true');
            $('body').removeClass('filter-drawer-open');
        }
        function updateFilterCount(){
            let count=0;
            $('.filter-item').each(function(){if($(this).val()) count++;});
            $('.filter-active-count').text(count).toggle(count>0);
        }
        function loadFilteredTransactions(){
            let filters={action:'transactionFilter',book_id:"<?=$book->id;?>"};
            $('.filter-item').each(function(){let val=$(this).val();if(val) filters[$(this).data('type')]=val;});
            $.ajax({
                url:'save/index.php',type:'POST',data:filters,
                beforeSend:function(){
                    $('.transactions-tbody').html("<tr><td colspan='8' class='text-center p-4'><i class='fa fa-spinner fa-spin'></i> Loading transactions...</td></tr>");
                },
                success:function(res){$('.transactions-tbody').html(res);updateFilterCount();},
                error:function(){
                    $('.transactions-tbody').html("<tr><td colspan='8' class='text-center text-danger p-4'><i class='fa fa-exclamation-triangle'></i> Failed to load transactions</td></tr>");
                }
            });
        }

        $(document).on('click','#openTransactionFilters',openTransactionFilters);
        $(document).on('click','#closeTransactionFilters,#transactionFilterOverlay',closeTransactionFilters);
        $(document).on('click','#applyTransactionFilters',function(){loadFilteredTransactions();closeTransactionFilters();});
        $(document).on('click','#clearDrawerFilters,#resetFilters',function(){
            $('.filter-item').val('');updateFilterCount();loadFilteredTransactions();
            if(this.id==='clearDrawerFilters') closeTransactionFilters();
        });
        $(document).on('keydown',function(e){if(e.key==='Escape') closeTransactionFilters();});

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

                if(type == 'creditorInjection')
                {
                    $('.income-creditor').show();
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

        // lock and display some points of the form
        $(document).on('change','#expense_type',function(){
            var type = $(this).val();
            if(type !='')
            {
                if(type == 'purchase')
                {
                    $('.expense-customer').hide();
                    $('.purchase-expense').show();
                }else if(type == 'expense'){
                    $('.expense-customer').hide();
                    $('.purchase-expense').hide();
                }else if(type == 'creditorPayment'){
                    $('.expense-customer').hide();
                    $('.purchase-expense').hide();
                    $('.expense-creditor').show();
                }else{
                    $('.expense-customer').show();
                    $('.purchase-expense').hide();
                }
            }
        });
    </script>