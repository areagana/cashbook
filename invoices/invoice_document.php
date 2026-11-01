<?php
/** Shared, read-only invoice data and the approved KashHub document layout. */
require_once __DIR__ . '/../assets/document_identity.php';

function khInvoiceCanAccessBook(int $bookId): bool
{
    if ($bookId < 1 || !isVerified()) return false;
    if (function_exists('khCanAccessBook') && !khCanAccessBook($bookId)) return false;
    $business = function_exists('businessFind') ? businessFind(auth()) : null;
    if (!$business && function_exists('businessFindId')) {
        $business = businessFindId(auth()->business_id ?? 0);
    }
    if (!$business) return false;
    $book = prepared_statements('SELECT id FROM cashbook_books WHERE id=? AND business_id=? LIMIT 1', 'ii', [$bookId, (int)$business->id]);
    if (!$book || !$book->num_rows) return false;
    if (hasRole(['owner', 'partner', 'superadministrator'])) return true;
    $member = prepared_statements('SELECT 1 FROM cashbook_book_users WHERE book_id=? AND user_id=? LIMIT 1', 'ii', [$bookId, (int)auth()->id]);
    return (bool)($member && $member->num_rows);
}

function khInvoiceLoad(int $invoiceId, int $bookId): ?array
{
    $res = prepared_statements('SELECT i.*, c.name AS customer, b.name AS book_name FROM cashbook_invoices i INNER JOIN cashbook_books b ON b.id=i.book_id LEFT JOIN cashbook_customers c ON c.id=i.customer_id AND c.book_id=i.book_id WHERE i.id=? AND i.book_id=? LIMIT 1', 'ii', [$invoiceId, $bookId]);
    $invoice = $res ? $res->fetch_assoc() : null;
    if (!$invoice) return null;
    $items = [];
    $q = prepared_statements('SELECT ii.quantity,ii.unit_price,ii.total,it.name AS item_name,it.units FROM cashbook_invoice_items ii LEFT JOIN cashbook_items it ON it.id=ii.item_id AND it.book_id=? WHERE ii.invoice_id=? ORDER BY ii.id', 'ii', [$bookId,$invoiceId]);
    if ($q) while ($row=$q->fetch_assoc()) $items[]=$row;
    $returns=[];
    $q=prepared_statements('SELECT ri.qty_returned,ri.unit_price,ri.total,it.name AS item_name,it.units FROM cashbook_invoice_return_items ri LEFT JOIN cashbook_items it ON it.id=ri.item_id AND it.book_id=? WHERE ri.invoice_id=? ORDER BY ri.id', 'ii', [$bookId,$invoiceId]);
    if ($q) while ($row=$q->fetch_assoc()) $returns[]=$row;
    $payments=[];
    $q=prepared_statements("SELECT l.credit_amount,l.details,t.created_at AS payment_date FROM cashbook_customer_ledger l LEFT JOIN cashbook_transactions t ON t.id=l.transaction_id AND t.book_id=l.book_id WHERE l.book_id=? AND l.customer_id=? AND l.invoice_id=? AND l.type IN ('invoice_payment','payment') AND l.credit_amount>0 ORDER BY t.created_at,l.id", 'iii', [$bookId,(int)$invoice['customer_id'],$invoiceId]);
    if ($q) while ($row=$q->fetch_assoc()) $payments[]=$row;
    return compact('invoice','items','returns','payments');
}

