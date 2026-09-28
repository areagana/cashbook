<?php
    require_once(__dir__.'/../assets/functions.php');
    if(hasRole(['owner','partner','superadministrator']))
    {
        pageHeader('Members');
        $bsid = request('bkid');
        $book = bookFind(encryptor('decrypt',$bsid));
        ?>
            <?php
                /*
                |--------------------------------------------------------------------------
                | MEMBERS DATA
                |--------------------------------------------------------------------------
                */
                $sql = "
                    SELECT
                        cu.*,
                        r.name AS role
                    FROM cashbook_users cu
                    LEFT JOIN cashbook_roles r
                        ON cu.role_id = r.id
                    WHERE business_id = ?
                    ORDER BY cu.name ASC
                ";

                $res = prepared_statements(
                    $sql,
                    'i',
                    [$book->business_id]
                );

                $members = [];
                $memberCount = 0;
                $roleCounts = [];

                if($res)
                {
                    while($r = $res->fetch_assoc())
                    {
                        $members[] = $r;
                        $memberCount++;

                        $roleName = trim((string)($r['role'] ?? ''));

                        if($roleName == '')
                        {
                            $roleName = 'Unassigned';
                        }

                        if(!isset($roleCounts[$roleName]))
                        {
                            $roleCounts[$roleName] = 0;
                        }

                        $roleCounts[$roleName]++;
                    }
                }

                $roleCount = count($roleCounts);
                $membersWithContact = 0;
                $membersWithEmail = 0;

                foreach($members as $member)
                {
                    if(trim((string)($member['contact'] ?? '')) != '')
                    {
                        $membersWithContact++;
                    }

                    if(trim((string)($member['email'] ?? '')) != '')
                    {
                        $membersWithEmail++;
                    }
                }
            ?>

            <style>
                .member-summary-card {
                    border-radius: 8px;
                    min-height: 125px;
                }

                .member-summary-card .summary-icon {
                    font-size: 34px;
                    opacity: .16;
                }

                .member-table td,
                .member-table th {
                    vertical-align: middle;
                }

                .member-role-badge {
                    font-size: 11px;
                    font-weight: 600;
                }

                .member-search {
                    max-width: 300px;
                }

                @media(max-width: 767px)
                {
                    .member-search {
                        max-width: 100%;
                    }
                }
            </style>


            <div class="container-fluid">

                <!-- BREADCRUMB / PAGE NAVIGATION -->
                <div class="row mx-1 align-items-center">

                    <div class="col-md-8 p-2">

                        <a
                            href="../books/?bkid=<?=$bsid;?>"
                            class="nav-link d-inline-block">
                            Books
                        </a>

                        <i class="fa fa-angle-right"></i>

                        <a class="nav-link d-inline-block">
                            Members
                        </a>

                    </div>

                </div>

                <hr>


                <!-- PAGE HEADER -->
                <div class="row mx-1 align-items-center">

                    <div class="col-md-8 p-2">

                        <h3 class="mb-1">
                            <?=strToUpper($book->name);?> - MEMBERS
                        </h3>

                        <small class="text-muted">
                            Manage business members, user roles and contact information
                        </small>

                    </div>


                    <?php if(hasRole(['owner','partner','superadministrator'])):?>

                        <div class="col-md-4 p-2 text-md-right">

                            <button
                                class="btn btn-sm btn-flat btn-outline-success btn-click"
                                data-title="add member"
                                data-section="member">

                                <i class="fa fa-plus-circle"></i>
                                Member

                            </button>

                        </div>

                    <?php endif;?>

                </div>

                <hr>


                <!-- SUMMARY CARDS -->
                <div class="row mx-1">

                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card member-summary-card shadow-sm border-left border-info">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Members
                                        </small>

                                        <h4 class="mt-2 mb-0 text-info">
                                            <?=$memberCount;?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-info">
                                        <i class="fa fa-users"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    Total business members
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card member-summary-card shadow-sm border-left border-success">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            Roles
                                        </small>

                                        <h4 class="mt-2 mb-0 text-success">
                                            <?=$roleCount;?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-success">
                                        <i class="fa fa-id-badge"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    Roles currently assigned
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card member-summary-card shadow-sm border-left border-primary">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            With Email
                                        </small>

                                        <h4 class="mt-2 mb-0 text-primary">
                                            <?=$membersWithEmail;?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-primary">
                                        <i class="fa fa-envelope"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    Members with email addresses
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-3 col-md-6 p-2">

                        <div class="card member-summary-card shadow-sm border-left border-warning">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted text-uppercase">
                                            With Contact
                                        </small>

                                        <h4 class="mt-2 mb-0 text-warning">
                                            <?=$membersWithContact;?>
                                        </h4>

                                    </div>

                                    <div class="summary-icon text-warning">
                                        <i class="fa fa-phone"></i>
                                    </div>

                                </div>

                                <small class="text-muted">
                                    Members with phone contacts
                                </small>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ROLE DISTRIBUTION -->
                <?php if(!empty($roleCounts)):?>

                    <div class="row mx-1">

                        <div class="col-12 p-2">

                            <div class="card shadow-sm border-0">

                                <div class="card-header bg-white">

                                    <strong>
                                        <i class="fa fa-user-circle-o mr-2"></i>
                                        Role Distribution
                                    </strong>

                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        <?php foreach($roleCounts as $roleName => $count):?>

                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">

                                                <div class="border p-2">

                                                    <small class="text-muted text-uppercase">
                                                        <?=htmlspecialchars($roleName);?>
                                                    </small>

                                                    <h5 class="mb-0 mt-1">
                                                        <?=$count;?>
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


                <!-- MEMBERS REGISTER -->
                <div class="row mx-1">

                    <div class="col-12 p-2">

                        <div class="card shadow-sm border-0">

                            <div class="card-header bg-white">

                                <div class="row align-items-center">

                                    <div class="col-md-7">

                                        <strong>
                                            <i class="fa fa-table mr-2"></i>
                                            Members Register
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            Users who have access to this business
                                        </small>

                                    </div>


                                    <div class="col-md-5 mt-2 mt-md-0">

                                        <input
                                            type="text"
                                            id="memberSearch"
                                            class="form-control form-control-sm member-search float-md-right"
                                            placeholder="Search name, email, role or contact...">

                                    </div>

                                </div>

                            </div>


                            <div class="table-responsive">

                                <table class="table table-sm table-striped table-hover member-table mb-0">

                                    <thead>

                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Contact</th>
                                            <th class="text-right">Action</th>
                                        </tr>

                                    </thead>


                                    <tbody id="memberTableBody">

                                    <?php if(empty($members)):?>

                                        <tr>

                                            <td
                                                colspan="6"
                                                class="text-center text-muted p-4">

                                                No members found.

                                            </td>

                                        </tr>

                                    <?php else:?>

                                        <?php $t = 0;?>

                                        <?php foreach($members as $r):?>

                                            <tr class="hover hover-hide-content">

                                                <td>
                                                    <?=++$t;?>
                                                </td>


                                                <td>

                                                    <strong>
                                                        <?=htmlspecialchars($r['name'] ?? '');?>
                                                    </strong>

                                                </td>


                                                <td>
                                                    <?=htmlspecialchars($r['email'] ?? '');?>
                                                </td>


                                                <td>

                                                    <span class="badge badge-light border member-role-badge">
                                                        <?=htmlspecialchars($r['role'] ?? 'Unassigned');?>
                                                    </span>

                                                </td>


                                                <td>
                                                    <?=htmlspecialchars($r['contact'] ?? '');?>
                                                </td>


                                                <td class="text-right">

                                                    <?php if(hasRole(['owner','partner','superadministrator'])):?>

                                                        <span class="hover-display text-sms">

                                                            <button
                                                                class="btn btn-sm btn-outline-info btn-click text-muted"
                                                                data-id="<?=$r['id'];?>"
                                                                data-section="member-edit"
                                                                data-title="EDIT <?=htmlspecialchars($r['name'] ?? 'MEMBER');?>"
                                                                title="Edit Member">

                                                                <i class="fa fa-edit"></i>

                                                            </button>


                                                            <button
                                                                class="btn btn-sm btn-outline-danger delete-user"
                                                                data-id="<?=$r['id'];?>"
                                                                title="Delete Member">

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
                    var id = $(this).data('id');
                    var category = $(this).data('section');
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
                                member_id: id
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

                $(document).on('click','.saveUser',function(){
                    submitSingleForm("newUserForm", "../books/save/index.php");
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
                | MEMBER SEARCH
                |--------------------------------------------------------------------------
                */
                $(document).on('keyup','#memberSearch',function(){

                    var value = $(this).val().toLowerCase();

                    $('#memberTableBody tr').each(function(){

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