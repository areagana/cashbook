<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Home');
        $business = (businessFind(auth())) ?? businessFindId(auth()->business_id) ;
        ?>
                    <div class="container">
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h1 class="p-1"><?=$business->name;?></h1>
                            </div>
                            <?php if(hasRole(['owner'])):?>
                                <div class="col p-4">
                                    <button class="btn btn-outline-primary new-profile" data-id="<?=$business->id;?>"><i class="fa fa-user"></i>Business Profile</button>
                                    <button class="btn btn-outline-secondary new-settings"></i> <i class="fa fa-cog"></i> Settings</button>
                                    <button class="btn btn-outline-info new-team"><i class="fa fa-plus-circle"></i> Add Team</button>
                                </div>
                            <?php endif;?>
                        </div>
                        <hr>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h2 class="p-2">BUSINESS BOOKS</h2>
                            </div>
                            <?php if(hasRole(['owner','partner'])):?>
                                <div class="col-md-3 p-4">
                                    <button class="btn btn-outline-info new-book right"><i class="fa fa-plus-circle"></i> Add Book</button>
                                </div>
                            <?php endif;?>
                        </div>
                        <hr>
                        <div class="row mx-1">
                            <div class="col p-2">
                                <?php
                                    $stmt = "SELECT * FROM cashbook_books cb WHERE business_id =?  ORDER BY id desc";
                                    $stmt = prepared_statements($stmt,'i',[$business->id]);

                                    //fetch total transactions for the book
                                    $query ="SELECT sum(debit_amount) as debits, sum(credit_amount) as credits FROM cashbook_transactions WHERE book_id = ?";
                                    while($rw = $stmt->fetch_assoc()):
                                            $bk= prepared_statements($query,'i',[$rw['id']]);
                                            $r = $bk->fetch_assoc();
                                            $credits = $r['credits']; $debits = $r['debits'];
                                            $bal = number_format(($credits - $debits),0);
                                        ?>  
                                        <div class="row mx-1 border h3 rounded-3">
                                            <div class="col p-4">
                                                <?=$rw['name'];?>
                                            </div>
                                            <div class="col-md-3 p-4 text-muted text-right text-sm border-left">
                                                Bal: <?=$bal;?>/=
                                            </div>
                                            <div class="col-md-3 p-4 border-left inline-block">
                                                <a href='../dashboard/?bkid=<?=encryptor('encrypt',$rw['id']);?>' class="btn btn-sm btn-outline-primary btn-flat right"> Dashboard</a>
                                                <a href='../books/?bkid=<?=encryptor('encrypt',$rw['id']);?>' class="btn btn-sm btn-outline-success btn-flat right">Details</a>
                                            </div>
                                        </div>
                                        <?php
                                    endwhile;
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- side modal for a cash in -->
                    <div class="p-2 bg-white side-modal-tall absolute border shadow" id='side-modal-newbook'>
                        <div class="side-modal-header">
                            <h3 class="side-modal-title text-dark">CREATE NEW BUSINESS BOOK</h3>
                            <button type='button' class='side-modal-close'>&times;</button>
                        </div>
                        <div class="side-modal-content">
                            <form action="../books/save/index.php" method="post">
                                <input type="hidden" name="business_id" value="<?=$business->id;?>">
                                <div class="form-row">
                                    <div class="col-md-3 p-2">
                                        <label for="book_title">BOOK TITLE</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="book_title" id="book_title" class="form-control">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-3 p-2">
                                        <label for="book_details">BOOK DETAILS</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="book_details" id="book_details" class="form-control">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col p-2">
                                        <button type="submit" name='saveNewBook' class="btn btn-flat btn-primary right">SUBMIT</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- business profile -->
                    <div class="p-2 bg-white side-modal-tall absolute border shadow" id='side-modal-new-profile'>
                        <div class="side-modal-header">
                            <h3 class="side-modal-title text-dark">BUSINESS PROFILE</h3>
                            <button type='button' class='side-modal-close'>&times;</button>
                        </div>
                        <div class="side-modal-content">
                            <form id='newBusinessForm' method="post" enctype='multipart/form-data'>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-3">
                                        <label for="profile">
                                            <img src="../images/profile.jpg" alt="" id='profilePreview' srcset="" width='90%' class='border rounded-3' height="100%">
                                        </label>
                                        <input type="file" name="profile" id="profile" opacity='0%' class='hidden'>
                                        <input type="hidden" name="action" value="businessProfile">
                                        <input type="hidden" name="business_id" value="<?=$business->id;?>">
                                    </div>
                                    <div class="col p-2">
                                        <div class="form-row">
                                            <div class="col-md-4 p-2">
                                                <label for="name">BUSINESS NAME:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="name" id="name" value='<?=$business->name;?>' class="form-control">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-md-4 p-2">
                                                <label for="email">EMAIL:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="email" id="email" value='<?=$business->email;?>' class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="col-md-3 p-2">
                                        <label for="address">ADDRESS:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="address" id="address" value='<?=$business->address;?>' class="form-control">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-3 p-2">
                                        <label for="contact1">CONTACT1:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="contact1" id="contact1" value='<?=$business->contact1;?>' class="form-control">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-3 p-2">
                                        <label for="contact2">CONTACT2:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="contact2" id="contact2" value='<?=$business->contact2;?>' class="form-control">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-3 p-2">
                                        <label for="reg_no">REGN NO:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="reg_no" id="reg_no" value='<?=$business->reg_no;?>' class="form-control">
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="col p-2">
                                        <button type="submit" class="btn btn-flat btn-primary right saveNewBusiness">SUBMIT</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- side modal for a cash out -->
                    <div class="p-2 bg-white side-modal-tall absolute border shadow" id='side-modal-cashout'>
                        <div class="side-modal-header">
                            <h3 class="side-modal-title">CASHOUT</h3>
                            <button type='button' class='side-modal-close'>&times;</button>
                        </div>
                        <div class="side-modal-content">
                            
                        </div>
                    </div>
                    
                    <script src="../assets/js/jquery-3.5.1.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"></script>

                    <script src="../assets/fontawesome-free-5.14.0-web/js/all.min.js"></script>
                    <script src="../assets/js/xdialog.3.4.0.min.js"></script>
                    <script src="../assets/js/custom.js"></script>

                    <script>
                        $(document).on('click', '.new-book', function(){
                            $('#side-modal-newbook').show();
                        });

                        $(document).on('click', '.new-profile', function(){
                            $('#side-modal-new-profile').show();
                        });

                        $(document).on('click', '.side-modal-close', function(){
                            $(this).closest('.absolute').hide();
                        });

                        document.addEventListener("DOMContentLoaded", function () {
                            const form = document.getElementById("newBusinessForm");
                            if (!form) return;
                            form.addEventListener("submit", function (e) {
                                e.preventDefault();
                                const formData = new FormData(form);
                                fetch("save/index.php", {
                                    method: "POST",
                                    body: formData
                                })
                                .then(res => res.text())
                                .then(response => {
                                    xdialog.alert("Business profile saved");
                                    window.location.reload();
                                })
                                .catch(err => console.error(err));
                            });

                        });
                    </script>
                </body>
                </html>
        <?php
    }else{
        redirect('../');
    }
?>