function khInvoiceHtml(array $data, string $type='invoice', bool $forPdf=true): string
{
    $invoice=$data['invoice']; $items=$data['items']; $returns=$data['returns']; $payments=$data['payments'];
    $identity=khDocumentIdentity((int)$invoice['book_id']);
    $logo=khDocumentLogoDataUri($identity);
    $companyLines=khDocumentCompanyLines($identity);
    $h=static fn($v)=>htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');
    $money=static fn($v)=>number_format((float)$v,2);
    $total=(float)($invoice['total']??0); $returned=(float)array_sum(array_column($returns,'total'));
    $paid=(float)($invoice['paid_amount']??0); $balance=(float)($invoice['balance']??0);
    $status=$balance>.005?'AMOUNT DUE':($balance<-.005?'CUSTOMER CREDIT':'SETTLED');
    $accent=$balance>.005?'#b42318':'#067647';
    ob_start(); ?>
    <style>
    .kh-doc {font-family:DejaVu Sans,Arial,sans-serif;color:#182230;font-size:10px;line-height:1.45;background:#fff;max-width:100%;}
    .kh-doc *{box-sizing:border-box}.kh-doc .header{width:100%;border-bottom:3px solid #176b51;padding-bottom:12px;margin-bottom:14px;border-collapse:collapse}
    .kh-doc .header td{border:0;vertical-align:top}.kh-doc .brand{font-size:19px;font-weight:bold}.kh-doc .logo{max-width:58px;max-height:52px;margin-bottom:5px}
    .kh-doc .muted{color:#667085;font-size:9px;line-height:1.6}.kh-doc .doc{text-align:right}.kh-doc .doc h2{font-size:15px;margin:0 0 8px;letter-spacing:1px}
    .kh-doc .info{width:100%;border-spacing:7px 0;border-collapse:separate;margin-bottom:12px}.kh-doc .info td{border:1px solid #d0d5dd;padding:12px;vertical-align:top;width:50%}
    .kh-doc .label{font-size:8px;color:#667085;font-weight:bold;letter-spacing:.5px}.kh-doc .customer{font-size:15px;font-weight:bold;margin:6px 0}
    .kh-doc .amount{text-align:right;background:#f8fafc;border-left:4px solid #176b51!important}.kh-doc .amount strong{font-size:19px;color:<?=$accent;?>}
    .kh-doc .stats{width:100%;border-spacing:7px 0;border-collapse:separate;margin-bottom:16px}.kh-doc .stats td{border:1px solid #d0d5dd;padding:9px;width:33.33%}
    .kh-doc h3{font-size:12px;margin:14px 0 9px}.kh-doc .ledger{width:100%;border-collapse:collapse;table-layout:fixed;font-size:9px}
    .kh-doc .ledger th,.kh-doc .ledger td{border:1px solid #cbd5e1;padding:7px 5px;vertical-align:top;overflow-wrap:break-word}
    .kh-doc .ledger th{background:#edf2f5;color:#344054;font-size:8px;text-transform:uppercase}.kh-doc .ledger tr:nth-child(even){background:#fafbfc}
    .kh-doc .ledger tr{page-break-inside:avoid}.kh-doc .num{text-align:right}.kh-doc .summary{width:52%;margin:16px 0 0 auto;border-collapse:collapse}
    .kh-doc .summary td{padding:7px;border-bottom:1px solid #d0d5dd}.kh-doc .summary tr:last-child{background:#e8f3ee;border-top:2px solid #176b51;font-weight:bold}
    .kh-doc .footer{border-top:1px solid #d0d5dd;padding-top:10px;margin-top:18px;width:100%;color:#667085;font-size:8px}
    </style>
    <div class="kh-doc">
      <table class="header"><tr><td style="width:55%">
        <?php if($logo): ?><img class="logo" src="<?=$h($logo);?>" alt="Logo"><?php endif; ?>
        <div class="brand"><?=$h($identity['company_name']);?></div>
        <?php foreach($companyLines as $line): ?><div class="muted"><?=$h($line);?></div><?php endforeach; ?>
        <div class="muted"><b>Book:</b> <?=$h($identity['book_name']);?></div>
        <div class="muted">Customer invoice · Business sales record</div>
      </td><td class="doc"><h2>INVOICE</h2><div class="muted"><b>No:</b> <?=$h($invoice['invoice_no']);?></div><div class="muted"><b>Date:</b> <?=$h($invoice['invoice_date']);?></div><div class="muted"><b>Generated:</b> <?=date('d M Y, h:i A');?></div></td></tr></table>
      <table class="info"><tr><td><div class="label">BILL TO</div><div class="customer"><?=$h($invoice['customer'] ?: 'Customer');?></div><div class="muted">Invoice # <?=$h($invoice['invoice_no']);?></div></td>
      <td class="amount"><div class="label"><?=$h($status);?></div><p><strong>UGX <?=$money(abs($balance));?></strong></p><div class="muted">Invoice date: <?=$h($invoice['invoice_date']);?></div></td></tr></table>
      <table class="stats"><tr><td><div class="label">INVOICE VALUE</div><br><strong>UGX <?=$money($total);?></strong></td><td><div class="label">RETURNS</div><br><strong>UGX <?=$money($returned);?></strong></td><td><div class="label">PAYMENTS</div><br><strong>UGX <?=$money($paid);?></strong></td></tr></table>
      <h3>INVOICE ITEMS</h3>
      <table class="ledger"><thead><tr><th style="width:7%">#</th><th>Item description</th><th style="width:16%">Qty</th><th style="width:20%">Rate (UGX)</th><th style="width:22%">Amount (UGX)</th></tr></thead><tbody>
      <?php foreach($items as $i=>$item): ?><tr><td><?=$i+1;?></td><td><?=$h($item['item_name'] ?: 'Item');?></td><td><?=$h($item['quantity']);?> <?=$h($item['units']);?></td><td class="num"><?=$money($item['unit_price']);?></td><td class="num"><?=$money($item['total']);?></td></tr><?php endforeach; ?>
      <?php if(!$items): ?><tr><td colspan="5">No invoice items recorded.</td></tr><?php endif; ?></tbody></table>
      <?php if($type==='returns'): ?><h3>RETURNED ITEMS</h3><table class="ledger"><thead><tr><th>Item</th><th>Quantity returned</th><th>Rate (UGX)</th><th>Amount (UGX)</th></tr></thead><tbody>
      <?php foreach($returns as $r): ?><tr><td><?=$h($r['item_name']);?></td><td><?=$h($r['qty_returned']);?> <?=$h($r['units']);?></td><td class="num"><?=$money($r['unit_price']);?></td><td class="num"><?=$money($r['total']);?></td></tr><?php endforeach; ?>
      <?php if(!$returns): ?><tr><td colspan="4">No returns recorded.</td></tr><?php endif; ?></tbody></table><?php endif; ?>
      <?php if($type==='payments'): ?><h3>INVOICE PAYMENT RECORDS</h3><table class="ledger"><thead><tr><th>Date</th><th>Reference / Details</th><th>Amount (UGX)</th></tr></thead><tbody>
      <?php foreach($payments as $r): ?><tr><td><?=$h($r['payment_date']);?></td><td><?=$h($r['details']);?></td><td class="num"><?=$money($r['credit_amount']);?></td></tr><?php endforeach; ?>
      <?php if(!$payments): ?><tr><td colspan="3">No individually linked payment entries recorded for this invoice.</td></tr><?php endif; ?></tbody></table><?php endif; ?>
      <table class="summary"><tr><td>Invoice total</td><td class="num">UGX <?=$money($total);?></td></tr><tr><td>Less: Returns</td><td class="num">- <?=$money($returned);?></td></tr><tr><td>Net invoice value</td><td class="num"><?=$money($total-$returned);?></td></tr><tr><td>Less: Payments</td><td class="num">- <?=$money($paid);?></td></tr><tr><td>OUTSTANDING BALANCE</td><td class="num">UGX <?=$money($balance);?></td></tr></table>
      <table class="footer"><tr><td>Thank you for your business. Amounts in Uganda shillings (UGX).</td><td style="text-align:right">Generated by KashHub · Computer-generated invoice</td></tr></table>
    </div>
    <?php return ob_get_clean();
}
