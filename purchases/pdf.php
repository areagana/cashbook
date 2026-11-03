<?php
    require_once __DIR__ . '/../assets/functions.php';
    /** PDF renderer shared by the period report and item statements. */
    if (!function_exists('khPurchaseStreamPdf')) {
        function khPurchaseStreamPdf(string $html, string $filename): void {
            foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../vendor/autoload.php'] as $autoload) {
                if (is_file($autoload)) require_once $autoload;
                if (class_exists('\Dompdf\Dompdf')) break;
            }
            if (!class_exists('\Dompdf\Dompdf')) {
                http_response_code(500);
                exit('Dompdf is not installed. Run composer require dompdf/dompdf');
            }
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isPhpEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $pdf = new \Dompdf\Dompdf($options);
            $pdf->loadHtml('<!doctype html><html><head><meta charset="utf-8"><style>@page{size:A4 landscape;margin:12mm}body{margin:0}</style></head><body>'.$html.'</body></html>', 'UTF-8');
            $pdf->setPaper('A4','landscape');
            $pdf->render();
            $pdf->stream($filename, ['Attachment' => 1]);
            exit;
        }
    }
    if (!isVerified()) { http_response_code(403); exit('Access denied.'); }
    {
        /* Period report: read-only AJAX HTML or downloadable PDF. */
        if (($_GET['purchase_report'] ?? '') === '1') {
            $reportBookId = (int)encryptor('decrypt', (string)($_GET['bsid'] ?? ''));
            $reportBook = $reportBookId ? bookFind($reportBookId) : null;
            if (!$reportBook || (int)$reportBook->id !== $reportBookId ||
                (function_exists('khCanAccessBook') && !khCanAccessBook($reportBookId))) {
                http_response_code(403); exit('Book access denied.');
            }
            $rf = trim((string)($_GET['from'] ?? ''));
            $rt = trim((string)($_GET['to'] ?? ''));
            foreach ([$rf, $rt] as $d) {
                $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $d);
                if (!$parsed || $parsed->format('Y-m-d') !== $d) {
                    http_response_code(400); exit('Select valid From and To dates.');
                }
            }
            if ($rf > $rt) { http_response_code(400); exit('From date cannot exceed To date.'); }
            $rid = max(0, (int)($_GET['item_id'] ?? 0));
            $args = [$reportBookId, $rf.' 00:00:00', $rt];
            $types = 'iss';
            $extra = '';
            if ($rid) {
                $check = prepared_statements('SELECT id FROM cashbook_items WHERE id=? AND book_id=? LIMIT 1','ii',[$rid,$reportBookId]);
                if (!$check || !$check->num_rows) { http_response_code(404); exit('Item not found in book.'); }
                $extra = ' AND cp.item_id=?'; $types .= 'i'; $args[] = $rid;
            }
            $columnCheck = $server->query("SHOW COLUMNS FROM cashbook_purchases LIKE 'creditor_id'");
            $hasCreditor = $columnCheck && $columnCheck->num_rows > 0;
            $fundingExpr = $hasCreditor ? "CASE WHEN COALESCE(cp.creditor_id,0)>0 THEN 'CREDIT' ELSE 'CASH' END" : "'CASH'";
            $q = prepared_statements("SELECT cp.id,cp.transaction_id,cp.created_at,cp.quantity,cp.unit_price,cp.total,ci.name,ci.units,{$fundingExpr} AS funding
                FROM cashbook_purchases cp INNER JOIN cashbook_items ci ON ci.id=cp.item_id AND ci.book_id=cp.book_id
                WHERE cp.book_id=? AND cp.created_at>=? AND cp.created_at<DATE_ADD(?,INTERVAL 1 DAY) {$extra}
                ORDER BY cp.created_at ASC,cp.id ASC", $types, $args);
            $rows=[]; $total=0; $qty=0; $cash=0; $credit=0;
            if ($q) while ($r=$q->fetch_assoc()) {
                $rows[]=$r; $total+=(float)$r['total']; $qty+=(float)$r['quantity'];
                if ($r['funding']==='CREDIT') $credit+=(float)$r['total']; else $cash+=(float)$r['total'];
            }
            $identityFile=__DIR__.'/../assets/document_identity.php';
            if (is_file($identityFile)) require_once $identityFile;
            $identity=function_exists('khDocumentIdentity') ? khDocumentIdentity($reportBookId) : [];
            $company=$identity['company_name'] ?? ($reportBook->name ?? 'KashHub');
            $bookName=$identity['book_name'] ?? ($reportBook->name ?? '');
            $logo=function_exists('khDocumentLogoDataUri') ? khDocumentLogoDataUri($identity) : '';
            $companyLines=function_exists('khDocumentCompanyLines') ? khDocumentCompanyLines($identity) : [];
            $h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
            ob_start(); ?>
            <style>
            .kh-purchase-doc{font-family:DejaVu Sans,Arial,sans-serif;color:#182230;font-size:10px;background:#fff;line-height:1.45}
            .kh-purchase-doc .kh-head{width:100%;border-bottom:3px solid #176b51;padding-bottom:12px;margin-bottom:14px}
            .kh-purchase-doc .kh-head td{vertical-align:top;border:0}
            .kh-purchase-doc .kh-brand{font-size:19px;font-weight:bold}
            .kh-purchase-doc .kh-logo{max-width:58px;max-height:52px}
            .kh-purchase-doc .kh-muted{font-size:9px;color:#667085}
            .kh-purchase-doc .kh-ledger{width:100%;border-collapse:collapse;font-size:9px}
            .kh-purchase-doc .kh-ledger th,.kh-purchase-doc .kh-ledger td{border:1px solid #cbd5e1;padding:7px 5px}
            .kh-purchase-doc .kh-ledger th{background:#edf2f5;color:#344054;text-transform:uppercase}
            .kh-purchase-doc .kh-ledger tr:nth-child(even){background:#fafbfc}
            .kh-purchase-doc .kh-num{text-align:right;white-space:nowrap}
            .kh-purchase-doc .kh-totals{width:60%;margin:15px 0 0 auto;border-collapse:collapse}
            .kh-purchase-doc .kh-totals td{padding:7px;border-bottom:1px solid #d0d5dd}
            .kh-purchase-doc .kh-totals tr:last-child{background:#e8f3ee;border-top:2px solid #176b51;font-weight:bold}
            .kh-purchase-doc .kh-foot{border-top:1px solid #d0d5dd;padding-top:10px;margin-top:16px;color:#667085;font-size:9px}
            </style>
            <div class="purchase-statement-print kh-purchase-doc">
              <table class="kh-head"><tr><td style="width:60%">
                <?php if($logo):?><img class="kh-logo" src="<?=$h($logo)?>" alt="Logo"><?php endif;?>
                <div class="kh-brand"><?=$h($company)?></div>
                <?php foreach($companyLines as $line):?><div class="kh-muted"><?=$h($line)?></div><?php endforeach;?>
                <div class="kh-muted"><b>Book:</b> <?=$h($bookName)?></div>
              </td><td style="text-align:right"><h3 style="margin:0">PURCHASE PERIOD REPORT</h3>
                <div class="kh-muted"><?=$h($rf)?> to <?=$h($rt)?></div>
                <div class="kh-muted">Generated: <?=date('d M Y, h:i A')?></div>
              </td></tr></table>
              <p><b>Period:</b> <?=$h($rf)?> to <?=$h($rt)?> &nbsp; | &nbsp; <b>Entries:</b> <?=count($rows)?></p>
              <table class="kh-ledger"><thead><tr><th>#</th><th>Date</th><th>Reference</th><th>Item</th><th>Type</th><th class="kh-num">Qty</th><th>Unit</th><th class="kh-num">Rate (UGX)</th><th class="kh-num">Amount (UGX)</th></tr></thead><tbody>
              <?php foreach($rows as $i=>$r):?><tr>
                <td><?=$i+1?></td><td><?=$h(date('d M Y',strtotime($r['created_at'])))?></td>
                <td><?=$h($r['transaction_id'] ?: 'P-'.$r['id'])?></td><td><?=$h($r['name'])?></td><td><?=$h($r['funding'])?></td>
                <td class="kh-num"><?=number_format((float)$r['quantity'],2)?></td><td><?=$h($r['units'])?></td>
                <td class="kh-num"><?=number_format((float)$r['unit_price'],2)?></td>
                <td class="kh-num"><?=number_format((float)$r['total'],2)?></td></tr><?php endforeach;?>
              <?php if(!$rows):?><tr><td colspan="9" style="text-align:center;padding:20px">No purchases found in this period.</td></tr><?php endif;?>
              </tbody></table>
              <table class="kh-totals">
                <tr><td>Purchase entries</td><td class="kh-num"><?=count($rows)?></td></tr>
                <tr><td>Quantity purchased</td><td class="kh-num"><?=number_format($qty,2)?></td></tr>
                <tr><td>Cash purchases</td><td class="kh-num">UGX <?=number_format($cash,2)?></td></tr>
                <tr><td>Credit purchases</td><td class="kh-num">UGX <?=number_format($credit,2)?></td></tr>
                <tr><td>TOTAL PURCHASES</td><td class="kh-num">UGX <?=number_format($total,2)?></td></tr>
              </table>
              <div class="kh-foot">Amounts in Uganda shillings (UGX). Generated by KashHub · Purchase period report</div>
            </div>
            <?php
            $html=ob_get_clean();
            if (($_GET['format']??'')==='pdf') khPurchaseStreamPdf($html,'Purchases_'.$rf.'_to_'.$rt.'.pdf');
            header('Content-Type: text/html; charset=utf-8'); echo $html; exit;
        }

        /* Item purchase statement: HTML preview or PDF download. */
        if (isset($_GET['purchase_statement']) && $_GET['purchase_statement'] === '1') {
            header('Content-Type: text/html; charset=utf-8');
            $statementBookToken = (string)($_GET['bsid'] ?? '');
            $statementBookId = (int)encryptor('decrypt', $statementBookToken);
            $statementBook = $statementBookId > 0 ? bookFind($statementBookId) : null;
            $statementItemId = max(0, (int)($_GET['item_id'] ?? 0));
            if (!$statementBook || (int)$statementBook->id !== $statementBookId || !$statementItemId) {
                http_response_code(400);
                exit('<div class="alert alert-danger">Invalid book or item selection.</div>');
            }
            if (function_exists('khCanAccessBook') && !khCanAccessBook($statementBookId)) {
                http_response_code(403);
                exit('<div class="alert alert-danger">Book access denied.</div>');
            }
            $itemResult = prepared_statements(
                'SELECT id, name, units FROM cashbook_items WHERE id = ? AND book_id = ? LIMIT 1',
                'ii', [$statementItemId, $statementBookId]
            );
            $statementItem = $itemResult ? $itemResult->fetch_assoc() : null;
            if (!$statementItem) {
                http_response_code(404);
                exit('<div class="alert alert-warning">This item does not belong to the selected book.</div>');
            }
            $sf = trim((string)($_GET['from'] ?? ''));
            $st = trim((string)($_GET['to'] ?? ''));
            foreach ([$sf, $st] as $dateValue) {
                if ($dateValue !== '') {
                    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
                    if (!$parsed || $parsed->format('Y-m-d') !== $dateValue) {
                        http_response_code(400);
                        exit('<div class="alert alert-danger">Invalid date range.</div>');
                    }
                }
            }
            if ($sf !== '' && $st !== '' && $sf > $st) {
                http_response_code(400);
                exit('<div class="alert alert-danger">The start date cannot be after the end date.</div>');
            }
            $statementWhere = 'cp.book_id = ? AND cp.item_id = ?';
            $statementTypes = 'ii';
            $statementArgs = [$statementBookId, $statementItemId];
            if ($sf !== '') {
                $statementWhere .= ' AND cp.created_at >= ?';
                $statementTypes .= 's';
                $statementArgs[] = $sf . ' 00:00:00';
            }
            if ($st !== '') {
                $statementWhere .= ' AND cp.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
                $statementTypes .= 's';
                $statementArgs[] = $st;
            }
            $statementRows = prepared_statements(
                "SELECT cp.id, cp.created_at, cp.transaction_id, cp.quantity, cp.unit_price, cp.total,
                        cp.creditor_id, cp.type
                 FROM cashbook_purchases cp WHERE {$statementWhere}
                 ORDER BY cp.created_at ASC, cp.id ASC",
                $statementTypes, $statementArgs
            );
            $sQty = 0; $sCash = 0; $sCredit = 0; $sTotal = 0; $sCount = 0;
            $escape = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
            // Reuse the approved document identity and typography used by invoice_document.php.
            $identityFile = __DIR__ . '/../assets/document_identity.php';
            if (is_file($identityFile)) require_once $identityFile;
            $identity = function_exists('khDocumentIdentity') ? khDocumentIdentity($statementBookId) : [];
            $company = $identity['company_name'] ?? ($statementBook->name ?? 'KashHub');
            $bookName = $identity['book_name'] ?? ($statementBook->name ?? '');
            $logo = function_exists('khDocumentLogoDataUri') ? khDocumentLogoDataUri($identity) : '';
            $companyLines = function_exists('khDocumentCompanyLines') ? khDocumentCompanyLines($identity) : [];
            $entries = [];
            if ($statementRows) while ($r = $statementRows->fetch_assoc()) {
                $amount = (float)$r['total']; $qty = (float)$r['quantity'];
                $credit = (int)($r['creditor_id'] ?? 0) > 0;
                $sQty += $qty; $sTotal += $amount; $sCount++;
                if ($credit) $sCredit += $amount; else $sCash += $amount;
                $r['running_total'] = $sTotal;
                $r['funding'] = $credit ? 'Credit' : 'Cash';
                $entries[] = $r;
            }
            $asPdf = (($_GET['format'] ?? '') === 'pdf');
            if ($asPdf) ob_start();
            ?>
            <style>
                .kh-purchase-doc {font-family:DejaVu Sans,Arial,sans-serif;color:#182230;font-size:11px;line-height:1.5;background:#fff}
                .kh-purchase-doc *{box-sizing:border-box}
                .kh-purchase-doc .kh-head{width:100%;border-bottom:3px solid #176b51;margin-bottom:14px;padding-bottom:12px}
                .kh-purchase-doc .kh-head td{vertical-align:top;border:0}
                .kh-purchase-doc .kh-brand{font-size:19px;font-weight:bold}
                .kh-purchase-doc .kh-logo{max-width:58px;max-height:52px}
                .kh-purchase-doc .kh-muted{font-size:10px;color:#667085}
                .kh-purchase-doc .kh-info{width:100%;margin:10px 0 15px;border-collapse:separate;border-spacing:6px}
                .kh-purchase-doc .kh-info td{width:50%;border:1px solid #d0d5dd;padding:10px}
                .kh-purchase-doc .kh-label{font-size:9px;color:#667085;font-weight:bold;text-transform:uppercase}
                .kh-purchase-doc .kh-ledger{width:100%;border-collapse:collapse;font-size:10px}
                .kh-purchase-doc .kh-ledger th,.kh-purchase-doc .kh-ledger td{border:1px solid #cbd5e1;padding:7px 5px}
                .kh-purchase-doc .kh-ledger th{background:#edf2f5;color:#344054;font-size:9px;text-transform:uppercase}
                .kh-purchase-doc .kh-ledger tr:nth-child(even){background:#fafbfc}
                .kh-purchase-doc .kh-num{text-align:right;white-space:nowrap}
                .kh-purchase-doc .kh-totals{width:65%;margin:16px 0 0 auto;border-collapse:collapse}
                .kh-purchase-doc .kh-totals td{padding:7px;border-bottom:1px solid #d0d5dd}
                .kh-purchase-doc .kh-totals tr:last-child{background:#e8f3ee;font-weight:bold;border-top:2px solid #176b51}
                .kh-purchase-doc .kh-foot{margin-top:18px;border-top:1px solid #d0d5dd;padding-top:10px;color:#667085;font-size:9px}
                @media print{.kh-purchase-doc .kh-ledger tr{page-break-inside:avoid}}
            </style>
            <div class="purchase-statement-print kh-purchase-doc">
                <table class="kh-head"><tr>
                    <td style="width:58%">
                        <?php if ($logo): ?><img class="kh-logo" src="<?=$escape($logo)?>" alt="Logo"><?php endif; ?>
                        <div class="kh-brand"><?=$escape($company)?></div>
                        <?php foreach ($companyLines as $line): ?><div class="kh-muted"><?=$escape($line)?></div><?php endforeach; ?>
                        <div class="kh-muted"><b>Book:</b> <?=$escape($bookName)?></div>
                    </td>
                    <td style="text-align:right"><h3 style="margin:0">ITEM PURCHASE STATEMENT</h3>
                        <div class="kh-muted">Generated: <?=date('d M Y, h:i A')?></div>
                    </td>
                </tr></table>
                <table class="kh-info"><tr>
                    <td><div class="kh-label">Item</div><strong><?=$escape($statementItem['name'])?></strong>
                        <div class="kh-muted">Unit: <?=$escape($statementItem['units'])?></div></td>
                    <td><div class="kh-label">Reporting period</div><strong><?=$escape($sf ?: 'Beginning')?> to <?=$escape($st ?: 'Latest')?></strong>
                        <div class="kh-muted"><?=$sCount?> purchase entries</div></td>
                </tr></table>
                <div style="overflow-x:auto"><table class="kh-ledger">
                    <thead><tr><th>Date</th><th>Reference</th><th>Type</th><th class="kh-num">Quantity</th><th class="kh-num">Rate (UGX)</th><th class="kh-num">Amount (UGX)</th><th class="kh-num">Running total (UGX)</th></tr></thead>
                    <tbody>
                    <?php foreach ($entries as $r): ?>
                        <tr><td><?=$escape(date('d M Y',strtotime($r['created_at'])))?></td>
                            <td><?=$escape($r['transaction_id'] ?: 'P-'.$r['id'])?></td>
                            <td><?=$escape($r['funding'])?></td>
                            <td class="kh-num"><?=number_format((float)$r['quantity'],2)?></td>
                            <td class="kh-num"><?=number_format((float)$r['unit_price'],2)?></td>
                            <td class="kh-num"><?=number_format((float)$r['total'],2)?></td>
                            <td class="kh-num"><?=number_format((float)$r['running_total'],2)?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$entries): ?><tr><td colspan="7" style="text-align:center;padding:20px">No purchases in the selected period.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
                <table class="kh-totals">
                    <tr><td>Quantity purchased</td><td class="kh-num"><?=number_format($sQty,2)?></td></tr>
                    <tr><td>Cash purchases</td><td class="kh-num">UGX <?=number_format($sCash,2)?></td></tr>
                    <tr><td>Credit purchases</td><td class="kh-num">UGX <?=number_format($sCredit,2)?></td></tr>
                    <tr><td>TOTAL PURCHASES</td><td class="kh-num">UGX <?=number_format($sTotal,2)?></td></tr>
                </table>
                <div class="kh-foot">Amounts in Uganda shillings (UGX). Generated by KashHub · Item purchase statement</div>
            </div>
            <?php
            if ($asPdf) {
                $documentHtml = ob_get_clean();
                khPurchaseStreamPdf($documentHtml, 'Item_Purchase_Statement_' . $statementItemId . '.pdf');
            }
            exit;
        }

        http_response_code(400);
        exit('Invalid purchase report request.');
    }
