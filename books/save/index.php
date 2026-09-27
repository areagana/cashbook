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

                                /*
                                |--------------------------------------------------------------------------
                                | REQUEST DATA
                                |--------------------------------------------------------------------------
                                */

                                $details      = request('cashin_details');
                                $category_id  = request('category_id');
                                $amount       = (float)request('inamount');
                                $payment_mode = request('paymode_id');
                                $transid      = (int)request('transaction_id');
                                $date         = request('created_at');
                                $user_id      = auth()->id;

                                $customer_id  = request('customer_id');
                                $creditor_id  = request('creditor_id');
                                $item_id      = request('item_id');
                                $quantity     = (float)request('quantity');
                                $type         = request('transaction_type');

                                $invoice_id = isset($_POST['invoice_id'])
                                    ? request('invoice_id')
                                    : "";


                                /*
                                |--------------------------------------------------------------------------
                                | GET ORIGINAL TRANSACTION
                                |--------------------------------------------------------------------------
                                |
                                | This MUST happen before updating cashbook_transactions because we need
                                | the old creditor, item and quantity for reconciliation.
                                |
                                */

                                $transaction = transactionFind($transid);

                                if(!$transaction)
                                {
                                    $_SESSION['error'] = "Transaction could not be found.";
                                    break;
                                }


                                $book_id = (int)$transaction->book_id;

                                $old_item_id = !empty($transaction->item_id)
                                    ? (int)$transaction->item_id
                                    : 0;

                                $old_quantity = (float)$transaction->quantity;

                                $old_type = $transaction->type;


                                /*
                                |--------------------------------------------------------------------------
                                | GET OLD CREDITOR
                                |--------------------------------------------------------------------------
                                |
                                | Do not depend only on cashbook_transactions.creditor_id here.
                                | The existing creditor ledger gives us the creditor currently attached
                                | to this transaction.
                                |
                                */

                                $old_creditor_id = 0;

                                $oldCreditorSql = "
                                    SELECT creditor_id
                                    FROM cashbook_creditor_ledger
                                    WHERE transaction_id = ?
                                    LIMIT 1
                                ";

                                $oldCreditorRes = prepared_statements(
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
                                        (int)$oldCreditorRow['creditor_id'];
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | NORMALISE OPTIONAL IDS
                                |--------------------------------------------------------------------------
                                */

                                $customer_id = !empty($customer_id)
                                    ? (int)$customer_id
                                    : 0;

                                $creditor_id = !empty($creditor_id)
                                    ? (int)$creditor_id
                                    : 0;

                                $item_id = !empty($item_id)
                                    ? (int)$item_id
                                    : 0;

                                $invoice_id = !empty($invoice_id)
                                    ? (int)$invoice_id
                                    : 0;


                                /*
                                |--------------------------------------------------------------------------
                                | TRACK TRANSACTION EDIT
                                |--------------------------------------------------------------------------
                                */

                                trackTransactionEdits(
                                    $transid,
                                    'edit'
                                );


                                /*
                                |--------------------------------------------------------------------------
                                | UPDATE MAIN TRANSACTION
                                |--------------------------------------------------------------------------
                                |
                                | creditor_id is now included so cashbook_transactions remains aligned
                                | with cashbook_creditor_ledger.
                                |
                                */

                                $sql = "
                                    UPDATE cashbook_transactions SET

                                        type = ?,
                                        credit_amount = ?,
                                        details = ?,
                                        category_id = ?,
                                        paymode_id = ?,
                                        created_at = ?,
                                        user_id = ?,
                                        customer_id = ?,
                                        creditor_id = ?,
                                        item_id = ?,
                                        quantity = ?,
                                        invoice_id = ?

                                    WHERE id = ?
                                ";


                                $res = prepared_statements(
                                    $sql,
                                    'sisiissiiiiii',
                                    [
                                        $type,
                                        $amount,
                                        $details,
                                        $category_id,
                                        $payment_mode,
                                        $date,
                                        $user_id,
                                        $customer_id,
                                        $creditor_id,
                                        $item_id,
                                        $quantity,
                                        $invoice_id,
                                        $transid
                                    ]
                                );


                                if(!$res)
                                {
                                    $_SESSION['error'] =
                                        "Transaction could not be updated.";

                                    break;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | UPDATE CASH-IN RECORD
                                |--------------------------------------------------------------------------
                                |
                                | Only these transaction types belong in cashbook_cashins.
                                |
                                */

                                if(
                                    $type === 'cash_sale' ||
                                    $type === 'payment' ||
                                    $type === 'other_income'
                                )
                                {
                                    $stmt = "
                                        UPDATE cashbook_cashins SET

                                            type = ?,
                                            customer_id = ?,
                                            amount = ?,
                                            category_id = ?,
                                            details = ?,
                                            paymode_id = ?,
                                            created_at = ?,
                                            user_id = ?,
                                            item_id = ?,
                                            quantity = ?

                                        WHERE transaction_id = ?
                                    ";


                                    prepared_statements(
                                        $stmt,
                                        'sidisisiiii',
                                        [
                                            $type,
                                            $customer_id,
                                            $amount,
                                            $category_id,
                                            $details,
                                            $payment_mode,
                                            $date,
                                            $user_id,
                                            $item_id,
                                            $quantity,
                                            $transid
                                        ]
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | STOCK RECONCILIATION
                                |--------------------------------------------------------------------------
                                |
                                | Restore the original stock movement first and then apply the newly
                                | edited movement.
                                |
                                */

                                if(
                                    $old_type === 'cash_sale' ||
                                    $old_type === 'payment' ||
                                    $old_type === 'other_income'
                                )
                                {
                                    if(
                                        $old_item_id > 0 &&
                                        $old_quantity > 0
                                    )
                                    {
                                        restoreStockOut(
                                            $old_item_id,
                                            $old_quantity
                                        );
                                    }
                                }


                                /*
                                | Apply new stock movement.
                                */

                                if(
                                    $type === 'cash_sale' ||
                                    $type === 'payment' ||
                                    $type === 'other_income'
                                )
                                {
                                    if(
                                        $item_id > 0 &&
                                        $quantity > 0
                                    )
                                    {
                                        stockOut(
                                            $item_id,
                                            $quantity,
                                            $transid
                                        );
                                    }
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CUSTOMER LEDGER
                                |--------------------------------------------------------------------------
                                */

                                if($customer_id > 0)
                                {
                                    customerLedgerUpdate(
                                        $customer_id,
                                        $amount,
                                        0,
                                        $category_id,
                                        $details,
                                        $book_id,
                                        $payment_mode,
                                        $transid,
                                        $date,
                                        $user_id,
                                        $item_id,
                                        $quantity,
                                        $type
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CREDITOR LEDGER
                                |--------------------------------------------------------------------------
                                |
                                | A creditor payment recorded through Cash In reduces the amount owed
                                | to the creditor.
                                |
                                | Creditor balance formula:
                                |
                                |     balance = previous balance + credit - debit
                                |
                                | Therefore:
                                |
                                |     credit = 0
                                |     debit  = payment amount
                                |
                                */


                                /*
                                |--------------------------------------------------------------------------
                                | CASE 1 & 2
                                | SAME CREDITOR OR NEW CREDITOR SELECTED
                                |--------------------------------------------------------------------------
                                |
                                | creditorLedgerUpdate() will:
                                |
                                | - update the existing ledger transaction;
                                | - move it to another creditor if creditor changed;
                                | - recalculate the old creditor when necessary;
                                | - recalculate the new/current creditor.
                                |
                                */

                                if($creditor_id > 0)
                                {
                                    $credit = 0;
                                    $debit  = $amount;


                                    creditorLedgerUpdate(
                                        $creditor_id,
                                        $credit,
                                        $debit,
                                        $category_id,
                                        $details,
                                        $book_id,
                                        $payment_mode,
                                        $transid,
                                        $date,
                                        $user_id,
                                        $item_id,
                                        $quantity,
                                        $type
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CASE 3
                                | CREDITOR REMOVED FROM TRANSACTION
                                |--------------------------------------------------------------------------
                                |
                                | Example:
                                |
                                | Before edit:
                                |     Creditor = Supplier A
                                |     Payment  = 300,000
                                |
                                | After edit:
                                |     Creditor = NONE
                                |
                                | We must remove the old creditor ledger entry and rebuild Supplier A's
                                | ledger/balance.
                                |
                                */

                                elseif($old_creditor_id > 0)
                                {
                                    $deleteCreditorLedger = "
                                        DELETE FROM cashbook_creditor_ledger
                                        WHERE transaction_id = ?
                                    ";

                                    prepared_statements(
                                        $deleteCreditorLedger,
                                        'i',
                                        [$transid]
                                    );


                                    /*
                                    | Rebuild old creditor's running balances after removing
                                    | this transaction.
                                    */

                                    recalculateCreditorLedger(
                                        $old_creditor_id
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | SUCCESS
                                |--------------------------------------------------------------------------
                                */

                                $_SESSION['success'] =
                                    "Transaction updated successfully.";

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

                                /*
                                |--------------------------------------------------------------------------
                                | REQUEST DATA
                                |--------------------------------------------------------------------------
                                */

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


                                /*
                                |--------------------------------------------------------------------------
                                | GET ORIGINAL TRANSACTION
                                |--------------------------------------------------------------------------
                                |
                                | IMPORTANT:
                                | We must capture the old transaction before updating anything.
                                |--------------------------------------------------------------------------
                                */

                                $transaction = transactionFind($transid);

                                if(!$transaction)
                                {
                                    $_SESSION['error'] = "Transaction could not be found.";
                                    break;
                                }


                                $book_id = (int) $transaction->book_id;

                                $old_item_id = !empty($transaction->item_id)
                                    ? (int) $transaction->item_id
                                    : 0;

                                $old_quantity = isset($transaction->quantity)
                                    ? (float) $transaction->quantity
                                    : 0;

                                $old_type = isset($transaction->type)
                                    ? $transaction->type
                                    : "";


                                /*
                                |--------------------------------------------------------------------------
                                | GET OLD CREDITOR LEDGER TRANSACTION
                                |--------------------------------------------------------------------------
                                |
                                | We need:
                                |
                                |   - old creditor
                                |   - old credit
                                |   - old debit
                                |
                                | This allows us to reverse ONLY the old effect and apply the new one.
                                |--------------------------------------------------------------------------
                                */

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

                                $oldCreditorRes = prepared_statements(
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


                                /*
                                |--------------------------------------------------------------------------
                                | NORMALISE OPTIONAL VALUES
                                |--------------------------------------------------------------------------
                                */

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


                                /*
                                |--------------------------------------------------------------------------
                                | TRACK TRANSACTION EDIT
                                |--------------------------------------------------------------------------
                                */

                                trackTransactionEdits(
                                    $transid,
                                    'edit'
                                );


                                /*
                                |--------------------------------------------------------------------------
                                | UPDATE MAIN TRANSACTION
                                |--------------------------------------------------------------------------
                                |
                                | Cash Out is stored in debit_amount.
                                |
                                | creditor_id is also saved so that cashbook_transactions remains
                                | synchronized with cashbook_creditor_ledger.
                                |--------------------------------------------------------------------------
                                */

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
                                        creditor_id = ?

                                    WHERE id = ?
                                ";


                                $res = prepared_statements(
                                    $sql,
                                    'iddsdsiiiiii',
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
                                        $transid
                                    ]
                                );


                                if(!$res)
                                {
                                    $_SESSION['error'] =
                                        "Transaction could not be updated.";

                                    break;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | PURCHASE RECORD
                                |--------------------------------------------------------------------------
                                */

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

                                    $purchase = prepared_statements(
                                        $purchase_check,
                                        'i',
                                        [$transid]
                                    );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | UPDATE EXISTING PURCHASE
                                    |--------------------------------------------------------------------------
                                    */

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

                                        prepared_statements(
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


                                    /*
                                    |--------------------------------------------------------------------------
                                    | INSERT PURCHASE IF IT DOES NOT EXIST
                                    |--------------------------------------------------------------------------
                                    */

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

                                        prepared_statements(
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


                                /*
                                |--------------------------------------------------------------------------
                                | TRANSACTION CHANGED FROM PURCHASE
                                |--------------------------------------------------------------------------
                                */

                                elseif($old_type === 'purchase')
                                {
                                    $deletePurchase = "
                                        DELETE FROM cashbook_purchases
                                        WHERE transaction_id = ?
                                    ";

                                    prepared_statements(
                                        $deletePurchase,
                                        'i',
                                        [$transid]
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CUSTOMER LEDGER
                                |--------------------------------------------------------------------------
                                |
                                | Preserve your existing borrowing / credit sale handling.
                                |--------------------------------------------------------------------------
                                */

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


                                /*
                                |--------------------------------------------------------------------------
                                | CREDITOR PAYMENT EDITING
                                |--------------------------------------------------------------------------
                                |
                                | IMPORTANT ACCOUNTING RULE
                                |--------------------------------------------------------------------------
                                |
                                | We DO NOT rebuild the creditor balance from zero.
                                |
                                | Instead:
                                |
                                |       reverse old effect
                                |       +
                                |       apply new effect
                                |
                                | Creditor balance:
                                |
                                |       balance = balance + credit - debit
                                |
                                | A creditor payment is therefore a DEBIT because it reduces what
                                | we owe the creditor.
                                |--------------------------------------------------------------------------
                                */


                                /*
                                |--------------------------------------------------------------------------
                                | CASE 1:
                                | SAME CREDITOR - AMOUNT / DATE / DETAILS EDITED
                                |--------------------------------------------------------------------------
                                */

                                if(
                                    $type === 'creditorPayment' &&
                                    $creditor_id > 0 &&
                                    $old_creditor_id > 0 &&
                                    $old_creditor_id === $creditor_id
                                )
                                {
                                    /*
                                    |--------------------------------------------------------------------------
                                    | UPDATE THE CREDITOR PAYMENT
                                    |--------------------------------------------------------------------------
                                    */

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


                                    prepared_statements(
                                        $creditorUpdate,
                                        'siddsiisiiii',
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


                                    /*
                                    |--------------------------------------------------------------------------
                                    | RECALCULATE FROM THIS TRANSACTION FORWARD
                                    |--------------------------------------------------------------------------
                                    */

                                    recalculateCreditorLedgerFromTransaction(
                                        $creditor_id,
                                        $transid
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CASE 2:
                                | CREDITOR CHANGED
                                |--------------------------------------------------------------------------
                                |
                                | Example:
                                |
                                | Old creditor = Supplier A
                                | Old payment  = 300,000
                                |
                                | New creditor = Supplier B
                                | New payment  = 250,000
                                |
                                | Restore 300,000 to Supplier A.
                                | Remove 250,000 from Supplier B.
                                |--------------------------------------------------------------------------
                                */

                                elseif(
                                    $type === 'creditorPayment' &&
                                    $creditor_id > 0 &&
                                    $old_creditor_id > 0 &&
                                    $old_creditor_id !== $creditor_id
                                )
                                {
                                    /*
                                    |--------------------------------------------------------------------------
                                    | RESTORE OLD CREDITOR
                                    |--------------------------------------------------------------------------
                                    */

                                    $oldCurrentBalance =
                                        (float) getCreditorBalance(
                                            $old_creditor_id
                                        );


                                    /*
                                    | Reverse the old payment.
                                    |
                                    | The old payment had reduced the creditor balance.
                                    | Therefore restoring it means adding it back.
                                    */

                                    $restoredOldBalance =
                                        $oldCurrentBalance +
                                        $old_creditor_debit -
                                        $old_creditor_credit;


                                    saveCreditorBalance(
                                        $old_creditor_id,
                                        $restoredOldBalance,
                                        $date
                                    );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | APPLY PAYMENT TO NEW CREDITOR
                                    |--------------------------------------------------------------------------
                                    */

                                    $newCurrentBalance =
                                        (float) getCreditorBalance(
                                            $creditor_id
                                        );


                                    $newCreditorBalance =
                                        $newCurrentBalance -
                                        $amount;


                                    /*
                                    |--------------------------------------------------------------------------
                                    | MOVE LEDGER TRANSACTION TO NEW CREDITOR
                                    |--------------------------------------------------------------------------
                                    */

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


                                    prepared_statements(
                                        $creditorUpdate,
                                        'siddsiisiiii',
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


                                    saveCreditorBalance(
                                        $creditor_id,
                                        $newCreditorBalance,
                                        $date
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CASE 3:
                                | NEW CREDITOR ADDED TO A TRANSACTION
                                |--------------------------------------------------------------------------
                                |
                                | There was no creditor ledger entry before, but the edited transaction
                                | is now a creditor payment.
                                |--------------------------------------------------------------------------
                                */

                                elseif(
                                    $type === 'creditorPayment' &&
                                    $creditor_id > 0 &&
                                    $old_creditor_id <= 0
                                )
                                {
                                    /*
                                    | Get current creditor balance.
                                    */

                                    $currentBalance =
                                        (float) getCreditorBalance(
                                            $creditor_id
                                        );


                                    /*
                                    | Apply the new payment.
                                    */

                                    $newBalance =
                                        $currentBalance -
                                        $amount;


                                    /*
                                    | Create creditor ledger record.
                                    */

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


                                    prepared_statements(
                                        $creditorInsert,
                                        'siddsiiisiiid',
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


                                    saveCreditorBalance(
                                        $creditor_id,
                                        $newBalance,
                                        $date
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CASE 4:
                                | CREDITOR REMOVED / TRANSACTION TYPE CHANGED
                                |--------------------------------------------------------------------------
                                |
                                | Example:
                                |
                                | Old:
                                |     creditorPayment
                                |     Supplier A
                                |     300,000
                                |
                                | New:
                                |     normal expense
                                |
                                | The old payment effect must be reversed.
                                |--------------------------------------------------------------------------
                                */

                                elseif(
                                    $old_creditor_id > 0 &&
                                    (
                                        $type !== 'creditorPayment' ||
                                        $creditor_id <= 0
                                    )
                                )
                                {
                                    /*
                                    | Get current balance.
                                    */

                                    $currentBalance =
                                        (float) getCreditorBalance(
                                            $old_creditor_id
                                        );


                                    /*
                                    | Reverse old creditor transaction.
                                    |
                                    | Original effect:
                                    |
                                    |     balance + credit - debit
                                    |
                                    | Reverse:
                                    |
                                    |     balance - credit + debit
                                    */

                                    $restoredBalance =
                                        $currentBalance
                                        - $old_creditor_credit
                                        + $old_creditor_debit;


                                    /*
                                    | Remove creditor ledger transaction.
                                    */

                                    $deleteCreditorLedger = "
                                        DELETE FROM cashbook_creditor_ledger
                                        WHERE transaction_id = ?
                                    ";


                                    prepared_statements(
                                        $deleteCreditorLedger,
                                        'i',
                                        [$transid]
                                    );


                                    /*
                                    | Save restored creditor balance.
                                    */

                                    saveCreditorBalance(
                                        $old_creditor_id,
                                        $restoredBalance,
                                        $date
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | UPDATE CASHOUT RECORD
                                |--------------------------------------------------------------------------
                                |
                                | Keep the actual cash movement synchronized with the edited transaction.
                                |--------------------------------------------------------------------------
                                */

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


                                prepared_statements(
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


                                /*
                                |--------------------------------------------------------------------------
                                | SUCCESS
                                |--------------------------------------------------------------------------
                                */

                                $_SESSION['success'] =
                                    "Cash out transaction updated successfully.";

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

                    /*
                    |--------------------------------------------------------------------------
                    | TRANSACTION REPORT
                    |--------------------------------------------------------------------------
                    |
                    | Financial movement:
                    |
                    |   Cash In  = credit transaction verified in cashbook_cashins
                    |   Cash Out = debit transaction verified in cashbook_cashouts
                    |
                    | Stock movement:
                    |
                    |   Purchase    = Quantity IN
                    |   Sale        = Quantity OUT
                    |   Net Qty     = Quantity IN - Quantity OUT
                    |
                    |--------------------------------------------------------------------------
                    */


                    /*
                    |--------------------------------------------------------------------------
                    | FILTER CONDITIONS
                    |--------------------------------------------------------------------------
                    */

                    $conditions = [];
                    $params     = [];
                    $types      = "";


                    /*
                    |--------------------------------------------------------------------------
                    | BOOK - REQUIRED
                    |--------------------------------------------------------------------------
                    */

                    $book_id = (int) request('book_id');

                    $conditions[] = "t.book_id = ?";
                    $params[]     = $book_id;
                    $types       .= "i";


                    /*
                    |--------------------------------------------------------------------------
                    | DATE FROM / DATE TO
                    |--------------------------------------------------------------------------
                    */

                    $min_date = !empty($_POST['min_date'])
                        ? trim($_POST['min_date'])
                        : '';

                    $max_date = !empty($_POST['max_date'])
                        ? trim($_POST['max_date'])
                        : '';


                    /*
                    | FROM only
                    */

                    if($min_date != '' && $max_date == '')
                    {
                        $conditions[] = "DATE(t.created_at) = ?";
                        $params[]     = $min_date;
                        $types       .= "s";
                    }


                    /*
                    | FROM and TO
                    */

                    if($min_date != '' && $max_date != '')
                    {
                        $conditions[] =
                            "DATE(t.created_at) BETWEEN ? AND ?";

                        $params[] = $min_date;
                        $params[] = $max_date;

                        $types .= "ss";
                    }


                    /*
                    | TO only
                    */

                    if($min_date == '' && $max_date != '')
                    {
                        $conditions[] = "DATE(t.created_at) = ?";
                        $params[]     = $max_date;
                        $types       .= "s";
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | MONTH / YEAR
                    |--------------------------------------------------------------------------
                    */

                    if(!empty($_POST['month']))
                    {
                        $month = (int) $_POST['month'];

                        $conditions[] = "MONTH(t.created_at) = ?";
                        $params[]     = $month;
                        $types       .= "i";


                        if(!empty($_POST['year']))
                        {
                            $year = (int) $_POST['year'];

                            $conditions[] = "YEAR(t.created_at) = ?";
                            $params[]     = $year;
                            $types       .= "i";
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CASH IN / CASH OUT FILTER
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    |
                    | We do not rely only on credit_amount/debit_amount.
                    |
                    | Cash In must actually exist in cashbook_cashins.
                    | Cash Out must actually exist in cashbook_cashouts.
                    |
                    |--------------------------------------------------------------------------
                    */

                    if(!empty($_POST['type']))
                    {
                        $transaction_direction =
                            trim($_POST['type']);


                        /*
                        | CASH IN
                        */

                        if($transaction_direction === 'credit')
                        {
                            $conditions[] = "

                                t.credit_amount > 0

                                AND EXISTS
                                (
                                    SELECT 1

                                    FROM cashbook_cashins cin_filter

                                    WHERE
                                        cin_filter.transaction_id = t.id
                                )

                            ";
                        }


                        /*
                        | CASH OUT
                        */

                        elseif($transaction_direction === 'debit')
                        {
                            $conditions[] = "

                                t.debit_amount > 0

                                AND EXISTS
                                (
                                    SELECT 1

                                    FROM cashbook_cashouts cout_filter

                                    WHERE
                                        cout_filter.transaction_id = t.id
                                )

                            ";
                        }
                    }


                    if(!empty($_POST['category']))
                    {
                        $category_id = (int)$_POST['category'];

                        $conditions[] = "t.category_id = ?

                            AND EXISTS
                            (
                                SELECT 1
                                FROM cashbook_categories category_check
                                WHERE category_check.id = t.category_id
                                AND category_check.book_id = t.book_id
                            )
                        ";

                        $params[] = $category_id;
                        $types .= "i";
                    }

                    if(!empty($_POST['customer']))
                    {
                        $customer_id = (int)$_POST['customer'];

                        $conditions[] = "
                            t.customer_id = ?

                            AND EXISTS
                            (
                                SELECT 1

                                FROM cashbook_customers customer_check

                                WHERE
                                    customer_check.id = t.customer_id

                                    AND customer_check.book_id = t.book_id
                            )
                        ";

                        $params[] = $customer_id;
                        $types .= "i";
                    }

                    if(!empty($_POST['item']))
                    {
                        $item_id = (int)$_POST['item'];

                        $conditions[] = "
                            t.item_id = ?

                            AND EXISTS
                            (
                                SELECT 1

                                FROM cashbook_items item_check

                                WHERE
                                    item_check.id = t.item_id

                                    AND item_check.book_id = t.book_id
                            )
                        ";

                        $params[] =
                            $item_id;

                        $types .= "i";
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | MAIN QUERY
                    |--------------------------------------------------------------------------
                    |
                    | real_cashin:
                    |   Credit is only recognised if transaction exists
                    |   in cashbook_cashins.
                    |
                    | real_cashout:
                    |   Debit is only recognised if transaction exists
                    |   in cashbook_cashouts.
                    |
                    |--------------------------------------------------------------------------
                    */

                    $sql = "SELECT
                            t.*,
                            c.name AS category_name,
                            cc.name AS customer_name,
                            ci.name AS item_name,
                            CASE
                                WHEN
                                    t.credit_amount > 0

                                    AND EXISTS
                                    (
                                        SELECT 1

                                        FROM cashbook_cashins cin

                                        WHERE
                                            cin.transaction_id = t.id
                                            AND cin.book_id = t.book_id
                                    )

                                THEN t.credit_amount

                                ELSE 0

                            END AS real_cashin,
                            CASE
                                WHEN
                                    t.debit_amount > 0

                                    AND EXISTS
                                    (
                                        SELECT 1

                                        FROM cashbook_cashouts cout

                                        WHERE
                                            cout.transaction_id = t.id
                                            AND cout.book_id = t.book_id
                                    )

                                THEN t.debit_amount

                                ELSE 0

                            END AS real_cashout
                        FROM cashbook_transactions t
                        LEFT JOIN cashbook_categories c
                            ON c.id = t.category_id
                            AND c.book_id = t.book_id
                        LEFT JOIN cashbook_customers cc
                            ON cc.id = t.customer_id
                            AND cc.book_id = t.book_id
                        LEFT JOIN cashbook_items ci
                            ON ci.id = t.item_id
                            AND ci.book_id = t.book_id
                    ";


                    /*
                    |--------------------------------------------------------------------------
                    | WHERE CONDITIONS
                    |--------------------------------------------------------------------------
                    */

                    if(!empty($conditions))
                    {
                        $sql .= "

                            WHERE " .
                            implode(
                                " AND ",
                                $conditions
                            );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | ORDER
                    |--------------------------------------------------------------------------
                    */

                    $sql .= "

                        ORDER BY

                            t.created_at ASC,

                            t.id ASC

                    ";


                    /*
                    |--------------------------------------------------------------------------
                    | PREPARE
                    |--------------------------------------------------------------------------
                    */

                    $stmt =
                        $server->prepare($sql);


                    if(!$stmt)
                    {
                        ?>

                        <tr>

                            <td
                                colspan="10"
                                class="text-center text-danger p-5"
                            >

                                <i
                                    class="
                                        fa
                                        fa-exclamation-triangle
                                        fa-2x
                                    "
                                ></i>

                                <div class="mt-2">

                                    Failed to prepare report.

                                </div>

                                <?php

                                /*
                                | Useful while developing.
                                | You can remove this later.
                                */

                                if(
                                    isset($_SESSION['role']) &&
                                    $_SESSION['role'] == 'superadministrator'
                                )
                                {
                                    ?>

                                    <small>
                                        <?= htmlspecialchars(
                                            $server->error
                                        ); ?>
                                    </small>

                                    <?php
                                }

                                ?>

                            </td>

                        </tr>

                        <?php

                        break;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | BIND PARAMETERS
                    |--------------------------------------------------------------------------
                    */

                    if(!empty($params))
                    {
                        $stmt->bind_param(
                            $types,
                            ...$params
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | EXECUTE
                    |--------------------------------------------------------------------------
                    */

                    $stmt->execute();

                    $result =
                        $stmt->get_result();


                    /*
                    |--------------------------------------------------------------------------
                    | REPORT TOTALS
                    |--------------------------------------------------------------------------
                    */

                    $totalCashIn  = 0;
                    $totalCashOut = 0;

                    $totalQtyIn   = 0;
                    $totalQtyOut  = 0;


                    /*
                    |--------------------------------------------------------------------------
                    | ITEM SUMMARY
                    |--------------------------------------------------------------------------
                    |
                    | Each item will eventually look like:
                    |
                    | [
                    |     name         => Gnuts,
                    |     qty_in       => 100,
                    |     qty_out      => 40,
                    |     transactions => 5
                    | ]
                    |
                    |--------------------------------------------------------------------------
                    */

                    $itemSummary = [];


                    /*
                    |--------------------------------------------------------------------------
                    | TRANSACTIONS FOUND
                    |--------------------------------------------------------------------------
                    */

                    if(
                        $result &&
                        $result->num_rows > 0
                    ):


                        while(
                            $row =
                            $result->fetch_assoc()
                        )
                        {

                            /*
                            |--------------------------------------------------------------------------
                            | VERIFIED CASH MOVEMENT
                            |--------------------------------------------------------------------------
                            */

                            $cashIn =
                                (float)
                                ($row['real_cashin'] ?? 0);


                            $cashOut =
                                (float)
                                ($row['real_cashout'] ?? 0);


                            $totalCashIn +=
                                $cashIn;


                            $totalCashOut +=
                                $cashOut;


                            /*
                            |--------------------------------------------------------------------------
                            | TRANSACTION QUANTITY
                            |--------------------------------------------------------------------------
                            */

                            $quantity =
                                (float)
                                ($row['quantity'] ?? 0);


                            /*
                            |--------------------------------------------------------------------------
                            | QUANTITY DIRECTION
                            |--------------------------------------------------------------------------
                            |
                            | IMPORTANT:
                            |
                            | Money direction and stock direction are different.
                            |
                            | PURCHASE:
                            |
                            |   Cash     -> OUT
                            |   Quantity -> IN
                            |
                            | SALE:
                            |
                            |   Cash     -> IN (for cash sale)
                            |   Quantity -> OUT
                            |
                            |--------------------------------------------------------------------------
                            */

                            $qtyIn  = 0;
                            $qtyOut = 0;


                            /*
                            |--------------------------------------------------------------------------
                            | NORMALISE TRANSACTION TYPE
                            |--------------------------------------------------------------------------
                            */

                            $transactionType =
                                strtolower(
                                    trim(
                                        (string)
                                        ($row['type'] ?? '')
                                    )
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | QUANTITY IN TYPES
                            |--------------------------------------------------------------------------
                            |
                            | These are transactions that bring stock into the business.
                            |
                            |--------------------------------------------------------------------------
                            */

                            $quantityInTypes = [

                                'purchase',

                                'purchases',

                                'stock_in',

                                'stock in'

                            ];


                            /*
                            |--------------------------------------------------------------------------
                            | QUANTITY OUT TYPES
                            |--------------------------------------------------------------------------
                            |
                            | These are transactions that remove stock from the business.
                            |
                            |--------------------------------------------------------------------------
                            */

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
                            | CLASSIFY QUANTITY
                            |--------------------------------------------------------------------------
                            */

                            if(
                                in_array(
                                    $transactionType,
                                    $quantityInTypes,
                                    true
                                )
                            )
                            {
                                $qtyIn =
                                    $quantity;
                            }


                            elseif(
                                in_array(
                                    $transactionType,
                                    $quantityOutTypes,
                                    true
                                )
                            )
                            {
                                $qtyOut =
                                    $quantity;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | FALLBACK CLASSIFICATION
                            |--------------------------------------------------------------------------
                            |
                            | This helps older transactions where type may not have been
                            | stored consistently.
                            |
                            | If an item exists:
                            |
                            |   Cash Out + quantity -> probably purchase -> Qty In
                            |
                            |   Cash In + quantity  -> probably sale     -> Qty Out
                            |
                            | Explicit transaction types above ALWAYS take priority.
                            |
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $quantity > 0 &&
                                $qtyIn == 0 &&
                                $qtyOut == 0 &&
                                !empty($row['item_id'])
                            )
                            {

                                /*
                                | Cash Out usually represents purchasing stock.
                                */

                                if(
                                    $cashOut > 0 &&
                                    $cashIn <= 0
                                )
                                {
                                    $qtyIn =
                                        $quantity;
                                }


                                /*
                                | Cash In usually represents selling stock.
                                */

                                elseif(
                                    $cashIn > 0 &&
                                    $cashOut <= 0
                                )
                                {
                                    $qtyOut =
                                        $quantity;
                                }

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | NET QUANTITY FOR THIS TRANSACTION
                            |--------------------------------------------------------------------------
                            */

                            $netQty =
                                $qtyIn -
                                $qtyOut;


                            /*
                            |--------------------------------------------------------------------------
                            | REPORT QUANTITY TOTALS
                            |--------------------------------------------------------------------------
                            */

                            $totalQtyIn +=
                                $qtyIn;


                            $totalQtyOut +=
                                $qtyOut;


                            /*
                            |--------------------------------------------------------------------------
                            | ITEM
                            |--------------------------------------------------------------------------
                            */

                            $transactionItemId =
                                !empty($row['item_id'])
                                    ? (int)$row['item_id']
                                    : 0;


                            /*
                            |--------------------------------------------------------------------------
                            | BUILD ITEM SUMMARY
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $transactionItemId > 0
                            )
                            {

                                /*
                                | Create item if not already present.
                                */

                                if(
                                    !isset(
                                        $itemSummary[
                                            $transactionItemId
                                        ]
                                    )
                                )
                                {
                                    $itemSummary[
                                        $transactionItemId
                                    ] = [

                                        'name' =>

                                            !empty(
                                                $row['item_name']
                                            )
                                                ? $row['item_name']
                                                : 'Unnamed Item',

                                        'qty_in' => 0,

                                        'qty_out' => 0,

                                        'transactions' => 0

                                    ];
                                }


                                /*
                                | Add quantities.
                                */

                                $itemSummary[
                                    $transactionItemId
                                ]['qty_in']
                                    += $qtyIn;


                                $itemSummary[
                                    $transactionItemId
                                ]['qty_out']
                                    += $qtyOut;


                                /*
                                | Count transaction.
                                */

                                $itemSummary[
                                    $transactionItemId
                                ]['transactions']++;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | TRANSACTION ROW
                            |--------------------------------------------------------------------------
                            */

                            ?>

                            <tr
                                class="
                                    transaction-details
                                    hover
                                    hover-hide-content
                                "

                                data-item-id="<?=
                                    $transactionItemId;
                                ?>"

                                data-qty-in="<?=
                                    $qtyIn;
                                ?>"

                                data-qty-out="<?=
                                    $qtyOut;
                                ?>"
                            >


                                <!-- DATE -->

                                <td>

                                    <?= date(
                                        'd-m-Y',
                                        strtotime(
                                            $row['created_at']
                                        )
                                    ); ?>

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['category_name']
                                        ?? ''
                                    ); ?>

                                </td>


                                <!-- ITEM -->

                                <td>

                                    <?php
                                    if(
                                        !empty(
                                            $row['item_name']
                                        )
                                    ):
                                    ?>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $row['item_name']
                                            ); ?>

                                        </strong>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            -
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- QUANTITY IN -->

                                <td
                                    class="
                                        text-right
                                        report-qty-in
                                    "

                                    data-quantity="<?=
                                        $qtyIn;
                                    ?>"
                                >

                                    <?php

                                    if($qtyIn > 0)
                                    {
                                        echo (
                                            floor($qtyIn)
                                            == $qtyIn
                                        )
                                            ? number_format(
                                                $qtyIn,
                                                0
                                            )
                                            : number_format(
                                                $qtyIn,
                                                2
                                            );
                                    }

                                    ?>

                                </td>


                                <!-- QUANTITY OUT -->

                                <td
                                    class="
                                        text-right
                                        report-qty-out
                                    "

                                    data-quantity="<?=
                                        $qtyOut;
                                    ?>"
                                >

                                    <?php

                                    if($qtyOut > 0)
                                    {
                                        echo (
                                            floor($qtyOut)
                                            == $qtyOut
                                        )
                                            ? number_format(
                                                $qtyOut,
                                                0
                                            )
                                            : number_format(
                                                $qtyOut,
                                                2
                                            );
                                    }

                                    ?>

                                </td>


                                <!-- CUSTOMER -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['customer_name']
                                        ?? ''
                                    ); ?>

                                </td>


                                <!-- DETAILS -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['details']
                                        ?? ''
                                    ); ?>

                                </td>


                                <!-- CASH IN -->

                                <td
                                    class="
                                        text-right
                                        report-cashin

                                        <?=
                                            $cashIn > 0
                                                ? 'text-primary font-weight-bold'
                                                : '';
                                        ?>
                                    "

                                    data-amount="<?=
                                        $cashIn;
                                    ?>"
                                >

                                    <?php

                                    if($cashIn > 0)
                                    {
                                        echo number_format(
                                            $cashIn,
                                            0
                                        );
                                    }

                                    ?>

                                </td>


                                <!-- CASH OUT -->

                                <td
                                    class="
                                        text-right
                                        report-cashout

                                        <?=
                                            $cashOut > 0
                                                ? 'text-danger font-weight-bold'
                                                : '';
                                        ?>
                                    "

                                    data-amount="<?=
                                        $cashOut;
                                    ?>"
                                >

                                    <?php

                                    if($cashOut > 0)
                                    {
                                        echo number_format(
                                            $cashOut,
                                            0
                                        );
                                    }

                                    ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <?php
                                    if(
                                        hasRole([
                                            'owner',
                                            'partner'
                                        ])
                                    ):
                                    ?>

                                        <span
                                            class="
                                                hover-display
                                                text-sms
                                            "
                                        >


                                            <!-- EDIT -->

                                            <button
                                                type="button"

                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-info
                                                    edit-trans
                                                    text-muted
                                                "

                                                data-id="<?=
                                                    $row['id'];
                                                ?>"

                                                data-type="<?=
                                                    $cashIn > 0
                                                        ? 'credit'
                                                        : 'debit';
                                                ?>"
                                            >

                                                <i
                                                    class="
                                                        fa
                                                        fa-edit
                                                    "
                                                ></i>

                                            </button>


                                            <!-- DELETE -->

                                            <button
                                                type="button"

                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-danger
                                                    delete-trans
                                                "

                                                data-id="<?=
                                                    $row['id'];
                                                ?>"

                                                data-type="<?=
                                                    $cashIn > 0
                                                        ? 'credit'
                                                        : 'debit';
                                                ?>"
                                            >

                                                <i
                                                    class="
                                                        fa
                                                        fa-trash
                                                    "
                                                ></i>

                                            </button>


                                        </span>

                                    <?php endif; ?>

                                </td>


                            </tr>

                            <?php

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | ITEM QUANTITY SUMMARY
                        |--------------------------------------------------------------------------
                        |
                        | This hidden row travels with the AJAX response.
                        |
                        | We can move/display its contents in the Reports page.
                        |
                        |--------------------------------------------------------------------------
                        */

                        ?>

                        <tr
                            class="item-summary-holder"
                            style="display:none;"
                        >

                            <td colspan="10">

                                <div
                                    class="
                                        item-quantity-summary-data
                                    "
                                >


                                    <?php
                                    if(
                                        !empty(
                                            $itemSummary
                                        )
                                    ):
                                    ?>


                                        <div
                                            class="
                                                table-responsive
                                            "
                                        >


                                            <table
                                                class="
                                                    table
                                                    table-sm
                                                    table-striped
                                                    table-hover
                                                    mb-0
                                                "
                                            >


                                                <thead>

                                                    <tr>

                                                        <th
                                                            style="
                                                                width:50px;
                                                            "
                                                        >
                                                            #
                                                        </th>


                                                        <th>
                                                            Item
                                                        </th>


                                                        <th
                                                            class="
                                                                text-center
                                                            "
                                                        >
                                                            Transactions
                                                        </th>


                                                        <th
                                                            class="
                                                                text-right
                                                            "
                                                        >
                                                            Qty In
                                                        </th>


                                                        <th
                                                            class="
                                                                text-right
                                                            "
                                                        >
                                                            Qty Out
                                                        </th>


                                                        <th
                                                            class="
                                                                text-right
                                                            "
                                                        >
                                                            Net Qty
                                                        </th>

                                                    </tr>

                                                </thead>


                                                <tbody>


                                                    <?php

                                                    $itemNumber = 1;

                                                    $summaryQtyIn  = 0;
                                                    $summaryQtyOut = 0;


                                                    foreach(
                                                        $itemSummary
                                                        as $summary
                                                    ):


                                                        $summaryNetQty =

                                                            $summary[
                                                                'qty_in'
                                                            ]

                                                            -

                                                            $summary[
                                                                'qty_out'
                                                            ];


                                                        $summaryQtyIn +=

                                                            $summary[
                                                                'qty_in'
                                                            ];


                                                        $summaryQtyOut +=

                                                            $summary[
                                                                'qty_out'
                                                            ];

                                                    ?>


                                                        <tr>


                                                            <td>

                                                                <?=
                                                                    $itemNumber++;
                                                                ?>

                                                            </td>


                                                            <td>

                                                                <strong>

                                                                    <?= htmlspecialchars(
                                                                        $summary[
                                                                            'name'
                                                                        ]
                                                                    ); ?>

                                                                </strong>

                                                            </td>


                                                            <td
                                                                class="
                                                                    text-center
                                                                "
                                                            >

                                                                <?= number_format(
                                                                    $summary[
                                                                        'transactions'
                                                                    ],
                                                                    0
                                                                ); ?>

                                                            </td>


                                                            <!-- QTY IN -->

                                                            <td
                                                                class="
                                                                    text-right
                                                                "
                                                            >

                                                                <?php

                                                                echo (
                                                                    floor(
                                                                        $summary[
                                                                            'qty_in'
                                                                        ]
                                                                    )
                                                                    ==
                                                                    $summary[
                                                                        'qty_in'
                                                                    ]
                                                                )
                                                                    ? number_format(
                                                                        $summary[
                                                                            'qty_in'
                                                                        ],
                                                                        0
                                                                    )
                                                                    : number_format(
                                                                        $summary[
                                                                            'qty_in'
                                                                        ],
                                                                        2
                                                                    );

                                                                ?>

                                                            </td>


                                                            <!-- QTY OUT -->

                                                            <td
                                                                class="
                                                                    text-right
                                                                "
                                                            >

                                                                <?php

                                                                echo (
                                                                    floor(
                                                                        $summary[
                                                                            'qty_out'
                                                                        ]
                                                                    )
                                                                    ==
                                                                    $summary[
                                                                        'qty_out'
                                                                    ]
                                                                )
                                                                    ? number_format(
                                                                        $summary[
                                                                            'qty_out'
                                                                        ],
                                                                        0
                                                                    )
                                                                    : number_format(
                                                                        $summary[
                                                                            'qty_out'
                                                                        ],
                                                                        2
                                                                    );

                                                                ?>

                                                            </td>


                                                            <!-- NET -->

                                                            <td
                                                                class="
                                                                    text-right
                                                                    font-weight-bold

                                                                    <?=
                                                                        $summaryNetQty < 0
                                                                            ? 'text-danger'
                                                                            : 'text-success';
                                                                    ?>
                                                                "
                                                            >

                                                                <?php

                                                                echo (
                                                                    floor(
                                                                        $summaryNetQty
                                                                    )
                                                                    ==
                                                                    $summaryNetQty
                                                                )
                                                                    ? number_format(
                                                                        $summaryNetQty,
                                                                        0
                                                                    )
                                                                    : number_format(
                                                                        $summaryNetQty,
                                                                        2
                                                                    );

                                                                ?>

                                                            </td>


                                                        </tr>


                                                    <?php endforeach; ?>


                                                </tbody>


                                                <tfoot>


                                                    <?php

                                                    $summaryNet =

                                                        $summaryQtyIn
                                                        -
                                                        $summaryQtyOut;

                                                    ?>


                                                    <tr
                                                        class="
                                                            font-weight-bold
                                                        "
                                                    >


                                                        <th
                                                            colspan="3"
                                                        >
                                                            TOTAL MOVEMENT
                                                        </th>


                                                        <th
                                                            class="
                                                                text-right
                                                            "
                                                        >
                                                            <?php

                                                            echo (
                                                                floor(
                                                                    $summaryQtyIn
                                                                )
                                                                ==
                                                                $summaryQtyIn
                                                            )
                                                                ? number_format(
                                                                    $summaryQtyIn,
                                                                    0
                                                                )
                                                                : number_format(
                                                                    $summaryQtyIn,
                                                                    2
                                                                );

                                                            ?>
                                                        </th>
                                                        <th
                                                            class="
                                                                text-right
                                                            "
                                                        >
                                                        <?php
                                                            echo (
                                                                floor(
                                                                    $summaryQtyOut
                                                                )
                                                                ==
                                                                $summaryQtyOut
                                                            )
                                                                ? number_format(
                                                                    $summaryQtyOut,
                                                                    0
                                                                )
                                                                : number_format(
                                                                    $summaryQtyOut,
                                                                    2
                                                                );

                                                            ?>
                                                        </th>
                                                        <th
                                                            class="
                                                                text-right
                                                            "
                                                        >
                                                        <?php
                                                            echo (
                                                                floor(
                                                                    $summaryNet
                                                                )
                                                                ==
                                                                $summaryNet
                                                            )
                                                                ? number_format(
                                                                    $summaryNet,
                                                                    0
                                                                )
                                                                : number_format(
                                                                    $summaryNet,
                                                                    2
                                                                );
                                                            ?>
                                                        </th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="
                                                text-center
                                                text-muted
                                                p-4
                                            "
                                        >
                                            No item quantity movements
                                            found for this report.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php


                    /*
                    |--------------------------------------------------------------------------
                    | NO TRANSACTIONS
                    |--------------------------------------------------------------------------
                    */

                    else:

                        ?>

                        <tr>

                            <td
                                colspan="10"
                                class="
                                    text-center
                                    text-muted
                                    p-5
                                "
                            >
                                <i
                                    class="
                                        fa
                                        fa-info-circle
                                        fa-2x
                                    "
                                ></i>
                                <div class="mt-2">
                                    No transactions found.
                                </div>
                            </td>
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

                            $sheet =
                                $spreadsheet->createSheet();


                            $sheet->setTitle(
                                'Transactions'
                            );


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


                            foreach (
                                $transactions
                                as $transaction
                            )
                            {
                                /*
                                |--------------------------------------------------------------------------
                                | QUANTITY
                                |--------------------------------------------------------------------------
                                */

                                $quantity =
                                    (float)(
                                        $transaction['quantity']
                                        ?? 0
                                    );


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

                                if (
                                    in_array(
                                        $transactionType,
                                        $quantityInTypes,
                                        true
                                    )
                                )
                                {
                                    $qtyIn =
                                        $quantity;
                                }
                                elseif (
                                    in_array(
                                        $transactionType,
                                        $quantityOutTypes,
                                        true
                                    )
                                )
                                {
                                    $qtyOut =
                                        $quantity;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CASH MOVEMENT
                                |--------------------------------------------------------------------------
                                */

                                $rowCashIn =
                                    isset(
                                        $transaction[
                                            'real_cashin'
                                        ]
                                    )
                                        ? (float)$transaction[
                                            'real_cashin'
                                        ]
                                        : (float)(
                                            $transaction[
                                                'credit_amount'
                                            ]
                                            ?? 0
                                        );


                                $rowCashOut =
                                    isset(
                                        $transaction[
                                            'real_cashout'
                                        ]
                                    )
                                        ? (float)$transaction[
                                            'real_cashout'
                                        ]
                                        : (float)(
                                            $transaction[
                                                'debit_amount'
                                            ]
                                            ?? 0
                                        );


                                /*
                                |--------------------------------------------------------------------------
                                | FALLBACK QUANTITY DIRECTION
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $quantity > 0 &&
                                    $qtyIn == 0 &&
                                    $qtyOut == 0 &&
                                    !empty(
                                        $transaction['item_id']
                                    )
                                )
                                {
                                    if (
                                        $rowCashOut > 0 &&
                                        $rowCashIn <= 0
                                    )
                                    {
                                        $qtyIn =
                                            $quantity;
                                    }
                                    elseif (
                                        $rowCashIn > 0 &&
                                        $rowCashOut <= 0
                                    )
                                    {
                                        $qtyOut =
                                            $quantity;
                                    }
                                }


                                $rowNetQty =
                                    $qtyIn - $qtyOut;


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

                            $totalRow =
                                $row;


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

                                $quantity =
                                    (float)($transaction['quantity'] ?? 0);

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

                                $netQty =
                                    $qtyIn - $qtyOut;


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


                            $netQuantity =
                                $totalQtyIn - $totalQtyOut;


                            $financialBalance =
                                $pdfCashIn - $pdfCashOut;


                            $pdfTransactionCount =
                                count($pdfTransactions);


                            /*
                            |--------------------------------------------------------------------------
                            | PDF CLASS
                            |--------------------------------------------------------------------------
                            */

                            class CashbookTransactionReportPDF extends FPDF
                            {
                                public $businessName = '';

                                public $reportTitle =
                                    'TRANSACTION REPORT';


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
                                    $cat =
                                        $catRes->fetch_assoc();


                                    if ($cat)
                                    {
                                        $filterText[] =

                                            'Category: ' .

                                            $cat['name'];
                                    }
                                }
                            }


                            /*
                            | CUSTOMER
                            */

                            if (
                                !empty(
                                    $filters['customer']
                                )
                            )
                            {
                                $custSql = "SELECT name
                                    FROM cashbook_customers
                                    WHERE id = ?
                                    AND book_id = ?
                                    LIMIT 1
                                ";

                                $custRes = prepared_statements(
                                    $custSql,
                                    'ii',
                                    [
                                        (int)$filters['customer'],
                                        (int)$book->id
                                    ]
                                );


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
                                $itemSql = "
                                    SELECT name
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


                            $number =
                                1;


                            /*
                            | Running financial balance.
                            */

                            $runningBalance =
                                0;


                            /*
                            | Add first table header.
                            */

                            $pdf->TableHeader();


                            foreach (
                                $pdfTransactions
                                as $transaction
                            )
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


                                /*
                                |--------------------------------------------------------------------------
                                | RUNNING BALANCE
                                |--------------------------------------------------------------------------
                                */

                                $runningBalance +=
                                    $credit - $debit;


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
            }
        }
            
        }else{
        redirect('../');
    }
?>