<?php
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
                                                        <input type="datetime-local" name="created_at" id="created_at" value="<?= date('Y-m-d\TH:i') ?>" class="form-control" required>
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
                                $creditor_id = request('creditor_id') ?? "";

                                if(!empty($invoice_id) || $invoice_id > 0 )
                                {
                                    $invoice = invoiceFind($invoice_id);
                                    $details = $invoice->invoice_no;
                                }

                                // distribute according to the transaction type
                                $credit = 0;
                                $debit = 0;

                                // CASH SALE
                                if($type === 'cash_sale')
                                {
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

                                //other_income
                                 if($type === 'creditorInjection'){
                                    $credit = (float)$amount; 
                                    $creditable = (float)$amount;
                                }
                                // save the content
                                $sql = "INSERT INTO cashbook_transactions  SET credit_amount = ?, debit_amount = ?, book_id = ?, details = ?, 
                                            category_id = ?,  paymode_id = ?, created_at = ?, user_id = ?,  type = ?,  customer_id = ?, 
                                            item_id = ?,  quantity = ?, invoice_id = ?,creditor_id = ?";

                                $res = prepared_statements(
                                    $sql,'ddisiisisiiiii',[$credit,$debit,$book_id, $details,$category_id,$payment_mode,$date,$user_id, $type,$customer_id,$item_id,$qty,$invoice_id,$creditor_id]
                                );

                                $trans_id = $server->insert_id;

                                // update invoices if not empty
                                if(!empty($invoice_id) || $invoice_id > 0 )
                                {
                                    $details = $invoice->invoice_no;
                                    $total_paid = $invoice->paid_amount + $amount;
                                    $total_balance = $invoice->balance - $amount;

                                    // update invoice
                                    $update = "UPDATE cashbook_invoices SET paid_amount = ?, balance = balance - ? WHERE id = ?";
                                    prepared_statements($update,'ddi',[$total_paid,$total_paid,$invoice_id]);
                                }
                               
                                 // credit cashins       
                                if($credit > 0)
                                {
                                    // insert into cashins table
                                    $stmt = "INSERT INTO  cashbook_cashins SET type = ?,customer_id = ?, amount = ?, category_id = ?, details = ?,book_id = ?,paymode_id = ?,transaction_id = ?,created_at=?,user_id=?,item_id = ?, quantity = ?, creditor_id = ?";
                                    prepared_statements($stmt,'siiisiiisiiii',[$type,$customer_id,$amount,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$creditor_id]);
                                }

                                // update stock item records
                                if(!empty($item_id) && $qty > 0 && $type != 'creditorInjection')
                                {
                                    stockOut($item_id,$qty,$trans_id);
                                }

                                // check if customer has been selected and update the ledger
                                if(!empty($customer_id))
                                {
                                    // update customer ledger
                                    customerLedgerUpdate($customer_id,$creditable,$debit,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$type);
                                }

                                // check if creditor has been selected and update the ledger
                                if($creditor_id > 0)
                                {
                                    // update customer ledger
                                    creditorLedgerUpdate($creditor_id,$creditable,$debit,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$type);
                                }

                                $_SESSION['success'] ='cashin save side';
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
                                $type = request('transaction_type');
                                $invoice_id = isset($_POST['invoice_id']) ? request('invoice_id') : "";
                                $creditor_id = request('creditor_id') ?? "";
                                $transaction = transactionFind($transid);
                                $book_id = $transaction->book_id;
                                $old_item_id = $transaction->item_id;
                                $old_quantity = (float) $transaction->quantity;
                                $old_type = $transaction->type;

                                // // track transaction edits
                                trackTransactionEdits($transid,'edit');

                                // save the content
                                $sql = "UPDATE cashbook_transactions SET type = ?, credit_amount = ?, details = ?,category_id = ?,paymode_id=?,created_at=?,user_id = ?,customer_id=?,item_id = ?, quantity = ?,invoice_id = ? WHERE id = ?";
                                $res = prepared_statements($sql,'sisiissiiiii',[$type,$amount,$details,$category_id,$payment_mode,$date,$user_id,$customer_id,$item_id,$quantity,$invoice_id,$transid]);
                                
                                // Chcek type to enter this transaction
                                if($type === 'cash_sale' || $type === 'payment' || $type === 'other_income')
                                {
                                    $stmt = "UPDATE cashbook_cashins SET type = ?,customer_id = ?, amount = ?, category_id = ?, details = ?,
                                            paymode_id = ?,created_at = ?,user_id = ?,item_id = ?, quantity = ? 
                                        WHERE transaction_id = ?";
                                    prepared_statements($stmt,'sidisisiiii',[$type,$customer_id,$amount,$category_id,$details,$payment_mode,$date,$user_id,$item_id,$quantity,$transid]);
                                }

                                if ($type === 'cash_sale' || $type === 'payment' || $type === 'other_income') 
                                {
                                    // If the old transaction had a stock item,
                                    // first restore its previous stock movement.
                                    if (!empty($old_item_id) && $old_quantity > 0) {
                                        restoreStockOut($old_item_id, $old_quantity);
                                    }

                                    // Apply the new stock-out
                                    if (!empty($item_id) && $quantity > 0) 
                                    {
                                        stockOut($item_id, $quantity, $transid);
                                    }
                                }

                                // would need to handle customer ledger records
                                if(!empty($customer_id))
                                {
                                    // update customer ledger
                                    customerLedgerUpdate($customer_id,$amount,0,$category_id,$details,$book_id,$payment_mode,$transid,$date,$user_id,$item_id,$quantity,$type);
                                }

                                // check if creditor has been selected and update the ledger
                                if(!empty($creditor_id))
                                {
                                    // update customer ledger
                                    creditorLedgerUpdate($creditor_id,$creditable,$debit,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$type);
                                }

                                $_SESSION['success'] = "Data saved";

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
                                $sql = "INSERT INTO cashbook_transactions SET item_id = ?, quantity = ?, rate=?, debit_amount = ?,book_id = ?, details = ?,category_id=?,paymode_id = ?,created_at = ?,user_id=?,type=?,customer_id=?";
                                $res = prepared_statements($sql,'iiiiisiisisi',[$item_id,$qty,$purchase_rate,$amount,$book_id,$details,$category_id,$payment_mode,$date,$user_id,$type,$customer_id]);
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

                                $details = request('cashout_details');
                                $category_id = request('category_id');
                                $amount = request('outamount');
                                $payment_mode = request('paymode_id');
                                $transid = request('transaction_id');
                                $date = request('created_at');
                                $customer_id = request('customer_id');
                                $type = request('expense_type');

                                $item_id = isset($_POST['item_id']) ? request('item_id') : "";
                                $qty = isset($_POST['quantity']) ? request('quantity') : "";
                                $purchase_rate = isset($_POST['purchase_rate']) ? request('purchase_rate') : "";
                                $suplier_id = isset($_POST['suplier_id']) ? request('suplier_id') : "";
                                $book_id = transactionFind($transid)->book_id;

                                // record transaction edits
                                trackTransactionEdits($transid,'edit');

                                // save the content
                                $sql = "UPDATE cashbook_transactions SET item_id = ?, quantity = ?, rate=?, type=?, debit_amount = ?, details = ?,category_id=?,paymode_id = ?,created_at=?,user_id = ?,customer_id=? WHERE id = ?";
                                $res = prepared_statements($sql,'iiisisiisiii',[$item_id,$qty,$purchase_rate,$type,$amount,$details,$category_id,$payment_mode,$date,$user_id,$customer_id,$transid]);
                                

                                // check if the transaction is a purchase and save to purchases table
                                if($type == 'purchase' && !empty($item_id) && !empty($qty) && !empty($purchase_rate))
                                {
                                    // fetch the purchase record for this transaction
                                    $purchase_check = "SELECT * FROM cashbook_purchases WHERE transaction_id = ?";
                                    $purchase = prepared_statements($purchase_check,'i',[$transid]);
                                    
                                    if($purchase->num_rows > 0)
                                    {
                                        // update the purchase record
                                        $stmt = "UPDATE cashbook_purchases SET item_id = ?, quantity = ?, unit_price = ?, total = ?, book_id = ?, created_at = ?, user_id = ? WHERE transaction_id = ?";
                                        prepared_statements($stmt,'iiidisi',[$item_id,$qty,$purchase_rate,$amount,$book_id,$date,$user_id,$transid]);
                                    
                                    }else{
                                        // insert record if does not exixt
                                        $stmt = "INSERT INTO cashbook_purchases SET transaction_id = ?, item_id = ?, quantity = ?, unit_price = ?, total = ?, book_id = ?, created_at = ?, user_id = ?";
                                        prepared_statements($stmt,'iiiidisi',[$trans_id,$item_id,$qty,$purchase_rate,$amount,$book_id,$date,$user_id]);
                                    }
                                }
    
                                // enter borrowing records
                                if(($type =='borrowing' || $type == 'credit_sale') && !empty($customer_id))
                                {
                                    // update customer ledger
                                    customerLedgerUpdate($customer_id,0,$amount,$category_id,$details,$book_id,$payment_mode,$transid,$date,$user_id,$item_id,$qty,$type);
                                }

                                 if($type == 'creditorPayment' && !empty($creditor_id))
                                {
                                    // update customer ledger
                                    CreditorLedgerUpdate($creditor_id,0,$amount,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$type);
                                }

                                // update cashouts table
                                $stmt = "UPDATE cashbook_cashouts SET item_id = ?, type=?,quantity=?,rate=?, amount = ?, category_id = ?, details = ?,paymode_id = ?,created_at=?,user_id =? WHERE transaction_id = ?";
                                prepared_statements($stmt,'isiiiisisii',[$item_id,$type,$qty,$purchase_rate,$amount,$category_id,$details,$payment_mode,$date,$user_id,$transid]);
                                $_SESSION['success'] = "checked";

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
                                $trans = "INSERT INTO cashbook_transactions SET customer_id = ?, details = ?, debit_amount = ?,user_id = ?, book_id = ?, type = ?, invoice_id = ?,created_at = ?";
                                $trans_id = prepared_statements($trans,'isdiisis',[$customer_id,$invoice->invoice_no,$amountt,$user_id,$book_id,'Invoice',$invoice_id,$invoice->created_at]);

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
                                $book_id = request('book_id');
                                $name = request('name');
                                $details = request('details');
                                $user_id = auth()->id;
                                $route_id = request('route_id') ?? "";

                                if(empty($route_id))
                                {
                                    $sql = "INSERT INTO cashbook_routes SET book_id = ?, name = ?,details = ?, user_id = ?";
                                    prepared_statements($sql,'issi',[$book_id,$name,$details,$user_id]);    
                                    $record_id = $server->insert_id;
                                }else{
                                    $sql = "UPDATE cashbook_routes SET name = ?,details = ?, user_id = ? WHERE id = ?";
                                    prepared_statements($sql,'ssii',[$name,$details,$user_id,$route_id]);
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
                        if (!empty($_POST['min_date']) && empty($_POST['max_date'])) 
                        {
                            $conditions[] = "DATE(t.created_at) = ?";
                            $params[]     = $_POST['min_date'];
                            $types       .= "s";
                        }

                        if (!empty($_POST['max_date']) && !empty($_POST['min_date']))
                        {
                            $conditions[] = "DATE(t.created_at) BETWEEN ? AND ?";
                            $params[]     = $_POST['min_date'];
                            $params[]     = $_POST['max_date'];
                            $types       .= "ss";
                        }

                        if (!empty($_POST['max_date']) && empty($_POST['min_date'])) 
                        {
                            $conditions[] = "DATE(t.created_at) = ?";
                            $params[]     = $_POST['max_date'];
                            $types       .= "s";
                        }

                        if (!empty($_POST['month'])) {
                            $conditions[] = "MONTH(t.created_at) = ?";
                            $params[]     = $_POST['month'];
                            $types       .= "i";
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

                        /* Customer filter */
                        if (!empty($_POST['item'])) {
                            $conditions[] = "t.item_id = ?";
                            $params[]     = $_POST['item'];
                            $types       .= "i";
                        }

                        // $conditions[] = "month(t.created_at) = month(now())"; // default filter

                        $sql = "SELECT 
                                t.*,ci.name as item_name,
                                c.name AS category_name,cc.name as customer_name,cco.amount as cashout
                                FROM cashbook_transactions t
                                LEFT JOIN cashbook_categories c ON c.id = t.category_id
                                LEFT JOIN cashbook_customers cc ON cc.id = t.customer_id
                                LEFT JOIN cashbook_items ci ON ci.id = t.item_id
                                LEFT JOIN cashbook_cashouts cco ON cco.transaction_id = t.id
                                LEFT JOIN cashbook_cashins cci ON cci.transaction_id = t.id
                        ";

                        if ($conditions) {
                            $sql .= " WHERE " . implode(" AND ", $conditions);
                        }

                        $sql .= " ORDER BY t.id ASC";

                        // echo mysqli_error($server);

                        $stmt = $server->prepare($sql);
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $result = $stmt->get_result();
                            
                        $credits =[];
                        $debits = [];
                        
                        /* Render rows */
                        if($result->num_rows >0):
                            while ($row = $result->fetch_assoc()) 
                            {
                                $credits[] = $row['credit_amount'];
                                // $debits[] = (empty($row['credit_amount']) ||  $row['credit_amount'] == 0 ) ? $row['debit_amount'] : "";
                                 $debits[] = ($row['cashout'] == $row['debit_amount']) ? $row['debit_amount'] : 0;
                                ?>
                                    <tr class='transaction-details hover hover-hide-content'>
                                        <td><?=date('d-m-Y', strtotime($row['created_at']));?></td>
                                        <td><?=$row['category_name'];?></td>
                                        <td><?=$row['item_name'];?></td>
                                        <td><?=$row['customer_name'];?></td>
                                        <td><?=$row['details'];?></td>
                                        <td class ="<?=$row['credit_amount'] > 0 ? " text-primary" : "";?>"><?=($row['credit_amount'] > 0) ? number_format($row['credit_amount'],0) : "";?></td>
                                        <td class ="<?=$row['debit_amount'] > 0 ? " text-danger" : "";?>"><?=($row['credit_amount'] > 0 ) ? "" : (($row['cashout'] == $row['debit_amount']) ? number_format($row['debit_amount'],0) : "");?></td>
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
                            ?>
                                    <tr>
                                        <th>TOTAL</th>
                                        <th colspan='4'></th>
                                        <th><?=number_format(array_sum($credits),0);?></th>
                                        <th><?=number_format(array_sum($debits),0);?></th>
                                        <th>BAL: <?=number_format((array_sum($credits)-array_sum($debits)),0);?></th>
                                    </tr>
                            <?php
                        else:
                            ?>
                                <tr>
                                    <td colspan='8'><center>No results found!</center></td>
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

                        $sql = "SELECT ct.id, ct.created_at, ct.type, ct.details, ct.item_id,  ct.credit_amount,  ct.debit_amount,
                                    COALESCE(cc.name,'') AS category_name,
                                    COALESCE(cu.name,'') AS customer_name,
                                    COALESCE(pm.name,'') AS paymode_name,
                                    COALESCE(ci.name,'') AS item_name
                                FROM cashbook_transactions ct
                                LEFT JOIN cashbook_categories cc ON cc.id = ct.category_id
                                LEFT JOIN cashbook_customers cu ON cu.id = ct.customer_id
                                LEFT JOIN cashbook_paymodes pm  ON pm.id = ct.paymode_id
                                LEFT JOIN cashbook_items ci  ON ci.id = ct.item_id
                                WHERE {$where}

                                AND (
                                        (
                                        ct.credit_amount > 0
                                        AND EXISTS (
                                            SELECT 1
                                            FROM cashbook_cashins ci
                                            WHERE ci.transaction_id = ct.id
                                        )
                                    )
                                    OR
                                    (
                                        ct.debit_amount > 0
                                        AND EXISTS (
                                            SELECT 1
                                            FROM cashbook_cashouts co
                                            WHERE co.transaction_id = ct.id
                                        )
                                    )
                                )
                                ORDER BY ct.id, ct.created_at ASC
                        ";

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
                            $spreadsheet = new Spreadsheet();

                            /*
                            |--------------------------------------------------------------------------
                            | SUMMARY SHEET
                            |--------------------------------------------------------------------------
                            */

                            $summary = $spreadsheet->getActiveSheet();
                            $summary->setTitle('Summary');
                            $summary->mergeCells(
                                'A1:D1'
                            );

                            $summary->setCellValue('A1',strtoupper($book->name ?? 'BUSINESS'));
                            $summary->mergeCells('A2:D2' );
                            $summary->setCellValue('A2','TRANSACTION REPORT');

                            $summary->setCellValue('A4','Generated');
                            $summary->setCellValue('B4',date('d-m-Y H:i'));
                            $summary->setCellValue('A6','Cash In');
                            $summary->setCellValue('B6',$cashin);
                            $summary->setCellValue('A7','Cash Out');
                            $summary->setCellValue('B7',$cashout);
                            $summary->setCellValue('A8','Net Balance');

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
                            | FILTER SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            $summary->setCellValue(
                                'A11',
                                'REPORT FILTERS'
                            );


                            $filterRow = 12;


                            $filterLabels = [
                                'min_date' => 'Date From',
                                'max_date' => 'Date To',
                                'month'    => 'Month',
                                'year'     => 'Year',
                                'type'     => 'Type',
                                'category' => 'Category',
                                'customer' => 'Customer',
                                'item' => 'Item'

                            ];


                            foreach ($filterLabels as $key => $label) 
                            {
                                if (isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== null) 
                                {
                                    $summary->setCellValue('A' . $filterRow, $label);
                                    $summary->setCellValue('B' . $filterRow, $filters[$key]);
                                    $filterRow++;
                                }
                            }

                            foreach(range('A','C') as $col)
                            {
                                $summary->getColumnDimension($col)->setAutoSize(true);
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | SUMMARY STYLING
                            |--------------------------------------------------------------------------
                            */

                            $summary->getStyle('A1:D2')->getFont()->setBold(true);

                            $summary->getStyle('A1:D2')->getAlignment()
                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);


                            $summary->getStyle('A6:B9')->getBorders()
                                    ->getAllBorders()
                                    ->setBorderStyle(
                                        Border::BORDER_THIN
                                    );

                            $summary->getStyle('A6:A9')->getFont()->setBold(true);
                            $summary->getColumnDimension('A') ->setWidth(25);
                            $summary->getColumnDimension('B') ->setWidth(30);


                            /*
                            |--------------------------------------------------------------------------
                            | TRANSACTIONS SHEET
                            |--------------------------------------------------------------------------
                            */

                            $sheet = $spreadsheet->createSheet();

                            $sheet->setTitle('Transactions');
                            $sheet->mergeCells('A1:I1');
                            $sheet->setCellValue('A1',strtoupper($book->name ?? 'BUSINESS'));
                            $sheet->mergeCells('A2:I2');
                            $sheet->setCellValue('A2','TRANSACTION STATEMENT');
                            $sheet->mergeCells( 'A3:I3');

                            $sheet->setCellValue( 'A3','Generated: ' .date('d-m-Y H:i'));


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
                                'Customer',
                                'Details',
                                'Payment Mode',
                                'Cash In',
                                'Cash Out',
                                'Balance'
                            ];


                            $column = 'A';
                            foreach ($headers as $header) {

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

                            $sheet->getStyle(
                                'A5:K5'
                            )->getFont()->setBold(true);


                            $sheet->getStyle(
                                'A5:K5'
                            )->getAlignment()
                            ->setHorizontal(
                                Alignment::HORIZONTAL_CENTER
                            );


                            $sheet->getStyle(
                                'A5:K5'
                            )->getBorders()
                            ->getAllBorders()
                            ->setBorderStyle(
                                Border::BORDER_THIN
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | DATA
                            |--------------------------------------------------------------------------
                            */

                            $row = 6;
                            $number = 1;


                            foreach ($transactions as $transaction) {

                                $sheet->setCellValue('A' . $row,$number);
                                $sheet->setCellValue(
                                    'B' . $row,
                                    !empty($transaction['created_at'])
                                        ? date(
                                            'd-m-Y H:i',
                                            strtotime(
                                                $transaction['created_at']
                                            )
                                        )
                                        : ''
                                );


                                $sheet->setCellValue(
                                    'C' . $row,
                                    $transaction['id']
                                );


                                $sheet->setCellValue(
                                    'D' . $row,
                                    $transaction['category_name']
                                );
                                $sheet->setCellValue(
                                    'E' . $row,
                                    $transaction['item_name']
                                );

                                $sheet->setCellValue(
                                    'F' . $row,
                                    $transaction['customer_name']
                                );

                                $sheet->setCellValue(
                                    'G' . $row,
                                    $transaction['details']
                                );

                                $sheet->setCellValue(
                                    'H' . $row,
                                    $transaction['paymode_name']
                                );

                                $sheet->setCellValue(
                                    'I' . $row,
                                    (float)$transaction['credit_amount']
                                );
                                $debitt = ($transaction['debit_amount'] == $transaction['credit_amount']) ? 0 : $transaction['debit_amount'];
                                $runningBalance = $transaction['credit_amount'] - $debitt;
                                $sheet->setCellValue('J' . $row, (float)$debitt);
                                $sheet->setCellValue('K' . $row,(float)$runningBalance);


                                $row++;
                                $number++;

                            }

                            foreach(range('A','K') as $col){
                                $sheet->getColumnDimension($col)->setAutoSize(true);
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | TOTAL ROW
                            |--------------------------------------------------------------------------
                            */

                            $totalRow = $row;


                            $sheet->setCellValue(
                                'A' . $totalRow,
                                'TOTAL'
                            );


                            $sheet->mergeCells(
                                'A' . $totalRow . ':H' . $totalRow
                            );


                            $sheet->setCellValue(
                                'I' . $totalRow,
                                $cashin
                            );


                            $sheet->setCellValue(
                                'J' . $totalRow,
                                $cashout
                            );


                            $sheet->setCellValue(
                                'K' . $totalRow,
                                $cashin - $cashout
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | TOTAL STYLE
                            |--------------------------------------------------------------------------
                            */

                            $sheet->getStyle(
                                'A' . $totalRow . ':K' . $totalRow
                            )->getFont()->setBold(true);

                            foreach(range('A','K') as $col){
                                $sheet->getColumnDimension($col)->setAutoSize(true);
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | AMOUNT FORMAT
                            |--------------------------------------------------------------------------
                            */

                            if ($totalRow >= 6) {

                                $sheet->getStyle(
                                    'H6:K' . $totalRow
                                )->getNumberFormat()
                                ->setFormatCode(
                                    '#,##0'
                                );

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | BORDERS
                            |--------------------------------------------------------------------------
                            */

                            $sheet->getStyle(
                                'A5:K' . $totalRow
                            )->getBorders()
                            ->getAllBorders()
                            ->setBorderStyle(
                                Border::BORDER_THIN
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | COLUMN WIDTHS
                            |--------------------------------------------------------------------------
                            */

                            $widths = [

                                'A' => 8,
                                'B' => 20,
                                'C' => 15,
                                'D' => 22,
                                'E' => 25,
                                'F' => 40,
                                'G' => 20,
                                'H' => 18,
                                'I' => 18,
                                'J' => 18,
                                'J' => 18
                            ];

                            foreach ($widths as $col => $width) {
                                $sheet->getColumnDimension($col)
                                    ->setWidth($width);
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | FREEZE HEADER
                            |--------------------------------------------------------------------------
                            */

                            $sheet->freezePane('A6');

                            /*
                            |--------------------------------------------------------------------------
                            | PRINT SETTINGS
                            |--------------------------------------------------------------------------
                            */

                            $sheet->getPageSetup()
                                    ->setOrientation( \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

                            $sheet->getPageSetup()->setFitToWidth(1);


                            /*
                            |--------------------------------------------------------------------------
                            | DOWNLOAD
                            |--------------------------------------------------------------------------
                            */

                            $filename ='transaction_report_' .date('Y-m-d_H-i-s') .'.xlsx';
                            if (ob_get_length()) {
                                ob_end_clean();
                            }


                            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
                            header('Content-Disposition: attachment; filename="' .$filename .'"' );

                            header('Cache-Control: max-age=0');
                            $writer = new Xlsx($spreadsheet);
                            $writer->save(
                                'php://output'
                            );
                            exit;
                        }

                        if ($format === 'pdf') 
                        {

                            /*
                            |--------------------------------------------------------------------------
                            | LOAD FPDF
                            |--------------------------------------------------------------------------
                            */

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
                                |
                                | This is automatically called by FPDF whenever AddPage() is called.
                                |
                                | IMPORTANT:
                                | Do NOT put transaction table headers here.
                                |
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
                                        strtoupper($this->businessName),
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
                                        'Generated: ' . date('d-m-Y H:i'),
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
                                | This is called manually when the transaction table begins.
                                |
                                */

                                function TableHeader()
                                {
                                    $this->SetFont(
                                        'Arial',
                                        'B',
                                        7
                                    );


                                    $this->Cell(8, 7, '#', 1);
                                    $this->Cell(28, 7, 'Date', 1);
                                    $this->Cell(32, 7, 'Category', 1);
                                    $this->Cell(32, 7, 'Item', 1);
                                    $this->Cell(27, 7, 'Customer', 1);
                                    $this->Cell(51, 7, 'Details', 1);
                                    $this->Cell(22, 7, 'Cash In', 1, 0, 'R');
                                    $this->Cell(22, 7, 'Cash Out', 1, 0, 'R');
                                    $this->Cell(22, 7, 'Balance', 1, 1, 'R');
                                    $this->SetFont(
                                        'Arial',
                                        '',
                                        7
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

                                function CheckTablePageBreak($height = 6)
                                {
                                    /*
                                    |--------------------------------------------------------------------------
                                    | Bottom margin
                                    |--------------------------------------------------------------------------
                                    */

                                    $bottomMargin = 18;


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Available page height
                                    |--------------------------------------------------------------------------
                                    */

                                    $pageHeight = $this->GetPageHeight();


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Current position
                                    |--------------------------------------------------------------------------
                                    */

                                    $currentY = $this->GetY();


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Check whether row fits
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        $currentY +
                                        $height +
                                        $bottomMargin
                                        >
                                        $pageHeight
                                    ) {

                                        $this->AddPage();

                                        /*
                                        |--------------------------------------------------------------------------
                                        | AddPage() already called Header()
                                        |--------------------------------------------------------------------------
                                        |
                                        | Now put the transaction headings below the normal page header.
                                        |
                                        */

                                        $this->TableHeader();

                                        $this->SetFont(
                                            'Arial',
                                            '',
                                            7
                                        );

                                    }
                                }
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | CREATE PDF
                            |--------------------------------------------------------------------------
                            */

                            $pdf =new CashbookTransactionReportPDF('L','mm','A4');
                            $pdf->businessName = $book->name ??'BUSINESS';
                            $pdf->SetMargins(8,8,8);
                            $pdf->SetAutoPageBreak(true, 18);
                            $pdf->AddPage();


                            /*
                            |--------------------------------------------------------------------------
                            | SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            $pdf->SetFont(
                                'Arial',
                                'B',
                                9
                            );


                            $pdf->Cell(
                                42,
                                7,
                                'CASH IN',
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                42,
                                7,
                                'CASH OUT',
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                42,
                                7,
                                'NET BALANCE',
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                42,
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
                                42,
                                7,
                                number_format($cashin, 0),
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                42,
                                7,
                                number_format($cashout, 0),
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                42,
                                7,
                                number_format(
                                    $cashin - $cashout,
                                    0
                                ),
                                1,
                                0,
                                'C'
                            );


                            $pdf->Cell(
                                42,
                                7,
                                $transactionCount,
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


                            if (!empty($filters['min_date'])) {

                                $filterText[] =
                                    'From: ' .
                                    date(
                                        'd-m-Y',
                                        strtotime(
                                            $filters['min_date']
                                        )
                                    );

                            }


                            if (!empty($filters['max_date'])) {

                                $filterText[] =
                                    'To: ' .
                                    date(
                                        'd-m-Y',
                                        strtotime(
                                            $filters['max_date']
                                        )
                                    );

                            }


                            if (!empty($filters['month'])) {

                                $filterText[] =
                                    'Month: ' .
                                    $filters['month'];

                            }


                            if (!empty($filters['year'])) {

                                $filterText[] =
                                    'Year: ' .
                                    $filters['year'];

                            }


                            if (!empty($filters['type'])) {

                                $filterText[] =
                                    'Type: ' .
                                    (
                                        $filters['type'] === 'credit'
                                        ? 'Cash In'
                                        : 'Cash Out'
                                    );

                            }


                            if (!empty($filters['category'])) {

                                $catSql = "
                                    SELECT name
                                    FROM cashbook_categories
                                    WHERE id = ?
                                    LIMIT 1
                                ";

                                $catRes =
                                    prepared_statements(
                                        $catSql,
                                        'i',
                                        [(int)$filters['category']]
                                    );

                                if ($catRes) {

                                    $cat =
                                        $catRes->fetch_assoc();

                                    if ($cat) {

                                        $filterText[] =
                                            'Category: ' .
                                            $cat['name'];

                                    }

                                }

                            }

                            if (!empty($filters['customer'])) 
                            {

                                $custSql = "
                                    SELECT name
                                    FROM cashbook_customers
                                    WHERE id = ?
                                    LIMIT 1
                                ";

                                $custRes =
                                    prepared_statements(
                                        $custSql,
                                        'i',
                                        [(int)$filters['customer']]
                                    );

                                if ($custRes) {

                                    $cust =
                                        $custRes->fetch_assoc();

                                    if ($cust) {

                                        $filterText[] =
                                            'Customer: ' .
                                            $cust['name'];

                                    }

                                }

                            }

                            if (!empty($filters['item'])) 
                            {
                                $itemSql = "SELECT name FROM cashbook_items WHERE id = ? LIMIT 1 ";
                                $itemRes = prepared_statements( $itemSql,'i',[(int)$filters['item']]);

                                if ($itemRes) 
                                {
                                    $itm = $itemRes->fetch_assoc();
                                    if ($cust) { $filterText[] = 'Item: ' . $itm['name'];}
                                }
                            }


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

                            $pdf->SetFont('Arial','',7);
                            
                            $number = 1;
                            // add table header
                            $pdf->TableHeader();

                            foreach ($transactions as $transaction) {

                                $pdf->CheckTablePageBreak(6);


                                $date =!empty($transaction['created_at'])
                                        ? date(
                                            'd-m-Y H:i',
                                            strtotime(
                                                $transaction['created_at']
                                            )
                                        )
                                        : '';


                                $category = $transaction['category_name']?? '';
                                $item = $transaction['item_name']?? '';
                                $customer = $transaction['customer_name'] ?? '';
                                $details = $transaction['details'] ?? '';


                                $credit =
                                    (float)(
                                        $transaction['credit_amount']
                                        ?? 0
                                    );

                                $debit = (float)( $transaction['debit_amount'] != $transaction['credit_amount']) ? $transaction['debit_amount'] : 0 ;

                                $rowBalance =
                                    (float)(
                                        $transaction['running_balance']
                                        ?? 0
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | LIMIT TEXT
                                |--------------------------------------------------------------------------
                                */

                                $category = mb_substr($category, 0, 28);
                                $item = mb_substr($item,0,28);

                                $customer =  mb_substr($customer,0, 28);
                                $details = mb_substr($details,0,58);

                                /*
                                |--------------------------------------------------------------------------
                                | DRAW ROW
                                |--------------------------------------------------------------------------
                                */

                                $pdf->Cell(
                                    8,
                                    6,
                                    $number,
                                    1
                                );

                                $pdf->Cell(
                                    28,
                                    6,
                                    $date,
                                    1
                                );

                                $pdf->Cell(
                                    32,
                                    6,
                                    $category,
                                    1
                                );

                                $pdf->Cell(
                                    32,
                                    6,
                                    $item,
                                    1
                                );

                                $pdf->Cell(
                                    27,
                                    6,
                                    $customer,
                                    1
                                );

                                $pdf->Cell(
                                    51,
                                    6,
                                    $details,
                                    1
                                );

                                $pdf->Cell(
                                    22,
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

                                $pdf->Cell(
                                    22,
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

                                $pdf->Cell(
                                    22,
                                    6,
                                    number_format(
                                        $rowBalance,
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
                            | TOTAL
                            |--------------------------------------------------------------------------
                            */

                            $pdf->CheckTablePageBreak(7);
                            $pdf->SetFont(
                                'Arial',
                                'B',
                                8
                            );

                            $pdf->Cell(
                                178,
                                7,
                                'TOTAL',
                                1,
                                0,
                                'R'
                            );

                            $pdf->Cell(
                                22,
                                7,
                                number_format(
                                    $cashin,
                                    0
                                ),
                                1,
                                0,
                                'R'
                            );

                            $pdf->Cell(
                                22,
                                7,
                                number_format(
                                    $cashout,
                                    0
                                ),
                                1,
                                0,
                                'R'
                            );

                            $pdf->Cell(
                                22,
                                7,
                                number_format(
                                    $cashin - $cashout,
                                    0
                                ),
                                1,
                                1,
                                'R'
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | DOWNLOAD
                            |--------------------------------------------------------------------------
                            */

                            $filename =
                                'transaction_report_' .
                                date('Y-m-d_H-i-s') .
                                '.pdf';


                            if (ob_get_length()) {
                                ob_end_clean();
                            }


                            $pdf->Output(
                                'D',
                                $filename
                            );


                            exit;

                        }
                    break;
            }
        }
            
        }else{
        redirect('../');
    }
?>