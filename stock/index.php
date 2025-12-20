<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Stock');
        $bsid = request('bkid');
        $book = bookFind(encryptor('decrypt',$bsid));
        ?>
            <style>
                .stock-card{
                    height:140px;
                    min-width:250px;
                    border-radius:8px;
                }
            </style>
            <div class="container-fluid stock-body">
                <div class="row mx-1">
                    <div class="col p-2 inline-block">
                        <a href="../books/?bkid=<?=$bsid;?>" class="nav-link">Books</a><i class="fa fa-angle-right"></i>
                        <a class="nav-link">Stock</a>
                    </div>
                </div>
                <hr>
                <div class="row mx-1">
                    <div class="col p-2">
                        <div class="row mx-1">
                            <div class="col p-2">
                                <h3 class="p-2"><?=strToUpper($book->name);?> - STOCK</h3>
                            </div>
                            <div class="col p-2">
                                <?php if(hasRole(['owner','partner'])):?>
                                    <div class="col p-3">
                                        <button class="btn btn-sm btn-flat btn-outline-success btn-click right" data-title='add stock Item' data-section='item'><i class="fa fa-plus-circle"></i> Add Stock Item</button>
                                    </div>
                                <?php endif;?>
                            </div>
                        </div>
                        <hr>
                        <div class="p-2 row">
                            <div class="col p-2">
                                <?php
                                    $sql = "SELECT  i.id, i.name,i.units, COALESCE(s.balance, 0) AS balance FROM cashbook_items i
                                                LEFT JOIN  (SELECT cs.item_id, cs.balance FROM cashbook_stocks cs INNER JOIN (
                                                        SELECT item_id, MAX(id) AS last_id FROM cashbook_stocks  WHERE book_id = ?
                                                        GROUP BY item_id
                                                    ) latest ON cs.id = latest.last_id) s ON s.item_id = i.id
                                                WHERE i.book_id = ? ";
                                    $res = prepared_statements($sql,'ii',[$book->id, $book->id]);
                                
                                    // loop data entry
                                   while ($r = $res->fetch_assoc()): 
                                        $borderClass = ($r['balance'] < 10)
                                            ? 'border-danger'
                                            : 'border-primary';
                                ?>
                                    <div class="p-2 border rounded-3 <?= $borderClass ?> m-1 float-left stock-card hover text-center"
                                        data-item="<?= $r['id']; ?>"
                                        data-title="<?= htmlspecialchars($r['name']); ?>">

                                        <h4 class="p-2 border-bottom">
                                            <?= htmlspecialchars($r['name']); ?>
                                        </h4>
                                        <small class="text-muted text-center">
                                            Balance: <h5><?= number_format($r['balance'], 0)." ".$r['units']; ?></h5>
                                        </small>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="col-md-3 border-left p-2">
                                <h3 class="p-2 border-bottom">STOCK CHECK < 10 (qty)</h3>
                                <?php
                                    $query = "SELECT i.id,i.name,COALESCE(s.balance, 0) AS balance
                                            FROM cashbook_items i
                                            LEFT JOIN ( SELECT cs.book_id, cs.item_id, cs.balance
                                                        FROM cashbook_stocks cs
                                                        INNER JOIN (
                                                            SELECT book_id, item_id, MAX(id) AS last_id
                                                            FROM cashbook_stocks
                                                            GROUP BY book_id, item_id
                                                        ) latest
                                                        ON cs.id = latest.last_id 
                                                    ) s 
                                                ON s.item_id = i.id
                                                    AND s.book_id = i.book_id
                                                    WHERE i.book_id = ?
                                                    ORDER BY balance ASC";
                                    $query = prepared_statements($query,'i',[$book->id]);
                                ?>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php while($r = $query->fetch_assoc()): 
                                        if($r['balance'] < 10):
                                    ?>
                                            <tr>
                                                <td><?=$r['name'];?></td>
                                                <td><?=$r['balance'];?></td>
                                            </tr>
                                    <?php endif; endwhile;?>
                                    </tbody>
                                </table>
                            </div>
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
                    var book_id = "<?=encryptor('decrypt',request('bkid'));?>";
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

                // stock card click and details
                $(document).on('click','.stock-card',function(){
                    $('#central-modal').show();
                    var title = $(this).data('title');
                    var id = $(this).data('item');
                    
                    // fetch item details
                   loadModalContent(id,title);
                });

                // load modal content
                function loadModalContent(id,title)
                {
                    $('.central-modal-title').html(title);
                    $('side-modal-tall').hide();

                    // send request to backend
                     $.ajax({
                        url:'save/index.php',
                        data:{
                            action:'ItemDetails',
                            item_id:id
                        },
                        beforeSend:()=>{
                            $('.central-modal-content').html("<h3 class='text-center'>Loading...</h3>");
                        },
                        success:(res)=>{
                            $('.central-modal-content').html(res);
                        },
                        error:(err)=>{
                            $('.central-modal-content').html("<h3 class='text-center'>Error Loading Data!!</h3>");
                        }
                    });
                }
                // add or remove stock form
                // 
                $(document).on('click','.stock-control',function(){
                    var id = $(this).data('id');
                    var type = $(this).data('type');
                    var title = type.toUpperCase();

                    // display the model without content
                    $('.side-modal-tall').show();
                    $('.side-modal-title').html(title);

                    loadForm(id,type);
                });

                function loadForm(id,type)
                {
                    $.ajax({
                        url:'save/index.php',
                        data:{
                            type:type,
                            id:id,
                            action:type
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

                function submitSingleForm(formId, backendUrl) 
                {
                    const form = document.getElementById(formId);
                    var title = $('#item_title').val();
                    var id = $('#item_id').val();
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
                            $('.side-modal-tall').hide();
                            xdialog.stopSpin();
                            loadModalContent(id,title);
                            var ress = JSON.parse(response);
                            xdialog.info(ress.message);
                            // refresh the home page to capture all data
                            refreshStockSection('stock-body');
                        })
                        .catch(err => {
                            console.error("AJAX error:", err);
                        });
                    });
                }
        function refreshStockSection(section) {
            $('.stock-body').load(window.location.href + ' .stock-body > *');
        }
        // Call the function for your form
        $(document).on('click','.saveIssueStock',function(){
            submitSingleForm("IssueStockForm", "save/index.php");
        });

        $(document).on('click','.saveNewStock',function(){
            submitSingleForm("AddStockForm", "save/index.php");
        });
                
            </script>
        <?php
    }else{
        redirect('../');
    }

?>