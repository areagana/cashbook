<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified() && hasRole(['owner','partner','staff','superadministrator']))
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
            switch($action)
            {
                case 'creditor-details':
                    /* 1. Creditor and existing ledger source */
                    $id = (int)request('creditor_id');
                    $creditor = creditorFind($id);
                    if (!$creditor) {
                        echo '<div class="alert alert-danger m-3">Creditor not found.</div>';
                        break;
                    }
                    $book_id = (int)$creditor->book_id;
                    // Keep the application's existing authenticated book-access check here.
                    $esc = static function($value) {
                        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
                    };
                    $money = static function($value) {
                        return number_format((float)$value, 2);
                    };

                    $sql = "SELECT ccl.*, ci.name AS item_name, ci.units, pm.name AS paymode_name
                            FROM cashbook_creditor_ledger ccl
                            LEFT JOIN cashbook_items ci ON ci.id=ccl.item_id AND ci.book_id=ccl.book_id
                            LEFT JOIN cashbook_paymodes pm ON pm.id=ccl.paymode_id AND pm.book_id=ccl.book_id
                            WHERE ccl.creditor_id=? AND ccl.book_id=?
                            ORDER BY ccl.created_at ASC, ccl.id ASC";
                    $res = prepared_statements($sql, 'ii', [$id, $book_id]);
                    $rawRows = [];
                    if ($res) {
                        while ($record = $res->fetch_assoc()) {
                            $rawRows[] = $record;
                        }
                    }

                    /* 2. Opening balance and statement movements
                       A zero-movement first entry may represent the original B/F.
                       Otherwise derive B/F from the first stored ledger balance.
                       This changes presentation only; no database updates occur. */
                    $openingBalance = 0.0;
                    $startIndex = 0;
                    $hasOpening = false;
                    if ($rawRows) {
                        $first = $rawRows[0];
                        $firstCredit = (float)($first['credit_amount'] ?? 0);
                        $firstDebit = (float)($first['debit_amount'] ?? 0);
                        $openingBalance = round((float)($first['balance'] ?? 0) - $firstCredit + $firstDebit, 2);
                        if (abs($firstCredit) < 0.005 && abs($firstDebit) < 0.005) {
                            $openingBalance = round((float)($first['balance'] ?? 0), 2);
                            $startIndex = 1;
                        }
                        $hasOpening = true;
                    }
                    $rows = [];
                    $running = $openingBalance;
                    $totalCredit = 0.0;
                    $totalDebit = 0.0;
                    $firstDate = '';
                    $lastDate = '';
                    $storedBalanceDifferences = 0;
                    for ($i = $startIndex; $i < count($rawRows); $i++) {
                        $r = $rawRows[$i];
                        $credit = (float)($r['credit_amount'] ?? 0);
                        $debit = (float)($r['debit_amount'] ?? 0);
                        $totalCredit += $credit;
                        $totalDebit += $debit;
                        $running = round($running + $credit - $debit, 2);
                        $r['statement_balance'] = $running;
                        if (abs($running - (float)($r['balance'] ?? 0)) > 0.01) {
                            $storedBalanceDifferences++;
                        }
                        $type = strtolower(trim((string)($r['type'] ?? '')));
                        $description = trim((string)(($r['details'] ?? '') ?: ($r['item_name'] ?? '')));
                        if ($description === '' || is_numeric($description)) {
                            $description = $credit > 0 ? 'Additional amount owed to creditor' : ($debit > 0 ? 'Payment to creditor' : 'Account adjustment');
                        }
                        $r['statement_description'] = $description;
                        $r['statement_activity'] = $credit > 0 && $debit == 0 ? 'Amount added' : ($debit > 0 && $credit == 0 ? 'Payment made' : ($credit != 0 || $debit != 0 ? 'Adjustment' : 'Note'));
                        $rows[] = $r;
                        if (!empty($r['created_at'])) {
                            if ($firstDate === '') $firstDate = $r['created_at'];
                            $lastDate = $r['created_at'];
                        }
                    }
                    if (!$firstDate && !empty($rawRows[0]['created_at'])) {
                        $firstDate = $lastDate = $rawRows[0]['created_at'];
                    }

                    /* 3. Compare against register balance, without replacing statement figures */
                    $registerResult = prepared_statements(
                        'SELECT balance FROM cashbook_creditor_balances WHERE creditor_id=? LIMIT 1',
                        'i', [$id]
                    );
                    $registerRow = $registerResult ? $registerResult->fetch_assoc() : null;
                    $registerBalance = $registerRow ? (float)$registerRow['balance'] : null;
                    $hasDifference = $registerBalance !== null && abs($registerBalance - $running) > 0.01;
                    $status = $running > 0.005 ? 'AMOUNT OWED TO CREDITOR' : ($running < -0.005 ? 'ADVANCE / OVERPAYMENT' : 'ACCOUNT SETTLED');
                    $statusColor = $running > 0.005 ? '#b42318' : ($running < -0.005 ? '#067647' : '#175cd3');
                    $period = $firstDate ? date('d M Y', strtotime($firstDate)).' — '.date('d M Y', strtotime($lastDate)) : 'No transactions';
                    $printId = 'kh-creditor-print-'.$id;
                    ?>
                    <style>
                    .kh-statement{--kh-ink:#182230;--kh-muted:#667085;--kh-border:#d0d5dd;color:var(--kh-ink);font-family:Arial,Helvetica,sans-serif;background:#fff;font-size:13px}
                    .kh-statement *{box-sizing:border-box}.kh-statement .kh-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;background:#f8fafc;border-bottom:1px solid var(--kh-border)}
                    .kh-statement .kh-sheet{padding:22px 26px}.kh-statement .kh-head{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;border-bottom:3px solid #176b51;padding-bottom:17px}
                    .kh-statement .kh-brand{font-size:22px;font-weight:800;letter-spacing:.2px}.kh-statement .kh-subtitle{color:var(--kh-muted);font-size:11px;margin-top:5px;line-height:1.5}
                    .kh-statement .kh-doc{text-align:right}.kh-statement .kh-doc h2{font-size:17px;letter-spacing:1px;margin:0 0 8px;font-weight:800}.kh-statement .kh-doc p{margin:3px 0;color:var(--kh-muted);font-size:11px}
                    .kh-statement .kh-details{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(200px,1fr);gap:18px;margin:20px 0}
                    .kh-statement .kh-panel{border:1px solid var(--kh-border);padding:14px 16px}.kh-statement .kh-panel-label{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--kh-muted);font-weight:800;margin-bottom:9px}
                    .kh-statement .kh-creditor-name{font-size:17px;font-weight:800;margin-bottom:8px}.kh-statement .kh-info{line-height:1.75;color:#475467;overflow-wrap:anywhere}
                    .kh-statement .kh-amount{text-align:right;border-left:4px solid #176b51;background:#f8fafc}.kh-statement .kh-amount-value{font-size:25px;font-weight:800;white-space:nowrap;font-variant-numeric:tabular-nums}
                    .kh-statement .kh-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:20px}
                    .kh-statement .kh-stat{border:1px solid var(--kh-border);padding:11px 13px}.kh-statement .kh-stat small{display:block;font-size:10px;text-transform:uppercase;color:var(--kh-muted);font-weight:700;margin-bottom:6px}.kh-statement .kh-stat strong{font-size:16px;font-variant-numeric:tabular-nums}
                    .kh-statement .kh-section{font-size:14px;font-weight:800;letter-spacing:.5px;margin:0 0 10px}.kh-statement .kh-scroll{overflow-x:auto}
                    .kh-statement table{width:100%;border-collapse:collapse;font-size:11px}.kh-statement th,.kh-statement td{border:1px solid #cbd5e1;padding:8px 7px;vertical-align:top}
                    .kh-statement thead th{background:#edf2f5;color:#344054;text-transform:uppercase;font-size:9px;letter-spacing:.25px}
                    .kh-statement tbody tr:nth-child(even){background:#fafbfc}.kh-statement .kh-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.kh-statement .kh-date,.kh-statement .kh-nowrap{white-space:nowrap}
                    .kh-statement tfoot th,.kh-statement tfoot td{background:#e8f3ee;font-weight:800;border-top:2px solid #176b51}
                    .kh-statement .kh-foot{display:flex;justify-content:space-between;gap:18px;border-top:1px solid var(--kh-border);margin-top:19px;padding-top:12px;color:var(--kh-muted);font-size:10px;line-height:1.6}
                    .kh-statement .kh-foot>div{max-width:48%}.kh-statement .kh-empty{text-align:center;color:var(--kh-muted);padding:20px}
                    @media(max-width:650px){.kh-statement .kh-sheet{padding:14px}.kh-statement .kh-head{gap:10px}.kh-statement .kh-brand{font-size:16px}.kh-statement .kh-doc h2{font-size:13px}.kh-statement .kh-details{grid-template-columns:1fr}.kh-statement .kh-stats{grid-template-columns:1fr 1fr}.kh-statement .kh-stat strong{font-size:14px}}
                    @media print{ @page{size:A4 portrait;margin:12mm}body *{visibility:hidden!important} .kh-statement .kh-sheet,.kh-statement .kh-sheet *{visibility:visible!important}
                    .kh-statement .kh-sheet{position:absolute!important;left:0!important;top:0!important;width:100%!important;margin:0!important;padding:0!important;border:0!important;box-shadow:none!important}
                    .kh-statement .kh-toolbar{display:none!important}.kh-statement .kh-scroll{overflow:visible!important}.kh-statement table{font-size:9px!important;table-layout:auto!important}
                    .kh-statement th,.kh-statement td{padding:5px 4px!important}.kh-statement thead{display:table-header-group}.kh-statement tfoot{display:table-footer-group}
                    .kh-statement tr{break-inside:avoid;page-break-inside:avoid}.kh-statement .kh-details,.kh-statement .kh-stats,.kh-statement .kh-head{break-inside:avoid;page-break-inside:avoid}
                    .kh-statement .kh-actions{display:none!important}.kh-statement .kh-foot{break-inside:avoid}.kh-statement .kh-num{white-space:nowrap}.kh-statement .kh-amount-value{font-size:20px!important}
                    .kh-statement{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
                    .kh-statement .kh-actions{text-align:center;white-space:nowrap}
                    .kh-statement .kh-opening-row{background:#edf5f0;font-weight:700}
                    .kh-statement .kh-explain{font-size:11px;color:#475467;margin:0 0 12px;line-height:1.6}
                    .kh-statement .kh-warning{border:1px solid #f0c36d;background:#fff8e8;padding:11px 13px;margin:12px 0;color:#7a4d00}
                    .kh-statement .kh-opening-row{background:#e8f3ee!important;font-weight:700}
                    .kh-statement .kh-closing-row{background:#e8f3ee!important;font-weight:800}
                    .kh-statement .kh-actions{text-align:center;white-space:nowrap}
                    @media(max-width:650px){.kh-statement .kh-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
                    @media print{.kh-statement .kh-actions,.kh-statement .kh-warning{display:none!important}}
                    </style>

                    <div class="kh-statement" id="<?=$esc($printId);?>">
                        <div class="kh-toolbar">
                            <strong><i class="fa fa-file-text-o"></i> Creditor Account Statement</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-flat" onclick="window.print()"><i class="fa fa-print"></i> Print A4 Statement</button>
                        </div>
                        <?php if ($hasDifference || $storedBalanceDifferences): ?>
                        <div class="kh-warning">
                            <strong>Account reconciliation needed.</strong>
                            <?php if ($hasDifference): ?>
                                Statement closing balance: UGX <?=$money($running);?>;
                                creditor register balance: UGX <?=$money($registerBalance);?>.
                            <?php endif; ?>
                            <?php if ($storedBalanceDifferences): ?>
                                <?=$storedBalanceDifferences;?> historical ledger balance(s) differ from the sequential calculation.
                            <?php endif; ?>
                            Check the original entries before issuing this statement as a final account confirmation.
                        </div>
                        <?php endif; ?>
                        <div class="kh-sheet" id="printable-div">
                            <div class="kh-head">
                                <div><div class="kh-brand">KASHHUB</div><div class="kh-subtitle">Creditor account statement<br>Borrowings, repayments &amp; outstanding balance</div></div>
                                <div class="kh-doc"><h2>ACCOUNT STATEMENT</h2><p>Generated: <?=date('d M Y, h:i A');?></p><p>Period: <?=$esc($period);?></p><p>Creditor ID: <?=$id;?></p></div>
                            </div>
                            <div class="kh-details">
                                <div class="kh-panel">
                                    <div class="kh-panel-label">Statement for</div>
                                    <div class="kh-creditor-name"><?=$esc($creditor->name ?? '');?></div>
                                    <div class="kh-info"><strong>Contact:</strong> <?=$esc($creditor->contact ?: 'Not provided');?><br><strong>Address:</strong> <?=$esc($creditor->address ?: 'Not provided');?></div>
                                </div>
                                <div class="kh-panel kh-amount">
                                    <div class="kh-panel-label"><?=$esc($status);?></div>
                                    <div class="kh-amount-value" style="color:<?=$statusColor;?>">UGX <?=$money(abs($running));?></div>
                                    <div class="kh-subtitle"><?=count($rows);?> transactions</div>
                                </div>
                            </div>
                            <div class="kh-stats">
                                <div class="kh-stat"><small>Opening balance B/F</small><strong>UGX <?=$money($openingBalance);?></strong></div>
                                <div class="kh-stat"><small>Additional credit</small><strong>UGX <?=$money($totalCredit);?></strong></div>
                                <div class="kh-stat"><small>Repayments / debits</small><strong>UGX <?=$money($totalDebit);?></strong></div>
                                <div class="kh-stat"><small>Closing balance C/D</small><strong>UGX <?=$money($running);?></strong></div>
                            </div>
                            <h3 class="kh-section">TRANSACTION HISTORY</h3>
                            <p class="kh-explain">Amounts added increase what the business owes. Payments made reduce the amount owed. The balance column shows the amount outstanding after each transaction.</p>
                            <div class="kh-scroll">
                                <table class="kh-sortable-ledger">
                                    <thead><tr>
                                        <th>Date</th><th>Ref.</th><th>Transaction</th><th>Description</th><th>Mode</th>
                                        <th class="kh-num">Amount added (+)</th><th class="kh-num">Payment made (−)</th><th class="kh-num">Amount outstanding</th><th class="kh-actions">Action</th>
                                    </tr></thead>
                                    <tbody>
                                        <?php if ($hasOpening): ?>
                                        <tr class="kh-opening-row">
                                            <td><?=$firstDate ? date('d-m-Y',strtotime($firstDate)) : '—';?></td><td>—</td><td>Opening balance</td>
                                            <td colspan="2">Balance brought forward</td><td class="kh-num">—</td><td class="kh-num">—</td>
                                            <td class="kh-num"><?=$money($openingBalance);?></td><td class="kh-actions"></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php foreach ($rows as $r): ?>
                                        <tr data-date="<?=$esc($r['created_at'] ?? '');?>" data-ref="<?=(int)($r['transaction_id'] ?? 0);?>" data-balance="<?=(float)$r['statement_balance'];?>">
                                            <td class="kh-date"><?=!empty($r['created_at']) ? date('d-m-Y',strtotime($r['created_at'])) : '—';?></td>
                                            <td class="kh-nowrap"><?=$esc($r['transaction_id'] ?? '—');?></td>
                                            <td><?=$esc($r['statement_activity']);?></td>
                                            <td><?=$esc($r['statement_description']);?></td>
                                            <td><?=$esc($r['paymode_name'] ?? '');?></td>
                                            <td class="kh-num"><?=((float)($r['credit_amount'] ?? 0) != 0) ? $money($r['credit_amount']) : '—';?></td>
                                            <td class="kh-num"><?=((float)($r['debit_amount'] ?? 0) != 0) ? $money($r['debit_amount']) : '—';?></td>
                                            <td class="kh-num"><strong><?=$money($r['statement_balance']);?></strong></td>
                                            <td class="kh-actions kh-nowrap">
                                                <button type="button" class="btn btn-sm btn-flat btn-outline-info transfer-credit" data-id="<?=(int)$r['id'];?>" data-trans_id="<?=(int)$r['transaction_id'];?>" title="Transfer"><i class="fa fa-share"></i></button>
                                                <button type="button" class="btn btn-sm btn-flat btn-outline-danger delete-credit" data-id="<?=(int)$r['id'];?>" data-trans_id="<?=(int)$r['transaction_id'];?>" title="Delete"><i class="fa fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$hasOpening): ?><tr><td colspan="9" class="kh-empty">No ledger transactions recorded for this creditor.</td></tr><?php endif; ?>
                                    </tbody>
                                    <tfoot><tr class="kh-closing-row">
                                        <th colspan="5" style="text-align:right">CLOSING BALANCE C/D</th>
                                        <td class="kh-num"><?=$money($totalCredit);?></td>
                                        <td class="kh-num"><?=$money($totalDebit);?></td>
                                        <td class="kh-num"><?=$money($running);?></td><td class="kh-actions"></td>
                                    </tr></tfoot>
                                </table>
                            </div>
                            <div class="kh-foot">
                                <div><strong>How to read this statement:</strong> Opening balance + amounts added − payments made = closing balance. All figures in Uganda shillings (UGX).</div>
                                <div style="text-align:right">Generated through KashHub<br>Computer-generated account statement</div>
                            </div>
                        </div>
                    </div>
                    <?php
                break;
                case 'deletecreditor':
                    $id = request('id');

                    // delete customer
                    $stmt = "DELETE FROM cashbook_creditors WHERE id = ?";
                    $res = prepared_statements($stmt,'i',[$id]);
                    if($res)
                    {
                        echo "Success";
                    }
                    break;
                case 'creditor-edit':
                        $id = request('creditor_id');
                        $Creditor = creditorFind($id);
                        $bkid = $Creditor->book_id;
                        $bal = getCreditorBalance($id);

                        ?>
                            <form id='newCreditorForm' method="post">
                                <input type="hidden" name="book_id" value="<?=$bkid;?>">
                                <input type="hidden" name="form" value='newCreditorSave'>
                                <input type="hidden" name="creditor_id" value="<?=$id;?>">
                                <input type="hidden" name="action" value='SaveForm'>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="name">NAME:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="name" id="name" value="<?=$Creditor->name;?>" class="form-control">
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="category_id">CONTACT:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="contact" value="<?=$Creditor->contact;?>" id="contact" class="form-control">
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="address">ADDRESS:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="address" value="<?=$Creditor->address;?>" id="address" class="form-control">
                                    </div>
                                </div>
                                <div class="row mx-1">
                                    <div class="col-md-3 p-2">
                                        <label for="credit_balance">BALANCE B/F:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="credit_balance" id="credit_balance" value="<?=$bal;?>" class="form-control">
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

                case 'findItems':
                        $id = request('id');
                        $bkid = request('book_id');
                        $stmt = "SELECT ci.id,ci.name AS item,cci.customer_id,cci.item_id FROM cashbook_items ci
                                    LEFT JOIN (
                                        SELECT DISTINCT customer_id, item_id
                                        FROM cashbook_customer_items
                                        WHERE customer_id = ?
                                    ) cci ON cci.item_id = ci.id 
                                WHERE ci.book_id = ? ORDER BY ci.name ASC ";
                        $res = prepared_statements($stmt,'ii',[$id,$bkid]);
                    ?>
                        <form action="save/index.php" method="post">
                            <input type="hidden" name="customer_id" value="<?=$id;?>">
                            <input type="hidden" name="action" value="AttachItems">
                            <div class="p-2 col-lg-3 col-3">
                                <?php
                                    $attached_all =[];
                                    while($r = $res->fetch_assoc()):
                                        $attached = !empty($r['customer_id']);
                                        if ($attached && !in_array($r['item_id'],$attached_all)) {
                                            $attached_all[] = $r['item_id'];
                                            ?>
                                            <div class="row mx-1">
                                                <div class="col p-2">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="item_id[]" id="item<?=$r['id'];?>" value="<?=$r['id'];?>" class="form-check-input" checked>
                                                        <label class='form-check-label' for="item<?=$r['id'];?>"><?=$r['item'];?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        } else {
                                            ?>
                                                <div class="row mx-1">
                                                    <div class="col p-2">
                                                        <div class="form-check">
                                                            <input type="checkbox" name="item_id[]" id="item<?=$r['id'];?>" value="<?=$r['id'];?>" class="form-check-input">
                                                            <label class='form-check-label' for="item<?=$r['id'];?>"><?=$r['item'];?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                        }
                                    endwhile;
                                ?>
                            </div>
                            <div class="P-2">
                                <button type="submit" name='AttachItems' class="btn btn-flat btn-primary right">Save</button>
                            </div>
                        </form>
                    <?php
                    break;
                case 'AttachItems':
                        $customer_id = request('customer_id');
                        $items = $_POST['item_id'];

                        // loop data entry
                        $stmt = "INSERT IGNORE INTO cashbook_customer_items (customer_id, item_id) VALUES (?, ?)";
                        // check to detach
                        $query = "SELECT item_id FROM cashbook_customer_items WHERE customer_id = ?";
                        $res = prepared_statements($query,'i',[$customer_id]);
                        $available =[];

                        while($r = $res->fetch_assoc())
                        {
                            $available[] = $r['item_id'];

                            // detach existing if not selected
                            if(!in_array($r['item_id'],$items))
                            {
                                customerDettachItem($customer_id,$r['item_id']);
                            }
                        }

                        // attach all selected items
                        foreach($items as $item)
                        {
                            prepared_statements($stmt,'ii',[$customer_id,$item]);
                        }
                        $_SESSION['success'] = "Data saved";
                        redirect(back());
                    break;

                case 'fetchCreditors':
                    $trans = request('trans_id');
                    $transaction = transactionFind($trans);
                    $book_id = $transaction->book_id;

                    // fetch creditors
                    $stmt = "SELECT * FROM cashbook_creditors WHERE book_id = ?";
                    $res = prepared_statements($stmt,'i',[$book_id]);

                    echo "<option value=''>-- select --</option>";
                    while($r = $res->fetch_assoc())
                    {
                        ?>
                            <option value="<?=$r['id'];?>"><?=$r['name'];?></option>
                        <?php
                    }

                    break;

                case 'transferTrans':
                        $creditor_id = (int)request('creditor_id');
                        $trans_id    = (int)request('trans_id');
                        $id          = (int)request('id');

                        /*
                        ==========================================================
                        VALIDATE INPUT
                        ==========================================================
                        */

                        if ($creditor_id <= 0 || $trans_id <= 0 || $id <= 0) {

                            $_SESSION['error'] = "Invalid transfer information";
                            break;
                        }

                        /*
                        ==========================================================
                        FIND CREDITOR LEDGER RECORD
                        ==========================================================
                        */

                        $sql = "
                            SELECT *
                            FROM cashbook_creditor_ledger
                            WHERE id = ?
                            LIMIT 1
                        ";

                        $res = prepared_statements(
                            $sql,
                            'i',
                            [$id]
                        );

                        if (!$res || $res->num_rows === 0) {

                            $_SESSION['error'] =
                                "Creditor transaction could not be found";

                            break;
                        }

                        $row = myObject($res->fetch_assoc());

                        $old_creditor_id = (int)$row->creditor_id;
                        $book_id         = (int)$row->book_id;

                        /*
                        ==========================================================
                        DON'T TRANSFER TO THE SAME CREDITOR
                        ==========================================================
                        */

                        if ($old_creditor_id === $creditor_id) {

                            $_SESSION['error'] =
                                "The transaction already belongs to this creditor";

                            break;
                        }

                        /*
                        ==========================================================
                        VERIFY TRANSACTION
                        ==========================================================
                        */

                        $transaction = transactionFind($trans_id);

                        if (!$transaction) {

                            $_SESSION['error'] =
                                "Transaction could not be found";

                            break;
                        }

                        /*
                        ==========================================================
                        START DATABASE TRANSACTION
                        ==========================================================
                        */

                        mysqli_begin_transaction($server);

                        try {

                            /*
                            ======================================================
                            1. TRANSFER CREDITOR LEDGER RECORD
                            ======================================================
                            */

                            $sql = "
                                UPDATE cashbook_creditor_ledger
                                SET creditor_id = ?
                                WHERE id = ?
                                AND book_id = ?
                            ";

                            $res = prepared_statements(
                                $sql,
                                'iii',
                                [
                                    $creditor_id,
                                    $id,
                                    $book_id
                                ]
                            );

                            if ($res === false) {
                                throw new Exception(
                                    "Failed to transfer creditor ledger"
                                );
                            }

                            /*
                            ======================================================
                            2. UPDATE MAIN CASHBOOK TRANSACTION
                            ======================================================
                            */

                            $sql = "
                                UPDATE cashbook_transactions
                                SET creditor_id = ?
                                WHERE id = ?
                                AND book_id = ?
                            ";

                            $res = prepared_statements(
                                $sql,
                                'iii',
                                [
                                    $creditor_id,
                                    $trans_id,
                                    $book_id
                                ]
                            );

                            if ($res === false) {
                                throw new Exception(
                                    "Failed to update main transaction creditor"
                                );
                            }

                            /*
                            ======================================================
                            3. REBUILD OLD CREDITOR BALANCE
                            ======================================================
                            */

                            $oldBalance = rebuildCreditorBalance(
                                $old_creditor_id,
                                $book_id
                            );

                            /*
                            ======================================================
                            4. REBUILD NEW CREDITOR BALANCE
                            ======================================================
                            */

                            $newBalance = rebuildCreditorBalance(
                                $creditor_id,
                                $book_id
                            );

                            /*
                            ======================================================
                            5. COMMIT EVERYTHING
                            ======================================================
                            */

                            mysqli_commit($server);

                            $_SESSION['success'] =
                                "Transaction successfully transferred";

                        } catch (Throwable $e) {

                            /*
                            ======================================================
                            ROLLBACK
                            ======================================================
                            */

                            mysqli_rollback($server);

                            $_SESSION['error'] =
                                "Error transferring transaction: "
                                . $e->getMessage();
                        }

                    break;
            }
        }
    }else{
        redirect('../');
    }
?>