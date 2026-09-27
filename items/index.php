<?php
    require_once(__dir__.'/../assets/functions.php');
    if(isVerified())
    {
        pageHeader('Items');
        $bsid = request('bsid');
        $book = bookFind(encryptor('decrypt',$bsid));
        ?>
            <?php
                /*
                |--------------------------------------------------------------------------
                | ITEMS
                |--------------------------------------------------------------------------
                */
                $sql = "
                    SELECT *
                    FROM cashbook_items
                    WHERE book_id = ?
                    ORDER BY name ASC
                ";

                $res = prepared_statements(
                    $sql,
                    'i',
                    [$book->id]
                );

                $items = [];

                if($res)
                {
                    while($r = $res->fetch_assoc())
                    {
                        $items[] = $r;
                    }
                }

                $itemCount = count($items);
                $itemsWithUnits = 0;
                $itemsWithDetails = 0;
                $unitTypes = [];

                foreach($items as $item)
                {
                    $unit = trim((string)($item['units'] ?? ''));

                    if($unit != '')
                    {
                        $itemsWithUnits++;

                        $unitKey = strtolower($unit);

                        if(!isset($unitTypes[$unitKey]))
                        {
                            $unitTypes[$unitKey] = [
                                'name' => $unit,
                                'count' => 0
                            ];
                        }

                        $unitTypes[$unitKey]['count']++;
                    }

                    if(trim((string)($item['details'] ?? '')) != '')
                    {
                        $itemsWithDetails++;
                    }
                }

                uasort(
                    $unitTypes,
                    function($a,$b)
                    {
                        return $b['count'] <=> $a['count'];
                    }
                );

                $unitTypeCount = count($unitTypes);
            ?>

            <style>
                .item-summary-card {
                    border-radius: 8px;
                    min-height: 125px;
                }

                .item-summary-card .summary-icon {
                    font-size: 34px;
                    opacity: .16;
                }

                .item-table td,
                .item-table th {
                    vertical-align: middle;
                }

                .item-search {
                    max-width: 300px;
                }

                .item-mini-stat {
                    border-left: 3px solid #ddd;
                    padding-left: 12px;
                }

                .unit-badge {
                    font-size: 11px;
                    font-weight: 600;
                }

                @media(max-width: 767px)
                {
                    .item-search {
                        max-width: 100%;
                    }
                }
            </style>


            <div class="container-fluid">

                <!-- BREADCRUMB -->
                <div class="row mx-1 align-items-center">

                    <div class="col-md-8 p-2">

                        <a
                            href="../books/?bkid=<?=$bsid;?>"
                            class="nav-link d-inline-block">
                            Books
                        </a>

                        <i class="fa fa-angle-right"></i>

                        <a class="nav-link d-inline-block">
                            Items
                        </a>

                    </div>

                </div>

                <hr>


                <!-- PAGE HEADER -->
                <div class="row mx-1 align-items-center">

                    <div class="col-md-8 p-2">

                        <h3 class="mb-1">
                            <?=strToUpper($book->name);?> - ITEMS
                        </h3>

                        <small class="text-muted">
                            Manage products and stock items used in this business
                        </small>

                    </div>


                    <?php if(hasRole(['owner','partner'])):?>

                        <div class="col-md-4 p-2 text-md-right">

                            <button
                                class="btn btn-sm btn-flat btn-outline-success btn-click"
                                data-title="add item"
                                data-section="item">

                                <i class="fa fa-plus-circle"></i>
                                Item

                            </button>

                        </div>

                    <?php endif;?>

                </div>

                <hr>


                <!-- SUMMARY CARDS -->
                <div class="row mx-1">

                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card item-summary-card shadow-sm border-left border-info">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Total Items
                                        </small>

                                        <h4 class="mt-2 mb-0 text-info">
                                            <?=$itemCount;?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-info">
                                        <i class="fa fa-cubes"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    Items registered in this book
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card item-summary-card shadow-sm border-left border-success">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Unit Types
                                        </small>

                                        <h4 class="mt-2 mb-0 text-success">
                                            <?=$unitTypeCount;?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-success">
                                        <i class="fa fa-tags"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    Different units of measurement
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card item-summary-card shadow-sm border-left border-primary">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Units Defined
                                        </small>

                                        <h4 class="mt-2 mb-0 text-primary">
                                            <?=$itemsWithUnits;?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-primary">
                                        <i class="fa fa-balance-scale"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    Items with units specified
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card item-summary-card shadow-sm border-left border-warning">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Described Items
                                        </small>

                                        <h4 class="mt-2 mb-0 text-warning">
                                            <?=$itemsWithDetails;?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-warning">
                                        <i class="fa fa-file-text-o"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    Items with additional details
                                </small>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- UNIT DISTRIBUTION -->
                <?php if(!empty($unitTypes)):?>

                    <div class="row mx-1">

                        <div class="col-12 p-2">

                            <div class="card shadow-sm border-0">

                                <div class="card-header bg-white">

                                    <strong>
                                        <i class="fa fa-bar-chart mr-2"></i>
                                        Item Unit Distribution
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        Number of registered items grouped by unit of measurement
                                    </small>

                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        <?php foreach($unitTypes as $unit):?>

                                            <div class="col-xl-2 col-md-3 col-sm-3 mb-2">

                                                <div class="item-mini-stat">

                                                    <small class="text-muted text-uppercase">
                                                        <?=htmlspecialchars($unit['name']);?>
                                                    </small>

                                                    <h5 class="mt-1 mb-0">
                                                        <?=number_format($unit['count']);?>
                                                    </h5>

                                                </div>

                                            </div>

                                        <?php endforeach;?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endif;?>


                <!-- ITEMS REGISTER -->
                <div class="row mx-1">

                    <div class="col-12 p-2">

                        <div class="card shadow-sm border-0">

                            <div class="card-header bg-white">

                                <div class="row align-items-center">

                                    <div class="col-md-7">

                                        <strong>
                                            <i class="fa fa-table mr-2"></i>
                                            Items Register
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            Complete list of products and stock items
                                        </small>

                                    </div>


                                    <div class="col-md-5 mt-2 mt-md-0">

                                        <input
                                            type="text"
                                            id="itemSearch"
                                            class="form-control form-control-sm item-search float-md-right"
                                            placeholder="Search item, unit or details...">

                                    </div>

                                </div>

                            </div>


                            <div class="table-responsive">

                                <table class="table table-sm table-striped table-hover item-table mb-0">

                                    <thead>

                                        <tr>
                                            <th>#</th>
                                            <th>Item</th>
                                            <th>Units</th>
                                            <th>Details</th>
                                            <th class="text-right">Action</th>
                                        </tr>

                                    </thead>


                                    <tbody id="itemTableBody">

                                    <?php if(empty($items)):?>

                                        <tr>

                                            <td
                                                colspan="5"
                                                class="text-center text-muted p-4">

                                                No items have been registered.

                                            </td>

                                        </tr>

                                    <?php else:?>

                                        <?php $count = 1;?>

                                        <?php foreach($items as $r):?>

                                            <tr class="hover hover-hide-content">

                                                <td>
                                                    <?=$count++;?>
                                                </td>


                                                <td>

                                                    <strong>
                                                        <?=htmlspecialchars($r['name'] ?? '');?>
                                                    </strong>

                                                </td>


                                                <td>

                                                    <?php if(trim((string)($r['units'] ?? '')) != ''):?>

                                                        <span class="badge badge-light border unit-badge">
                                                            <?=htmlspecialchars($r['units']);?>
                                                        </span>

                                                    <?php else:?>

                                                        <span class="text-muted">
                                                            -
                                                        </span>

                                                    <?php endif;?>

                                                </td>


                                                <td class="text-muted">
                                                    <?=htmlspecialchars($r['details'] ?? '');?>
                                                </td>


                                                <td class="text-right">

                                                    <?php if(hasRole(['owner','partner'])):?>

                                                        <span class="hover-display text-sms">

                                                            <button
                                                                class="btn btn-sm btn-outline-info edit-item text-muted btn-click"
                                                                data-title="Item Edit"
                                                                data-section="item-edit"
                                                                data-id="<?=$r['id'];?>"
                                                                title="Edit Item">

                                                                <i class="fa fa-edit"></i>

                                                            </button>


                                                            <button
                                                                class="btn btn-sm btn-outline-danger delete-item"
                                                                data-id="<?=$r['id'];?>"
                                                                title="Delete Item">

                                                                <i class="fa fa-trash"></i>

                                                            </button>

                                                        </span>

                                                    <?php endif;?>

                                                </td>

                                            </tr>

                                        <?php endforeach;?>

                                    <?php endif;?>

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
                    var id = $(this).data('id');
                    fetchData(category,id);
                });

                function fetchData(sect,id)
                {
                    var book_id = "<?=$book->id;?>";
                    if(sect !='')
                    {
                        $.ajax({
                            url:'../books/save/index.php',
                            data:{
                                section:sect,
                                book_id:book_id,
                                action:'fetchForm',
                                item_id: id
                            },
                            beforeSend:function(){
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
                $(document).on('click','.saveItem',function(){
                    submitSingleForm("newItemForm", "../books/save/index.php");
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

                /*
                |--------------------------------------------------------------------------
                | ITEM SEARCH
                |--------------------------------------------------------------------------
                */
                $(document).on('keyup','#itemSearch',function(){

                    var value = $(this).val().toLowerCase();

                    $('#itemTableBody tr').each(function(){

                        var rowText = $(this).text().toLowerCase();

                        $(this).toggle(
                            rowText.indexOf(value) !== -1
                        );

                    });

                });

            </script>
        <?php
    }else{
        redirect('../');
    }

?>