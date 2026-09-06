<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Customers');
        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));
    ?>
            <div class="container-fluid">
                <div class="row mx-1">
                    <div class="col p-2 inline-block">
                        <a href="../books/?bkid=<?=$bsid;?>" class="nav-link">Books</a><i class="fa fa-angle-right"></i>
                        <a class="nav-link">Customers</a>
                    </div>
                    <div class="col p-2">
                        <a href="index_ai.php?bsid=<?=$bsid;?>" class="nav-link">AI PAGE</a>
                    </div>
                </div>
                <hr>
                <div class="row mx-1">
                    <div class="col p-2 border m-1 tale-responsive">
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h3 class="p-2"><?=strToUpper($book->name);?> - CUSTOMERS</h3>
                            </div>
                            <div class="col p-2">
                                <?php if(hasRole(['owner','partner','staff'])):?>
                                    <div class="col p-3">
                                        <button class="btn btn-sm btn-flat btn-outline-success btn-click right" data-title='add customer' data-section='customer'><i class="fa fa-plus-circle"></i> Customer</button>
                                    </div>
                                <?php endif;?>
                            </div>
                        </div>
                        <hr>
                        <div class="p-2">
                            <?php
                                $sql = "SELECT c.*,COUNT(DISTINCT cci.item_id) AS items, cr.name as route, crm.name as route_manager,COALESCE(l.balance,0) AS balance FROM cashbook_customers c
                                            LEFT JOIN (
                                                    SELECT cl.customer_id, cl.balance
                                                    FROM cashbook_customer_ledger cl
                                                    INNER JOIN (
                                                        SELECT customer_id, MAX(id) as max_id
                                                        FROM cashbook_customer_ledger
                                                        GROUP BY customer_id
                                                    ) latest
                                                    ON latest.max_id = cl.id
                                                ) l
                                            ON l.customer_id = c.id
                                            LEFT JOIN cashbook_routes cr ON cr.id = c.route_id
                                            LEFT JOIN cashbook_route_managers crm ON crm.id = c.route_manager_id
                                            LEFT JOIN cashbook_customer_items cci ON cci.customer_id = c.id
                                        WHERE c.book_id = ? GROUP BY c.id, cr.name, crm.name, l.balance
                                        ORDER BY l.balance DESC, c.name ASC";

                                $res = prepared_statements($sql,'i',[$book->id]);
                                $s =1;
                            ?>
                                <table class="table table-sm table-striped dataTable" id='dataTable'>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Route</th>
                                            <th>Manager</th>
                                            <th class='text-right'>Account Status</th>
                                            <th class='text-right'>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php  while($r = $res->fetch_assoc()):?>
                                            <tr class='hover hover-hide-content'>
                                                <td><?=$s++;?></td>
                                                <td><?=$r['name'];?></td>
                                                <td><?=$r['route'];?></td>
                                                <td><?=$r['route_manager'];?></td>
                                                <td class='text-right'><?=number_format($r['balance'],0);?></td>
                                                <td class='text-right'>
                                                    <?php if(hasRole(['owner','partner','staff'])):?>
                                                        <span class="hover-display text-sms">
                                                            <?php if(hasRole(['owner','partner'])):?>
                                                                <button class="btn btn-sm btn-outline-info edit-customer text-muted" data-id="<?=$r['id'];?>"  data-title="<?=$r['name'];?>"><i class="fa fa-edit"></i></button>
                                                                <button class="btn btn-sm btn-outline-danger delete-customer" data-id="<?=$r['id'];?>"><i class="fa fa-trash"></i></button>
                                                            <?php endif;?>
                                                            <button class="btn btn-sm btn-outline-info view-customer text-muted" data-id="<?=$r['id'];?>" data-title="<?=$r['name'];?>"><i class="fa fa-eye"></i></button> 
                                                            <button class="btn btn-sm btn-outline-secondary customer-attach-item" data-id="<?=$r['id'];?>"><i class="fa fa-plus"></i></button>
                                                        </span>
                                                    <?php endif;?>
                                                </td>
                                            </tr>
                                        <?php endwhile;?>
                                    </tbody>
                                </table>
                        </div>
                    </div>
                    <div class="col-md-4 p-2 border m-1 table-responsive">
                        <h4 class="p-2 border-bottom">Customer Balances</h4>
                        <div class="p-2">
                            <?php
                                $customersSql = "SELECT ccb.customer_id, cc.name AS customer_name, ccb.balance FROM cashbook_customer_balances ccb
                                        INNER JOIN cashbook_customers cc ON cc.id = ccb.customer_id
                                        WHERE ccb.book_id = ? AND ccb.balance > 0
                                        ORDER BY ccb.balance DESC";
                                $customersResult = prepared_statements($customersSql, 'i', [$book->id]);
                            ?>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer</th>
                                        <th class='text-right'>Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $count = 1; $bal = 0; ?>
                                    <?php while ($customer = $customersResult->fetch_assoc()): $bal += $customer['balance']; ?>
                                        <tr>
                                            <td><?= $count++; ?></td>
                                            <td><?= $customer['customer_name']; ?></td>
                                            <td class='text-right'><?=number_format($customer['balance'], 0); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class='text-right'>Total Balance:</th>
                                        <th class='text-right'><?= number_format($bal, 0); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- side modal for a cash in -->
            <div class="p-0 bg-white side-modal-tall absolute border shadow" id='side-modal-customer'>
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
                    fetchData(category);
                });

                function fetchData(sect)
                {
                    var book_id = "<?=encryptor('decrypt',request('bsid'));?>";
                    if(sect !='')
                    {
                        $.ajax({
                            url:'../books/save/index.php',
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

                // Call the function for your form
                $(document).on('click','.saveCustomer',function(){
                    submitSingleForm("newCustomerForm", "../books/save/index.php");
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

                // view customer details
                $(document).on('click','.view-customer',function(){
                    $('#central-modal').show();
                    var title = $(this).data('title')+" Transactions";
                    $('.central-modal-title').html(title);
                    var id = $(this).data('id');

                    $.ajax({
                        url:'save/index.php',
                        data:{
                            customer_id:id,
                            action:'Customer-details'
                        },
                        beforeSend:function(){
                            $('.central-modal-content').html("<center><h3>Loading...</h3></center>");
                        },
                        success:function(res){
                            $('.central-modal-content').html(res);
                        },
                        error:function(err){
                            $('.central-modal-content').html("<center><h3>!!! Error Loading data</h3></center>");
                        }
                    });
                });

                // view customer details
                $(document).on('click','.edit-customer',function(){
                    $('#side-modal-customer').show();
                    var title = $(this).data('title')+" Edit";
                    $('.side-modal-title').html(title);
                    var id = $(this).data('id');

                    $.ajax({
                        url:'save/index.php',
                        data:{
                            customer_id:id,
                            action:'Customer-edit'
                        },
                        beforeSend:function(){
                            $('.side-modal-content').html("<center><h3>Loading...</h3></center>");
                        },
                        success:function(res){
                            $('.side-modal-content').html(res);
                        },
                        error:function(err){
                            $('.side-modal-content').html("<center><h3>!!! Error Loading data</h3></center>");
                        }
                    });
                });

                // delete customer
                $(document).on('click','.delete-customer',function(){
                    var id = $(this).data('id');
                    xdialog.confirm('Confirm to delete customer?',function(){
                        $.ajax({
                            url:'save/index.php',
                            data:{
                                action:'deleteCustomer',
                                id:id
                            },
                            beforeSend:function(){
                                xdialog.startSpin();
                            },
                            success:function(res){
                                xdialog.stopSpin();
                                xdialog.info("Customer deleted successfully");
                                window.location.reload();
                            },
                            error:function(err){
                                xdialog.info("Error removing customer");
                            }
                        });
                    });
                });

                // attach items to the customer
                $(document).on('click','.customer-attach-item',function(){
                    var id = $(this).data('id');
                    var book_id = '<?=$book->id;?>';
                    $('.side-modal-title').html("ATTACH ITEMS TO CUSTOMER");
                    $('#side-modal-customer').show();
                    $.ajax({
                        url:'save/index.php',
                        data:{
                            action:'findItems',
                            id:id,
                            book_id: book_id
                        },
                        success:function(res){
                            $('.side-modal-content').html(res);
                        },
                        error:function(err){
                            xdialog.info("Error loading items");
                        }
                    });
                });

            </script>
        <?php
    }else{
        redirect('../');
    }

?>