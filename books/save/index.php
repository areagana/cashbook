<?php
    // session_start();
    error_reporting(E_ALL);
    require_once(__dir__.'/../../assets/functions.php');
    require_once(__DIR__ . '/../../assets/fpdf/fpdf.php');
    require_once(__DIR__ . '/../../assets/vendor/autoload.php');

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

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
                        $book = bookFind($bkid);

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
                            case 'edit-category':
                                    $id = request('category_id');
                                    $category = categoryFind($id);
                                ?>
                                        <form id='newCategoryForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="category_id" value="<?=$id;?>">
                                                <input type="hidden" name="form" value='newCategorySave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="category_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="category_name" value="<?=$category->name;?>" id="category_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="">DETAILS:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="category_details" value="<?=$category->details;?>" id="category_details" class="form-control">
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
                            case 'item-edit':
                                $id = request('item_id');
                                $item = itemFind($id);
                                    ?>
                                        <form id='newItemForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="item_id" value="<?=$id;?>">
                                                <input type="hidden" name="form" value='newItemSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="item_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="item_name" value="<?=$item->name;?>" id="item_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="item_units">Units:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="item_units" value="<?=$item->units;?>" id="item_units" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="item_details">DETAILS:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="item_details" value="<?=$item->details;?>" id="item_details" class="form-control">
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
                            case 'creditor':
                                    ?>
                                        <form id='newCreditorForm' method="post">
                                            <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                            <input type="hidden" name="form" value='newCreditorSave'>
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
                                                    <label for="credit_balance">BALANCE B/F:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="credit_balance" id="credit_balance" class="form-control">
                                                </div>
                                            </div>
                                            <div class="roww mx-1">
                                                <div class="col p-2">
                                                    <button class="btn btn-flat btn-primary right saveCreditor">Save</button>
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
                            case 'paymode-edit':
                                $id = request('mode_id');
                                $stmt = "SELECT * FROM cashbook_paymodes WHERE id = ?";
                                $res = prepared_statements($stmt,'i',[$id]);
                                $mode  = myObject($res->fetch_assoc());
                                ?>
                                        <form id='newPaymodeForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="mode_id" value="<?=$id;?>">
                                                <input type="hidden" name="form" value='newPaymodeSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="paymode_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="paymode_name"   value="<?=$mode->name;?>" id="paymode_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="">DETAILS:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="paymode_details" value="<?=$mode->details;?>" id="paymode_details" class="form-control">
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
                                                        <?php while($rw = $sql->fetch_assoc()): if($rw['name'] !='superadministrator'):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['display_name'];?></option>
                                                        <?php endif; endwhile;?>
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
                                $id = request('member_id');
                                $member = memberFind($id);
                                    ?>
                                        <form id='newUserForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="member_id" value="<?=$id;?>">
                                                <input type="hidden" name="form" value='newMemberSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="user_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="user_name" value="<?=$member->name;?>" id="user_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="email">EMAIL:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="email" name="email" value="<?=$member->email;?>" id="email" class="form-control">
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
                                                        <option value="<?=$member->role_id;?>" selected><?=$member->role_name;?></option>
                                                        <option hidden>Select</option>
                                                        <?php while($rw = $sql->fetch_assoc()): if($rw['name'] !='superadministrator'):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['display_name'];?></option>
                                                        <?php endif; endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="contact">CONTACT:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="contact" value="<?=$member->contact;?>" id="contact" class="form-control">
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
                                                            <option value="creditorInjection">Creditor Support</option>
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
                                                        $sql = "SELECT * FROM cashbook_customers WHERE book_id = ? ORDER BY name ASC";
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
                                                <div class="p-0 income-creditor hidden">
                                                    <div class="row mx-1">
                                                        <div class="col-md-3 p-2">
                                                            <label for="creditor_id">CREDITOR:</label>
                                                        </div>
                                                        <?php
                                                            $sql = "SELECT * FROM cashbook_creditors WHERE book_id = ?  ORDER BY name ASC";
                                                            $res = prepared_statements($sql,'i',[$bkid]);
                                                        ?>
                                                        <div class="col p-2">
                                                            <select name="creditor_id" id="creditor_id" class="form-control">
                                                                <option hidden>Select</option>
                                                                <?php while($rw = $res->fetch_assoc()):?>
                                                                    <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                                <?php endwhile;?>
                                                            </select>
                                                        </div>
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
                                                    <?php
                                                        $entryDate = $_SESSION['entry_date'] ?? date('Y-m-d');
                                                        $entryDate = strtotime($entryDate);
                                                        $entryDate = $entryDate ? date('Y-m-d',$entryDate) : date('Y-m-d');
                                                    ?>
                                                    <div class="col p-2">
                                                        <input type="date" name="created_at" id="created_at" value="<?=htmlspecialchars($entryDate);?>" class="form-control" required>
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
                                                <label for="type">TYPE:</label>
                                            </div>
                                            <div class="col p-2">
                                                <select name="expense_type" id="expense_type" class="form-control" required>
                                                    <option value="" selected disabled>Select</option>
                                                    <option value="purchase">Purchase</option>
                                                    <option value="expense">Expense</option>
                                                    <option value="borrowing">Borrowing</option>
                                                    <option value="creditorPayment">Creditor Payment</option>
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
                                                <select name="category_id" id="category_id" class="form-control" required>
                                                    <option value="" selected disabled>Select</option>
                                                    <?php while($rw = $res->fetch_assoc()):?>
                                                        <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="p-0 expense-customer hidden">
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="customer_id">CUSTOMER:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_customers WHERE book_id = ?  ORDER BY name ASC";
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
                                        </div>
                                        <div class="p-0 purchase-expense hidden">
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="item_id">ITEM:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_items WHERE book_id = ?  ORDER BY name ASC";
                                                    $res = prepared_statements($sql,'i',[$bkid]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="item_id" id="item_id" class="form-control">
                                                        <option hidden>---Select---</option>
                                                        <?php while($rw = $res->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="quantity">QUANTITY:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="quantity" id="quantity" class="form-control" placeholder='Qty....'>
                                                </div>
                                            </div>

                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="purchase_rate">RATE:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="purchase_rate" id="purchase_rate" class="form-control" placeholder='Amount per unit....'>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-0 expense-creditor hidden">
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="creditor_id">CREDITOR:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_creditors WHERE book_id = ?  ORDER BY name ASC";
                                                    $res = prepared_statements($sql,'i',[$bkid]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="creditor_id" id="creditor_id" class="form-control">
                                                        <option hidden>Select</option>
                                                        <?php while($rw = $res->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
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
                                            <?php
                                                $entryDate = $_SESSION['entry_date'] ?? date('Y-m-d');
                                                $entryDate = strtotime($entryDate);
                                                $entryDate = $entryDate ? date('Y-m-d',$entryDate) : date('Y-m-d');
                                            ?>
                                            <div class="col p-2">
                                                <input type="date" name="created_at" id="created_at" value="<?=htmlspecialchars($entryDate);?>" class="form-control" required>
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
                                                    $sqlc = "SELECT * FROM cashbook_customers WHERE book_id = ? ORDER BY name asc";
                                                    $ress = prepared_statements($sqlc,'i',[$book->id]);
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
                            case 'route-manager-edit':
                                $id = request('route_manager_id');
                                $manager = routeManagerFind($id);
                                    ?>
                                        <form id='newRouteManagerForm' method="post">
                                            <div class="row mx-1">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="route_manager_id" value="<?=$id;?>">
                                                <input type="hidden" name="form" value='newRouteManagerSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="col-md-3 p-2">
                                                    <label for="user_name">NAME:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="name" value="<?=$manager->name;?>" id="route_manager_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="Contact1">CONTACT 1:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="contact1" value="<?=$manager->contact1;?>"  id="contact1" class="form-control">
                                                </div>
                                            </div>
                                             <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="Contact2">CONTACT 2:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="contact2" value="<?=$manager->contact2;?>" id="contact2" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="nin">NIN:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="nin" value="<?=$manager->nin;?>" id="nin" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="residence">RESIDENCE:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="residence" value="<?=$manager->residence;?>" id="residence" class="form-control">
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
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="manager">Manager:</label>
                                            </div>
                                            <div class="col p-2">
                                                <?php 
                                                    $sql = "SELECT * FROM cashbook_route_managers WHERE book_id = ? ORDER BY name asc";
                                                    $res = prepared_statements($sql,'i',[$book->id]);
                                                ?>
                                                <select name="manager" id="manager" class="form-control">
                                                    <option value="">-- select --</option>
                                                    <?php  while($re = $res->fetch_assoc()):?>
                                                        <option value="<?=$re['id'];?>"><?=$re['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
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
                            case 'route-edit':
                                $id = request('route_id');
                                $route = routeFind($id);

                                ?>
                                     <form id='newRouteForm' method="post">
                                        <div class="row mx-1">
                                            <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                            <input type="hidden" name="route_id" value="<?=$id;?>">
                                            <input type="hidden" name="form" value='newRouteSave'>
                                            <input type="hidden" name="action" value='SaveForm'>
                                            <div class="col-md-3 p-2">
                                                <label for="route_name">NAME:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="name" id="route_name" value="<?=$route->name;?>" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="details">Details:</label>
                                            </div>
                                            <div class="col p-2">
                                                <input type="text" name="details" value="<?=$route->details;?>" id="details" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row mx-1">
                                            <div class="col-md-3 p-2">
                                                <label for="manager">Manager:</label>
                                            </div>
                                            <div class="col p-2">
                                                <?php 
                                                    $sql = "SELECT * FROM cashbook_route_managers WHERE book_id = ? ORDER BY name asc";
                                                    $res = prepared_statements($sql,'i',[$book->id]);
                                                ?>
                                                <select name="manager" id="manager" class="form-control">
                                                    <option value="">-- select --</option>
                                                    <?php  while($re = $res->fetch_assoc()):?>
                                                        <option value="<?=$re['id'];?>"><?=$re['name'];?></option>
                                                    <?php endwhile;?>
                                                </select>
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
                            case 'purchase':
                                    ?>
                                        <form id='newPurchaseForm' method="post">
                                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                                <input type="hidden" name="form" value='newPurchaseSave'>
                                                <input type="hidden" name="action" value='SaveForm'>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for = "purchase_type">TRANSACTION TYPE:</label>
                                                    </div>
                                                    <div class="col p-2">
                                                        <select name="purchase_type"  id ='purchase_type' class="form-control" required>
                                                            <option value="" disabled selected>Select</option>
                                                            <option value="cash_purchase">Cash Purchase</option>
                                                            <option value="credit_purchase">Credit Purchase</option>
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
                                                    <label for="purchase_quantity">QUANTITY:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="quantity" id="purchase_quantity" class="form-control" autocomplete='off' placeholder='Qty'>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="purchase_rate">RATE:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="rate" id="purchase_rate" class="form-control" autocomplete='off' placeholder='Rate'>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="purchase_amount">AMOUNT:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="amount" id="purchase_amount" class="form-control" placeholder='Amount..' required>
                                                </div>
                                            </div>
                                            <div class="p-2">
                                                <div class="p-0 purchase-creditor hidden">
                                                    <div class="row mx-1">
                                                        <div class="col-md-3 p-2">
                                                            <label for="creditor_id">CREDITOR:</label>
                                                        </div>
                                                        <?php
                                                            $sql = "SELECT * FROM cashbook_creditors WHERE book_id = ?  ORDER BY name ASC";
                                                            $res = prepared_statements($sql,'i',[$bkid]);
                                                        ?>
                                                        <div class="col p-2">
                                                            <select name="creditor_id" id="creditor_id" class="form-control">
                                                                <option hidden>Select</option>
                                                                <?php while($rw = $res->fetch_assoc()):?>
                                                                    <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                                <?php endwhile;?>
                                                            </select>
                                                        </div>
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
                                                        <label for="details">DETAILS:</label>
                                                    </div>
                                                    <div class="col p-2">
                                                        <input type="text" name="details" id="details" class="form-control" placeholder='Details'>
                                                    </div>
                                                </div>
                                                <div class="row mx-1">
                                                    <div class="col-md-3 p-2">
                                                        <label for="created_at">DATE:</label>
                                                    </div>
                                                    <div class="col p-2">
                                                        <input type="datetime" name="created_at" id="created_at" value="<?=date('Y-m-d');?>" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="roww mx-1">
                                                    <div class="col p-2">
                                                        <button type='submit' class="btn btn-flat btn-primary right savePurchase">Save</button>
                                                    </div>
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
                                $category_id = request('category_id') ?? "";

                                // ssave the content
                                if(empty($category_id))
                                {
                                    $sql = "INSERT INTO cashbook_categories SET name = ?,book_id = ?, details = ?";
                                    prepared_statements($sql,'sis',[$name,$book_id,$details]);
                                }else{
                                    $sql = "UPDATE cashbook_categories SET name = ?, details = ? WHERE id = ?";
                                    prepared_statements($sql,'ssi',[$name,$details,$category_id]);
                                }
                            break;
                        case 'newItemSave':
                                $book_id = request('book_id');
                                $details = request('item_details');
                                $name = request('item_name');
                                $units = request('item_units');
                                $user_id = auth()->id;
                                $item_id = request('item_id') ?? "";

                                // ssave the content
                                if(empty($item_id))
                                {
                                    $sql = "INSERT INTO cashbook_items SET name = ?,book_id = ?, details = ?,units=?,user_id=?";
                                    prepared_statements($sql,'sissi',[$name,$book_id,$details,$units,$user_id]);
                                }else{
                                    $sql = "UPDATE cashbook_items SET name = ?, details = ?,units=?,user_id=? WHERE id = ?";
                                    prepared_statements($sql,'sssii',[$name,$details,$units,$user_id,$item_id]);
                                }
                                $_SESSION['success'] = 'Item Saved';

                            break;
                        case 'newPaymodeSave':
                                $book_id = request('book_id');
                                $details = request('paymode_details');
                                $name = request('paymode_name');
                                $mode_id = request('mode_id') ?? '';

                                // ssave the content
                                if(empty($mode_id))
                                {
                                    $sql = "INSERT INTO cashbook_paymodes SET name = ?,book_id = ?, details = ?";
                                    prepared_statements($sql,'sis',[$name,$book_id,$details]);
                                }else{
                                    $sql = "UPDATE cashbook_paymodes SET name = ?, details = ? WHERE id = ?";
                                    prepared_statements($sql,'ssi',[$name,$details,$mode_id]);
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
                            $member_id = request('member_id') ?? "";

                                if(empty($member_id))
                                {
                                    $password = password_hash($password,PASSWORD_DEFAULT);
                                    $sql = "INSERT INTO cashbook_users SET name = ?,email=?,contact = ?,business_id = ?,password =?,role_id=?";
                                    prepared_statements($sql,'sssisi',[$name,$email,$contact,$business_id,$password,$role]);
                                    $user_id = $server->insert_id;
                                }else{
    
                                    if(!empty($password)):  // update password is not empty
                                        $password = password_hash($password,PASSWORD_DEFAULT);
                                        // update user here, check user books
                                        $sql = "UPDATE cashbook_users SET name = ?,email=?,contact = ?,password =?,role_id= ? WHERE id = ?";
                                        prepared_statements($sql,'ssssii',[$name,$email,$contact,$password,$role,$member_id]);
                                        
                                    else: // leave out password update
                                        $sql = "UPDATE cashbook_users SET name = ?,email=?,contact = ?,role_id= ? WHERE id = ?";
                                        prepared_statements($sql,'sssii',[$name,$email,$contact,$role,$member_id]);
                                    endif;
                                    $user_id = $member_id;  
                                }
                                

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
                            $customer_id = request('customer_id') ?? "";

                                // save the content                                
                                if(!empty($customer_id))
                                {
                                    $sql = "UPDATE cashbook_customers SET name = ?,address=?,contact = ?,book_id = ?,route_id = ?,route_manager_id = ?,user_id = ? WHERE id = ?";
                                    $res = prepared_statements($sql,'sssiiiii',[$name,$address,$contact,$book_id,$route_id,$manager,$user_id,$customer_id]);
                                }else{
                                    $sql = "INSERT INTO cashbook_customers SET name = ?,address=?,contact = ?,book_id = ?,route_id = ?,route_manager_id = ?,user_id = ?";
                                    $res = prepared_statements($sql,'sssiiii',[$name,$address,$contact,$book_id,$route_id,$manager,$user_id]);
                                    $customer_id = $server->insert_id;
                                }

                                // update creditor balance


                                if($res) // prevent duplicate entries
                                {
                                    $route = "INSERT INTO cashbook_customer_route (customer_id, route_id)
                                                SELECT ?, ?
                                                WHERE NOT EXISTS (
                                                    SELECT 1
                                                    FROM cashbook_customer_route
                                                    WHERE customer_id = ?
                                                    AND route_id = ?
                                                )";
                                    prepared_statements($route,'iiii',[$customer_id, $route_id, $customer_id, $route_id]);
                                }
                            break;
                        case 'newCreditorSave':
                                $book_id = request('book_id');
                                $name = request('name');
                                $address = request('address');
                                $contact = request('contact');
                                $user_id = auth()->id;
                                $creditor_id = request('creditor_id') ?? "";
                                $bbf = request('credit_balance') ?? 0;
                                $balance = $bbf;

                                // save the content                                
                                if(!empty($creditor_id))
                                {
                                    $sql = "UPDATE cashbook_creditors SET name = ?,address=?,contact = ?,book_id = ?, user_id = ? WHERE id = ?";
                                    $res = prepared_statements($sql,'sssiii',[$name,$address,$contact,$book_id,$user_id,$creditor_id]);
                                }else{
                                    $sql = "INSERT INTO cashbook_creditors SET name = ?,address=?,contact = ?,book_id = ?,user_id = ?";
                                    $res = prepared_statements($sql,'sssii',[$name,$address,$contact,$book_id,$user_id]);
                                    $creditor_id = $server->insert_id;
                                }
                                
                                if($balance > 0)
                                {
                                    $check = "SELECT * FROM cashbook_creditor_ledger WHERE creditor_id = ?";
                                    $res = prepared_statements($check,'i',[$creditor_id]);
                                    
                                    if($res->num_rows > 0) // update the balance
                                    {
                                        $date = date('Y-m-d');
                                        saveCreditorBalance($creditor_id,$balance,$date);
                                    }else{ // insert initial creditor transaction in the ledger
                                        $stmt = "INSERT INTO  cashbook_creditor_ledger SET creditor_id = ?, credit_amount = ?, details = ?,book_id = ?,created_at=?,user_id=?,balance = ?";
                                        prepared_statements($stmt,'idsisid',[$creditor_id,$credit,$details,$book_id,$date,$user_id,$balance]);
                                        
                                        $date = date('Y-m-d');
                                        saveCreditorBalance($creditor_id,$balance,$date);  
                                    }
                                }
                                $_SESSION['success'] = 'Record saved';

                            break;

                        case 'newCashinSave':
                            // Stock reconciliation is owned by the surrounding cash-in transaction.
                            // Never invoke stockOut()/restoreStockOut() here: they may commit separately.
                            $syncSaleStock = function($book,$transaction,$newItem,$newQty,$actor) use ($server)
                            {
                                $book=(int)$book; 
                                $transaction=(int)$transaction;
                                $newItem=(int)$newItem; 
                                $newQty = round((float)$newQty,2);
                                if($newQty < 0 || ($newQty > 0 && $newItem <= 0)) throw new Exception('Invalid sale stock quantity');
                                $old = prepared_statements(
                                            "SELECT id,item_id,quantity_out FROM cashbook_stocks WHERE book_id = ? AND reference = ? AND transaction_type ='stock_out' FOR UPDATE",
                                            'ii',[$book,$transaction]
                                        );
                                $oldRows=[]; 
                                $affected=[];
                                while($r=$old->fetch_assoc()){
                                    $oldRows[]=$r;
                                    $affected[(int)$r['item_id']]=true;
                                }
                                if($newItem > 0 && $newQty > 0) $affected[$newItem]=true;
                                if(!$affected) return true;
                                $ids = array_keys($affected); sort($ids,SORT_NUMERIC);
                                $opening=[];
                                foreach($ids as $id){
                                    $item = prepared_statements('SELECT id FROM cashbook_items WHERE id=? AND book_id=? FOR UPDATE','ii',[$id,$book]);
                                    if($item->num_rows!==1) throw new Exception('Stock item does not belong to this book: '.$id);
                                    $first = prepared_statements('SELECT balance,quantity_in,quantity_out FROM cashbook_stocks WHERE book_id=? AND item_id=? ORDER BY id LIMIT 1','ii',[$book,$id]);
                                    if($first->num_rows){
                                        $r = $first->fetch_assoc();
                                        $opening[$id] = round((float)$r['balance']-(float)$r['quantity_in'] + (float)$r['quantity_out'],2);
                                    }else{
                                        $opening[$id]=round((float)getItemStockBalance($id),2);
                                    }
                                }
                                prepared_statements("DELETE FROM cashbook_stocks WHERE book_id=? AND reference=? AND transaction_type='stock_out'",'ii',[$book,$transaction]);
                                if($newItem > 0 && $newQty > 0){
                                    prepared_statements("INSERT INTO cashbook_stocks (item_id,book_id,transaction_type,quantity_in,quantity_out,balance,reference,user_id) VALUES (?,?,'stock_out',0,?,0,?,?)",'iidii',[$newItem,$book,$newQty,$transaction,(int)$actor]);
                                }
                                foreach($ids as $id){
                                    $balance=$opening[$id];
                                    $movements=prepared_statements('SELECT id,quantity_in,quantity_out FROM cashbook_stocks WHERE book_id=? AND item_id=? ORDER BY id','ii',[$book,$id]);
                                    while($movement = $movements->fetch_assoc()){
                                        $balance = round($balance+(float)$movement['quantity_in']-(float)$movement['quantity_out'],2);
                                        // Temporary policy: permit negative stock until stock reconciliation is improved.
                                        prepared_statements('UPDATE cashbook_stocks SET balance=? WHERE id=?','di',[$balance,(int)$movement['id']]);
                                    }
                                    if(updateStockItemBalance($id,$balance)===false) throw new Exception('Failed to update item stock balance: '.$id);
                                }
                                return true;
                            };

                            $book_id = (int)request('book_id');
                            $details = trim((string)request('cashin_details'));
                            $category_id = (int)request('category_id');
                            $amount = round((float)str_replace(',','',(string)request('inamount')),2);
                            $payment_mode = (int)(($_POST['paymode_id'] ?? 0));
                            $date = trim((string)request('created_at'));
                            if($date === ''){
                                throw new Exception('Transaction date is required. Please select a date.');
                            }
                            $user_id = (int)auth()->id;
                            $customer_id = (int)(($_POST['customer_id'] ?? 0));
                            $item_id = (int)(($_POST['item_id'] ?? 0));
                            $qty = (float)(($_POST['quantity'] ?? 0));
                            $type = trim((string)request('transaction_type'));
                            $invoice_id = (int)(($_POST['invoice_id'] ?? 0));
                            $creditor_id = (int)(($_POST['creditor_id'] ?? 0));

                            try{
                                if($book_id <= 0 || $category_id <= 0 || $amount <= 0){
                                    throw new Exception('Enter a valid amount, category and business book');
                                }
                                if(!in_array($type,['cash_sale','credit_sale','payment','other_income','creditorInjection'],true)){
                                    throw new Exception('Invalid transaction type');
                                }
                                if(in_array($type,['credit_sale','payment'],true) && $customer_id <= 0){
                                    throw new Exception('Select a customer for this transaction');
                                }
                                if($type === 'creditorInjection' && $creditor_id <= 0){
                                    throw new Exception('Select a creditor');
                                }
                                if($type !== 'credit_sale' && $payment_mode <= 0){
                                    throw new Exception('Select a payment mode');
                                }
                                if($invoice_id > 0 && $type !== 'payment'){
                                    throw new Exception('Only customer payments can be linked to an invoice');
                                }
                                if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || !checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))){
                                    throw new Exception('Invalid transaction date');
                                }

                                mysqli_begin_transaction($server);

                                if($customer_id > 0){
                                    $check = prepared_statements('SELECT id FROM cashbook_customers WHERE id = ? AND book_id = ?','ii',[$customer_id,$book_id]);
                                    if(!$check || $check->num_rows === 0) throw new Exception('Customer does not belong to this book');
                                }
                                if($creditor_id > 0){
                                    $check = prepared_statements('SELECT id FROM cashbook_creditors WHERE id = ? AND book_id = ?','ii',[$creditor_id,$book_id]);
                                    if(!$check || $check->num_rows === 0) throw new Exception('Creditor does not belong to this book');
                                }
                                if($item_id > 0){
                                    $check = prepared_statements('SELECT id FROM cashbook_items WHERE id = ? AND book_id = ?','ii',[$item_id,$book_id]);
                                    if(!$check || $check->num_rows === 0) throw new Exception('Item does not belong to this book');
                                }
                                if($invoice_id > 0){
                                    $check = prepared_statements('SELECT id,customer_id,balance,invoice_no FROM cashbook_invoices WHERE id = ? AND book_id = ? FOR UPDATE','ii',[$invoice_id,$book_id]);
                                    if(!$check || $check->num_rows === 0) throw new Exception('Invoice not found');
                                    $invoice = $check->fetch_assoc();
                                    if((int)$invoice['customer_id'] !== $customer_id) throw new Exception('Invoice belongs to a different customer');
                                    if($amount > round((float)$invoice['balance'],2)) throw new Exception('Payment exceeds invoice balance');
                                    $details = $invoice['invoice_no'];
                                }

                                $credit = 0;
                                $debit = 0;
                                $creditable = 0;

                                switch($type){
                                    case 'cash_sale':
                                            $credit = $amount;
                                            $debit = $amount;
                                            $creditable = $amount;
                                        break;
                                    case 'credit_sale':
                                            $debit = $amount;
                                            $payment_mode = 0;
                                        break;
                                    case 'payment':
                                    case 'other_income':
                                    case 'creditorInjection':
                                            $credit = $amount;
                                            $creditable = $amount;
                                        break;
                                }

                                // Credit sales are receivables, not cash receipts.
                                $sql = "INSERT INTO cashbook_transactions SET credit_amount = ?,debit_amount = ?,book_id = ?,details = ?,category_id = ?,paymode_id = NULLIF(?,0),created_at = ?,user_id = ?,type = ?,customer_id = NULLIF(?,0),item_id = NULLIF(?,0),quantity = ?,invoice_id = NULLIF(?,0),creditor_id = NULLIF(?,0)";
                                $res = prepared_statements($sql,'ddisiisisiidii',[$credit,$debit,$book_id,$details,$category_id,$payment_mode,$date,$user_id,$type,$customer_id,$item_id,$qty,$invoice_id,$creditor_id]);
                                if($res === false) throw new Exception('Failed to save main transaction');
                                $trans_id = (int)mysqli_insert_id($server);
                                if($trans_id <= 0) throw new Exception('Could not obtain transaction ID');

                                if($invoice_id > 0){
                                    $sql = 'UPDATE cashbook_invoices SET paid_amount = COALESCE(paid_amount,0) + ?,balance = COALESCE(balance,0) - ? WHERE id = ? AND book_id = ? AND balance >= ?';
                                    $res = prepared_statements($sql,'ddiid',[$amount,$amount,$invoice_id,$book_id,$amount]);
                                    if($res === false) throw new Exception('Failed to update invoice payment');
                                    // The invoice row was locked and its balance validated above.
                                }

                                if($credit > 0){
                                    $sql = "INSERT INTO cashbook_cashins SET type = ?,customer_id = NULLIF(?,0),amount = ?,category_id = ?,details = ?,book_id = ?,paymode_id = ?,transaction_id = ?,created_at = ?,user_id = ?,item_id = NULLIF(?,0),quantity = ?,creditor_id = NULLIF(?,0)";
                                    $res = prepared_statements($sql,'sidisiiisiidi',[$type,$customer_id,$amount,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$creditor_id]);
                                    if($res === false) throw new Exception('Failed to save cash-in');
                                }

                                if($item_id > 0 && $qty > 0 && in_array($type,['cash_sale','credit_sale'],true)){
                                    $syncSaleStock($book_id,$trans_id,$item_id,$qty,$user_id);
                                }
                                
                                // error_log("CUSTOMER LEDGER DEBUG: transaction={$trans_id}, customer={$customer_id}, type={$type}, credit={$creditable}, debit={$debit}");
                                if($customer_id > 0){
                                    $res = customerLedgerUpdate($customer_id,$creditable,$debit,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$type);
                                    if($res === false) throw new Exception('Failed to update customer ledger');
                                }
                                if($creditor_id > 0){
                                    $res = creditorLedgerUpdate($creditor_id,$creditable,$debit,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$type);
                                    if($res === false) throw new Exception('Failed to update creditor ledger');
                                }

                                mysqli_commit($server);
                                $_SESSION['success'] = 'Transaction saved successfully';
                            }catch(Throwable $e){
                                mysqli_rollback($server);
                                // error_log('newCashinSave: '.$e->getMessage());
                                $_SESSION['error'] = $e->getMessage();
                            }
                            break;
                        case 'editCashinSave':
                                $transactionId = (int) request('transaction_id');
                                $transactionStarted = false;
                                $updateStage = 'reading submitted fields';

                                try {
                                    // 1. Read and validate the submitted transaction fields.
                                    $details       = trim((string) request('cashin_details'));
                                    $categoryId    = (int) request('category_id');
                                    $amount        = round((float) str_replace(',', '', (string) request('inamount')), 2);
                                    $paymentMode   = (int) ($_POST['paymode_id'] ?? 0);
                                    $submittedDate = trim((string) request('created_at'));
                                    $userId        = (int) auth()->id;
                                    $customerId    = (int) ($_POST['customer_id'] ?? 0);
                                    $creditorId    = (int) ($_POST['creditor_id'] ?? 0);
                                    $itemId        = (int) ($_POST['item_id'] ?? 0);
                                    $quantity      = round((float) ($_POST['quantity'] ?? 0), 2);
                                    $type          = trim((string) request('transaction_type'));
                                    $invoiceId     = (int) ($_POST['invoice_id'] ?? 0);

                                    if ($transactionId <= 0 || $categoryId <= 0 || $amount <= 0 || $quantity < 0) {
                                        throw new RuntimeException('Invalid transaction, category, amount or quantity.');
                                    }
                                    if (!in_array($type, ['cash_sale', 'credit_sale', 'payment', 'other_income', 'creditorInjection'], true)) {
                                        throw new RuntimeException('Invalid transaction type.');
                                    }
                                    if (in_array($type, ['credit_sale', 'payment'], true) && $customerId <= 0) {
                                        throw new RuntimeException('Please select a customer.');
                                    }
                                    if ($type === 'creditorInjection' && $creditorId <= 0) {
                                        throw new RuntimeException('Please select a creditor.');
                                    }
                                    if ($type !== 'credit_sale' && $paymentMode <= 0) {
                                        throw new RuntimeException('Please select a payment mode.');
                                    }
                                    if ($invoiceId > 0 && $type !== 'payment') {
                                        throw new RuntimeException('Only customer payments can be linked to invoices.');
                                    }

                                    // 2. All direct queries use the same mysqli connection and transaction.
                                    $run = function (string $sql, string $types = '', array $params = []) use ($server) {
                                        $placeholderCount = substr_count($sql, '?');
                                        $typeCount = strlen($types);
                                        $parameterCount = count($params);

                                        if ($placeholderCount !== $parameterCount || $typeCount !== $parameterCount) {
                                            throw new RuntimeException(
                                                'SQL parameter mismatch: placeholders=' . $placeholderCount .
                                                ', types=' . $typeCount . ', values=' . $parameterCount .
                                                ' | SQL: ' . $sql
                                            );
                                        }

                                        $statement = mysqli_prepare($server, $sql);
                                        if (!$statement) {
                                            throw new RuntimeException('SQL prepare failed: ' . mysqli_error($server) . ' | SQL: ' . $sql);
                                        }

                                        if ($parameterCount > 0 && !mysqli_stmt_bind_param($statement, $types, ...$params)) {
                                            throw new RuntimeException('SQL binding failed: ' . mysqli_stmt_error($statement) . ' | SQL: ' . $sql);
                                        }

                                        if (!mysqli_stmt_execute($statement)) {
                                            throw new RuntimeException('SQL execution failed: ' . mysqli_stmt_error($statement) . ' | SQL: ' . $sql);
                                        }

                                        return $statement;
                                    };

                                    $one = function (string $sql, string $types = '', array $params = []) use ($run) {
                                        $result = mysqli_stmt_get_result($run($sql, $types, $params));
                                        if ($result === false) {
                                            throw new RuntimeException('Could not read SQL result.');
                                        }
                                        return mysqli_fetch_assoc($result) ?: null;
                                    };

                                    $updateStage = 'begin database transaction';
                                    mysqli_begin_transaction($server);
                                    $transactionStarted = true;

                                    $updateStage = 'load original transaction';
                                    // 3. Lock the original transaction and preserve its original time of day.
                                    $old = $one('SELECT * FROM cashbook_transactions WHERE id=? FOR UPDATE', 'i', [$transactionId]);
                                    if (!$old) {
                                        throw new RuntimeException('Transaction could not be found.');
                                    }
                                    $bookId       = (int) $old['book_id'];
                                    $oldCustomer  = (int) $old['customer_id'];
                                    $oldCreditor  = (int) $old['creditor_id'];
                                    $oldInvoice   = (int) $old['invoice_id'];
                                    $oldType      = (string) $old['type'];
                                    $oldAmount    = (float) $old['credit_amount'];
                                    $originalTime = substr((string) ($old['created_at'] ?? ''), 11, 8);
                                    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $originalTime)) {
                                        $originalTime = '00:00:00';
                                    }

                                    // HTML date inputs submit Y-m-d. datetime-local inputs submit Y-m-dTH:i.
                                    $dateInput = str_replace('T', ' ', $submittedDate);
                                    $format = null;
                                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
                                        $format = '!Y-m-d';
                                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dateInput)) {
                                        $format = '!Y-m-d H:i';
                                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateInput)) {
                                        $format = '!Y-m-d H:i:s';
                                    }
                                    $parsedDate = $format ? DateTimeImmutable::createFromFormat($format, $dateInput) : false;
                                    if (!$parsedDate || $parsedDate->format(substr($format, 1)) !== $dateInput) {
                                        throw new RuntimeException('Invalid transaction date: ' . $submittedDate);
                                    }
                                    $dateOnly = $parsedDate->format('Y-m-d');
                                    $transactionDate = ($format === '!Y-m-d')
                                        ? $dateOnly . ' ' . $originalTime
                                        : $parsedDate->format('Y-m-d H:i:s');

                                    $updateStage = 'validate related records';
                                    // 4. Validate linked records belong to the original business book.
                                    if ($customerId > 0 && !$one('SELECT id FROM cashbook_customers WHERE id=? AND book_id=?', 'ii', [$customerId, $bookId])) {
                                        throw new RuntimeException('Customer does not belong to this book.');
                                    }
                                    if ($creditorId > 0 && !$one('SELECT id FROM cashbook_creditors WHERE id=? AND book_id=?', 'ii', [$creditorId, $bookId])) {
                                        throw new RuntimeException('Creditor does not belong to this book.');
                                    }
                                    if ($itemId > 0 && !$one('SELECT id FROM cashbook_items WHERE id=? AND book_id=?', 'ii', [$itemId, $bookId])) {
                                        throw new RuntimeException('Item does not belong to this book.');
                                    }

                                    $updateStage = 'calculate transaction amounts';
                                    // 5. Determine transaction and customer-ledger amounts.
                                    $credit = 0.0;
                                    $debit = 0.0;
                                    $customerCredit = 0.0;
                                    if ($type === 'cash_sale') {
                                        $credit = $amount;
                                        $debit = $amount;
                                        $customerCredit = $amount;
                                    } elseif ($type === 'credit_sale') {
                                        $debit = $amount;
                                        $paymentMode = 0;
                                    } else {
                                        $credit = $amount;
                                        $customerCredit = $amount;
                                    }

                                    $updateStage = 'reconcile invoices';
                                    // 6. Reconcile linked invoice payments only when an invoice is attached.
                                    if ($oldInvoice > 0) {
                                        if ($oldType !== 'payment') {
                                            throw new RuntimeException('Original invoice linkage has an unexpected transaction type.');
                                        }
                                        $run(
                                            'UPDATE cashbook_invoices SET paid_amount=COALESCE(paid_amount,0)-?, balance=COALESCE(balance,0)+? WHERE id=? AND book_id=?',
                                            'ddii', [$oldAmount, $oldAmount, $oldInvoice, $bookId]
                                        );
                                    }
                                    if ($invoiceId > 0) {
                                        $invoice = $one(
                                            'SELECT id,customer_id,balance,invoice_no FROM cashbook_invoices WHERE id=? AND book_id=? FOR UPDATE',
                                            'ii', [$invoiceId, $bookId]
                                        );
                                        if (!$invoice) {
                                            throw new RuntimeException('Invoice not found.');
                                        }
                                        if ((int) $invoice['customer_id'] !== $customerId) {
                                            throw new RuntimeException('Invoice belongs to another customer.');
                                        }
                                        if ((float) $invoice['balance'] + 0.001 < $amount) {
                                            throw new RuntimeException('Payment exceeds the available invoice balance.');
                                        }
                                        $details = (string) $invoice['invoice_no'];
                                        $run(
                                            'UPDATE cashbook_invoices SET paid_amount=COALESCE(paid_amount,0)+?, balance=COALESCE(balance,0)-? WHERE id=? AND book_id=?',
                                            'ddii', [$amount, $amount, $invoiceId, $bookId]
                                        );
                                    }

                                    $updateStage = 'audit and update transaction';
                                    // 7. Record the edit before overwriting the transaction.
                                    $updateStage = 'trackTransactionEdits()';
                                    trackTransactionEdits($transactionId, 'edit');
                                    $updateStage = 'update cashbook_transactions';
                                    $run(
                                        'UPDATE cashbook_transactions SET type=?, credit_amount=?, debit_amount=?, details=?, category_id=?, paymode_id=NULLIF(?,0), created_at=?, user_id=?, customer_id=NULLIF(?,0), creditor_id=NULLIF(?,0), item_id=NULLIF(?,0), quantity=?, invoice_id=NULLIF(?,0) WHERE id=? AND book_id=?',
                                        'sddsiisiiiidiii',
                                        [$type, $credit, $debit, $details, $categoryId, $paymentMode, $transactionDate, $userId, $customerId, $creditorId, $itemId, $quantity, $invoiceId, $transactionId, $bookId]
                                    );

                                    $updateStage = 'synchronize cash-in receipt';
                                    // 8. Synchronize cash receipts (credit sales have no cash receipt).
                                    $run('DELETE FROM cashbook_cashins WHERE transaction_id=? AND book_id=?', 'ii', [$transactionId, $bookId]);
                                    if ($credit > 0) {
                                        $run(
                                            'INSERT INTO cashbook_cashins (type,customer_id,amount,category_id,details,book_id,paymode_id,transaction_id,created_at,user_id,item_id,quantity,creditor_id) VALUES (?,NULLIF(?,0),?,?,?,?,?,?,?, ?,NULLIF(?,0),?,NULLIF(?,0))',
                                            'sidisiiisiidi',
                                            [$type, $customerId, $amount, $categoryId, $details, $bookId, $paymentMode, $transactionId, $transactionDate, $userId, $itemId, $quantity, $creditorId]
                                        );
                                    }

                                    $updateStage = 'reconcile stock';
                                    // 9. Reconcile sale stock within this SAME transaction (no independent commits).
                                    $saleItem = in_array($type, ['cash_sale', 'credit_sale'], true) ? $itemId : 0;
                                    $saleQuantity = in_array($type, ['cash_sale', 'credit_sale'], true) ? $quantity : 0.0;
                                    if ($saleQuantity < 0 || ($saleQuantity > 0 && $saleItem <= 0)) {
                                        throw new RuntimeException('Invalid sale stock quantity or item.');
                                    }
                                    $oldStock = $run(
                                        "SELECT id,item_id,quantity_out FROM cashbook_stocks WHERE book_id=? AND reference=? AND transaction_type='stock_out' FOR UPDATE",
                                        'ii', [$bookId, $transactionId]
                                    );
                                    $stockRows = mysqli_stmt_get_result($oldStock);
                                    $affectedItems = [];
                                    while ($stockRow = mysqli_fetch_assoc($stockRows)) {
                                        $affectedItems[(int) $stockRow['item_id']] = true;
                                    }
                                    if ($saleItem > 0 && $saleQuantity > 0) {
                                        $affectedItems[$saleItem] = true;
                                    }
                                    $itemIds = array_keys($affectedItems);
                                    sort($itemIds, SORT_NUMERIC);
                                    $openingBalances = [];
                                    foreach ($itemIds as $stockItemId) {
                                        if (!$one('SELECT id FROM cashbook_items WHERE id=? AND book_id=? FOR UPDATE', 'ii', [$stockItemId, $bookId])) {
                                            throw new RuntimeException('Stock item does not belong to this book: ' . $stockItemId);
                                        }
                                        $first = $one(
                                            'SELECT balance,quantity_in,quantity_out FROM cashbook_stocks WHERE book_id=? AND item_id=? ORDER BY id LIMIT 1',
                                            'ii', [$bookId, $stockItemId]
                                        );
                                        $openingBalances[$stockItemId] = $first
                                            ? round((float) $first['balance'] - (float) $first['quantity_in'] + (float) $first['quantity_out'], 2)
                                            : round((float) getItemStockBalance($stockItemId), 2);
                                    }
                                    $run(
                                        "DELETE FROM cashbook_stocks WHERE book_id=? AND reference=? AND transaction_type='stock_out'",
                                        'ii', [$bookId, $transactionId]
                                    );
                                    if ($saleItem > 0 && $saleQuantity > 0) {
                                        $run(
                                            "INSERT INTO cashbook_stocks (item_id,book_id,transaction_type,quantity_in,quantity_out,balance,reference,user_id) VALUES (?,?,'stock_out',0,?,0,?,?)",
                                            'iidii', [$saleItem, $bookId, $saleQuantity, $transactionId, $userId]
                                        );
                                    }
                                    foreach ($itemIds as $stockItemId) {
                                        $runningStock = $openingBalances[$stockItemId];
                                        $movements = mysqli_stmt_get_result($run(
                                            'SELECT id,quantity_in,quantity_out FROM cashbook_stocks WHERE book_id=? AND item_id=? ORDER BY id',
                                            'ii', [$bookId, $stockItemId]
                                        ));
                                        while ($movement = mysqli_fetch_assoc($movements)) {
                                            $runningStock = round($runningStock + (float) $movement['quantity_in'] - (float) $movement['quantity_out'], 2);
                                            // Temporary policy: retain negative running balances without rejecting the transaction.
                                            $run('UPDATE cashbook_stocks SET balance=? WHERE id=?', 'di', [$runningStock, (int) $movement['id']]);
                                        }
                                        $updateStage = 'updateStockItemBalance()';
                                        if (updateStockItemBalance($stockItemId, $runningStock) === false) {
                                            throw new RuntimeException('Failed to update stock balance for item ' . $stockItemId);
                                        }
                                    }

                                    $updateStage = 'synchronize customer ledger';
                                    // 10. Rebuild customer balances chronologically after updating the ledger date.
                                    $rebuildCustomer = function (int $id) use ($run, $dateOnly) {
                                        $rows = mysqli_stmt_get_result($run(
                                            'SELECT id,credit_amount,debit_amount FROM cashbook_customer_ledger WHERE customer_id=? ORDER BY created_at,id',
                                            'i', [$id]
                                        ));
                                        $balance = 0.0;
                                        while ($entry = mysqli_fetch_assoc($rows)) {
                                            $balance = round($balance + (float) $entry['debit_amount'] - (float) $entry['credit_amount'], 2);
                                            $run('UPDATE cashbook_customer_ledger SET balance=? WHERE id=?', 'di', [$balance, (int) $entry['id']]);
                                        }
                                        if (!function_exists('saveCustomerBalance') || saveCustomerBalance($id, $balance, $dateOnly) === false) {
                                            throw new RuntimeException('Could not update customer balance summary.');
                                        }
                                    };
                                    if ($oldCustomer > 0 && $oldCustomer !== $customerId) {
                                        $run('DELETE FROM cashbook_customer_ledger WHERE transaction_id=? AND customer_id=?', 'ii', [$transactionId, $oldCustomer]);
                                        $rebuildCustomer($oldCustomer);
                                    }
                                    if ($customerId > 0) {
                                        $updateStage = 'customerLedgerUpdate()';
                                        $result = customerLedgerUpdate($customerId, $customerCredit, $debit, $categoryId, $details, $bookId, $paymentMode, $transactionId, $transactionDate, $userId, $itemId, $quantity, $type);
                                        if ($result === false) {
                                            throw new RuntimeException('Customer ledger update failed.');
                                        }
                                        $run(
                                            'UPDATE cashbook_customer_ledger SET created_at=? WHERE transaction_id=? AND customer_id=?',
                                            'sii', [$transactionDate, $transactionId, $customerId]
                                        );
                                        $entry = $one(
                                            'SELECT credit_amount,debit_amount FROM cashbook_customer_ledger WHERE transaction_id=? AND customer_id=? LIMIT 1',
                                            'ii', [$transactionId, $customerId]
                                        );
                                        if (!$entry || abs((float) $entry['credit_amount'] - $customerCredit) > 0.001 || abs((float) $entry['debit_amount'] - $debit) > 0.001) {
                                            throw new RuntimeException('Customer ledger amounts do not match transaction.');
                                        }
                                        $rebuildCustomer($customerId);
                                    }

                                    $updateStage = 'synchronize creditor ledger';
                                    // 11. Synchronize creditor ledger, including the new transaction date.
                                    if ($oldCreditor > 0 && $oldCreditor !== $creditorId) {
                                        $run('DELETE FROM cashbook_creditor_ledger WHERE transaction_id=? AND creditor_id=?', 'ii', [$transactionId, $oldCreditor]);
                                        $updateStage = 'recalculateCreditorLedger()';
                                        if (recalculateCreditorLedger($oldCreditor) === false) {
                                            throw new RuntimeException('Could not rebuild previous creditor ledger.');
                                        }
                                    }
                                    if ($creditorId > 0) {
                                        $updateStage = 'creditorLedgerUpdate()';
                                        if (creditorLedgerUpdate($creditorId, 0, $amount, $categoryId, $details, $bookId, $paymentMode, $transactionId, $transactionDate, $userId, $itemId, $quantity, $type) === false) {
                                            throw new RuntimeException('Creditor ledger update failed.');
                                        }
                                        $run(
                                            'UPDATE cashbook_creditor_ledger SET created_at=? WHERE transaction_id=? AND creditor_id=?',
                                            'sii', [$transactionDate, $transactionId, $creditorId]
                                        );
                                        $updateStage = 'recalculateCreditorLedger()';
                                        if (recalculateCreditorLedger($creditorId) === false) {
                                            throw new RuntimeException('Could not rebuild creditor ledger.');
                                        }
                                    }

                                    $updateStage = 'commit transaction';
                                    mysqli_commit($server);
                                    $transactionStarted = false;
                                    $_SESSION['success'] = 'Transaction updated successfully.';
                                } catch (Throwable $exception) {
                                    if ($transactionStarted) {
                                        mysqli_rollback($server);
                                    }
                                    error_log('editCashinSave #' . $transactionId . ' | Stage: ' . $updateStage . ' | ' . $exception->getMessage() . ' | ' . $exception->getFile() . ':' . $exception->getLine());
                                    // Use this detailed message only while testing; restore a generic message in production.
                                    $_SESSION['error'] = 'Transaction update failed at ' . $updateStage . ': ' . $exception->getMessage();
                                }
                                break;

                        case 'newCashoutSave':
                                $book_id = request('book_id');
                                $details = request('cashout_details');
                                $category_id = request('category_id');
                                $amount = request('outamount');
                                $payment_mode = request('paymode_id');
                                $date = request('created_at');
                                $user_id = auth()->id;
                                $customer_id = request('customer_id');
                                $type = request('expense_type');
                                $creditor_id = request('creditor_id') ?? "";

                                // get items for purchase if available
                                $item_id = isset($_POST['item_id']) ? request('item_id') : "";
                                $qty = isset($_POST['quantity']) ? request('quantity') : "";
                                $purchase_rate = isset($_POST['purchase_rate']) ? request('purchase_rate') : "";
                                $suplier_id = isset($_POST['suplier_id']) ? request('suplier_id') : "";

                                // save the content
                                $sql = "INSERT INTO cashbook_transactions SET item_id = ?, quantity = ?, rate=?, debit_amount = ?,book_id = ?, details = ?,category_id=?,paymode_id = ?,created_at = ?,user_id=?,type=?,customer_id=?,creditor_id = ?";
                                $res = prepared_statements($sql,'iiiiisiisisii',[$item_id,$qty,$purchase_rate,$amount,$book_id,$details,$category_id,$payment_mode,$date,$user_id,$type,$customer_id,$creditor_id]);
                                $trans_id = $server->insert_id;

                                 // check if the transaction is a purchase and save to purchases table
                                if($type == 'purchase' && !empty($item_id) && !empty($qty) && !empty($purchase_rate))
                                {
                                    $stmt = "INSERT INTO cashbook_purchases SET transaction_id = ?, item_id = ?, quantity = ?, unit_price = ?, total = ?, book_id = ?, created_at = ?, user_id = ?";
                                    prepared_statements($stmt,'iiiidisi',[$trans_id,$item_id,$qty,$purchase_rate,$amount,$book_id,$date,$user_id]);
                                }

                                // if its a customer borrowing, put the record on their page as a debit
                                if($type == 'borrowing' && !empty($customer_id))
                                {
                                    // update customer ledger
                                    customerLedgerUpdate($customer_id,0,$amount,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$type);
                                }
                                
                                if($type == 'creditorPayment' && !empty($creditor_id))
                                {
                                    // update customer ledger
                                    CreditorLedgerUpdate($creditor_id,0,$amount,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$type);
                                }

                                $stmt = "INSERT INTO  cashbook_cashouts SET item_id = ?, type=?,quantity=?,rate=?,amount = ?, category_id = ?, details = ?,book_id = ?,transaction_id = ?,paymode_id = ?,created_at = ?,user_id=?";
                                prepared_statements($stmt,'isiiiisiiisi',[$item_id,$type,$qty,$purchase_rate,$amount,$category_id,$details,$book_id,$trans_id,$payment_mode,$date,$user_id]);
                                $_SESSION['success'] = 'Transaction Saved';

                            break;

                        case 'editCashoutSave':
                            $cashoutTransactionStarted = false;
                            try {
                            $details      = request('cashout_details');
                            $category_id  = request('category_id');
                            $amount       = (float) request('outamount');
                            $payment_mode = request('paymode_id');
                            $transid      = (int) request('transaction_id');
                            $date         = request('created_at');
                            $user_id      = auth()->id;

                            $customer_id = request('customer_id');
                            $creditor_id = request('creditor_id');
                            $type        = request('expense_type');

                            $item_id = isset($_POST['item_id'])
                                ? request('item_id')
                                : "";

                            $qty = isset($_POST['quantity'])
                                ? (float) request('quantity')
                                : 0;

                            $purchase_rate = isset($_POST['purchase_rate'])
                                ? (float) request('purchase_rate')
                                : 0;

                            $suplier_id = isset($_POST['suplier_id'])
                                ? request('suplier_id')
                                : "";

                            if ($transid <= 0 || (float)$amount <= 0 || (int)$category_id <= 0) {
                                throw new Exception('Invalid transaction, category or amount.');
                            }
                            if ($qty < 0 || $purchase_rate < 0) {
                                throw new Exception('Quantity and purchase rate cannot be negative.');
                            }

                            mysqli_begin_transaction($server);
                            $cashoutTransactionStarted = true;

                            $transaction = transactionFind($transid);

                            if(!$transaction)
                            {
                                throw new Exception('Transaction could not be found.');
                            }

                            $book_id = (int) $transaction->book_id;

                            // Accept HTML date/datetime-local and common display formats.
                            // Date-only edits retain the original transaction time.
                            $submittedDate = trim((string)$date);
                            $originalDate  = (string)($transaction->created_at ?? '');
                            $originalTime  = preg_match('/\b(\d{2}:\d{2}:\d{2})\b/', $originalDate, $timeMatch)
                                ? $timeMatch[1] : '00:00:00';

                            $dateFormats = [
                                'Y-m-d H:i:s', 'Y-m-d H:i',
                                'Y-m-d\TH:i:s', 'Y-m-d\TH:i',
                                'Y-m-d', 'd/m/Y', 'd-m-Y',
                                'd/m/Y H:i', 'd/m/Y H:i:s',
                                'd-m-Y H:i', 'd-m-Y H:i:s'
                            ];
                            $parsedDate = null;
                            $dateOnly = false;

                            foreach ($dateFormats as $format) {
                                $candidate = DateTimeImmutable::createFromFormat('!' . $format, $submittedDate);
                                if ($candidate && $candidate->format($format) === $submittedDate) {
                                    $parsedDate = $candidate;
                                    $dateOnly = !str_contains($format, 'H:i');
                                    break;
                                }
                            }

                            if (!$parsedDate) {
                                throw new Exception('Invalid transaction date: ' . $submittedDate .
                                    '. Use YYYY-MM-DD or a valid date/time value.');
                            }

                            $date = $parsedDate->format('Y-m-d') . ' ' .
                                ($dateOnly ? $originalTime : $parsedDate->format('H:i:s'));

                            // Validate bindings before passing them to the project's SQL helper.
                            $cashoutQuery = function ($sql, $types, $values) {
                                $placeholderCount = substr_count($sql, '?');
                                if ($placeholderCount !== count($values) || strlen($types) !== count($values)) {
                                    throw new Exception('Cash-out SQL parameter mismatch: ' . strlen($types) .
                                        ' types, ' . count($values) . ' values, ' . $placeholderCount .
                                        ' placeholders. SQL: ' . $sql);
                                }
                                $result = prepared_statements($sql, $types, $values);
                                if ($result === false) {
                                    throw new Exception('Cash-out SQL failed: ' . $sql);
                                }
                                return $result;
                            };

                            $old_item_id = !empty($transaction->item_id)
                                ? (int) $transaction->item_id
                                : 0;

                            $old_quantity = isset($transaction->quantity)
                                ? (float) $transaction->quantity
                                : 0;

                            $old_type = isset($transaction->type)
                                ? $transaction->type
                                : "";

                            $old_creditor_id     = 0;
                            $old_creditor_credit = 0;
                            $old_creditor_debit  = 0;

                            $oldCreditorSql = "
                                SELECT
                                    creditor_id,
                                    credit_amount,
                                    debit_amount
                                FROM cashbook_creditor_ledger
                                WHERE transaction_id = ?
                                LIMIT 1
                            ";

                            $oldCreditorRes = $cashoutQuery(
                                $oldCreditorSql,
                                'i',
                                [$transid]
                            );

                            if(
                                $oldCreditorRes &&
                                $oldCreditorRes->num_rows > 0
                            )
                            {
                                $oldCreditorRow =
                                    $oldCreditorRes->fetch_assoc();

                                $old_creditor_id =
                                    (int) $oldCreditorRow['creditor_id'];

                                $old_creditor_credit =
                                    (float) $oldCreditorRow['credit_amount'];

                                $old_creditor_debit =
                                    (float) $oldCreditorRow['debit_amount'];
                            }

                            $customer_id = !empty($customer_id)
                                ? (int) $customer_id
                                : 0;

                            $creditor_id = !empty($creditor_id)
                                ? (int) $creditor_id
                                : 0;

                            $item_id = !empty($item_id)
                                ? (int) $item_id
                                : 0;

                            $suplier_id = !empty($suplier_id)
                                ? (int) $suplier_id
                                : 0;

                            trackTransactionEdits($transid, 'edit');

                            $sql = "UPDATE cashbook_transactions SET
                                    item_id = ?,
                                    quantity = ?,
                                    rate = ?,
                                    type = ?,
                                    debit_amount = ?,
                                    details = ?,
                                    category_id = ?,
                                    paymode_id = ?,
                                    user_id = ?,
                                    customer_id = ?,
                                    creditor_id = ?,
                                    created_at = ?
                                WHERE id = ? AND book_id = ?
                            ";

                            $res = $cashoutQuery($sql,'iddsdsiiiiisii',
                                [
                                    $item_id,
                                    $qty,
                                    $purchase_rate,
                                    $type,
                                    $amount,
                                    $details,
                                    $category_id,
                                    $payment_mode,
                                    $user_id,
                                    $customer_id,
                                    $creditor_id,
                                    $date,
                                    $transid,
                                    $book_id
                                ]
                            );

                            if(
                                $type === 'purchase' &&
                                $item_id > 0 &&
                                $qty > 0 &&
                                $purchase_rate > 0
                            )
                            {
                                $purchase_check = "
                                    SELECT id
                                    FROM cashbook_purchases
                                    WHERE transaction_id = ?
                                    LIMIT 1
                                ";

                                $purchase = $cashoutQuery(
                                    $purchase_check,
                                    'i',
                                    [$transid]
                                );

                                if(
                                    $purchase &&
                                    $purchase->num_rows > 0
                                )
                                {
                                    $stmt = "
                                        UPDATE cashbook_purchases SET

                                            item_id = ?,
                                            quantity = ?,
                                            unit_price = ?,
                                            total = ?,
                                            book_id = ?,
                                            created_at = ?,
                                            user_id = ?

                                        WHERE transaction_id = ?
                                    ";

                                    $cashoutQuery(
                                        $stmt,
                                        'idddisii',
                                        [
                                            $item_id,
                                            $qty,
                                            $purchase_rate,
                                            $amount,
                                            $book_id,
                                            $date,
                                            $user_id,
                                            $transid
                                        ]
                                    );
                                }

                                else
                                {
                                    $stmt = "
                                        INSERT INTO cashbook_purchases SET

                                            transaction_id = ?,
                                            item_id = ?,
                                            quantity = ?,
                                            unit_price = ?,
                                            total = ?,
                                            book_id = ?,
                                            created_at = ?,
                                            user_id = ?
                                    ";

                                    $cashoutQuery(
                                        $stmt,
                                        'iidddisi',
                                        [
                                            $transid,
                                            $item_id,
                                            $qty,
                                            $purchase_rate,
                                            $amount,
                                            $book_id,
                                            $date,
                                            $user_id
                                        ]
                                    );
                                }
                            }

                            elseif($old_type === 'purchase' || $type === 'purchase')
                            {
                                $deletePurchase = "
                                    DELETE FROM cashbook_purchases
                                    WHERE transaction_id = ?
                                ";

                                $cashoutQuery(
                                    $deletePurchase,
                                    'i',
                                    [$transid]
                                );
                            }

                            $customerLedgerResult = $cashoutQuery(
                                'SELECT customer_id FROM cashbook_customer_ledger WHERE transaction_id=? AND book_id=? LIMIT 1',
                                'ii', [$transid, $book_id]
                            );
                            $oldCustomerLedger = $customerLedgerResult->fetch_assoc();
                            $oldCustomerLedgerId = (int)($oldCustomerLedger['customer_id'] ?? 0);
                            $requiresCustomerLedger = in_array($type, ['borrowing', 'credit_sale'], true) && $customer_id > 0;
                            if ($oldCustomerLedgerId > 0 && (!$requiresCustomerLedger || $oldCustomerLedgerId !== $customer_id)) {
                                $cashoutQuery(
                                    'DELETE FROM cashbook_customer_ledger WHERE transaction_id=? AND book_id=?',
                                    'ii', [$transid, $book_id]
                                );
                                rebuildCustomerLedgerBalances($oldCustomerLedgerId, $book_id);
                            }

                            if(
                                (
                                    $type === 'borrowing' ||
                                    $type === 'credit_sale'
                                ) &&
                                $customer_id > 0
                            )
                            {
                                customerLedgerUpdate(
                                    $customer_id,
                                    0,
                                    $amount,
                                    $category_id,
                                    $details,
                                    $book_id,
                                    $payment_mode,
                                    $transid,
                                    $date,
                                    $user_id,
                                    $item_id,
                                    $qty,
                                    $type
                                );
                            }

                            if(
                                $type === 'creditorPayment' &&
                                $creditor_id > 0 &&
                                $old_creditor_id > 0 &&
                                $old_creditor_id === $creditor_id
                            )
                            {

                                $creditorUpdate = "
                                    UPDATE cashbook_creditor_ledger SET

                                        type = ?,
                                        creditor_id = ?,
                                        credit_amount = ?,
                                        debit_amount = ?,
                                        details = ?,
                                        book_id = ?,
                                        paymode_id = ?,
                                        created_at = ?,
                                        user_id = ?,
                                        item_id = ?,
                                        quantity = ?

                                    WHERE transaction_id = ?
                                ";

                                $cashoutQuery(
                                    $creditorUpdate,
                                    'siddsiisiidi',
                                    [
                                        $type,
                                        $creditor_id,
                                        0,
                                        $amount,
                                        $details,
                                        $book_id,
                                        $payment_mode,
                                        $date,
                                        $user_id,
                                        $item_id,
                                        $qty,
                                        $transid
                                    ]
                                );

                                recalculateCreditorLedger($creditor_id);
                            }

                            elseif(
                                $type === 'creditorPayment' &&
                                $creditor_id > 0 &&
                                $old_creditor_id > 0 &&
                                $old_creditor_id !== $creditor_id
                            )
                            {

                                // Balances will be rebuilt after moving the ledger entry.

                                $creditorUpdate = "
                                    UPDATE cashbook_creditor_ledger SET

                                        type = ?,
                                        creditor_id = ?,
                                        credit_amount = ?,
                                        debit_amount = ?,
                                        details = ?,
                                        book_id = ?,
                                        paymode_id = ?,
                                        created_at = ?,
                                        user_id = ?,
                                        item_id = ?,
                                        quantity = ?

                                    WHERE transaction_id = ?
                                ";

                                $cashoutQuery(
                                    $creditorUpdate,
                                    'siddsiisiidi',
                                    [
                                        $type,
                                        $creditor_id,
                                        0,
                                        $amount,
                                        $details,
                                        $book_id,
                                        $payment_mode,
                                        $date,
                                        $user_id,
                                        $item_id,
                                        $qty,
                                        $transid
                                    ]
                                );

                                recalculateCreditorLedger($old_creditor_id);
                                recalculateCreditorLedger($creditor_id);
                            }

                            elseif(
                                $type === 'creditorPayment' &&
                                $creditor_id > 0 &&
                                $old_creditor_id <= 0
                            )
                            {

                                $newBalance = 0.0; // Recalculated from ledger after insert.

                                $creditorInsert = "INSERT INTO cashbook_creditor_ledger
                                    (
                                        type,
                                        creditor_id,
                                        credit_amount,
                                        debit_amount,
                                        details,
                                        book_id,
                                        paymode_id,
                                        transaction_id,
                                        created_at,
                                        user_id,
                                        item_id,
                                        quantity,
                                        balance
                                    )
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ";

                                $cashoutQuery(
                                    $creditorInsert,
                                    'siddsiiisiidd',
                                    [
                                        $type,
                                        $creditor_id,
                                        0,
                                        $amount,
                                        $details,
                                        $book_id,
                                        $payment_mode,
                                        $transid,
                                        $date,
                                        $user_id,
                                        $item_id,
                                        $qty,
                                        $newBalance
                                    ]
                                );

                                recalculateCreditorLedger($creditor_id);
                            }

                            elseif(
                                $old_creditor_id > 0 &&
                                (
                                    $type !== 'creditorPayment' ||
                                    $creditor_id <= 0
                                )
                            )
                            {

                                // Delete the old entry and rebuild its creditor balance.
                                $deleteCreditorLedger = "
                                    DELETE FROM cashbook_creditor_ledger
                                    WHERE transaction_id = ?
                                ";

                                $cashoutQuery(
                                    $deleteCreditorLedger,
                                    'i',
                                    [$transid]
                                );

                                recalculateCreditorLedger($old_creditor_id);
                            }

                            $stmt = "
                                UPDATE cashbook_cashouts SET

                                    item_id = ?,
                                    type = ?,
                                    quantity = ?,
                                    rate = ?,
                                    amount = ?,
                                    category_id = ?,
                                    details = ?,
                                    paymode_id = ?,
                                    created_at = ?,
                                    user_id = ?

                                WHERE transaction_id = ?
                            ";

                            $cashoutQuery(
                                $stmt,
                                'isdddisisii',
                                [
                                    $item_id,
                                    $type,
                                    $qty,
                                    $purchase_rate,
                                    $amount,
                                    $category_id,
                                    $details,
                                    $payment_mode,
                                    $date,
                                    $user_id,
                                    $transid
                                ]
                            );

                            mysqli_commit($server);
                            $cashoutTransactionStarted = false;
                            $_SESSION['success'] = 'Cash out transaction updated successfully.';

                            } catch (Throwable $exception) {
                                if ($cashoutTransactionStarted) {
                                    mysqli_rollback($server);
                                }
                                error_log('editCashoutSave #' . ($transid ?? 0) . ': ' . $exception->getMessage());
                                $_SESSION['error'] = 'Cash-out update failed: ' . $exception->getMessage();
                            }
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
                        $_SESSION['success'] = "Invoice Created successfully";
                    break;
                            // save invoice items
                        
                        case 'newInvoiceItemSave':

                            $invoice_id = (int)request('invoice_id');
                            $user_id = (int)auth()->id;

                            $items = $_POST['item_id'] ?? [];
                            $quantities = $_POST['qty'] ?? [];
                            $rates = $_POST['rate'] ?? [];

                            $invoice = invoiceFind($invoice_id);

                            if(!$invoice){
                                $_SESSION['error'] = "Invoice not found";
                                break;
                            }

                            $book_id = (int)$invoice->book_id;
                            $customer_id = (int)$invoice->customer_id;
                            $invoice_no = $invoice->invoice_no;

                            // Validate invoice ownership against the active book.
                            $active_book_id = (int)encryptor('decrypt',$_SESSION['book_id']);

                            if($book_id !== $active_book_id){
                                $_SESSION['error'] = "Invalid invoice access";
                                break;
                            }

                            if(!is_array($items) || !count($items)){
                                $_SESSION['error'] = "Please add at least one invoice item";
                                break;
                            }

                            if(count($items)!==count($quantities) || count($items)!==count($rates)){
                                $_SESSION['error'] = "Invalid invoice item data";
                                break;
                            }

                            /*
                            * IMPORTANT:
                            * Use a database transaction so that invoice items,
                            * invoice totals and related financial records remain consistent.
                            */

                            mysqli_begin_transaction($server);

                            try {

                                // Lock invoice to prevent concurrent changes.
                                $lock = prepared_statements(
                                    "SELECT id FROM cashbook_invoices WHERE id=? AND book_id=? FOR UPDATE",
                                    'ii',
                                    [$invoice_id,$book_id]
                                );

                                if(!$lock || !$lock->num_rows){
                                    throw new Exception("Invoice could not be locked");
                                }

                                $stmt = "INSERT INTO cashbook_invoice_items
                                        (invoice_id,item_id,quantity,unit_price,total,user_id)
                                        VALUES (?,?,?,?,?,?)";

                                $newAmount = 0;

                                foreach($items as $k=>$item){

                                    $item_id = (int)$item;
                                    $qty = (float)$quantities[$k];
                                    $rate = (float)$rates[$k];

                                    if($item_id <= 0 || !is_finite($qty) || !is_finite($rate) || $qty <= 0 || $rate < 0){
                                        throw new Exception("Invalid quantity, rate or item");
                                    }

                                    // Confirm the item belongs to this business.
                                    $check = prepared_statements(
                                        "SELECT id FROM cashbook_items WHERE id=? AND book_id=?",
                                        'ii',
                                        [$item_id,$book_id]
                                    );

                                    if(!$check || !$check->num_rows){
                                        throw new Exception("Invalid business item");
                                    }

                                    $amount = round($qty*$rate,2);

                                    $save = prepared_statements(
                                        $stmt,
                                        'iidddi',
                                        [$invoice_id,$item_id,$qty,$rate,$amount,$user_id]
                                    );

                                    if(!$save){
                                        throw new Exception("Failed to save invoice item");
                                    }

                                    $newAmount += $amount;
                                }

                                /*
                                * Calculate complete invoice total from ALL saved items.
                                * This includes old and newly inserted items.
                                */

                                $totalQuery = prepared_statements(
                                    "SELECT COALESCE(SUM(total),0) AS total
                                    FROM cashbook_invoice_items
                                    WHERE invoice_id=?",
                                    'i',
                                    [$invoice_id]
                                );

                                if(!$totalQuery){
                                    throw new Exception("Failed to calculate invoice total");
                                }

                                $invoiceTotal = round(
                                    (float)$totalQuery->fetch_assoc()['total'],
                                    2
                                );

                                /*
                                * Preserve existing payments.
                                *
                                * Assumes invoice.balance stores the gross unpaid amount
                                * before returns, consistent with your invoice register.
                                */

                                $paid = (float)($invoice->paid_amount ?? 0);
                                $balance = round($invoiceTotal-$paid,2);

                                $update = prepared_statements(
                                    "UPDATE cashbook_invoices
                                    SET total=?,balance=?,status=?
                                    WHERE id=? AND book_id=?",
                                    'ddsii',
                                    [$invoiceTotal,$balance,'sent',$invoice_id,$book_id]
                                );

                                if(!$update){
                                    throw new Exception("Failed to update invoice");
                                }

                                /*
                                * Do not create another invoice transaction here.
                                *
                                * The existing invoice transaction and customer ledger
                                * must be reconciled separately against the correct
                                * invoice amount.
                                */

                                mysqli_commit($server);

                                $_SESSION['success'] = "Invoice items saved successfully. Total: UGX ".number_format($invoiceTotal,2);

                            }catch(Throwable $e){

                                mysqli_rollback($server);

                                error_log("Invoice Save Error: ".$e->getMessage());

                                $_SESSION['error'] = "Invoice save failed: ".$e->getMessage();
                            }

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
                            $manager_id = request('route_manager_id') ?? "";
                            
                            if(empty($manager_id))
                            {
                                $sql = "INSERT INTO cashbook_route_managers SET name = ?,nin=?,contact1 = ?,contact2 = ?,residence = ?,book_id = ?,user_id = ?";
                                prepared_statements($sql,'sssssii',[$name,$nin,$contact1,$contact2,$residence,$book_id,$user_id]);
                                $record_id = $server->insert_id;
                            }else{
                                $sql = "UPDATE cashbook_route_managers SET name = ?,nin=?,contact1 = ?,contact2 = ?,residence = ?,user_id = ? WHERE id = ?";
                                prepared_statements($sql,'sssssii',[$name,$nin,$contact1,$contact2,$residence,$user_id,$manager_id]);
                            }
                                
                            break;
                        case 'newRouteSave':
                            /*
                            |--------------------------------------------------------------------------
                            | REQUEST DATA
                            |--------------------------------------------------------------------------
                            */

                            $book_id = (int)request('book_id');

                            $name = trim(
                                request('name') ?? ''
                            );

                            $details = trim(
                                request('details') ?? ''
                            );

                            $user_id = (int)auth()->id;

                            $route_id = (int)(
                                request('route_id') ?? 0
                            );

                            $manager = (int)(
                                request('manager') ?? 0
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | VALIDATION
                            |--------------------------------------------------------------------------
                            */

                            if($book_id <= 0)
                            {
                                echo "Invalid book.";
                                break;
                            }


                            if($name === '')
                            {
                                echo "Route name is required.";
                                break;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | CREATE ROUTE
                            |--------------------------------------------------------------------------
                            */

                            if($route_id <= 0)
                            {

                                $sql = "

                                    INSERT INTO cashbook_routes

                                    SET
                                        book_id = ?,
                                        name = ?,
                                        details = ?,
                                        user_id = ?,
                                        managers = ?

                                ";


                                $saved = prepared_statements(
                                    $sql,
                                    'issii',
                                    [
                                        $book_id,
                                        $name,
                                        $details,
                                        $user_id,
                                        $manager
                                    ]
                                );


                                if($saved)
                                {
                                    $record_id = $server->insert_id;

                                    echo "Route saved successfully.";
                                }
                                else
                                {
                                    echo "Unable to save route.";
                                }

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | UPDATE ROUTE
                            |--------------------------------------------------------------------------
                            */

                            else
                            {

                                $sql = "

                                    UPDATE cashbook_routes

                                    SET
                                        name = ?,
                                        details = ?,
                                        user_id = ?,
                                        managers = ?

                                    WHERE id = ?

                                    AND book_id = ?

                                ";


                                $saved = prepared_statements(
                                    $sql,
                                    'ssiiii',
                                    [
                                        $name,
                                        $details,
                                        $user_id,
                                        $manager,
                                        $route_id,
                                        $book_id
                                    ]
                                );


                                if($saved)
                                {
                                    echo "Route updated successfully.";
                                }
                                else
                                {
                                    echo "Unable to update route.";
                                }

                            }


                            break;
                        case 'newPurchaseSave':
                                $item_id = request('item_id');
                                $type = request('purchase_type');
                                $qty = request('quantity');
                                $rate = request('rate');
                                $amount = request('amount');
                                $creditor_id = request('creditor_id') ?? "";
                                $mode = request('pay_mode');
                                $details = request('details');
                                $date = request('created_at');
                                $book_id = request('book_id');
                                $category_id = request('category_id') ?? "";
                                $credit = $amount;
                                $debit = 0;

                                // enter purchase transaction
                                $sql = "INSERT INTO cashbook_transactions SET item_id = ?, quantity = ?, rate=?, debit_amount = ?,book_id = ?, details = ?,category_id=?,paymode_id = ?,created_at = ?,user_id=?,type=?,customer_id=?";
                                $res = prepared_statements($sql,'iiiiisiisisi',[$item_id,$qty,$rate,$amount,$book_id,$details,$category_id,$mode,$date,$user_id,$type,$customer_id]);
                                $trans_id = $server->insert_id;

                                if($type == 'cash_purchase')
                                {
                                    $credit = $debit = $amount;

                                    // affect cash flow statement
                                    $stmt = "INSERT INTO  cashbook_cashouts SET item_id = ?, type=?,quantity=?,rate=?,amount = ?, category_id = ?, details = ?,book_id = ?,transaction_id = ?,paymode_id = ?,created_at = ?,user_id=?";
                                    prepared_statements($stmt,'isiiiisiiisi',[$item_id,$type,$qty,$rate,$amount,$category_id,$details,$book_id,$trans_id,$mode,$date,$user_id]);
                                    
                                }elseif($type =='credit_purchase')
                                { 
                                    $credit = $amount;
                                    $debit = 0;
                                    // does not affect cashflow statement // affects creditor statement
                                    CreditorLedgerUpdate($creditor_id,$credit,$debit,$category_id,$details,$book_id,$mode,$trans_id,$date,$user_id,$item_id,$qty,$type);
                                }
                                
                                // affect stock
                                stockIn($item_id, $qty, $trans_id);
                                
                                // save purchase
                                $stmt = "INSERT INTO cashbook_purchases SET transaction_id = ?, item_id = ?, quantity = ?, unit_price = ?, total = ?, book_id = ?, created_at = ?, user_id = ?,creditor_id = ?,type = ?";
                                prepared_statements($stmt,'iiiidisiis',[$trans_id,$item_id,$qty,$rate,$amount,$book_id,$date,$user_id,$creditor_id,$type]);

                                $_SESSION['success'] = "Data saved";
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
                                                <label>TRANSACTION TYPE:</label>
                                            </div>
                                            <div class="col p-2">
                                                <select name="transaction_type"  id ='transaction_type' class="form-control" required>
                                                    <option value="<?=$transaction->type ?? '';?>"><?=$transaction->type ?? '';?></option>
                                                    <option hidden>--- Select ---</option>
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
                                        <div class="p-0 income-creditor">
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="creditor_id">CREDITOR:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_creditors WHERE book_id = ?  ORDER BY name ASC";
                                                    $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="creditor_id" id="creditor_id" class="form-control">
                                                        <option value="<?=($transaction->creditor_id) ?? ''?>"><?=($transaction->creditor_name) ?? 'Select Customer'?></option>
                                                        <?php while($rw = $res->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
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
                                                    <label for="invoice_id">INVOICE:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <select name="invoice_id" id="invoice_id" class="form-control">
                                                        <option value="<?=$transaction->invoice_id ?? '';?>"><?=$transaction->invoice_no ?? '';?></option>
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
                                $book = bookFind($transaction->book_id);
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
                                                <label for="type">TYPE:</label>
                                            </div>
                                            <div class="col p-2">
                                                <select name="expense_type" id="expense_type" class="form-control" required>
                                                    <option value="<?=$transaction->type;?>"><?=$transaction->type;?></option>
                                                    <option value="purchase">Purchase</option>
                                                    <option value="expense">Expense</option>
                                                    <option value="borrowing">Borrowing</option>
                                                </select>
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
                                        <div class="p-0 expense-customer">
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="customer_id">CUSTOMER:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_customers WHERE book_id = ?  ORDER BY name ASC";
                                                    $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="customer_id" id="customer_id" class="form-control">
                                                        <option value="<?=$transaction->customer_id;?>"><?=$transaction->customer_name;?></option>
                                                        <?php while($rw = $res->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-0 purchase-expense">
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="item_id">ITEM:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_items WHERE book_id = ?  ORDER BY name ASC";
                                                    $res = prepared_statements($sql,'i',[$transaction->book_id]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="item_id" id="item_id" class="form-control">
                                                        <option value="<?=$transaction->item_id;?>"><?=$transaction->item_name;?></option>
                                                        <?php while($rw = $res->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="quantity">QUANTITY:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="quantity" id="quantity"  value="<?=$transaction->quantity;?>" class="form-control" placeholder='Qty....'>
                                                </div>
                                            </div>

                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="purchase_rate">RATE:</label>
                                                </div>
                                                <div class="col p-2">
                                                    <input type="text" name="purchase_rate" id="purchase_rate"  value="<?=$transaction->rate;?>" class="form-control" placeholder='Amount per unit....'>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-0 expense-creditor">
                                            <div class="row mx-1">
                                                <div class="col-md-3 p-2">
                                                    <label for="creditor_id">CREDITOR:</label>
                                                </div>
                                                <?php
                                                    $sql = "SELECT * FROM cashbook_creditors WHERE book_id = ?  ORDER BY name ASC";
                                                    $res = prepared_statements($sql,'i',[$book->id]);
                                                ?>
                                                <div class="col p-2">
                                                    <select name="creditor_id" id="creditor_id" class="form-control">
                                                        <option value="<?=$transaction->creditor_id;?>"><?=$transaction->creditor_name;?></option>
                                                        <?php while($rw = $res->fetch_assoc()):?>
                                                            <option value="<?=$rw['id'];?>"><?=$rw['name'];?></option>
                                                        <?php endwhile;?>
                                                    </select>
                                                </div>
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
                    $id = (int)request('id');
                    $transaction = transactionFind($id);
                    if(!$transaction){
                        $response = ['status'=>false,'message'=>'Transaction not found'];
                        break;
                    }

                    try{
                        mysqli_begin_transaction($server);

                        $customer_id = (int)($transaction->customer_id ?? 0);
                        $creditor_id = (int)($transaction->creditor_id ?? 0);
                        $book_id = (int)$transaction->book_id;

                        // Keep transaction history before deletion
                        $tracked = trackTransactionEdits($id,'delete');
                        if($tracked === false){
                            throw new Exception('Failed to save transaction deletion history');
                        }

                        // Remove customer ledger records
                        $sql = "DELETE FROM cashbook_customer_ledger WHERE transaction_id = ? AND book_id = ?";
                        $result = prepared_statements($sql,'ii',[$id,$book_id]);
                        if($result === false) throw new Exception('Failed to delete customer ledger');

                        // Remove creditor ledger records
                        $sql = "DELETE FROM cashbook_creditor_ledger WHERE transaction_id = ? AND book_id = ?";
                        $result = prepared_statements($sql,'ii',[$id,$book_id]);
                        if($result === false) throw new Exception('Failed to delete creditor ledger');

                        // Remove cash-in records
                        $sql = "DELETE FROM cashbook_cashins WHERE transaction_id = ?";
                        $result = prepared_statements($sql,'i',[$id]);
                        if($result === false) throw new Exception('Failed to delete cash-in records');

                        // Remove cash-out records
                        $sql = "DELETE FROM cashbook_cashouts WHERE transaction_id = ?";
                        $result = prepared_statements($sql,'i',[$id]);
                        if($result === false) throw new Exception('Failed to delete cash-out records');

                        // Remove stock movements linked to the transaction
                        $sql = "DELETE FROM cashbook_stocks WHERE reference = ? AND book_id = ?";
                        $result = prepared_statements($sql,'ii',[$id,$book_id]);
                        if($result === false) throw new Exception('Failed to delete stock movements');

                        // Purchase records must be handled here using their actual table and reference column.
                        // Do not delete purchases by customer_id, creditor_id or item_id alone.

                        // Delete the main transaction last
                       $sql = "DELETE FROM cashbook_transactions WHERE id = ? AND book_id = ?";
                        $result = prepared_statements($sql,'ii',[$id,$book_id]);

                        if($result === false){
                            throw new Exception('Delete query failed: '.mysqli_error($server));
                        }

                        $sql = "SELECT id FROM cashbook_transactions WHERE id = ? AND book_id = ?";
                        $check = prepared_statements($sql,'ii',[$id,$book_id]);

                        if($check === false){
                            throw new Exception('Failed to verify transaction deletion');
                        }

                        // Rebuild affected customer and creditor running balances
                        if($customer_id > 0){
                            if(!rebuildCustomerLedgerAfterDelete($customer_id,$book_id)){
                                throw new Exception('Failed to rebuild customer balances');
                            }
                        }

                        if($creditor_id > 0){
                            if(!rebuildCreditorLedgerAfterDelete($creditor_id,$book_id)){
                                throw new Exception('Failed to rebuild creditor balances');
                            }
                        }

                        mysqli_commit($server);
                        $response = ['status'=>true,'message'=>'Transaction deleted successfully'];
                        echo json_encode($response);

                    }catch(Throwable $e){
                        mysqli_rollback($server);
                        error_log('DeleteTransaction: '.$e->getMessage());
                        echo json_encode(['status'=>false,'message'=>'Error deleting transaction: '.$e->getMessage()]);
                    }
                    break;
                case 'transactionFilter':
                    $book_id = (int)request('book_id');
                    $where = ['t.book_id=?']; $params = [$book_id]; $types = 'i';

                    // ---------------- FILTERS ----------------
                    $min = trim($_POST['min_date'] ?? '');
                    $max = trim($_POST['max_date'] ?? '');
                    if ($min && $max) {
                        $where[] = 'DATE(t.created_at) BETWEEN ? AND ?'; array_push($params,$min,$max); $types .= 'ss';
                    } elseif ($min || $max) {
                        $where[] = 'DATE(t.created_at)=?'; $params[] = $min ?: $max; $types .= 's';
                    }

                    if (!empty($_POST['month'])) {
                        $where[] = 'MONTH(t.created_at)=?'; $params[] = (int)$_POST['month']; $types .= 'i';
                        if (!empty($_POST['year'])) {
                            $where[] = 'YEAR(t.created_at)=?'; $params[] = (int)$_POST['year']; $types .= 'i';
                        }
                    }

                    $direction = trim($_POST['type'] ?? '');
                    if ($direction === 'credit') {
                        $where[] = "t.credit_amount>0 AND EXISTS(
                            SELECT 1 FROM cashbook_cashins ci WHERE ci.transaction_id=t.id AND ci.book_id=t.book_id
                        )";
                    } elseif ($direction === 'debit') {
                        $where[] = "t.debit_amount>0 AND EXISTS(
                            SELECT 1 FROM cashbook_cashouts co WHERE co.transaction_id=t.id AND co.book_id=t.book_id
                        )";
                    }

                    $filters = [
                        'category'=>['t.category_id','cashbook_categories'],
                        'customer'=>['t.customer_id','cashbook_customers'],
                        'item'=>['t.item_id','cashbook_items']
                    ];
                    foreach ($filters as $key=>[$field,$table]) {
                        if (!empty($_POST[$key])) {
                            $where[] = "$field=? AND EXISTS(SELECT 1 FROM $table x WHERE x.id=$field AND x.book_id=t.book_id)";
                            $params[] = (int)$_POST[$key]; $types .= 'i';
                        }
                    }

                    // ---------------- TRANSACTIONS ----------------
                    $sql = "SELECT t.*,c.name category_name,cu.name customer_name,i.name item_name,
                        CASE WHEN t.credit_amount>0 AND EXISTS(
                            SELECT 1 FROM cashbook_cashins ci WHERE ci.transaction_id=t.id AND ci.book_id=t.book_id
                        ) THEN t.credit_amount ELSE 0 END real_cashin,
                        CASE WHEN t.debit_amount>0 AND EXISTS(
                            SELECT 1 FROM cashbook_cashouts co WHERE co.transaction_id=t.id AND co.book_id=t.book_id
                        ) THEN t.debit_amount ELSE 0 END real_cashout
                        FROM cashbook_transactions t
                        LEFT JOIN cashbook_categories c ON c.id=t.category_id AND c.book_id=t.book_id
                        LEFT JOIN cashbook_customers cu ON cu.id=t.customer_id AND cu.book_id=t.book_id
                        LEFT JOIN cashbook_items i ON i.id=t.item_id AND i.book_id=t.book_id
                        WHERE ".implode(' AND ',$where)." ORDER BY t.created_at ASC,t.id ASC";

                    $stmt = $server->prepare($sql);
                    if (!$stmt) {
                        echo "<tr><td colspan='8' class='text-center text-danger p-4'><i class='fa fa-exclamation-triangle'></i> Report query failed.</td></tr>";
                        break;
                    }
                    if ($params) $stmt->bind_param($types,...$params);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    $rows=[]; $items=[]; $cashin=$cashout=$qtyIn=$qtyOut=0;
                    while ($res && $r=$res->fetch_assoc()) {
                        $in=(float)($r['real_cashin']??0); $out=(float)($r['real_cashout']??0);
                        $qin=$qout=0; $qty=(float)($r['quantity']??0); $tt=strtolower(trim((string)($r['type']??'')));

                        if (in_array($tt,['purchase','purchases','stock_in','stock in'],true)) $qin=$qty;
                        elseif (in_array($tt,['sale','sales','cash_sale','cash sale','credit_sale','credit sale','stock_out','stock out'],true)) $qout=$qty;
                        elseif ($qty>0 && !empty($r['item_id'])) {
                            if ($out>0 && $in<=0) $qin=$qty;
                            elseif ($in>0 && $out<=0) $qout=$qty;
                        }

                        $cashin+=$in; $cashout+=$out; $qtyIn+=$qin; $qtyOut+=$qout;
                        $iid=(int)($r['item_id']??0);
                        if($iid){
                            if(!isset($items[$iid])){
                                $items[$iid]=[
                                    'name'=>$r['item_name']?:'Unnamed Item',
                                    'in'=>0,
                                    'out'=>0,
                                    'count'=>0,
                                    'transactions'=>[]
                                ];
                            }

                            $items[$iid]['in'] += $qin;
                            $items[$iid]['out'] += $qout;
                            $items[$iid]['count']++;

                            $items[$iid]['transactions'][]=[
                                'id'=>$r['id'],
                                'date'=>$r['created_at'],
                                'type'=>$in>0?'credit':'debit',
                                'details'=>$r['details']??'',
                                'cashin'=>$in,
                                'cashout'=>$out,
                                'qtyin'=>$qin,
                                'qtyout'=>$qout
                            ];
                        }
                        $r['_in']=$in; $r['_out']=$out; $rows[]=$r;
                    }

                    if (!$rows) {
                        echo "<tr><td colspan='8' class='text-center text-muted p-5'><i class='fa fa-info-circle'></i> No transactions found for the selected filter.</td></tr>";
                        break;
                    }

                    $money = fn($n)=>number_format((float)$n,0);
                    $qty = fn($n)=>floor((float)$n)==(float)$n ? number_format((float)$n,0) : number_format((float)$n,2);
                    $balance=$cashin-$cashout; $netQty=$qtyIn-$qtyOut;
                    ?>

                    <tr class="filter-summary-row">
                        <td colspan="8">
                            <div class="filter-summary-head">
                                <div><strong><i class="fa fa-filter"></i> FILTERED REPORT</strong><small><?=count($rows);?> transaction<?=count($rows)==1?'':'s';?></small></div>
                                <div class="filter-net <?=$balance < 0 ?'negative':'';?>">Net Movement <strong><?=$money($balance);?></strong></div>
                            </div>
                            <div class="filter-summary-grid">
                                <div class="filter-card in"><span>Cash In</span><strong><?=$money($cashin);?></strong></div>
                                <div class="filter-card out"><span>Cash Out</span><strong><?=$money($cashout);?></strong></div>
                                <div class="filter-card balance"><span>Balance</span><strong><?=$money($balance);?></strong></div>
                                <div class="filter-card qty"><span>Qty In / Out</span><strong><?=$qty($qtyIn);?> / <?=$qty($qtyOut);?></strong><small>Net <?=$qty($netQty);?></small></div>
                            </div>
                        </td>
                    </tr>

                    <?php $n=1; foreach ($rows as $r): $in=$r['_in']; $out=$r['_out']; ?>
                    <tr class="transaction-details hover hover-hide-content">
                        <td><?=$n++;?></td>
                        <td><?=date('d-m-Y',strtotime($r['created_at']));?></td>
                        <td><?=htmlspecialchars($r['category_name']??'');?></td>
                        <td><?=htmlspecialchars($r['customer_name']??'');?></td>
                        <td><?=htmlspecialchars($r['details']??'');?></td>
                        <td class="text-right <?=$in>0?'text-success font-weight-bold':'';?>" data-amount="<?=$in;?>"><?=$in>0?$money($in):'';?></td>
                        <td class="text-right <?=$out>0?'text-danger font-weight-bold':'';?>" data-amount="<?=$out;?>"><?=$out>0?$money($out):'';?></td>
                        <td class="text-nowrap">
                            <?php if(hasRole(['owner','partner','superadministrator'])):?>
                            <span class="hover-display text-sms">
                                <button type="button" class="btn btn-sm btn-outline-info edit-trans text-muted" data-id="<?=$r['id'];?>" data-type="<?=$in>0?'credit':'debit';?>"><i class="fa fa-edit"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-trans" data-id="<?=$r['id'];?>" data-type="<?=$in>0?'credit':'debit';?>"><i class="fa fa-trash"></i></button>
                            </span>
                            <?php endif;?>
                        </td>
                    </tr>
                    <?php endforeach;?>

                    <tr class="filter-total-row">
                        <th class="text-right">FILTER TOTALS</th>
                        <th colspan="4"></th>
                        <th class="text-right text-success"><?=$money($cashin);?></th>
                        <th class="text-right text-danger"><?=$money($cashout);?></th>
                        <th></th>
                    </tr>

                    <?php if($items):?>
                    <tr class="item-summary-holder">
                        <td colspan="8" class="p-0">
                            <div class="filter-item-summary">
                                <div class="filter-item-title"><strong><i class="fa fa-cubes"></i> ITEM MOVEMENT</strong><small>Quantity movement for this filtered report</small></div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>#</th><th>Item</th><th class="text-center">Transactions</th><th class="text-right">Qty In</th><th class="text-right">Qty Out</th><th class="text-right">Net</th></tr></thead>
                                        <tbody>
                                        <?php $x=1; foreach($items as $item): $net=$item['in']-$item['out'];?>
                                            <tr>
                                                <td><?=$x++;?></td><td><strong><?=htmlspecialchars($item['name']);?></strong></td>
                                                <td class="text-center"><?=$item['count'];?></td><td class="text-right"><?=$qty($item['in']);?></td>
                                                <td class="text-right"><?=$qty($item['out']);?></td>
                                                <td class="text-right font-weight-bold <?=$net<0?'text-danger':'text-success';?>"><?=$qty($net);?></td>
                                                <?php if(hasRole(['owner','partner'])):?>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-info edit-trans text-muted"
                                                    data-id="<?=$trans['id'];?>"
                                                    data-type="<?=$trans['type'];?>"
                                                    title="Edit transaction">
                                                    <i class="fa fa-edit"></i>
                                                </button>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger delete-trans"
                                                    data-id="<?=$trans['id'];?>"
                                                    data-type="<?=$trans['type'];?>"
                                                    title="Delete transaction">
                                                    <i class="fa fa-trash"></i>
                                                </button>

                                            <?php endif;?>
                                            </tr>
                                        <?php endforeach;?>
                                        </tbody>
                                        <tfoot><tr><th colspan="3">TOTAL</th><th class="text-right"><?=$qty($qtyIn);?></th><th class="text-right"><?=$qty($qtyOut);?></th><th class="text-right"><?=$qty($netQty);?></th></tr></tfoot>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif;
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
                case 'exportTransactionReport':

                        /*
                        |--------------------------------------------------------------------------
                        | EXCEL / PDF EXPORT
                        |--------------------------------------------------------------------------
                        */

                        $book_id = (int)request('book_id');
                        $format = strtolower(request('format'));

                        /*
                        |--------------------------------------------------------------------------
                        | FILTERS
                        |--------------------------------------------------------------------------
                        */

                        $filters = [
                            'min_date' => request('min_date'),
                            'max_date' => request('max_date'),
                            'month'    => request('month'),
                            'year'     => request('year'),
                            'type'     => request('type'),
                            'category' => request('category'),
                            'customer' => request('customer'),
                            'item'     => request('item'),
                        ];


                        /*
                        |--------------------------------------------------------------------------
                        | FILTER SQL
                        |--------------------------------------------------------------------------
                        */

                        $filterData = buildCashbookReportFilters($book_id,$filters);

                        $where = $filterData['where'];
                        $types =  $filterData['types'];
                        $params =  $filterData['params'];
                        $book = bookFind($book_id);

                        /*
                        |--------------------------------------------------------------------------
                        | TRANSACTIONS
                        |--------------------------------------------------------------------------
                        */

                        $sql = "SELECT
                            ct.id,
                            ct.created_at,
                            ct.type,
                            ct.details,
                            ct.item_id,
                            ct.credit_amount,
                            ct.debit_amount,

                            COALESCE(cc.name, '') AS category_name,
                            COALESCE(cu.name, '') AS customer_name,
                            COALESCE(pm.name, '') AS paymode_name,
                            COALESCE(it.name, '') AS item_name,

                            /* STOCK INFORMATION */
                            COALESCE(cs.quantity_in, 0) AS quantity_in,
                            COALESCE(cs.quantity_out, 0) AS quantity_out,
                            COALESCE(cs.balance, 0) AS stock_balance,
                            COALESCE(cs.unit_cost, 0) AS unit_cost,
                            COALESCE(cs.total_cost, 0) AS total_cost,
                            COALESCE(cs.transaction_type, '') AS stock_transaction_type

                        FROM cashbook_transactions ct

                        LEFT JOIN cashbook_categories cc
                            ON cc.id = ct.category_id
                            AND cc.book_id = ct.book_id

                        LEFT JOIN cashbook_customers cu
                            ON cu.id = ct.customer_id
                            AND cu.book_id = ct.book_id

                        LEFT JOIN cashbook_paymodes pm
                            ON pm.id = ct.paymode_id
                            AND pm.book_id = ct.book_id

                        LEFT JOIN cashbook_items it
                            ON it.id = ct.item_id
                            AND it.book_id = ct.book_id

                        /* LINK TRANSACTION TO STOCK MOVEMENT */
                        LEFT JOIN cashbook_stocks cs
                            ON cs.reference = ct.id
                            AND cs.book_id = ct.book_id
                            AND cs.item_id = ct.item_id

                        WHERE {$where}

                        AND (
                            (
                                ct.credit_amount > 0
                                AND EXISTS (
                                    SELECT 1
                                    FROM cashbook_cashins cin
                                    WHERE cin.transaction_id = ct.id
                                    AND cin.book_id = ct.book_id
                                )
                            )

                            OR

                            (
                                ct.debit_amount > 0
                                AND EXISTS (
                                    SELECT 1
                                    FROM cashbook_cashouts cout
                                    WHERE cout.transaction_id = ct.id
                                    AND cout.book_id = ct.book_id
                                )
                            )
                        )
                        ORDER BY ct.created_at ASC, ct.id ASC";

                        $res =prepared_statements($sql,$types,$params);
                        if (!$res) {
                            die(
                                "Failed to load report transactions."
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | LOAD ALL DATA FIRST
                        |--------------------------------------------------------------------------
                        */

                        $transactions = [];

                        $cashin = 0;
                        $cashout = 0;
                        $runningBalance = 0;

                        while ($r = $res->fetch_assoc()) 
                        {

                            $credit = (float)($r['credit_amount'] ?? 0);
                            $debit = ($r['debit_amount'] == $r['credit_amount']) ? 0 : (float)$r['debit_amount'];
                            $cashin += $credit;
                            $cashout += $debit;
                            $runningBalance += $credit - $debit;
                            $r['running_balance'] =  $runningBalance;

                            $transactions[] = $r;
                        }

                        $transactionCount = count($transactions);

                        /*
                        |--------------------------------------------------------------------------
                        | EXPORT EXCEL
                        |--------------------------------------------------------------------------
                        */

                        if ($format === 'excel')
                        {
                            /*
                            |--------------------------------------------------------------------------
                            | CREATE SPREADSHEET
                            |--------------------------------------------------------------------------
                            */

                            $spreadsheet = new Spreadsheet();


                            /*
                            |--------------------------------------------------------------------------
                            | HELPER: FORMAT QUANTITY
                            |--------------------------------------------------------------------------
                            */

                            $formatQty = function($value)
                            {
                                $value = (float)$value;

                                return $value;
                            };


                            /*
                            |--------------------------------------------------------------------------
                            | QUANTITY MOVEMENT TYPES
                            |--------------------------------------------------------------------------
                            |
                            | Purchase / stock in:
                            |       Quantity IN
                            |
                            | Sale / stock out:
                            |       Quantity OUT
                            |
                            |--------------------------------------------------------------------------
                            */

                            $quantityInTypes = [
                                'purchase',
                                'purchases',
                                'stock_in',
                                'stock in'
                            ];

                            $quantityOutTypes = [
                                'sale',
                                'sales',
                                'cash_sale',
                                'cash sale',
                                'credit_sale',
                                'credit sale',
                                'stock_out',
                                'stock out'
                            ];


                            /*
                            |--------------------------------------------------------------------------
                            | BUILD QUANTITY SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            $totalQtyIn  = 0;
                            $totalQtyOut = 0;

                            $itemSummary = [];


                            foreach ($transactions as $transaction)
                            {
                                $quantity = (float)($transaction['quantity'] ?? 0);

                                $qtyIn  = 0;
                                $qtyOut = 0;


                                /*
                                |--------------------------------------------------------------------------
                                | NORMALISE TYPE
                                |--------------------------------------------------------------------------
                                */

                                $transactionType = strtolower(
                                    trim(
                                        (string)($transaction['type'] ?? '')
                                    )
                                );


                                /*
                                |--------------------------------------------------------------------------
                                | EXPLICIT QUANTITY DIRECTION
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    in_array(
                                        $transactionType,
                                        $quantityInTypes,
                                        true
                                    )
                                )
                                {
                                    $qtyIn = $quantity;
                                }
                                elseif (
                                    in_array(
                                        $transactionType,
                                        $quantityOutTypes,
                                        true
                                    )
                                )
                                {
                                    $qtyOut = $quantity;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CASH VALUES
                                |--------------------------------------------------------------------------
                                |
                                | If real_cashin / real_cashout were prepared by the report query,
                                | use them.
                                |
                                | Otherwise fall back to the transaction amounts.
                                |--------------------------------------------------------------------------
                                */

                                $rowCashIn = isset($transaction['real_cashin'])
                                    ? (float)$transaction['real_cashin']
                                    : (float)($transaction['credit_amount'] ?? 0);


                                $rowCashOut = isset($transaction['real_cashout'])
                                    ? (float)$transaction['real_cashout']
                                    : (float)($transaction['debit_amount'] ?? 0);


                                /*
                                |--------------------------------------------------------------------------
                                | FALLBACK QUANTITY CLASSIFICATION
                                |--------------------------------------------------------------------------
                                |
                                | Only used when the transaction type did not tell us the direction.
                                |
                                | Cash Out + Item + Qty = likely purchase -> Qty In
                                | Cash In  + Item + Qty = likely sale     -> Qty Out
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $quantity > 0 &&
                                    $qtyIn == 0 &&
                                    $qtyOut == 0 &&
                                    !empty($transaction['item_id'])
                                )
                                {
                                    if (
                                        $rowCashOut > 0 &&
                                        $rowCashIn <= 0
                                    )
                                    {
                                        $qtyIn = $quantity;
                                    }
                                    elseif (
                                        $rowCashIn > 0 &&
                                        $rowCashOut <= 0
                                    )
                                    {
                                        $qtyOut = $quantity;
                                    }
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | TOTAL QUANTITY MOVEMENT
                                |--------------------------------------------------------------------------
                                */

                                $totalQtyIn  += $qtyIn;
                                $totalQtyOut += $qtyOut;


                                /*
                                |--------------------------------------------------------------------------
                                | ITEM SUMMARY
                                |--------------------------------------------------------------------------
                                */

                                $itemId = (int)($transaction['item_id'] ?? 0);

                                if ($itemId > 0)
                                {
                                    if (!isset($itemSummary[$itemId]))
                                    {
                                        $itemSummary[$itemId] = [
                                            'name'         => $transaction['item_name'] ?? 'Unnamed Item',
                                            'qty_in'       => 0,
                                            'qty_out'      => 0,
                                            'transactions' => 0
                                        ];
                                    }


                                    $itemSummary[$itemId]['qty_in'] += $qtyIn;

                                    $itemSummary[$itemId]['qty_out'] += $qtyOut;

                                    $itemSummary[$itemId]['transactions']++;
                                }
                            }


                            $netQuantity =
                                $totalQtyIn - $totalQtyOut;


                            /*
                            |--------------------------------------------------------------------------
                            | SUMMARY SHEET
                            |--------------------------------------------------------------------------
                            */

                            $summary =
                                $spreadsheet->getActiveSheet();

                            $summary->setTitle('Summary');


                            /*
                            |--------------------------------------------------------------------------
                            | TITLE
                            |--------------------------------------------------------------------------
                            */

                            $summary->mergeCells('A1:D1');

                            $summary->setCellValue(
                                'A1',
                                strtoupper(
                                    $book->name ?? 'BUSINESS'
                                )
                            );


                            $summary->mergeCells('A2:D2');

                            $summary->setCellValue(
                                'A2',
                                'TRANSACTION REPORT'
                            );


                            $summary->setCellValue(
                                'A4',
                                'Generated'
                            );

                            $summary->setCellValue(
                                'B4',
                                date('d-m-Y H:i')
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | FINANCIAL SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            $summary->setCellValue(
                                'A6',
                                'Cash In'
                            );

                            $summary->setCellValue(
                                'B6',
                                $cashin
                            );


                            $summary->setCellValue(
                                'A7',
                                'Cash Out'
                            );

                            $summary->setCellValue(
                                'B7',
                                $cashout
                            );


                            $summary->setCellValue(
                                'A8',
                                'Net Balance'
                            );

                            $summary->setCellValue(
                                'B8',
                                $cashin - $cashout
                            );


                            $summary->setCellValue(
                                'A9',
                                'Transactions'
                            );

                            $summary->setCellValue(
                                'B9',
                                $transactionCount
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | QUANTITY SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            $summary->setCellValue(
                                'A11',
                                'QUANTITY MOVEMENT'
                            );


                            $summary->setCellValue(
                                'A12',
                                'Quantity In'
                            );

                            $summary->setCellValue(
                                'B12',
                                $totalQtyIn
                            );


                            $summary->setCellValue(
                                'A13',
                                'Quantity Out'
                            );

                            $summary->setCellValue(
                                'B13',
                                $totalQtyOut
                            );


                            $summary->setCellValue(
                                'A14',
                                'Net Quantity'
                            );

                            $summary->setCellValue(
                                'B14',
                                $netQuantity
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | FILTER SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            $summary->setCellValue(
                                'A16',
                                'REPORT FILTERS'
                            );


                            $filterRow = 17;


                            $filterLabels = [

                                'min_date' => 'Date From',

                                'max_date' => 'Date To',

                                'month' => 'Month',

                                'year' => 'Year',

                                'type' => 'Type',

                                'category' => 'Category',

                                'customer' => 'Customer',

                                'item' => 'Item'

                            ];


                            foreach (
                                $filterLabels
                                as $key => $label
                            )
                            {
                                if (
                                    isset($filters[$key]) &&
                                    $filters[$key] !== '' &&
                                    $filters[$key] !== null
                                )
                                {
                                    $summary->setCellValue(
                                        'A' . $filterRow,
                                        $label
                                    );

                                    $summary->setCellValue(
                                        'B' . $filterRow,
                                        $filters[$key]
                                    );

                                    $filterRow++;
                                }
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | SUMMARY STYLING
                            |--------------------------------------------------------------------------
                            */

                            $summary
                                ->getStyle('A1:D2')
                                ->getFont()
                                ->setBold(true);


                            $summary
                                ->getStyle('A1:D2')
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_CENTER
                                );


                            /*
                            | Financial summary borders
                            */

                            $summary
                                ->getStyle('A6:B9')
                                ->getBorders()
                                ->getAllBorders()
                                ->setBorderStyle(
                                    Border::BORDER_THIN
                                );


                            /*
                            | Quantity summary borders
                            */

                            $summary
                                ->getStyle('A12:B14')
                                ->getBorders()
                                ->getAllBorders()
                                ->setBorderStyle(
                                    Border::BORDER_THIN
                                );


                            $summary
                                ->getStyle('A6:A9')
                                ->getFont()
                                ->setBold(true);


                            $summary
                                ->getStyle('A12:A14')
                                ->getFont()
                                ->setBold(true);


                            $summary
                                ->getStyle('A11:B11')
                                ->getFont()
                                ->setBold(true);


                            $summary
                                ->getStyle('A16:B16')
                                ->getFont()
                                ->setBold(true);


                            /*
                            | Money format
                            */

                            $summary
                                ->getStyle('B6:B8')
                                ->getNumberFormat()
                                ->setFormatCode('#,##0');


                            /*
                            | Quantity format
                            */

                            $summary
                                ->getStyle('B12:B14')
                                ->getNumberFormat()
                                ->setFormatCode('#,##0.##');


                            $summary
                                ->getColumnDimension('A')
                                ->setWidth(25);


                            $summary
                                ->getColumnDimension('B')
                                ->setWidth(30);


                            /*
                            |--------------------------------------------------------------------------
                            | TRANSACTIONS SHEET
                            |--------------------------------------------------------------------------
                            */

                            $sheet = $spreadsheet->createSheet();


                            $sheet->setTitle('Transactions');


                            /*
                            |--------------------------------------------------------------------------
                            | TRANSACTION SHEET TITLE
                            |--------------------------------------------------------------------------
                            |
                            | We now use columns A:N
                            |--------------------------------------------------------------------------
                            */

                            $sheet->mergeCells(
                                'A1:N1'
                            );

                            $sheet->setCellValue(
                                'A1',
                                strtoupper(
                                    $book->name ?? 'BUSINESS'
                                )
                            );


                            $sheet->mergeCells(
                                'A2:N2'
                            );

                            $sheet->setCellValue(
                                'A2',
                                'TRANSACTION STATEMENT'
                            );


                            $sheet->mergeCells(
                                'A3:N3'
                            );

                            $sheet->setCellValue(
                                'A3',
                                'Generated: ' .
                                date('d-m-Y H:i')
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | HEADERS
                            |--------------------------------------------------------------------------
                            */

                            $headers = [

                                'No.',

                                'Date',

                                'Transaction ID',

                                'Category',

                                'Item',

                                'Qty In',

                                'Qty Out',

                                'Net Qty',

                                'Customer',

                                'Details',

                                'Payment Mode',

                                'Cash In',

                                'Cash Out',

                                'Balance'

                            ];


                            $column = 'A';


                            foreach ($headers as $header)
                            {
                                $sheet->setCellValue(
                                    $column . '5',
                                    $header
                                );

                                $column++;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | HEADER STYLE
                            |--------------------------------------------------------------------------
                            */

                            $sheet
                                ->getStyle('A5:N5')
                                ->getFont()
                                ->setBold(true);


                            $sheet
                                ->getStyle('A5:N5')
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_CENTER
                                );


                            $sheet
                                ->getStyle('A5:N5')
                                ->getBorders()
                                ->getAllBorders()
                                ->setBorderStyle(
                                    Border::BORDER_THIN
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | DATA
                            |--------------------------------------------------------------------------
                            */

                            $row    = 6;
                            $number = 1;


                            /*
                            |--------------------------------------------------------------------------
                            | RUNNING BALANCE
                            |--------------------------------------------------------------------------
                            */

                            $runningBalance = 0;


                            foreach ($transactions as $transaction)
                            {
                                /*
                                |--------------------------------------------------------------------------
                                | QUANTITY
                                |--------------------------------------------------------------------------
                                */

                                $quantity = (float)($transaction['quantity'] ?? 0);
                                $qtyIn  = 0;
                                $qtyOut = 0;

                                $transactionType =
                                    strtolower(
                                        trim(
                                            (string)(
                                                $transaction['type']
                                                ?? ''
                                            )
                                        )
                                    );
                                /*
                                | Explicit stock direction.
                                */

                                if (in_array($transactionType,$quantityInTypes,true))
                                {
                                    $qtyIn = $quantity;
                                }
                                elseif (in_array($transactionType,$quantityOutTypes,true))
                                {
                                    $qtyOut = $quantity;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CASH MOVEMENT
                                |--------------------------------------------------------------------------
                                */

                                $rowCashIn = isset($transaction['real_cashin'])
                                        ? (float)$transaction['real_cashin']
                                        : (float)($transaction['credit_amount' ] ?? 0);


                                $rowCashOut = isset($transaction['real_cashout'])
                                        ? (float)$transaction['real_cashout']
                                        : (float)($transaction['debit_amount'] ?? 0 );

                                if($rowCashOut == $rowCashIn) $rowCashOut = 0;
                                
                                /*
                                |--------------------------------------------------------------------------
                                | FALLBACK QUANTITY DIRECTION
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $quantity > 0 &&
                                    $qtyIn == 0 &&
                                    $qtyOut == 0 &&
                                    !empty($transaction['item_id'])
                                )
                                {
                                    if ($rowCashOut > 0 && $rowCashIn <= 0)
                                    {
                                        $qtyIn = $quantity;
                                    }
                                    elseif ($rowCashIn > 0 && $rowCashOut <= 0)
                                    {
                                        $qtyOut = $quantity;
                                    }
                                }


                                $rowNetQty = $qtyIn - $qtyOut;


                                /*
                                |--------------------------------------------------------------------------
                                | RUNNING FINANCIAL BALANCE
                                |--------------------------------------------------------------------------
                                */

                                $runningBalance +=
                                    $rowCashIn -
                                    $rowCashOut;


                                /*
                                |--------------------------------------------------------------------------
                                | WRITE ROW
                                |--------------------------------------------------------------------------
                                */


                                /*
                                | A - Number
                                */

                                $sheet->setCellValue(
                                    'A' . $row,
                                    $number
                                );


                                /*
                                | B - Date
                                */

                                $sheet->setCellValue(

                                    'B' . $row,

                                    !empty(
                                        $transaction['created_at']
                                    )
                                        ? date(
                                            'd-m-Y H:i',
                                            strtotime(
                                                $transaction[
                                                    'created_at'
                                                ]
                                            )
                                        )
                                        : ''

                                );


                                /*
                                | C - Transaction ID
                                */

                                $sheet->setCellValue(
                                    'C' . $row,
                                    $transaction['id'] ?? ''
                                );


                                /*
                                | D - Category
                                */

                                $sheet->setCellValue(
                                    'D' . $row,
                                    $transaction[
                                        'category_name'
                                    ] ?? ''
                                );


                                /*
                                | E - Item
                                */

                                $sheet->setCellValue(
                                    'E' . $row,
                                    $transaction[
                                        'item_name'
                                    ] ?? ''
                                );


                                /*
                                | F - Quantity In
                                */

                                $sheet->setCellValue(
                                    'F' . $row,
                                    $qtyIn
                                );


                                /*
                                | G - Quantity Out
                                */

                                $sheet->setCellValue(
                                    'G' . $row,
                                    $qtyOut
                                );


                                /*
                                | H - Net Quantity
                                */

                                $sheet->setCellValue(
                                    'H' . $row,
                                    $rowNetQty
                                );


                                /*
                                | I - Customer
                                */

                                $sheet->setCellValue(
                                    'I' . $row,
                                    $transaction[
                                        'customer_name'
                                    ] ?? ''
                                );


                                /*
                                | J - Details
                                */

                                $sheet->setCellValue(
                                    'J' . $row,
                                    $transaction[
                                        'details'
                                    ] ?? ''
                                );


                                /*
                                | K - Payment Mode
                                */

                                $sheet->setCellValue(
                                    'K' . $row,
                                    $transaction[
                                        'paymode_name'
                                    ] ?? ''
                                );


                                /*
                                | L - Cash In
                                */

                                $sheet->setCellValue(
                                    'L' . $row,
                                    $rowCashIn
                                );


                                /*
                                | M - Cash Out
                                */

                                $sheet->setCellValue(
                                    'M' . $row,
                                    $rowCashOut
                                );


                                /*
                                | N - Running Balance
                                */

                                $sheet->setCellValue(
                                    'N' . $row,
                                    $runningBalance
                                );


                                $row++;

                                $number++;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | TOTAL ROW
                            |--------------------------------------------------------------------------
                            */

                            $totalRow =$row;


                            $sheet->setCellValue(
                                'A' . $totalRow,
                                'TOTAL'
                            );


                            /*
                            | Merge A:E
                            */

                            $sheet->mergeCells(
                                'A' . $totalRow .
                                ':E' . $totalRow
                            );


                            /*
                            | Qty In
                            */

                            $sheet->setCellValue(
                                'F' . $totalRow,
                                $totalQtyIn
                            );


                            /*
                            | Qty Out
                            */

                            $sheet->setCellValue(
                                'G' . $totalRow,
                                $totalQtyOut
                            );


                            /*
                            | Net Qty
                            */

                            $sheet->setCellValue(
                                'H' . $totalRow,
                                $netQuantity
                            );


                            /*
                            | Merge I:K
                            */

                            $sheet->mergeCells(
                                'I' . $totalRow .
                                ':K' . $totalRow
                            );


                            /*
                            | Cash In
                            */

                            $sheet->setCellValue(
                                'L' . $totalRow,
                                $cashin
                            );


                            /*
                            | Cash Out
                            */

                            $sheet->setCellValue(
                                'M' . $totalRow,
                                $cashout
                            );


                            /*
                            | Balance
                            */

                            $sheet->setCellValue(
                                'N' . $totalRow,
                                $cashin - $cashout
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | TOTAL STYLE
                            |--------------------------------------------------------------------------
                            */

                            $sheet
                                ->getStyle(
                                    'A' .
                                    $totalRow .
                                    ':N' .
                                    $totalRow
                                )
                                ->getFont()
                                ->setBold(true);


                            /*
                            |--------------------------------------------------------------------------
                            | QUANTITY NUMBER FORMAT
                            |--------------------------------------------------------------------------
                            */

                            if ($totalRow >= 6)
                            {
                                $sheet
                                    ->getStyle(
                                        'F6:H' .
                                        $totalRow
                                    )
                                    ->getNumberFormat()
                                    ->setFormatCode(
                                        '#,##0.##'
                                    );
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | MONEY NUMBER FORMAT
                            |--------------------------------------------------------------------------
                            */

                            if ($totalRow >= 6)
                            {
                                $sheet
                                    ->getStyle(
                                        'L6:N' .
                                        $totalRow
                                    )
                                    ->getNumberFormat()
                                    ->setFormatCode(
                                        '#,##0'
                                    );
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | BORDERS
                            |--------------------------------------------------------------------------
                            */

                            $sheet
                                ->getStyle(
                                    'A5:N' .
                                    $totalRow
                                )
                                ->getBorders()
                                ->getAllBorders()
                                ->setBorderStyle(
                                    Border::BORDER_THIN
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | ALIGN QUANTITY AND MONEY
                            |--------------------------------------------------------------------------
                            */

                            $sheet
                                ->getStyle(
                                    'F6:H' .
                                    $totalRow
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_RIGHT
                                );


                            $sheet
                                ->getStyle(
                                    'L6:N' .
                                    $totalRow
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_RIGHT
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | COLUMN WIDTHS
                            |--------------------------------------------------------------------------
                            */

                            $widths = [

                                'A' => 8,     // No.

                                'B' => 20,    // Date

                                'C' => 15,    // Transaction ID

                                'D' => 22,    // Category

                                'E' => 25,    // Item

                                'F' => 14,    // Qty In

                                'G' => 14,    // Qty Out

                                'H' => 14,    // Net Qty

                                'I' => 25,    // Customer

                                'J' => 40,    // Details

                                'K' => 18,    // Payment Mode

                                'L' => 18,    // Cash In

                                'M' => 18,    // Cash Out

                                'N' => 18     // Balance

                            ];


                            foreach (
                                $widths
                                as $col => $width
                            )
                            {
                                $sheet
                                    ->getColumnDimension($col)
                                    ->setWidth($width);
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | WRAP LONG TEXT
                            |--------------------------------------------------------------------------
                            */

                            $sheet
                                ->getStyle(
                                    'D5:K' .
                                    $totalRow
                                )
                                ->getAlignment()
                                ->setWrapText(true);


                            /*
                            |--------------------------------------------------------------------------
                            | FREEZE HEADER
                            |--------------------------------------------------------------------------
                            */

                            $sheet->freezePane(
                                'A6'
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | AUTO FILTER
                            |--------------------------------------------------------------------------
                            */

                            $sheet->setAutoFilter(
                                'A5:N5'
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | ITEM QUANTITY SUMMARY SHEET
                            |--------------------------------------------------------------------------
                            */

                            $itemSheet =
                                $spreadsheet->createSheet();


                            $itemSheet->setTitle(
                                'Item Movement'
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | TITLE
                            |--------------------------------------------------------------------------
                            */

                            $itemSheet->mergeCells(
                                'A1:F1'
                            );


                            $itemSheet->setCellValue(
                                'A1',
                                strtoupper(
                                    $book->name ?? 'BUSINESS'
                                )
                            );


                            $itemSheet->mergeCells(
                                'A2:F2'
                            );


                            $itemSheet->setCellValue(
                                'A2',
                                'ITEM QUANTITY MOVEMENT'
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | ITEM HEADERS
                            |--------------------------------------------------------------------------
                            */

                            $itemHeaders = [

                                'No.',

                                'Item',

                                'Transactions',

                                'Qty In',

                                'Qty Out',

                                'Net Qty'

                            ];


                            $column = 'A';


                            foreach (
                                $itemHeaders
                                as $header
                            )
                            {
                                $itemSheet->setCellValue(
                                    $column . '4',
                                    $header
                                );

                                $column++;
                            }


                            $itemSheet
                                ->getStyle('A4:F4')
                                ->getFont()
                                ->setBold(true);


                            $itemSheet
                                ->getStyle('A4:F4')
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_CENTER
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | ITEM DATA
                            |--------------------------------------------------------------------------
                            */

                            $itemRow =
                                5;

                            $itemNumber =
                                1;


                            foreach (
                                $itemSummary
                                as $item
                            )
                            {
                                $itemNet =

                                    (float)$item['qty_in']

                                    -

                                    (float)$item['qty_out'];


                                $itemSheet->setCellValue(
                                    'A' . $itemRow,
                                    $itemNumber
                                );


                                $itemSheet->setCellValue(
                                    'B' . $itemRow,
                                    $item['name']
                                );


                                $itemSheet->setCellValue(
                                    'C' . $itemRow,
                                    $item['transactions']
                                );


                                $itemSheet->setCellValue(
                                    'D' . $itemRow,
                                    $item['qty_in']
                                );


                                $itemSheet->setCellValue(
                                    'E' . $itemRow,
                                    $item['qty_out']
                                );


                                $itemSheet->setCellValue(
                                    'F' . $itemRow,
                                    $itemNet
                                );


                                $itemRow++;

                                $itemNumber++;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | ITEM TOTAL
                            |--------------------------------------------------------------------------
                            */

                            $itemTotalRow =
                                $itemRow;


                            $itemSheet->setCellValue(
                                'A' . $itemTotalRow,
                                'TOTAL'
                            );


                            $itemSheet->mergeCells(
                                'A' .
                                $itemTotalRow .
                                ':C' .
                                $itemTotalRow
                            );


                            $itemSheet->setCellValue(
                                'D' . $itemTotalRow,
                                $totalQtyIn
                            );


                            $itemSheet->setCellValue(
                                'E' . $itemTotalRow,
                                $totalQtyOut
                            );


                            $itemSheet->setCellValue(
                                'F' . $itemTotalRow,
                                $netQuantity
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | ITEM SHEET STYLE
                            |--------------------------------------------------------------------------
                            */

                            $itemSheet
                                ->getStyle(
                                    'A4:F' .
                                    $itemTotalRow
                                )
                                ->getBorders()
                                ->getAllBorders()
                                ->setBorderStyle(
                                    Border::BORDER_THIN
                                );


                            $itemSheet
                                ->getStyle(
                                    'A' .
                                    $itemTotalRow .
                                    ':F' .
                                    $itemTotalRow
                                )
                                ->getFont()
                                ->setBold(true);


                            $itemSheet
                                ->getStyle(
                                    'D5:F' .
                                    $itemTotalRow
                                )
                                ->getNumberFormat()
                                ->setFormatCode(
                                    '#,##0.##'
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | ITEM WIDTHS
                            |--------------------------------------------------------------------------
                            */

                            $itemSheet
                                ->getColumnDimension('A')
                                ->setWidth(8);


                            $itemSheet
                                ->getColumnDimension('B')
                                ->setWidth(30);


                            $itemSheet
                                ->getColumnDimension('C')
                                ->setWidth(18);


                            $itemSheet
                                ->getColumnDimension('D')
                                ->setWidth(18);


                            $itemSheet
                                ->getColumnDimension('E')
                                ->setWidth(18);


                            $itemSheet
                                ->getColumnDimension('F')
                                ->setWidth(18);


                            $itemSheet->freezePane(
                                'A5'
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | PRINT SETTINGS - TRANSACTIONS
                            |--------------------------------------------------------------------------
                            */

                            $sheet
                                ->getPageSetup()
                                ->setOrientation(
                                    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                                );


                            $sheet
                                ->getPageSetup()
                                ->setFitToWidth(1);


                            $sheet
                                ->getPageSetup()
                                ->setFitToHeight(0);


                            /*
                            |--------------------------------------------------------------------------
                            | PRINT SETTINGS - ITEM MOVEMENT
                            |--------------------------------------------------------------------------
                            */

                            $itemSheet
                                ->getPageSetup()
                                ->setOrientation(
                                    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                                );


                            $itemSheet
                                ->getPageSetup()
                                ->setFitToWidth(1);


                            /*
                            |--------------------------------------------------------------------------
                            | OPEN SUMMARY SHEET FIRST
                            |--------------------------------------------------------------------------
                            */

                            $spreadsheet->setActiveSheetIndex(0);


                            /*
                            |--------------------------------------------------------------------------
                            | DOWNLOAD
                            |--------------------------------------------------------------------------
                            */

                            $filename =
                                'transaction_report_' .
                                date('Y-m-d_H-i-s') .
                                '.xlsx';


                            if (ob_get_length())
                            {
                                ob_end_clean();
                            }


                            header(
                                'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            );


                            header(
                                'Content-Disposition: attachment; filename="' .
                                $filename .
                                '"'
                            );


                            header(
                                'Cache-Control: max-age=0'
                            );


                            $writer =
                                new Xlsx(
                                    $spreadsheet
                                );


                            $writer->save(
                                'php://output'
                            );


                            exit;
                        }

                        if ($format === 'pdf')
                        {
                            /*
                            |--------------------------------------------------------------------------
                            | QUANTITY MOVEMENT TYPES
                            |--------------------------------------------------------------------------
                            */

                            $quantityInTypes = [
                                'purchase',
                                'purchases',
                                'stock_in',
                                'stock in'
                            ];

                            $quantityOutTypes = [
                                'sale',
                                'sales',
                                'cash_sale',
                                'cash sale',
                                'credit_sale',
                                'credit sale',
                                'stock_out',
                                'stock out'
                            ];


                            /*
                            |--------------------------------------------------------------------------
                            | PREPARE REPORT DATA
                            |--------------------------------------------------------------------------
                            |
                            | We prepare quantity movement and verified cash movement before
                            | generating the PDF.
                            |--------------------------------------------------------------------------
                            */

                            $pdfTransactions = [];

                            $totalQtyIn  = 0;
                            $totalQtyOut = 0;

                            $pdfCashIn  = 0;
                            $pdfCashOut = 0;

                            $itemSummary = [];


                            foreach ($transactions as $transaction)
                            {
                                /*
                                |--------------------------------------------------------------------------
                                | QUANTITY
                                |--------------------------------------------------------------------------
                                */

                                $quantity = (float)($transaction['quantity'] ?? 0);

                                $qtyIn  = 0;
                                $qtyOut = 0;


                                /*
                                |--------------------------------------------------------------------------
                                | TRANSACTION TYPE
                                |--------------------------------------------------------------------------
                                */

                                $transactionType =
                                    strtolower(
                                        trim(
                                            (string)($transaction['type'] ?? '')
                                        )
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | VERIFIED CASH MOVEMENT
                                |--------------------------------------------------------------------------
                                |
                                | Prefer real_cashin / real_cashout if the export query supplies them.
                                |--------------------------------------------------------------------------
                                */

                                $rowCashIn =
                                    isset($transaction['real_cashin'])
                                        ? (float)$transaction['real_cashin']
                                        : (float)($transaction['credit_amount'] ?? 0);


                                $rowCashOut =
                                    isset($transaction['real_cashout'])
                                        ? (float)$transaction['real_cashout']
                                        : (float)($transaction['debit_amount'] ?? 0);


                                /*
                                |--------------------------------------------------------------------------
                                | EXPLICIT QUANTITY DIRECTION
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    in_array(
                                        $transactionType,
                                        $quantityInTypes,
                                        true
                                    )
                                )
                                {
                                    $qtyIn = $quantity;
                                }
                                elseif (
                                    in_array(
                                        $transactionType,
                                        $quantityOutTypes,
                                        true
                                    )
                                )
                                {
                                    $qtyOut = $quantity;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | FALLBACK QUANTITY DIRECTION
                                |--------------------------------------------------------------------------
                                |
                                | Older records may not have consistent transaction type values.
                                |
                                | Cash Out + Item + Qty = likely purchase => Qty In
                                | Cash In  + Item + Qty = likely sale     => Qty Out
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $quantity > 0 &&
                                    $qtyIn == 0 &&
                                    $qtyOut == 0 &&
                                    !empty($transaction['item_id'])
                                )
                                {
                                    if (
                                        $rowCashOut > 0 &&
                                        $rowCashIn <= 0
                                    )
                                    {
                                        $qtyIn = $quantity;
                                    }
                                    elseif (
                                        $rowCashIn > 0 &&
                                        $rowCashOut <= 0
                                    )
                                    {
                                        $qtyOut = $quantity;
                                    }
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | NET QUANTITY
                                |--------------------------------------------------------------------------
                                */

                                $netQty = $qtyIn - $qtyOut;


                                /*
                                |--------------------------------------------------------------------------
                                | TOTALS
                                |--------------------------------------------------------------------------
                                */

                                $totalQtyIn +=
                                    $qtyIn;

                                $totalQtyOut +=
                                    $qtyOut;

                                $pdfCashIn +=
                                    $rowCashIn;

                                $pdfCashOut +=
                                    $rowCashOut;


                                /*
                                |--------------------------------------------------------------------------
                                | ITEM SUMMARY
                                |--------------------------------------------------------------------------
                                */

                                $itemId =
                                    (int)($transaction['item_id'] ?? 0);


                                if ($itemId > 0)
                                {
                                    if (!isset($itemSummary[$itemId]))
                                    {
                                        $itemSummary[$itemId] = [

                                            'name' =>
                                                $transaction['item_name']
                                                ?? 'Unnamed Item',

                                            'transactions' => 0,

                                            'qty_in' => 0,

                                            'qty_out' => 0
                                        ];
                                    }


                                    $itemSummary[$itemId]['transactions']++;

                                    $itemSummary[$itemId]['qty_in'] +=
                                        $qtyIn;

                                    $itemSummary[$itemId]['qty_out'] +=
                                        $qtyOut;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | STORE PREPARED VALUES
                                |--------------------------------------------------------------------------
                                */

                                $transaction['_qty_in'] =
                                    $qtyIn;

                                $transaction['_qty_out'] =
                                    $qtyOut;

                                $transaction['_net_qty'] =
                                    $netQty;

                                $transaction['_cash_in'] =
                                    $rowCashIn;

                                $transaction['_cash_out'] =
                                    $rowCashOut;


                                $pdfTransactions[] =
                                    $transaction;
                            }

                            $netQuantity =  $totalQtyIn - $totalQtyOut;
                            $financialBalance = $pdfCashIn - $pdfCashOut;
                            $pdfTransactionCount = count($pdfTransactions);


                            /*
                            |--------------------------------------------------------------------------
                            | PDF CLASS
                            |--------------------------------------------------------------------------
                            */

                            class CashbookTransactionReportPDF extends FPDF
                            {
                                public $businessName = '';
                                public $reportTitle = 'TRANSACTION REPORT';

                                /*
                                |--------------------------------------------------------------------------
                                | PAGE HEADER
                                |--------------------------------------------------------------------------
                                */

                                function Header()
                                {
                                    $this->SetFont(
                                        'Arial',
                                        'B',
                                        16
                                    );


                                    $this->Cell(
                                        0,
                                        8,
                                        strtoupper(
                                            $this->businessName
                                        ),
                                        0,
                                        1,
                                        'C'
                                    );


                                    $this->SetFont(
                                        'Arial',
                                        'B',
                                        12
                                    );


                                    $this->Cell(
                                        0,
                                        7,
                                        $this->reportTitle,
                                        0,
                                        1,
                                        'C'
                                    );


                                    $this->SetFont(
                                        'Arial',
                                        '',
                                        8
                                    );


                                    $this->Cell(
                                        0,
                                        5,
                                        'Generated: ' .
                                        date('d-m-Y H:i'),
                                        0,
                                        1,
                                        'C'
                                    );


                                    $this->Ln(4);
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | TRANSACTION TABLE HEADER
                                |--------------------------------------------------------------------------
                                |
                                | Landscape A4 usable width with 8mm margins:
                                |
                                | 297 - 16 = 281mm
                                |--------------------------------------------------------------------------
                                */

                                function TableHeader()
                                {
                                    $this->SetFont(
                                        'Arial',
                                        'B',
                                        6.5
                                    );


                                    $this->Cell(
                                        7,
                                        7,
                                        '#',
                                        1,
                                        0,
                                        'C'
                                    );


                                    $this->Cell(
                                        24,
                                        7,
                                        'Date',
                                        1,
                                        0,
                                        'C'
                                    );


                                    $this->Cell(
                                        27,
                                        7,
                                        'Category',
                                        1,
                                        0,
                                        'C'
                                    );


                                    $this->Cell(
                                        27,
                                        7,
                                        'Item',
                                        1,
                                        0,
                                        'C'
                                    );


                                    $this->Cell(
                                        13,
                                        7,
                                        'Qty In',
                                        1,
                                        0,
                                        'R'
                                    );


                                    $this->Cell(
                                        13,
                                        7,
                                        'Qty Out',
                                        1,
                                        0,
                                        'R'
                                    );


                                    $this->Cell(
                                        13,
                                        7,
                                        'Net',
                                        1,
                                        0,
                                        'R'
                                    );


                                    $this->Cell(
                                        25,
                                        7,
                                        'Customer',
                                        1,
                                        0,
                                        'C'
                                    );


                                    $this->Cell(
                                        50,
                                        7,
                                        'Details',
                                        1,
                                        0,
                                        'C'
                                    );


                                    $this->Cell(
                                        27,
                                        7,
                                        'Cash In',
                                        1,
                                        0,
                                        'R'
                                    );


                                    $this->Cell(
                                        27,
                                        7,
                                        'Cash Out',
                                        1,
                                        0,
                                        'R'
                                    );


                                    $this->Cell(
                                        28,
                                        7,
                                        'Balance',
                                        1,
                                        1,
                                        'R'
                                    );


                                    $this->SetFont(
                                        'Arial',
                                        '',
                                        6.5
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | PAGE FOOTER
                                |--------------------------------------------------------------------------
                                */

                                function Footer()
                                {
                                    $this->SetY(-15);


                                    $this->SetFont(
                                        'Arial',
                                        '',
                                        7
                                    );


                                    $this->Cell(
                                        0,
                                        5,
                                        'Page ' .
                                        $this->PageNo() .
                                        ' | Cashbook Transaction Report',
                                        0,
                                        0,
                                        'C'
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CHECK TABLE PAGE BREAK
                                |--------------------------------------------------------------------------
                                */

                                function CheckTablePageBreak(
                                    $height = 6
                                )
                                {
                                    $bottomMargin =
                                        18;


                                    $pageHeight =
                                        $this->GetPageHeight();


                                    $currentY =
                                        $this->GetY();


                                    if (
                                        $currentY +
                                        $height +
                                        $bottomMargin
                                        >
                                        $pageHeight
                                    )
                                    {
                                        $this->AddPage();

                                        $this->TableHeader();

                                        $this->SetFont(
                                            'Arial',
                                            '',
                                            6.5
                                        );
                                    }
                                }
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | CREATE PDF
                            |--------------------------------------------------------------------------
                            */

                            $pdf =
                                new CashbookTransactionReportPDF(
                                    'L',
                                    'mm',
                                    'A4'
                                );


                            $pdf->businessName =
                                $book->name ?? 'BUSINESS';


                            $pdf->SetMargins(
                                8,
                                8,
                                8
                            );


                            $pdf->SetAutoPageBreak(
                                true,
                                18
                            );


                            $pdf->AddPage();


                            /*
                            |--------------------------------------------------------------------------
                            | FINANCIAL SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            $pdf->SetFont(
                                'Arial',
                                'B',
                                9
                            );


                            $summaryWidth =
                                42;


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                'CASH IN',
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                'CASH OUT',
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                'NET BALANCE',
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                'TRANSACTIONS',
                                1,
                                1,
                                'C'
                            );


                            $pdf->SetFont(
                                'Arial',
                                '',
                                9
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                number_format(
                                    $pdfCashIn,
                                    0
                                ),
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                number_format(
                                    $pdfCashOut,
                                    0
                                ),
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                number_format(
                                    $financialBalance,
                                    0
                                ),
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                number_format(
                                    $pdfTransactionCount,
                                    0
                                ),
                                1,
                                1,
                                'C'
                            );


                            $pdf->Ln(3);


                            /*
                            |--------------------------------------------------------------------------
                            | QUANTITY SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            $pdf->SetFont(
                                'Arial',
                                'B',
                                9
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                'QTY IN',
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                'QTY OUT',
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                'NET QTY',
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                'ITEMS',
                                1,
                                1,
                                'C'
                            );


                            $pdf->SetFont(
                                'Arial',
                                '',
                                9
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                number_format(
                                    $totalQtyIn,
                                    2
                                ),
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                number_format(
                                    $totalQtyOut,
                                    2
                                ),
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                number_format(
                                    $netQuantity,
                                    2
                                ),
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                $summaryWidth,
                                7,
                                number_format(
                                    count($itemSummary),
                                    0
                                ),
                                1,
                                1,
                                'C'
                            );


                            $pdf->Ln(4);


                            /*
                            |--------------------------------------------------------------------------
                            | FILTER INFORMATION
                            |--------------------------------------------------------------------------
                            */

                            $filterText = [];


                            /*
                            | DATE FROM
                            */

                            if (
                                !empty(
                                    $filters['min_date']
                                )
                            )
                            {
                                $filterText[] =

                                    'From: ' .

                                    date(
                                        'd-m-Y',
                                        strtotime(
                                            $filters['min_date']
                                        )
                                    );
                            }


                            /*
                            | DATE TO
                            */

                            if (
                                !empty(
                                    $filters['max_date']
                                )
                            )
                            {
                                $filterText[] =

                                    'To: ' .

                                    date(
                                        'd-m-Y',
                                        strtotime(
                                            $filters['max_date']
                                        )
                                    );
                            }


                            /*
                            | MONTH
                            */

                            if (
                                !empty(
                                    $filters['month']
                                )
                            )
                            {
                                $monthNumber =
                                    (int)$filters['month'];


                                if (
                                    $monthNumber >= 1 &&
                                    $monthNumber <= 12
                                )
                                {
                                    $filterText[] =

                                        'Month: ' .

                                        date(
                                            'F',
                                            mktime(
                                                0,
                                                0,
                                                0,
                                                $monthNumber,
                                                1
                                            )
                                        );
                                }
                            }


                            /*
                            | YEAR
                            */

                            if (
                                !empty(
                                    $filters['year']
                                )
                            )
                            {
                                $filterText[] =

                                    'Year: ' .

                                    $filters['year'];
                            }


                            /*
                            | TYPE
                            */

                            if (
                                !empty(
                                    $filters['type']
                                )
                            )
                            {
                                $filterText[] =

                                    'Type: ' .

                                    (
                                        $filters['type']
                                        === 'credit'

                                        ? 'Cash In'

                                        : 'Cash Out'
                                    );
                            }


                            /*
                            | CATEGORY
                            */

                            if (
                                !empty(
                                    $filters['category']
                                )
                            )
                            {
                               $catSql = "
                                    SELECT name
                                    FROM cashbook_categories
                                    WHERE id = ?
                                    AND book_id = ?
                                    LIMIT 1
                                ";

                                $catRes = prepared_statements(
                                    $catSql,
                                    'ii',
                                    [
                                        (int)$filters['category'],
                                        (int)$book->id
                                    ]
                                );  


                                if ($catRes)
                                {
                                    $cat =$catRes->fetch_assoc();
                                    if ($cat)
                                    {
                                        $filterText[] = 'Category: ' . $cat['name'];
                                    }
                                }
                            }


                            /*
                            | CUSTOMER
                            */

                            if (!empty($filters['customer']))
                            {
                                $custSql = "SELECT name
                                    FROM cashbook_customers
                                    WHERE id = ?
                                    AND book_id = ?
                                    LIMIT 1
                                ";

                                $custRes = prepared_statements($custSql,'ii',[(int)$filters['customer'],(int)$book->id]);
                                if ($custRes)
                                {
                                    $cust =$custRes->fetch_assoc();
                                    if ($cust)
                                    {
                                        $filterText[] ='Customer: ' . $cust['name'];
                                    }
                                }
                            }


                            /*
                            | ITEM
                            */

                            if (
                                !empty(
                                    $filters['item']
                                )
                            )
                            {
                                $itemSql = "SELECT name
                                    FROM cashbook_items
                                    WHERE id = ?
                                    AND book_id = ?
                                    LIMIT 1
                                ";

                                $itemRes = prepared_statements(
                                    $itemSql,
                                    'ii',
                                    [
                                        (int)$filters['item'],
                                        (int)$book->id
                                    ]
                                );

                                if($itemRes)
                                {
                                    $itm = $itemRes->fetch_assoc();

                                    if($itm)
                                    {
                                        $filterText[] =
                                            'Item: ' . $itm['name'];
                                    }
                                }
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | PRINT FILTERS
                            |--------------------------------------------------------------------------
                            */

                            $pdf->SetFont(
                                'Arial',
                                'B',
                                8
                            );


                            $pdf->Cell(
                                0,
                                5,
                                'REPORT FILTERS',
                                0,
                                1
                            );


                            $pdf->SetFont(
                                'Arial',
                                '',
                                8
                            );


                            $pdf->MultiCell(
                                0,
                                5,

                                !empty($filterText)

                                    ? implode(
                                        ' | ',
                                        $filterText
                                    )

                                    : 'All Transactions',

                                0,

                                'L'
                            );


                            $pdf->Ln(3);


                            /*
                            |--------------------------------------------------------------------------
                            | TRANSACTION DATA
                            |--------------------------------------------------------------------------
                            */

                            $pdf->SetFont(
                                'Arial',
                                '',
                                6.5
                            );


                            $number =  1;


                            /*
                            | Running financial balance.
                            */

                            $runningBalance = 0;


                            /*
                            | Add first table header.
                            */

                            $pdf->TableHeader();


                            foreach ( $pdfTransactions as $transaction)
                            {
                                /*
                                |--------------------------------------------------------------------------
                                | PAGE BREAK
                                |--------------------------------------------------------------------------
                                */

                                $pdf->CheckTablePageBreak(
                                    6
                                );


                                /*
                                |--------------------------------------------------------------------------
                                | VALUES
                                |--------------------------------------------------------------------------
                                */

                                $date =

                                    !empty(
                                        $transaction[
                                            'created_at'
                                        ]
                                    )

                                        ? date(
                                            'd-m-Y H:i',

                                            strtotime(
                                                $transaction[
                                                    'created_at'
                                                ]
                                            )
                                        )

                                        : '';


                                $category =
                                    $transaction[
                                        'category_name'
                                    ] ?? '';


                                $item =
                                    $transaction[
                                        'item_name'
                                    ] ?? '';


                                $customer =
                                    $transaction[
                                        'customer_name'
                                    ] ?? '';


                                $details =
                                    $transaction[
                                        'details'
                                    ] ?? '';


                                $qtyIn =
                                    (float)(
                                        $transaction[
                                            '_qty_in'
                                        ]
                                        ?? 0
                                    );


                                $qtyOut =
                                    (float)(
                                        $transaction[
                                            '_qty_out'
                                        ]
                                        ?? 0
                                    );


                                $rowNetQty =
                                    (float)(
                                        $transaction[
                                            '_net_qty'
                                        ]
                                        ?? 0
                                    );


                                $credit =
                                    (float)(
                                        $transaction[
                                            '_cash_in'
                                        ]
                                        ?? 0
                                    );


                                $debit =
                                    (float)(
                                        $transaction[
                                            '_cash_out'
                                        ]
                                        ?? 0
                                    );
                                
                                if($debit == $credit) $debit = 0;

                                /*
                                |--------------------------------------------------------------------------
                                | RUNNING BALANCE
                                |--------------------------------------------------------------------------
                                */

                                $runningBalance += $credit - $debit;


                                /*
                                |--------------------------------------------------------------------------
                                | LIMIT TEXT
                                |--------------------------------------------------------------------------
                                */

                                $category =
                                    mb_substr(
                                        $category,
                                        0,
                                        22
                                    );


                                $item =
                                    mb_substr(
                                        $item,
                                        0,
                                        22
                                    );


                                $customer =
                                    mb_substr(
                                        $customer,
                                        0,
                                        20
                                    );


                                $details =
                                    mb_substr(
                                        $details,
                                        0,
                                        48
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | DRAW ROW
                                |--------------------------------------------------------------------------
                                */


                                /*
                                | #
                                */

                                $pdf->Cell(
                                    7,
                                    6,
                                    $number,
                                    1,
                                    0,
                                    'C'
                                );


                                /*
                                | DATE
                                */

                                $pdf->Cell(
                                    24,
                                    6,
                                    $date,
                                    1
                                );


                                /*
                                | CATEGORY
                                */

                                $pdf->Cell(
                                    27,
                                    6,
                                    $category,
                                    1
                                );


                                /*
                                | ITEM
                                */

                                $pdf->Cell(
                                    27,
                                    6,
                                    $item,
                                    1
                                );


                                /*
                                | QTY IN
                                */

                                $pdf->Cell(
                                    13,
                                    6,

                                    $qtyIn > 0

                                        ? (
                                            floor($qtyIn)
                                            == $qtyIn

                                            ? number_format(
                                                $qtyIn,
                                                0
                                            )

                                            : number_format(
                                                $qtyIn,
                                                2
                                            )
                                        )

                                        : '',

                                    1,
                                    0,
                                    'R'
                                );


                                /*
                                | QTY OUT
                                */

                                $pdf->Cell(
                                    13,
                                    6,

                                    $qtyOut > 0

                                        ? (
                                            floor($qtyOut)
                                            == $qtyOut

                                            ? number_format(
                                                $qtyOut,
                                                0
                                            )

                                            : number_format(
                                                $qtyOut,
                                                2
                                            )
                                        )

                                        : '',

                                    1,
                                    0,
                                    'R'
                                );


                                /*
                                | NET QTY
                                */

                                $pdf->Cell(
                                    13,
                                    6,

                                    $rowNetQty != 0

                                        ? (
                                            floor($rowNetQty)
                                            == $rowNetQty

                                            ? number_format(
                                                $rowNetQty,
                                                0
                                            )

                                            : number_format(
                                                $rowNetQty,
                                                2
                                            )
                                        )

                                        : '',

                                    1,
                                    0,
                                    'R'
                                );


                                /*
                                | CUSTOMER
                                */

                                $pdf->Cell(
                                    25,
                                    6,
                                    $customer,
                                    1
                                );


                                /*
                                | DETAILS
                                */

                                $pdf->Cell(
                                    50,
                                    6,
                                    $details,
                                    1
                                );


                                /*
                                | CASH IN
                                */

                                $pdf->Cell(
                                    27,
                                    6,

                                    $credit > 0

                                        ? number_format(
                                            $credit,
                                            0
                                        )

                                        : '',

                                    1,
                                    0,
                                    'R'
                                );


                                /*
                                | CASH OUT
                                */

                                $pdf->Cell(
                                    27,
                                    6,

                                    $debit > 0

                                        ? number_format(
                                            $debit,
                                            0
                                        )

                                        : '',

                                    1,
                                    0,
                                    'R'
                                );


                                /*
                                | RUNNING BALANCE
                                */

                                $pdf->Cell(
                                    28,
                                    6,

                                    number_format(
                                        $runningBalance,
                                        0
                                    ),

                                    1,
                                    1,
                                    'R'
                                );


                                $number++;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | TRANSACTION TOTAL
                            |--------------------------------------------------------------------------
                            */

                            $pdf->CheckTablePageBreak(
                                7
                            );


                            $pdf->SetFont(
                                'Arial',
                                'B',
                                7
                            );


                            /*
                            | # + Date + Category + Item
                            |
                            | 7 + 24 + 27 + 27 = 85
                            */

                            $pdf->Cell(
                                85,
                                7,
                                'TOTAL',
                                1,
                                0,
                                'R'
                            );


                            /*
                            | Qty In
                            */

                            $pdf->Cell(
                                13,
                                7,

                                number_format(
                                    $totalQtyIn,
                                    2
                                ),

                                1,
                                0,
                                'R'
                            );


                            /*
                            | Qty Out
                            */

                            $pdf->Cell(
                                13,
                                7,

                                number_format(
                                    $totalQtyOut,
                                    2
                                ),

                                1,
                                0,
                                'R'
                            );


                            /*
                            | Net Qty
                            */

                            $pdf->Cell(
                                13,
                                7,

                                number_format(
                                    $netQuantity,
                                    2
                                ),

                                1,
                                0,
                                'R'
                            );


                            /*
                            | Customer + Details
                            |
                            | 25 + 50 = 75
                            */

                            $pdf->Cell(
                                75,
                                7,
                                '',
                                1
                            );


                            /*
                            | Cash In
                            */

                            $pdf->Cell(
                                27,
                                7,

                                number_format(
                                    $pdfCashIn,
                                    0
                                ),

                                1,
                                0,
                                'R'
                            );


                            /*
                            | Cash Out
                            */

                            $pdf->Cell(
                                27,
                                7,

                                number_format(
                                    $pdfCashOut,
                                    0
                                ),

                                1,
                                0,
                                'R'
                            );


                            /*
                            | Balance
                            */

                            $pdf->Cell(
                                28,
                                7,

                                number_format(
                                    $financialBalance,
                                    0
                                ),

                                1,
                                1,
                                'R'
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | ITEM MOVEMENT SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            if (!empty($itemSummary))
                            {
                                /*
                                |--------------------------------------------------------------------------
                                | NEW PAGE
                                |--------------------------------------------------------------------------
                                */

                                $pdf->AddPage();


                                $pdf->SetFont(
                                    'Arial',
                                    'B',
                                    11
                                );


                                $pdf->Cell(
                                    0,
                                    8,
                                    'ITEM QUANTITY MOVEMENT SUMMARY',
                                    0,
                                    1,
                                    'C'
                                );


                                $pdf->Ln(2);


                                /*
                                |--------------------------------------------------------------------------
                                | ITEM TABLE HEADER
                                |--------------------------------------------------------------------------
                                */

                                $pdf->SetFont(
                                    'Arial',
                                    'B',
                                    8
                                );


                                $pdf->Cell(
                                    10,
                                    7,
                                    '#',
                                    1,
                                    0,
                                    'C'
                                );


                                $pdf->Cell(
                                    80,
                                    7,
                                    'Item',
                                    1
                                );


                                $pdf->Cell(
                                    40,
                                    7,
                                    'Transactions',
                                    1,
                                    0,
                                    'C'
                                );


                                $pdf->Cell(
                                    45,
                                    7,
                                    'Qty In',
                                    1,
                                    0,
                                    'R'
                                );


                                $pdf->Cell(
                                    45,
                                    7,
                                    'Qty Out',
                                    1,
                                    0,
                                    'R'
                                );


                                $pdf->Cell(
                                    45,
                                    7,
                                    'Net Qty',
                                    1,
                                    1,
                                    'R'
                                );


                                /*
                                |--------------------------------------------------------------------------
                                | ITEM ROWS
                                |--------------------------------------------------------------------------
                                */

                                $pdf->SetFont(
                                    'Arial',
                                    '',
                                    8
                                );


                                $itemNumber =
                                    1;


                                foreach (
                                    $itemSummary
                                    as $summary
                                )
                                {
                                    /*
                                    | Page break for item table.
                                    */

                                    if (
                                        $pdf->GetY() + 7 + 18
                                        >
                                        $pdf->GetPageHeight()
                                    )
                                    {
                                        $pdf->AddPage();


                                        $pdf->SetFont(
                                            'Arial',
                                            'B',
                                            8
                                        );


                                        $pdf->Cell(
                                            10,
                                            7,
                                            '#',
                                            1,
                                            0,
                                            'C'
                                        );


                                        $pdf->Cell(
                                            80,
                                            7,
                                            'Item',
                                            1
                                        );


                                        $pdf->Cell(
                                            40,
                                            7,
                                            'Transactions',
                                            1,
                                            0,
                                            'C'
                                        );


                                        $pdf->Cell(
                                            45,
                                            7,
                                            'Qty In',
                                            1,
                                            0,
                                            'R'
                                        );


                                        $pdf->Cell(
                                            45,
                                            7,
                                            'Qty Out',
                                            1,
                                            0,
                                            'R'
                                        );


                                        $pdf->Cell(
                                            45,
                                            7,
                                            'Net Qty',
                                            1,
                                            1,
                                            'R'
                                        );


                                        $pdf->SetFont(
                                            'Arial',
                                            '',
                                            8
                                        );
                                    }


                                    $itemNetQty =

                                        (float)$summary[
                                            'qty_in'
                                        ]

                                        -

                                        (float)$summary[
                                            'qty_out'
                                        ];


                                    $pdf->Cell(
                                        10,
                                        7,
                                        $itemNumber,
                                        1,
                                        0,
                                        'C'
                                    );


                                    $pdf->Cell(
                                        80,
                                        7,

                                        mb_substr(
                                            $summary['name'],
                                            0,
                                            45
                                        ),

                                        1
                                    );


                                    $pdf->Cell(
                                        40,
                                        7,

                                        number_format(
                                            $summary[
                                                'transactions'
                                            ],
                                            0
                                        ),

                                        1,
                                        0,
                                        'C'
                                    );


                                    $pdf->Cell(
                                        45,
                                        7,

                                        number_format(
                                            $summary[
                                                'qty_in'
                                            ],
                                            2
                                        ),

                                        1,
                                        0,
                                        'R'
                                    );


                                    $pdf->Cell(
                                        45,
                                        7,

                                        number_format(
                                            $summary[
                                                'qty_out'
                                            ],
                                            2
                                        ),

                                        1,
                                        0,
                                        'R'
                                    );


                                    $pdf->Cell(
                                        45,
                                        7,

                                        number_format(
                                            $itemNetQty,
                                            2
                                        ),

                                        1,
                                        1,
                                        'R'
                                    );


                                    $itemNumber++;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | ITEM TOTAL
                                |--------------------------------------------------------------------------
                                */

                                $pdf->SetFont(
                                    'Arial',
                                    'B',
                                    8
                                );


                                $pdf->Cell(
                                    130,
                                    7,
                                    'TOTAL MOVEMENT',
                                    1,
                                    0,
                                    'R'
                                );


                                $pdf->Cell(
                                    45,
                                    7,

                                    number_format(
                                        $totalQtyIn,
                                        2
                                    ),

                                    1,
                                    0,
                                    'R'
                                );


                                $pdf->Cell(
                                    45,
                                    7,

                                    number_format(
                                        $totalQtyOut,
                                        2
                                    ),

                                    1,
                                    0,
                                    'R'
                                );


                                $pdf->Cell(
                                    45,
                                    7,

                                    number_format(
                                        $netQuantity,
                                        2
                                    ),

                                    1,
                                    1,
                                    'R'
                                );
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | DOWNLOAD
                            |--------------------------------------------------------------------------
                            */

                            $filename =

                                'transaction_report_' .

                                date(
                                    'Y-m-d_H-i-s'
                                ) .

                                '.pdf';


                            if (ob_get_length())
                            {
                                ob_end_clean();
                            }


                            $pdf->Output(
                                'D',
                                $filename
                            );


                            exit;
                        }
                    break;
                case 'setEntryDate':
                        $date = request('entry_date');
                        $_SESSION['entry_date'] = $date ?? '';
                    break;
                case 'ResetsetEntryDate':
                        unset($_SESSION['entry_date']);
                    break;
            }
        }
            
        }else{
        redirect('../');
    }
?>