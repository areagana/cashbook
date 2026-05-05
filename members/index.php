<?php
    require_once(__dir__.'/../assets/functions.php');
    if(hasRole(['owner','partner']))
    {
        pageHeader('Members');
        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));
        ?>
            <div class="container">
                <div class="row mx-1">
                    <div class="col p-2 inline-block">
                        <a href="../books/?bkid=<?=$bsid;?>" class="nav-link">Books</a><i class="fa fa-angle-right"></i>
                        <a class="nav-link">Members</a>
                    </div>
                </div>
                <hr>
                <div class="row mx-1">
                    <div class="col p-2">
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h3 class="p-2">BOOK MEMBERS</h3>
                            </div>
                            <div class="col p-2">
                                <?php if(hasRole(['owner','partner'])):?>
                                    <div class="col p-3">
                                        <button class="btn btn-sm btn-flat btn-outline-success btn-click right" data-title='add membet' data-section='member'><i class="fa fa-plus-circle"></i> Member</button>
                                    </div>
                                <?php endif;?>
                            </div>
                        </div>
                        <hr>
                        <div class="p-2">
                            <?php
                                $sql = "SELECT cu.*, r.name as role FROM cashbook_users cu
                                        LEFT JOIN cashbook_roles r ON cu.role_id = r.id
                                        WHERE business_id =?";
                                $res =prepared_statements($sql,'i',[$book->business_id]);
                                $t =0;
                            ?>
                                <table class="table table-sm table-striped dataTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Contact</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php  while($r = $res->fetch_assoc()):?>
                                            <tr class='hover hover-hide-content'>
                                                <td><?=++$t;?></td>
                                                <td><?=$r['name'];?></td>
                                                <td><?=$r['email'];?></td>
                                                <td><?=$r['role'];?></td>
                                                <td><?=$r['contact'];?></td>
                                                <td>
                                                    <?php if(hasRole(['owner','partner'])):?>
                                                        <span class="hover-display text-sms">
                                                            <button class="btn btn-sm btn-outline-info btn-click text-muted" data-id="<?=$r['id'];?>" data-section="member-edit" data-title="EDIT <?=$r['name'];?>"><i class="fa fa-edit"></i></button>
                                                            <button class="btn btn-sm btn-outline-danger delete-user" data-id="<?=$r['id'];?>"><i class="fa fa-trash"></i></button> 
                                                        </span>
                                                    <?php endif;?>
                                                </td>
                                            </tr>
                                        <?php endwhile;?> <!-- member_id -->
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
                    var id = $(this).attr('data-id') ? $(this).data('id') : null;
                    fetchData(category,id);
                });
                
                function fetchData(sect,id = null)
                {
                    var book_id = "<?=encryptor('decrypt',request('bsid'));?>";                    
                    if(sect !='')
                    {
                        $.ajax({
                            url:'../books/save/index.php',
                            data:{
                                section:sect,
                                book_id:book_id,
                                id:id,
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
                $(document).on('click','.saveEditUser',function(){
                    submitSingleForm("MemberEditForm", "../books/save/index.php");
                });

                

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
                            window.location.reload();
                        })
                        .catch(err => {
                            console.error("AJAX error:", err);
                        });
                    });
                }
            </script>
        <?php
    }else{
        redirect('../');
    }

?>