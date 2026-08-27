<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Routes');
        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));
    ?>
            <div class="container">
                <div class="row mx-1">
                    <div class="col p-2 inline-block">
                        <a href="../books/?bkid=<?=$bsid;?>" class="nav-link">Books</a><i class="fa fa-angle-right"></i>
                        <a class="nav-link">Routes</a>
                    </div>
                </div>
                <hr>
                <div class="row mx-1">
                    <div class="col p-2">
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h3 class="p-2"><?=strToUpper($book->name);?> - ROUTES</h3>
                            </div>
                            <div class="col p-2">
                                <?php if(hasRole(['owner','partner'])):?>
                                    <div class="col p-3">
                                        <button class="btn btn-sm btn-flat btn-outline-success btn-click right" data-title='add_route' data-section='route'><i class="fa fa-plus-circle"></i> Create Route</button>
                                    </div>
                                <?php endif;?>
                            </div>
                        </div>
                        <hr>
                        <div class="p-2">
                            <?php
                                $sql = "SELECT * FROM cashbook_routes WHERE book_id =?";
                                $res =prepared_statements($sql,'i',[$book->id]);
                                $s =1;

                                // count route customers
                                $qry = "SELECT COUNT(id) as customers FROM cashbook_customers WHERE route_id = ?";
                            ?>
                                <table class="table table-sm table-striped dataTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th class=''>Details</th>
                                            <th>Managers</th>
                                            <th>Customers</th>
                                            <th class='text-right'>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php  while($r = $res->fetch_assoc()):
                                                    $rid = $r['id'];
                                                    $rr = prepared_statements($qry,'i',[$rid]);
                                                    $rc = $rr->fetch_assoc();
                                        ?>
                                            <tr class='hover hover-hide-content'>
                                                <td><?=$s++;?></td>
                                                <td><?=$r['name'];?></td>
                                                <td class=''><?=$r['details'];?></td>
                                                <td><?=$r['managers'];?></td>
                                                <td><?=$rc['customers'];?></td>
                                                <td class='text-right'>
                                                    <?php if(hasRole(['owner','partner'])):?>
                                                        <span class="hover-display text-sms">
                                                            <?php if(hasRole(['owner'])):?>
                                                                <button class="btn btn-sm btn-outline-info edit-route text-muted" data-id="<?=$r['id'];?>"><i class="fa fa-edit"></i></button>
                                                                <button class="btn btn-sm btn-outline-danger delete-route" data-id="<?=$r['id'];?>"><i class="fa fa-trash"></i></button>
                                                            <?php endif;?>
                                                            <button class="btn btn-sm btn-outline-info view-route text-muted" data-id="<?=$r['id'];?>" data-title="<?=$r['name'];?>"><i class="fa fa-eye"></i></button> 
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
            <div class="p-2 bg-white side-modal-tall absolute border shadow" id='side-modal-cashin'>
                <div class="side-modal-header">
                    <h3 class="side-modal-title text-dark"></h3>
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

                // view customer details
                $(document).on('click','.view-route',function(){
                    $('#central-modal').show();
                    var title = $(this).data('title')+" Transactions";
                    $('.central-modal-title').html(title);
                    var id = $(this).data('id');

                    $.ajax({
                        url:'save/index.php',
                        data:{
                            customer_id:id,
                            action:'route-details'
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
            </script>
        <?php
    }else{
        redirect('../');
    }

?>