<?php
/* =============================================================
   ITEM STATEMENT - AJAX MODAL CONTENT
   The statement stays within items/index.php.
============================================================= */
if (isset($_GET['item_statement']) && $_GET['item_statement'] === '1') {
    require_once __DIR__ . '/item_helpers.php';
    require_once __DIR__ . '/../assets/document_identity.php';

    $documentIdentity = khDocumentIdentity($bookId);
    $documentLogo = khDocumentLogoDataUri($documentIdentity);
    $documentCompanyLines = khDocumentCompanyLines($documentIdentity);

    $transactions = itemQuery(
        'SELECT id, created_at, details, credit_amount, debit_amount, quantity, rate, type, customer_id, category_id
         FROM cashbook_transactions WHERE book_id = ? AND item_id = ? ORDER BY created_at DESC, id DESC',
        'ii', [$bookId, $itemId]
    );

    $stocks = itemQuery(
        'SELECT id, created_at, transaction_type, quantity_in, quantity_out, balance, unit_cost, total_cost, reference
         FROM cashbook_stocks WHERE book_id = ? AND item_id = ? ORDER BY created_at DESC, id DESC',
        'ii', [$bookId, $itemId]
    );

    $references = itemReferences($itemId);
    $otherItems = itemQuery(
        'SELECT id, name, units FROM cashbook_items WHERE book_id = ? AND id <> ? ORDER BY name',
        'ii', [$bookId, $itemId]
    );

    $transactionRows = $transactions->fetch_all(MYSQLI_ASSOC);
    $stockRows = $stocks->fetch_all(MYSQLI_ASSOC);
    $totalCredits = array_sum(array_map(fn($r) => (float)$r['credit_amount'], $transactionRows));
    $totalDebits = array_sum(array_map(fn($r) => (float)$r['debit_amount'], $transactionRows));
    $totalStockIn = array_sum(array_map(fn($r) => (float)$r['quantity_in'], $stockRows));
    $totalStockOut = array_sum(array_map(fn($r) => (float)$r['quantity_out'], $stockRows));
    ?>
    <style>
        /* KashHub statement styling, aligned with customer PDF */
        .item-statement { color: #182230; font-size: 13px; }
        .item-statement .statement-header { border-bottom: 3px solid #176b51; padding-bottom: 14px; margin-bottom: 16px; }
        .item-statement .statement-brand { font-size: 22px; font-weight: 800; letter-spacing: .2px; }
        .item-statement .statement-logo { max-height: 64px; max-width: 90px; object-fit: contain; margin-right: 12px; }
        .item-statement .statement-company-line { font-size: 11px; line-height: 1.55; color: #667085; }
        .item-statement .statement-document { font-size: 17px; font-weight: 700; letter-spacing: 1px; }
        .item-statement .statement-muted, .item-statement .statement-label { color: #667085; }
        .item-statement .statement-label { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .item-statement .statement-info { border: 1px solid #d0d5dd; border-radius: 4px; padding: 15px; height: 100%; }
        .item-statement .statement-highlight { background: #f8fafc; border-left: 4px solid #176b51; }
        .item-statement .statement-item-name { font-size: 20px; font-weight: 700; margin: 6px 0; }
        .item-statement .statement-stat { border: 1px solid #d0d5dd; padding: 12px; height: 100%; }
        .item-statement .statement-stat strong { display: block; font-size: 16px; margin-top: 7px; }
        .item-statement .card-header { background: #edf2f5 !important; color: #344054; }
        .item-statement table thead th { background: #edf2f5; color: #344054; text-transform: uppercase; font-size: 11px; white-space: nowrap; }
        .item-statement table tfoot td { background: #e8f3ee; border-top: 2px solid #176b51; }
        .item-statement .statement-footer { border-top: 1px solid #d0d5dd; padding-top: 12px; color: #667085; font-size: 12px; }
        @media print {
            body * { visibility: hidden !important; }
            #itemStatementContent, #itemStatementContent * { visibility: visible !important; }
            #itemStatementContent { position: absolute; left: 0; top: 0; width: 100%; }
            .item-statement .no-print, .item-statement .transfer-btn { display: none !important; }
            .item-statement .table-responsive { overflow: visible !important; }
        }
    </style>
    <div class="item-statement px-2 py-2">
        <!-- Brand and document heading: customer-statement format -->
        <div class="statement-header d-flex justify-content-between align-items-start flex-wrap">
            <div class="d-flex align-items-start">
                <?php if ($documentLogo): ?>
                    <img class="statement-logo" src="<?= itemEsc($documentLogo); ?>" alt="Company logo">
                <?php endif; ?>
                <div>
                    <div class="statement-brand"><?= itemEsc($documentIdentity['company_name']); ?></div>
                    <?php foreach ($documentCompanyLines as $companyLine): ?>
                        <div class="statement-company-line"><?= itemEsc($companyLine); ?></div>
                    <?php endforeach; ?>
                    <div class="statement-company-line"><strong>Book:</strong> <?= itemEsc($documentIdentity['book_name']); ?></div>
                    <div class="statement-muted">Item transaction statement · Inventory movements &amp; transaction records</div>
                </div>
            </div>
            <div class="text-right">
                <div class="statement-document">ITEM STATEMENT</div>
                <div class="statement-muted">Generated: <?= date('d M Y, h:i A'); ?></div>
                <div class="statement-muted">Item ID: <?= (int)$itemId; ?></div>
                <a class="btn btn-sm btn-success mt-2 no-print" target="_blank" rel="noopener"
                   href="pdf.php?<?= http_build_query(['bsid' => $bsid, 'item_id' => $itemId]); ?>">
                    <i class="fa fa-file-pdf-o"></i> Download PDF
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 no-print" onclick="window.print()">
                    <i class="fa fa-print"></i> Print Statement
                </button>
            </div>
        </div>

        <!-- Item details and closing position -->
        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <div class="statement-info">
                    <div class="statement-label">Statement for</div>
                    <div class="statement-item-name"><?= itemEsc($item['name']); ?></div>
                    <div class="statement-muted"><strong>Business:</strong> <?= itemEsc($book->name); ?></div>
                    <div class="statement-muted"><strong>Units:</strong> <?= itemEsc($item['units'] ?? '-'); ?></div>
                    <div class="statement-muted"><strong>Details:</strong> <?= itemEsc($item['details'] ?? '-'); ?></div>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <div class="statement-info statement-highlight text-right">
                    <div class="statement-label">Net recorded stock movement</div>
                    <div class="statement-item-name" style="color:#176b51;">
                        <?= number_format($totalStockIn - $totalStockOut, 2); ?> <?= itemEsc($item['units'] ?? ''); ?>
                    </div>
                    <div class="statement-muted"><?= count($transactionRows); ?> transactions · <?= count($stockRows); ?> stock movements</div>
                    <div class="statement-muted">Movement total, not necessarily current stock on hand</div>
                </div>
            </div>
        </div>

        <!-- Summary tiles -->
        <div class="row mb-3">
            <?php foreach ([
                'Transaction credits' => $totalCredits,
                'Transaction debits' => $totalDebits,
                'Stock in' => $totalStockIn,
                'Stock out' => $totalStockOut
            ] as $label => $value): ?>
                <div class="col-lg-3 col-sm-6 mb-2">
                    <div class="statement-stat">
                        <div class="statement-label"><?= itemEsc($label); ?></div>
                        <strong><?= number_format((float)$value, 2); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="alert alert-info small">
            Transaction credits and debits are accounting entries, not necessarily cash received or paid.
            Stock movements are reported separately to prevent double counting.
        </div>

        <!-- Transaction statement -->
        <div class="card mb-3">
            <div class="card-header bg-white"><strong>Transaction Statement</strong></div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th><th>Details</th><th>Type</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Credit</th>
                            <th class="text-right">Debit</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactionRows as $row): ?>
                            <tr>
                                <td><?= itemEsc($row['created_at']); ?></td>
                                <td><?= itemEsc($row['details']); ?></td>
                                <td><?= itemEsc($row['type']); ?></td>
                                <td class="text-right"><?= number_format((float)$row['quantity'], 2); ?></td>
                                <td class="text-right"><?= number_format((float)$row['credit_amount'], 2); ?></td>
                                <td class="text-right"><?= number_format((float)$row['debit_amount'], 2); ?></td>
                                <td>
                                    <?php if (isItemManager()): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary transfer-btn"
                                                data-id="<?= (int)$row['id']; ?>">Transfer</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$transactionRows): ?>
                            <tr><td colspan="7" class="text-center text-muted p-3">No transactions recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="font-weight-bold bg-light">
                        <tr>
                            <td colspan="4">TOTAL</td>
                            <td class="text-right"><?= number_format($totalCredits, 2); ?></td>
                            <td class="text-right"><?= number_format($totalDebits, 2); ?></td><td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Stock movement statement -->
        <div class="card mb-3">
            <div class="card-header bg-white"><strong>Stock Movement Statement</strong></div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered mb-0">
                    <thead class="thead-light">
                        <tr><th>Date</th><th>Movement</th><th>Reference</th>
                            <th class="text-right">In</th><th class="text-right">Out</th>
                            <th class="text-right">Recorded Balance</th><th class="text-right">Cost</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockRows as $row): ?>
                            <tr>
                                <td><?= itemEsc($row['created_at']); ?></td>
                                <td><?= itemEsc($row['transaction_type']); ?></td>
                                <td><?= itemEsc($row['reference']); ?></td>
                                <td class="text-right"><?= number_format((float)$row['quantity_in'], 2); ?></td>
                                <td class="text-right"><?= number_format((float)$row['quantity_out'], 2); ?></td>
                                <td class="text-right"><?= number_format((float)$row['balance'], 2); ?></td>
                                <td class="text-right"><?= number_format((float)$row['total_cost'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$stockRows): ?>
                            <tr><td colspan="7" class="text-center text-muted p-3">No stock movements.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="font-weight-bold bg-light">
                        <tr><td colspan="3">TOTAL MOVEMENT</td>
                            <td class="text-right"><?= number_format($totalStockIn, 2); ?></td>
                            <td class="text-right"><?= number_format($totalStockOut, 2); ?></td><td colspan="2"></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Item deletion eligibility -->
        <div class="card mb-3">
            <div class="card-header bg-white"><strong>Related Records / Deletion Eligibility</strong></div>
            <div class="card-body">
                <?php if (!$references): ?>
                    <span class="badge badge-success">No related records — owner can permanently delete after password confirmation</span>
                <?php else: ?>
                    <p class="text-warning mb-2">This item has related records. Administrative deletion will archive it while preserving history.</p>
                    <?php foreach ($references as $table => $count): ?>
                        <span class="badge badge-secondary mr-2 mb-2"><?= itemEsc($table); ?>: <?= (int)$count; ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="statement-footer mb-3">
            <strong>Statement guide:</strong> Stock in increases recorded movement; stock out decreases it.
            Monetary credits and debits are shown as recorded and are not a profit calculation.
            <span class="float-right">Prepared for <?= itemEsc($documentIdentity['company_name']); ?> · Generated by KashHub</span>
        </div>

        <!-- Transfer transaction (existing safe transfer endpoint) -->
        <?php if (isItemManager()): ?>
            <div class="card mb-3">
                <div class="card-header bg-white"><strong>Transfer Transaction to Another Item</strong></div>
                <div class="card-body">
                    <p class="text-muted small">
                        Related cash and ledger references transfer together. Stock-linked transactions remain blocked
                        until their stock balances can be reconciled safely.
                    </p>
                    <form id="transferForm" class="form-row align-items-end">
                        <input type="hidden" name="action" value="transfer">
                        <input type="hidden" name="bsid" value="<?= itemEsc($bsid); ?>">
                        <input type="hidden" name="item_id" value="<?= (int)$itemId; ?>">
                        <div class="col-md-3 mb-2">
                            <label>Transaction ID</label>
                            <input class="form-control" id="transactionId" type="number" min="1" name="transaction_id" required>
                        </div>
                        <div class="col-md-5 mb-2">
                            <label>Move to Item</label>
                            <select class="form-control" name="target_item_id" required>
                                <option value="">Select target item</option>
                                <?php while ($other = $otherItems->fetch_assoc()): ?>
                                    <option value="<?= (int)$other['id']; ?>">
                                        <?= itemEsc($other['name'].' ('.($other['units'] ?? '').')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <button class="btn btn-primary btn-block" type="submit">Transfer Transaction</button>
                        </div>
                    </form>
                    <div id="actionMessage" class="mt-2"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    exit;
}

/* =============================================================
   ITEMS REGISTER - EXISTING PAGE
============================================================= */
    require_once(__dir__.'/../assets/functions.php');
    require_once(__dir__.'/item_helpers_bootstrap.php');
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
                      AND archived_at IS NULL
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

                    <?php if(hasRole(['owner','partner','superadministrator'])):?>

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

                                                    <?php if(hasRole(['owner','partner','superadministrator'])):?>

                                                        <span class="hover-display text-sms">

                                                                                                                         <a href="#" data-item-id="<?= (int)$r['id']; ?>" class="btn btn-sm btn-outline-primary view-item-statement" title="View Item Statement"><i class="fa fa-list-alt"></i> Statement</a>

<button
                                                                class="btn btn-sm btn-outline-info edit-item text-muted btn-click"
                                                                data-title="Item Edit"
                                                                data-section="item-edit"
                                                                data-id="<?=$r['id'];?>"
                                                                title="Edit Item">

                                                                <i class="fa fa-edit"></i>

                                                            </button>

                                                            <?php if (isItemDeleteAdmin()): ?>
                                                            <button
                                                                class="btn btn-sm btn-outline-danger delete-item"
                                                                data-id="<?=$r['id'];?>"
                                                                title="Delete Item">

                                                                <i class="fa fa-trash"></i>

                                                            </button>
                                                            <?php endif; ?>

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

            <!-- Password confirmation: masked field, never browser prompt() -->
            <div id="itemDeleteConfirm" class="modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="itemDeleteHeading" style="display:none;background:rgba(0,0,0,.55);z-index:1080;">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form id="itemDeleteConfirmForm" autocomplete="off">
                            <div class="modal-header">
                                <h5 class="modal-title" id="itemDeleteHeading">Confirm item removal</h5>
                                <button type="button" class="close item-delete-cancel" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body">
                                <div id="itemDeleteWarning" class="alert alert-warning" style="white-space:pre-line;"></div>
                                <label for="itemDeletePassword">Your KashHub account password</label>
                                <input type="password" id="itemDeletePassword" class="form-control" autocomplete="current-password" required>
                                <small class="text-muted">Your password is masked and is checked by the server. Archived items remain in the database to protect financial history.</small>
                                <div id="itemDeleteError" class="text-danger mt-2" role="alert"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary item-delete-cancel">Cancel</button>
                                <button type="submit" id="itemDeleteSubmit" class="btn btn-danger">Confirm removal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
             <!-- ITEM STATEMENT - KASHHUB CUSTOM CENTRAL MODAL -->
             <div class="p-0 bg-white central-modal absolute border shadow" id="itemStatementModal" style="display:none;">
                 <div class="central-modal-header bg-success">
                     <h3 class="central-modal-title text-white" id="itemStatementTitle">Item Transaction Statement</h3>
                     <button type="button" class="central-modal-close" aria-label="Close item statement">&times;</button>
                 </div>
                 <div class="central-modal-content" id="itemStatementContent"></div>
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

                /* =========================================================
                   ITEM STATEMENT MODAL AND SAFE ITEM DELETION
                ========================================================= */
                const itemBookToken = <?=json_encode($bsid);?>;
                let activeStatementItem = null;

                function openItemStatement(itemId) {
                    activeStatementItem = itemId;
                    $('#itemStatementModal').show();
                    $('#itemStatementContent').html('<div class="p-5 text-center"><i class="fa fa-spinner fa-spin"></i> Loading item statement...</div>');
                    $.ajax({
                        url: 'index.php', method: 'GET',
                        data: {item_statement: 1, item_id: itemId, bsid: itemBookToken},
                        success: function (html) { $('#itemStatementContent').html(html); },
                        error: function (xhr) {
                            $('#itemStatementContent').text(xhr.responseText || 'Unable to load item statement.');
                        }
                    });
                }

                $(document).on('click', '#itemStatementModal .central-modal-close', function () {
                    $('#itemStatementModal').hide();
                });

                $(document).on('click', '.view-item-statement', function (event) {
                    event.preventDefault();
                    openItemStatement($(this).data('item-id'));
                });

                $(document).on('click', '#itemStatementModal .transfer-btn', function () {
                    $('#itemStatementModal #transactionId').val($(this).data('id'));
                    $('#itemStatementModal #transactionId').trigger('focus');
                });

                $(document).on('submit', '#itemStatementModal #transferForm', function (event) {
                    event.preventDefault();
                    if (!confirm('Transfer this transaction to the selected item?')) return;
                    $.ajax({
                        url: 'action.php', method: 'POST', dataType: 'json', data: $(this).serialize(),
                        success: function (response) {
                            xdialog.alert(response.message);
                            if (response.ok) openItemStatement(activeStatementItem);
                        },
                        error: function (xhr) {
                            xdialog.alert(xhr.responseJSON?.message || 'Transfer failed.');
                        }
                    });
                });

                /* =========================================================
                   PASSWORD-CONFIRMED ADMINISTRATIVE ITEM DELETION
                ========================================================= */
                const itemDeleteCsrf = <?= json_encode($_SESSION['kh_item_csrf'] ?? ''); ?>;

                let itemPendingDeletion = null;

                function closeItemDeleteDialog() {
                    $('#itemDeletePassword').val('');
                    $('#itemDeleteError').text('');
                    $('#itemDeleteConfirm').hide();
                    itemPendingDeletion = null;
                }

                $(document).on('click', '.item-delete-cancel', closeItemDeleteDialog);

                $(document).on('click', '.delete-item', function () {
                    const itemId = Number($(this).data('id'));
                    $.ajax({
                        url: 'action.php', method: 'POST', dataType: 'json',
                        data: {action: 'preview_delete', item_id: itemId, bsid: itemBookToken},
                        success: function (response) {
                            if (!response.ok) { alert(response.message); return; }
                            itemPendingDeletion = itemId;
                            const references = Object.entries(response.references || {})
                                .map(([table, count]) => table + ': ' + count).join('\n');
                            const explanation = response.mode === 'archive'
                                ? 'This item has related records and will be ARCHIVED, not physically erased.'
                                : 'This item has no references and will be PERMANENTLY DELETED.';
                            const warning = 'Stock movements: ' + Number(response.stock_movements || 0)
                                + '\n' + (references ? 'Related records:\n' + references + '\n' : '')
                                + '\n' + explanation;
                            $('#itemDeleteWarning').text(warning);
                            $('#itemDeleteError').text('');
                            $('#itemDeletePassword').val('');
                            $('#itemDeleteConfirm').show();
                            $('#itemDeletePassword').trigger('focus');
                        },
                        error: function (xhr) {
                            alert(xhr.responseJSON?.message || 'Unable to check item references.');
                        }
                    });
                });

                $(document).on('submit', '#itemDeleteConfirmForm', function (event) {
                    event.preventDefault();
                    if (!itemPendingDeletion) return;
                    const password = $('#itemDeletePassword').val();
                    if (!password) return;
                    const submit = $('#itemDeleteSubmit').prop('disabled', true);
                    $.ajax({
                        url: 'action.php', method: 'POST', dataType: 'json',
                        data: {action: 'delete', item_id: itemPendingDeletion,
                               bsid: itemBookToken, password: password, csrf: itemDeleteCsrf},
                        success: function (response) {
                            if (response.ok) {
                                closeItemDeleteDialog();
                                alert(response.message);
                                window.location.reload();
                            } else {
                                $('#itemDeleteError').text(response.message || 'Removal failed.');
                            }
                        },
                        error: function (xhr) {
                            $('#itemDeleteError').text(xhr.responseJSON?.message || 'Unable to remove item.');
                        },
                        complete: function () {
                            $('#itemDeletePassword').val('');
                            submit.prop('disabled', false);
                        }
                    });
                });

                /* =========================================================
                   ITEM SEARCH
                ========================================================= */
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