<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified())
    {
        if(isset($_POST['saveNewBook']))
        {
            $name = request('book_title');
            $details = request('book_details');
            $business_id = request('business_id');
            $user_id = auth()->id;

            $stmt = "INSERT INTO cashbook_books SET name = ?, details = ?,business_id = ?,user_id = ?";
            prepared_statements($stmt,'ssii',[$name,$details,$business_id,$user_id]);
            $book_id = $server->insert_id;

            //LINK BOOK TO user
            $sql = "INSERT INTO cashbook_book_users SET user_id = ?, book_id = ?";
            prepared_statements($sql,'ii',[$user_id,$book_id]);
            $_SESSION['success'] ='Book Saved';
            redirect(back());
        }

        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $user_id = auth()->id;
            $action = request('action');
            switch($action)
            {
                case 'fetchForm':
                        $category = request('section');
                        $bkid =  request('book_id');

                        switch($category)
                        {
                            case 'category':
                                    ?>
                                        <form id='newCategoryForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="form" value='newCategorySave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="category_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="category_name" id="category_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="">DETAILS:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="category_details" id="category_details" class="form-control">
                                                </div>
                                            </div>
                                            <div class="roww mx-1">
                                                <div class="col p-2">
                                                    <button type='submit' class="btn btn-flat btn-primary right saveCategory">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php
                                break;
                            case 'item':
                                    ?>
                                        <form id='newItemForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="form" value='newItemSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="item_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="item_name" id="item_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="item_units">Units:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="item_units" id="item_units" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="item_details">DETAILS:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="item_details" id="item_details" class="form-control">
                                                </div>
                                            </div>
                                            <div class="roww mx-1">
                                                <div class="col p-2">
                                                    <button type='submit' class="btn btn-flat btn-primary right saveItem">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php
                                break;
                            case 'customer':
                                    ?>
                                        <form id='newCustomerForm' method="post">
                                            <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                            <input type="hidden" name="form" value='newCustomerSave'>
                                            <input type="hidden" name="action" value='SaveForm'>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="name" id="name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="category_id">CONTACT:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="contact" id="contact" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="address">ADDRESS:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="address" id="address" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="route">ROUTE:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_routes WHERE book_id = ?";
                                                    $res = prepared_statements($sql,'i',[$bkid]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="route_id" id="route_id" class="form-control search-select" required>
                                                        <option value="" selected disabled>Select</option>
                                                        <?php while($rw = $res->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="address">ROUTE MANAGER:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_route_managers WHERE book_id = ?";
                                                    $res = prepared_statements($sql,'i',[$bkid]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="route_manager_id" id="route_manager_id" class="form-control search-select" required>
                                                        <option value="" selected disabled>Select</option>
                                                        <?php while($rw = $res->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="roww mx-1">
                                                <div class="col p-2">
                                                    <button class="btn btn-flat btn-primary right saveCustomer">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php
                                break;
                            case 'paymode':
                                    ?>
                                        <form id='newPaymodeForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="form" value='newPaymodeSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="paymode_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="paymode_name" id="paymode_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="">DETAILS:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="paymode_details" id="paymode_details" class="form-control">
                                                </div>
                                            </div>
                                            <div class="roww mx-1">
                                                <div class="col p-2">
                                                    <button type='submit' class="btn btn-flat btn-primary right savePaymode">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php
                                break;
                            case 'member':
                                    ?>
                                        <form id='newUserForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="form" value='newMemberSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="user_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="user_name" id="user_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="email">EMAIL:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="email" name="email" id="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="role_id">ROLE:</label>
                                                </div>
                                                <?php
                                                    $sql = mysqli_query($server,"SELECT * FROM cashbook_roles");
                                                ?>
                                                <div class="col p-2">
                                                    <select name="role_id" id="role_id" class="form-control">
                                                        <option hidden>Select</option>
                                                        <?php while($rw = $sql->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['display_name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="contact">CONTACT:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="contact" id="contact" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="password">PASSWORD:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="password" name="password" id="password" class="form-control">
                                                </div>
                                            </div>
                                            <div class="roww mx-1">
                                                <div class="col p-2">
                                                    <button  type='submit' class="btn btn-flat btn-primary right saveUser">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php
                                break;
                            case 'member-edit':
                                    $id = request('id');
                                    // fetch user from the database
                                    $sql = "SELECT * FROM cashbook_users WHERE id = ?";
                                    $res = prepared_statements($sql,'i',[$id]);
                                    $row = $res->fetch_assoc();
                                    ?>
                                        <form id='MemberEditForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="form" value='MemberEditSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <input type="hidden" name="member-id" value='<?=$row['id'];?>'>
                                                <div class="col-md-3 p-2">
                                                    <label for="user_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="user_name" id="user_name" value="<?=$row['name'] ?? '';?>" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="email">EMAIL:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="email" name="email" id="email" value="<?=$row['email'] ?? '';?>" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="role_id">ROLE:</label>
                                                </div>
                                                <?php
                                                    $sql = mysqli_query($server,"SELECT * FROM cashbook_roles");
                                                ?>
                                                <div class="col p-2">
                                                    <select name="role_id" id="role_id" class="form-control" required>
                                                        <option hidden> --- Select --- </option>
                                                        <?php while($rw = $sql->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['display_name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="contact">CONTACT:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="contact" id="contact" value="<?=$row['contact'] ?? '';?>" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="password">PASSWORD:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="password" name="password" id="password" class="form-control">
                                                </div>
                                            </div>
                                            <div class="roww mx-1">
                                                <div class="col p-2">
                                                    <button  type='submit' class="btn btn-flat btn-primary right saveEditUser">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php
                                break;
                            case 'cashin':
                                    ?>
                                        <form id='newCashinForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="form" value='newCashinSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="inamount">AMOUNT:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="inamount" id="inamount" class="form-control" placeholder='Amount..' required>
                                                </div>
                                            </div>
                                            <div class="p-2 inAmount-controlled hidden">
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label>TRANSACTION TYPE:</label>
                                                    </div>
                                                    <div class="col p-2">
                                                        <select name="transaction_type"  id ='transaction_type' class="form-control" required>
                                                            <option value="" disabled selected>Select</option>
                                                            <option value="cash_sale">Cash Sale</option>
                                                            <option value="payment">Customer Payment</option>
                                                            <option value="credit_sale">Credit Sale</option>
                                                            <option value="other_income">Other Income</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for="category_id">CATEGORY:</label>
                                                    </div>
                                                    <?php
                                                        $sql = "SELECT * FROM cashbook_categories WHERE book_id = ?";
                                                        $res = prepared_statements($sql,'i',[$bkid]);
                                                    ?>
                                                    <div class="col p-2">
                                                        <select name="category_id" id="category_id" class="form-control search-select" required>
                                                            <option value="" selected disabled>Select</option>
                                                            <?php while($rw = $res->fetch_assoc()):?>
                                                                <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                            <?php endwhile;?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for="customer_id">CUSTOMER:</label>
                                                    </div>
                                                    <?php
                                                        $sql = "SELECT * FROM cashbook_customers WHERE book_id = ?";
                                                        $res = prepared_statements($sql,'i',[$bkid]);
                                                    ?>
                                                    <div class="col p-2">
                                                        <select name="customer_id" id="customer_id" class="form-control search-select">
                                                            <option value="" disabled selected>Select</option>
                                                            <?php while($rw = $res->fetch_assoc()):?>
                                                                <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                            <?php endwhile;?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for="customer_id">ITEM:</label>
                                                    </div>
                                                    <?php
                                                        $sql = "SELECT * FROM cashbook_items WHERE book_id = ?";
                                                        $res = prepared_statements($sql,'i',[$bkid]);
                                                    ?>
                                                    <div class="col p-2">
                                                        <select name="item_id" id="item_id" class="form-control search-select">
                                                            <option value="" selected disabled>Select</option>
                                                            <?php while($rw = $res->fetch_assoc()):?>
                                                                <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                            <?php endwhile;?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for="invoice_id">INVOICE:</label>
                                                    </div>
                                                    <div class="col p-2">
                                                        <select name="invoice_id" id="invoice_id" class="form-control">
                                                            <option value="" selected disabled>Select</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for="customer_id">QUANTITY:</label>
                                                    </div>
                                                    <div class="col p-2">
                                                        <input type="text" name="quantity" id="quantity" class="form-control" autocomplete='off' placeholder='Qty'>
                                                    </div>
                                                </div>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for="paymode_id">PAYMENT MODE:</label>
                                                    </div>
                                                    <?php
                                                        $sql = "SELECT * FROM cashbook_paymodes WHERE book_id = ?";
                                                        $res = prepared_statements($sql,'i',[$bkid]);
                                                    ?>
                                                    <div class="col p-2">
                                                        <select name="paymode_id" id="paymode_id" class="form-control search-select">
                                                            <option value="" selected disabled>Select</option>
                                                            <?php while($rw = $res->fetch_assoc()):?>
                                                                <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                            <?php endwhile;?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for="cashin_details">DETAILS:</label>
                                                    </div>
                                                    <div class="col p-2">
                                                        <input type="text" name="cashin_details" id="cashin_details" class="form-control" placeholder='Details'>
                                                    </div>
                                                </div>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for="created_at">DATE:</label>
                                                    </div>
                                                    <div class="col p-2">
                                                        <input type="datetime-local" name="created_at" id="created_at" value="<?= date('Y-m-d\TH:i') ?>" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="roww mx-1">
                                                    <div class="col p-2">
                                                        <button type='submit' class="btn btn-flat btn-primary right saveCashin">Save</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    <?php
                                break;
                            case 'cashout':
                                ?>
                                    <form id='newCashoutForm' method="post">
                                        <div class="row mx-1">
                                            <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                            <input type="hidden" name="form" value='newCashoutSave'>
                                            <input type="hidden" name="action" value='SaveForm'>
                                            <div class="col-md-3 p-2">
                                                <label for="outamount">AMOUNT:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="outamount" id="outamount" class="form-control" placeholder='Amount..' required>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="category_id">CATEGORY:</label>
                                            </div>
                                            <?php
                                                $sql = "SELECT * FROM cashbook_categories WHERE book_id = ?";
                                                $res = prepared_statements($sql,'i',[$bkid]);
                                            ?>
                                            <div class="col p-2">
                                                <select name="category_id" id="category_id" class="form-control" required>
                                                    <option value="" selected disabled>Select</option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="customer_id">CUSTOMER:</label>
                                            </div>
                                            <?php
                                                $sql = "SELECT * FROM cashbook_customers WHERE book_id = ?";
                                                $res = prepared_statements($sql,'i',[$bkid]);
                                            ?>
                                            <div class="col p-2">
                                                <select name="customer_id" id="customer_id" class="form-control">
                                                    <option hidden>Select</option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="paymode_id">PAYMENT MODE:</label>
                                            </div>
                                            <?php
                                                $sql = "SELECT * FROM cashbook_paymodes WHERE book_id = ?";
                                                $res = prepared_statements($sql,'i',[$bkid]);
                                            ?>
                                            <div class="col p-2">
                                                <select name="paymode_id" id="paymode_id" class="form-control" required>
                                                    <option hidden>Select</option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="cashout_details">DETAILS:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="cashout_details" id="cashout_details" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="created_at">DATE:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="date" name="created_at" id="created_at" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="roww mx-1">
                                            <div class="col p-2">
                                                <button type='submit' class="btn btn-flat btn-primary right saveCashout">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php
                                break;
                            case 'invoice':
                                    ?>
                                        <form id='newInvoiceForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="form" value='newInvoiceSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="customer_id">CUSTOMER:</label>
                                                </div>
                                                <?php
                                                    $sqlc = "SELECT * FROM cashbook_customers WHERE book_id = ?";
                                                    $ress = prepared_statements($sqlc,'i',[$bkid]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="customer_id" id="customer_id" class="form-control search-select" required>
                                                        <option value="">-- select--</option>
                                                        <?php while($rr = $ress->fetch_assoc()):?>
                                                            <option value="<?=$rr['id'];?>"><?=$rr['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="invoice_date">INVOICE DATE:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="date" name="invoice_date" id="invoice_date" class="form-control">
                                                </div>
                                            </div>
                                            <div class="roww mx-1">
                                                <div class="col p-2">
                                                    <button type='submit' class="btn btn-flat btn-primary right saveInvoice">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php
                                break;
                            case 'route_manager':
                                    ?>
                                        <form id='newRouteManagerForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="form" value='newRouteManagerSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="user_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="name" id="route_manager_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="Contact1">CONTACT 1:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="contact1" id="contact1" class="form-control">
                                                </div>
                                            </div>
                                             <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="Contact2">CONTACT 2:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="contact2" id="contact2" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="nin">NIN:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="nin" id="nin" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="residence">RESIDENCE:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="residence" id="residence" class="form-control">
                                                </div>
                                            </div>
                                            <div class="roww mx-1">
                                                <div class="col p-2">
                                                    <button  type='submit' class="btn btn-flat btn-primary right saveRouteManager">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php
                                break;
                            case 'route':
                                ?>
                                     <form id='newRouteForm' method="post">
                                        <div class="row mx-1">
                                            <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                            <input type="hidden" name="form" value='newRouteSave'>
                                            <input type="hidden" name="action" value='SaveForm'>
                                            <div class="col-md-3 p-2">
                                                <label for="route_name">NAME:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="name" id="route_name" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="details">Details:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="details" id="details" class="form-control">
                                            </div>
                                        </div>
                                        <div class="roww mx-1">
                                            <div class="col p-2">
                                                <button  type='submit' class="btn btn-flat btn-primary right saveRoute">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                    <?php
                                break;
                        }
                    break;
                    
                case 'SaveForm':
                    $form = request('form');
                    
                    switch($form)
                    {
                        case 'newCategorySave':
                                $book_id = request('book_id');
                                $details = request('category_details');
                                $name = request('category_name');

                                // ssave the content
                                $sql = "INSERT INTO cashbook_categories SET name = ?,book_id = ?, details = ?";
                                if(prepared_statements($sql,'sis',[$name,$book_id,$details]))
                                {
                                    echo "success";
                                }
                            break;
                        case 'newItemSave':
                                $book_id = request('book_id');
                                $details = request('item_details');
                                $name = request('item_name');
                                $units = request('item_units');
                                $user_id = auth()->id;

                                // ssave the content
                                $sql = "INSERT INTO cashbook_items SET name = ?,book_id = ?, details = ?,units=?,user_id=?";
                                prepared_statements($sql,'sissi',[$name,$book_id,$details,$units,$user_id]);
                                $_SESSION['success'] = 'Item Saved';
                            break;
                        case 'newPaymodeSave':
                                $book_id = request('book_id');
                                $details = request('paymode_details');
                                $name = request('paymode_name');

                                // ssave the content
                                $sql = "INSERT INTO cashbook_paymodes SET name = ?,book_id = ?, details = ?";
                                if(prepared_statements($sql,'sis',[$name,$book_id,$details]))
                                {
                                    echo "success";
                                }

                            break;
                        case 'MemberEditSave':
                            $book_id = request('book_id');
                            $email = request('email');
                            $name = request('user_name');
                            $contact = request('contact');
                            $business_id = bookFind($book_id)->business_id;
                            $password = request('password');
                            $role = request('role_id');
                            $member_id = request('member-id');

                            // check if user already exists and edit
                                // save the content
                                if(!empty($member_id))
                                {   
                                    if(!empty($password)):  // update password is not empty
                                        $password = password_hash($password,PASSWORD_DEFAULT);
                                        // update user here, check user books
                                        $sql = "UPDATE cashbook_users SET name = ?,email=?,contact = ?,business_id = ?,password =?,role_id= ? WHERE id = ?";
                                        prepared_statements($sql,'sssisii',[$name,$email,$contact,$business_id,$password,$role,$member_id]);
                                        
                                    else: // leave out password update
                                        $sql = "UPDATE cashbook_users SET name = ?,email=?,contact = ?,business_id = ?,role_id= ? WHERE id = ?";
                                        prepared_statements($sql,'sssiii',[$name,$email,$contact,$business_id,$role,$member_id]);
                                    endif;

                                    $user_id = $member_id;
                                }

                                // check user book attachment
                                $sql = "SELECT * FROM  cashbook_book_users WHERE user_id = ? AND book_id = ?";
                                $check = prepared_statements($sql,'ii',[$member_id,$book_id]);
                                
                                // attach book if user is not linked
                                if($check->num_rows == 0)
                                {
                                     // link user to books
                                    $stmt = "INSERT INTO cashbook_book_users SET user_id = ?, book_id = ?";
                                    prepared_statements($stmt,'ii',[$member,$book_id]);
                                }

                            break;
                        case 'newMemberSave':

                            $book_id = request('book_id');
                            $email = request('email');
                            $name = request('user_name');
                            $contact = request('contact');
                            $business_id = bookFind($book_id)->business_id;
                            $password = request('password');
                            $role = request('role_id');

                                $sql = "INSERT INTO cashbook_users SET name = ?,email=?,contact = ?,business_id = ?,password =?,role_id=?";
                                prepared_statements($sql,'sssisi',[$name,$email,$contact,$business_id,$password,$role]);
                                $user_id = $server->insert_id;

                                // check user book attachment
                                $sql = "SELECT * FROM  cashbook_book_users WHERE user_id = ? AND book_id = ?";
                                $check = prepared_statements($sql,'ii',[$user_id,$book_id]);
                                
                                // attach book if user is not linked
                                if($check->num_rows == 0)
                                {
                                     // link user to books
                                    $stmt = "INSERT INTO cashbook_book_users SET user_id = ?, book_id = ?";
                                    prepared_statements($stmt,'ii',[$user_id,$book_id]);
                                }

                            break;
                        case 'newCustomerSave':
                            $book_id = request('book_id');
                            $name = request('name');
                            $address = request('address');
                            $contact = request('contact');
                            $route_id = request('route_id');
                            $manager = request('route_manager_id');
                            $user_id = auth()->id;

                                // save the content
                                $sql = "INSERT INTO cashbook_customers SET name = ?,address=?,contact = ?,book_id = ?,route_id = ?,route_manager_id = ?,user_id = ?";
                                $res = prepared_statements($sql,'sssiiii',[$name,$address,$contact,$book_id,$route_id,$manager,$user_id]);
                                $customer_id = $server->insert_id;

                                if($res)
                                {
                                    // attach customer to manager and route
                                    $route = "INSERT INTO cashbook_customer_route SET customer_id = ?, route_id = ?";
                                    prepared_statements($route,'ii',[$customer_id,$route_id]);
                                }
                            break;
                        case 'newCashinSave':

                                $book_id = request('book_id');
                                $details = request('cashin_details');
                                $category_id = request('category_id');
                                $amount = request('inamount');
                                $payment_mode = (isset($_REQUEST['paymode_id'])) ? request('paymode_id') : "";
                                $date = request('created_at');
                                $user_id = auth()->id;
                                $customer_id = request('customer_id');
                                $item_id = isset($_POST['item_id']) ? request('item_id') : 0;
                                $qty = request('quantity');
                                // $rate = request('rate');
                                $type = request('transaction_type');
                                $invoice_id = isset($_POST['invoice_id']) ? request('invoice_id') : 0;

                                if(!empty($invoice_id) || $invoice_id > 0 )
                                {
                                    $invoice = invoiceFind($invoice_id);
                                    $details = $invoice->invoice_no;
                                }

                                // distribute according to the transaction type
                                $credit = 0;
                                $debit = 0;

                                // CASH SALE
                                if($type === 'cash_sale'){
                                    $credit = (float)$amount; // cash increases
                                    $creditable = $credit;
                                    $debit = $credit;

                                    // include both values to balance the page and keep debts flowing right
                                }

                                // CREDIT SALE
                                if($type === 'credit_sale'){
                                    $debit = (float)$amount; 
                                    $creditable = 0;
                                }

                                // capture payment the same way as cash sale
                                if($type === 'payment'){
                                    $credit = (float)$amount;
                                    $creditable = (float)$amount; 
                                }

                                //other_income
                                 if($type === 'other_income'){
                                    $credit = (float)$amount; 
                                    $creditable = (float)$amount;
                                }
                                // save the content
                                $sql = "INSERT INTO cashbook_transactions  SET credit_amount = ?, debit_amount = ?, book_id = ?, details = ?, 
                                            category_id = ?,  paymode_id = ?, created_at = ?, user_id = ?,  type = ?,  customer_id = ?, 
                                            item_id = ?,  quantity = ?";

                                $res = prepared_statements(
                                    $sql,'ddisiisisiii',[$credit,$debit,$book_id, $details,$category_id,$payment_mode,$date,$user_id, $type,$customer_id,$item_id,$qty]
                                );

                                $trans_id = $server->insert_id;

                                // update invoices if not empty
                                if(!empty($invoice_id) || $invoice_id > 0 )
                                {
                                    $details = $invoice->invoice_no;
                                    $total_paid = $invoice->paid_amount + $amount;
                                    $total_balance = $invoice->balance - $amount;

                                    // update invoice
                                    $update = "UPDATE cashbook_invoices SET paid_amount = ?, balance = ? WHERE id = ?";
                                    prepared_statements($update,'ddi',[$total_paid,$total_balance,$invoice_id]);
                                }
                               
                                 // credit cashins       
                                if($credit > 0)
                                {
                                    // insert into cashins table
                                    $stmt = "INSERT INTO  cashbook_cashins SET amount = ?, category_id = ?, details = ?,book_id = ?,paymode_id = ?,transaction_id = ?,created_at=?,user_id=?,item_id = ?, quantity = ?";
                                    prepared_statements($stmt,'iisiiisiii',[$amount,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty]);
                                }

                                // check if customer has been selected and update the ledger
                                if(!empty($customer_id))
                                {
                                    // update customer ledger
                                    customerLedgerUpdate($customer_id,$creditable,$debit,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty);
                                }
                                $_SESSION['success'] ='Data Saved';
                            break;

                        case 'editCashinSave':
                                $details = request('cashin_details');
                                $category_id = request('category_id');
                                $amount = request('inamount');
                                $payment_mode = request('paymode_id');
                                $transid = request('transaction_id');
                                $date = request('created_at');
                                $user_id = auth()->id;
                                $customer_id = request('customer_id');
                                $item_id = request('item_id');
                                $quantity = request('quantity');

                                // track transaction edits
                                trackTransactionEdits($transid,'edit');

                                // save the content
                                $sql = "UPDATE cashbook_transactions SET credit_amount = ?, details = ?,category_id = ?,paymode_id=?,created_at=?,user_id = ?,customer_id=?,item_id = ?, quantity = ? WHERE id = ?";
                                $res = prepared_statements($sql,'isiisiiiii',[$amount,$details,$category_id,$payment_mode,$date,$user_id,$customer_id,$item_id,$quantity,$transid]);

                                $stmt = "UPDATE cashbook_cashins SET amount = ?, category_id = ?, details = ?,paymode_id = ?,created_at=?,user_id = ?,item_id = ?, quantity = ? WHERE transaction_id = ?";
                                prepared_statements($stmt,'iisisiiii',[$amount,$category_id,$details,$payment_mode,$date,$user_id,$item_id,$quantity,$transid]);

                                // would need to handle customer ledger records
                                $_SESSION['success'] = "Data Saved";
                            break;
                            
                        case 'newCashoutSave':
                                $book_id = request('book_id');
                                $details = request('cashout_details');
                                $category_id = request('category_id');
                                $amount = request('outamount');
                                $payment_mode = request('paymode_id');
                                $date = request('created_at');
                                $user_id = auth()->id;
                                $type='debit';
                                $customer_id = request('customer_id');

                                // ssave the content
                                $sql = "INSERT INTO cashbook_transactions SET debit_amount = ?,book_id = ?, details = ?,category_id=?,paymode_id = ?,created_at = ?,user_id=?,type=?,customer_id=?";
                                $res = prepared_statements($sql,'iisiisisi',[$amount,$book_id,$details,$category_id,$payment_mode,$date,$user_id,$type,$customer_id]);
                                $trans_id = $server->insert_id;

                                $stmt = "INSERT INTO  cashbook_cashouts SET amount = ?, category_id = ?, details = ?,book_id = ?,transaction_id = ?,paymode_id = ?,created_at = ?,user_id=?";
                                prepared_statements($stmt,'iisiiisi',[$amount,$category_id,$details,$book_id,$trans_id,$payment_mode,$date,$user_id]);
                                $_SESSION['success'] = 'Transaction Saved';

                            break;
                        case 'editCashoutSave':

                                $details = request('cashout_details');
                                $category_id = request('category_id');
                                $amount = request('outamount');
                                $payment_mode = request('paymode_id');
                                $transid = request('transaction_id');
                                $date = request('created_at');
                                $customer_id = request('customer_id');

                                // record transaction edits
                                trackTransactionEdits($transid,'edit');

                                // ssave the content
                                $sql = "UPDATE cashbook_transactions SET debit_amount = ?, details = ?,category_id=?,paymode_id = ?,created_at=?,user_id = ?,customer_id=? WHERE id = ?";
                                $res = prepared_statements($sql,'isiisiii',[$amount,$details,$category_id,$payment_mode,$date,$user_id,$customer_id,$transid]);

                                $stmt = "UPDATE cashbook_cashouts SET amount = ?, category_id = ?, details = ?,paymode_id = ?,created_at=?,user_id =? WHERE transaction_id = ?";
                                prepared_statements($stmt,'iisisii',[$amount,$category_id,$details,$payment_mode,$date,$user_id,$transid]);
                                $_SESSION['success'] = "Data Saved";


                            break;

                        case 'newInvoiceSave':
                                $customer_id = request('customer_id');
                                $user_id = auth()->id;
                                $invoice_date = request('invoice_date');
                                $book_id = request('book_id');

                                // save data
                                $stmt = "INSERT INTO cashbook_invoices SET customer_id = ?, book_id = ?, user_id = ?, invoice_date = ?";
                                $invoice_id = prepared_statements($stmt,'iiis',[$customer_id,$book_id,$user_id,$invoice_date]);
                                $invoice_no = 'INV-'.date('Y').'-'.str_pad($invoice_id,6,'0',STR_PAD_LEFT);

                                // update invoice number
                                $update = "UPDATE cashbook_invoices SET invoice_no = ? WHERE id = ?";
                                prepared_statements($update,'si',[$invoice_no,$invoice_id]);

                                // find invoice to load its details
                                $invoice = invoiceFind($invoice_id);
                                echo json_encode($invoice);
                            break;
                            // save invoice items
                        case 'newInvoiceItemSave':

                            $invoice_id = request('invoice_id');
                            $items = $_REQUEST['item_id'];
                            $quantities = $_REQUEST['qty'];
                            $rates = $_REQUEST['rate'];
                            $amounts = $_REQUEST['amount']; 
                            $user_id = auth()->id;
                            $invoiceAmount = request('InvoiceAmount');
                            $invoice = invoiceFind($invoice_id);
                            $customer_id = $invoice->customer_id;
                            $book_id = $invoice->book_id;
                            $invoice_no = $invoice->invoice_no;

                            // save data into the table
                            $stmt = "INSERT INTO cashbook_invoice_items SET invoice_id = ?,item_id = ?,quantity = ?, unit_price = ?,total = ?, user_id = ?";

                            $amountt = 0;
                            // loop through items
                            foreach($items as $k => $item)
                            {
                                $item = $items[$k];
                                $qty = $quantities[$k];
                                $rate = $rates[$k];
                                $amount = $amounts[$k];
                                $amountt += $amount;

                                // use prepared statements
                                prepared_statements($stmt,'iiiidi',[$invoice_id,$item,$qty,$rate,$amount,$user_id]);
                            }

                            $status = 'sent';
                            // update invoice
                            $stm = "UPDATE cashbook_invoices SET total = ?,balance = ?,status = ? WHERE id = ?";
                            $ress = prepared_statements($stm,'iisi',[$amountt,$amountt,$status,$invoice_id]);

                            if($ress > 0) // if the invoice has been updated
                            {
                                // enter transaction in the table to link to customer
                                $trans = "INSERT INTO cashbook_transactions SET customer_id = ?, details = ?, debit_amount = ?,user_id = ?, book_id = ?, type = ? ";
                                $trans_id = prepared_statements($trans,'isdiis',[$customer_id,$invoice->invoice_no,$amountt,$user_id,$book_id,'Invoice']);

                                // update customer ledger section
                                insertCustomerLedgerInvoice($customer_id, 'invoice',$amountt,$invoice_id,$trans_id,$book_id,$invoice_no);
                            }
                            
                            $_SESSION['success'] = "Invoice Details Saved";

                            break;
                        case 'routeManagerEditSave':
                                $book_id = request('book_id');
                                $residence = request('residence');
                                $name = request('name');
                                $contact1 = request('contact1');
                                $contact2 = request('contact2');
                                $nin = request('nin');
                                $business_id = bookFind($book_id)->business_id;
                                $manager_id = request('manager_id') ?? "";

                                // check if user already exists and edit
                                    // save the content
                                    if(!empty($manager_id))
                                    {   
                                        $sql = "UPDATE cashbook_route_managers SET name = ?,nin=?,contact1 = ?,contact2 = ?,residence = ?,book_id = ? WHERE id = ?";
                                        prepared_statements($sql,'sssssi',[$name,$nin,$contact1,$contact2,$residence,$manager_id]);
                                    }

                            break;
                        case 'newRouteManagerSave':

                            $book_id = request('book_id');
                            $residence = request('residence');
                            $name = request('name');
                            $contact1 = request('contact1');
                            $contact2 = request('contact2');
                            $nin = request('nin');
                            $business_id = bookFind($book_id)->business_id;
                            $user_id = auth()->id;
                            
                                $sql = "INSERT INTO cashbook_route_managers SET name = ?,nin=?,contact1 = ?,contact2 = ?,residence = ?,book_id = ?,user_id = ?";
                                prepared_statements($sql,'sssssii',[$name,$nin,$contact1,$contact2,$residence,$book_id,$user_id]);
                                $record_id = $server->insert_id;
                            break;
                        case 'newRouteSave':
                                $book_id = request('book_id');
                                $name = request('name');
                                $details = request('details');
                                $user_id = auth()->id;
                            
                                $sql = "INSERT INTO cashbook_routes SET book_id = ?, name = ?,details = ?, user_id = ?";
                                prepared_statements($sql,'issi',[$book_id,$name,$details,$user_id]);
                                $record_id = $server->insert_id;
                                echo $record_id;
                            break;
                    }
                    break;
                case 'editTransaction':
                    $type = request('type');
                    $id = request('id');
                    $transaction = transactionFind($id);
                        switch($type)
                        {
                            case 'credit':
                                ?>
                                    <form id='newCashinForm' method="post">
                                        <div class="row mx-1">
                                            <input type="hidden" name="transaction_id" value="<?=$transaction->id;?>">
                                            <input type="hidden" name="form" value='editCashinSave'>
                                            <input type="hidden" name="action" value='SaveForm'>
                                            <div class="col-md-3 p-2">
                                                <label for="inamount">AMOUNT:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="inamount" id="inamount" value="<?=$transaction->credit_amount;?>" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="category_id">CATEGORY:</label>
                                            </div>
                                            <?php
                                                $sql = "SELECT * FROM cashbook_categories WHERE book_id = ?";
                                                $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                            ?>
                                            <div class="col p-2">
                                                <select name="category_id" id="category_id" class="form-control" required>
                                                    <option value="<?=$transaction->category_id;?>"><?=$transaction->category;?></option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="customer_id">CUSTOMER:</label>
                                            </div>
                                            <?php
                                                $sql = "SELECT * FROM cashbook_customers WHERE book_id = ?";
                                                $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                            ?>
                                            <div class="col p-2">
                                                <select name="customer_id" id="customer_id" class="form-control">
                                                    <option value="<?=($transaction->customer_id) ?? ''?>"><?=($transaction->customer_name) ?? 'Select Customer'?></option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="customer_id">ITEM:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_items WHERE book_id = ?";
                                                    $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="item_id" id="item_id" class="form-control">
                                                        <option value ='<?=$transaction->item_id ?? ''?>'><?=$transaction->item_name ?? ''?></option>
                                                        <option hidden>--- Select ---</option>
                                                        <?php while($rw = $res->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="customer_id">QUANTITY:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="quantity" id="quantity" class="form-control" value="<?=($transaction->quantity) ?? ''?>" autocomplete='off' placeholder='Qty'>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="paymode_id">PAYMENT MODE:</label>
                                            </div>
                                            <?php
                                                $sql = "SELECT * FROM cashbook_paymodes WHERE book_id = ?";
                                                $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                            ?>
                                            <div class="col p-2">
                                                <select name="paymode_id" id="paymode_id" class="form-control">
                                                    <option value="<?=$transaction->paymode_id;?>"><?=$transaction->paymode;?></option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="cashin_details">DETAILS:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="cashin_details" id="cashin_details" value="<?=!empty($transaction->details) ? $transaction->details: '';?>" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="created_at">DATE:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="datetime-local" name="created_at" value="<?=$transaction->created_at;?>" id="created_at" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="roww mx-1">
                                            <div class="col p-2">
                                                <button type='submit' class="btn btn-flat btn-primary right saveCashin">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php
                                break;
                            case 'debit':
                                ?>
                                    <form id='newCashoutForm' method="post">
                                        <div class="row mx-1">
                                            <input type="hidden" name="form" value='editCashoutSave'>
                                            <input type="hidden" name="transaction_id" value="<?=$transaction->id;?>">
                                            <input type="hidden" name="action" value='SaveForm'>
                                            <div class="col-md-3 p-2">
                                                <label for="outamount">AMOUNT:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="outamount" id="outamount" value="<?=$transaction->debit_amount;?>" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="category_id">CATEGORY:</label>
                                            </div>
                                            <?php
                                                $sql = "SELECT * FROM cashbook_categories WHERE book_id = ?";
                                                $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                            ?>
                                            <div class="col p-2">
                                                <select name="category_id" id="category_id" class="form-control" required>
                                                    <option value="<?=$transaction->category_id;?>"><?=$transaction->category;?></option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="customer_id">CUSTOMER:</label>
                                            </div>
                                            <?php
                                                $sql = "SELECT * FROM cashbook_customers WHERE book_id = ?";
                                                $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                            ?>
                                            <div class="col p-2">
                                                <select name="customer_id" id="customer_id" class="form-control">
                                                    <option hidden>Select</option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="paymode_id">PAYMENT MODE:</label>
                                            </div>
                                            <?php
                                                $sql = "SELECT * FROM cashbook_paymodes WHERE book_id = ?";
                                                $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                            ?>
                                            <div class="col p-2">
                                                <select name="paymode_id" id="paymode_id" class="form-control">
                                                    <option value="<?=$transaction->paymode_id;?>"><?=$transaction->paymode;?></option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="cashout_details">DETAILS:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="cashout_details" value="<?=!empty($transaction->details) ? $transaction->details: '';?>" id="cashout_details" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="created_at">DATE:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="datetime-local" name="created_at" value="<?=$transaction->created_at;?>" id="created_at" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="roww mx-1">
                                            <div class="col p-2">
                                                <button type='submit' class="btn btn-flat btn-primary right saveCashout">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php
                                break;
                        }
                    break;
                case 'DeleteTransaction':
                        $type = request('type');
                        $id = request('id');
                        $transaction = transactionFind($id);

                        // track changes before deletion
                        trackTransactionEdits($id,'delete');
                        
                        // delete transaction
                        $sql = "DELETE FROM cashbook_transactions WHERE id = ?";
                        prepared_statements($sql,'i',[$id]);

                        switch($type)
                        {
                            case 'credit':
                                // delete
                                $sql = "DELETE FROM cashbook_cashins WHERE transaction_id = ?";
                                prepared_statements($sql,'i',[$id]);
                                break;
                            case 'debit':
                                $sql = "DELETE FROM cashbook_cashouts WHERE transaction_id = ?";
                                prepared_statements($sql,'i',[$id]);
                                break;
                        }
                    break;
                case 'transactionFilter':
                        $conditions = [];
                        $params     = [];
                        $types      = "";

                        /* Required */
                        $conditions[] = "t.book_id = ?";
                        $params[]     = request('book_id');
                        $types       .= "i";

                        /* Date filter (ignore time) */
                        if (!empty($_POST['date'])) {
                            $conditions[] = "DATE(t.created_at) = ?";
                            $params[]     = $_POST['date'];
                            $types       .= "s";
                        }

                        /* Type filter */
                        if (!empty($_POST['type'])) {
                            $conditions[] = "t.type = ?";
                            $params[]     = $_POST['type'];
                            $types       .= "s";
                        }

                        /* Category filter */
                        if (!empty($_POST['category'])) {
                            $conditions[] = "t.category_id = ?";
                            $params[]     = $_POST['category'];
                            $types       .= "i";
                        }

                        /* Customer filter */
                        if (!empty($_POST['customer'])) {
                            $conditions[] = "t.customer_id = ?";
                            $params[]     = $_POST['customer'];
                            $types       .= "i";
                        }

                        // $conditions[] = "month(t.created_at) = month(now())"; // default filter

                        $sql = "
                            SELECT 
                                t.*,
                                c.name AS category_name,cc.name as customer_name
                                FROM cashbook_transactions t
                                LEFT JOIN cashbook_categories c ON c.id = t.category_id
                                LEFT JOIN cashbook_customers cc ON cc.id = t.customer_id
                        ";

                        if ($conditions) {
                            $sql .= " WHERE " . implode(" AND ", $conditions);
                        }

                        $sql .= " ORDER BY t.created_at ASC";

                        $stmt = $server->prepare($sql);
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        /* Render rows */
                        if($result->num_rows >0):
                            while ($row = $result->fetch_assoc()) {
                                ?>
                                    <tr class='transaction-details hover hover-hide-content'>
                                        <td></td>
                                        <td><?=date('d-m-Y', strtotime($row['created_at']));?></td>
                                        <td><?=$row['category_name'];?></td>
                                        <td><?=$row['customer_name'];?></td>
                                        <td><?=$row['details'];?></td>
                                        <td><?=number_format($row['credit_amount'],0);?></td>
                                        <td><?=number_format($row['debit_amount'],0);?></td>
                                        <td>
                                            <?php if(hasRole(['owner','partner'])):?>
                                                <span class="hover-display text-sms">
                                                    <button class="btn btn-sm btn-outline-info edit-trans text-muted" data-id="<?=$row['id'];?>" data-type="<?=($row['credit_amount'] > 0) ? 'credit':'debit';?>"><i class="fa fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger delete-trans" data-id="<?=$row['id'];?>" data-type="<?=($row['credit_amount'] > 0) ? 'credit':'debit';?>"><i class="fa fa-trash"></i></button> 
                                                </span>
                                            <?php endif;?>
                                        </td>
                                    </tr>
                                <?php
                            }
                        else:
                            ?>
                                <tr>
                                    <td colspan='7'><center>No results found!</center></td>
                                </tr>
                            <?php
                        endif;
                    break;
                case 'findCustomerInvoices':
                    $id = request('customer_id');
                    // fetch invoices
                    $stmt = "SELECT * FROM cashbook_invoices WHERE customer_id = ? AND balance  > 0";
                    $res = prepared_statements($stmt,'i',[$id]);
                    ?>
                        <option value="" selected disabled>-- select --</option>
                    <?php
                    while ($r = $res->fetch_assoc()):
                        ?>
                            <option value="<?=$r['id'];?>"><?=$r['invoice_no'];?> (<?=$r['balance'];?>)</option>
                        <?php
                    endwhile;

                    break;

            }
        }
    }else{
        redirect('../');
    }
?>