<?php
/** KashHub Period Performance — shared read-only reporting engine. */
require_once __DIR__ . '/../../assets/functions.php';
if (!isVerified() || !hasRole(['owner', 'partner', 'superadministrator'])) {
    http_response_code(403);
    exit('You do not have permission to view this report.');
}
$encryptedBookId = (string)request('bkid', '');
$bookId = (int)encryptor('decrypt', $encryptedBookId);
$book = $bookId > 0 ? bookFind($bookId) : null;
if (!$book || (int)$book->id !== $bookId) {
    http_response_code(404);
    exit('Book not found.');
}
setBook($book);
$from = (string)request('from', date('Y-m-01'));
$to = (string)request('to', date('Y-m-d'));
foreach ([$from, $to] as $date) {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        http_response_code(400); exit('Invalid reporting date.');
    }
}
if ($from > $to) { http_response_code(400); exit('Start date must precede end date.'); }
$endExclusive = (new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');
function ppRows(string $sql, string $types, array $params): array {
    return prepared_statements($sql, $types, $params)->fetch_all(MYSQLI_ASSOC);
}
function ppAmount($number): string { return number_format((float)$number, 2); }
function ppEsc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function ppData(int $bookId, string $from, string $end): array {
    $cash = ppRows("SELECT kind, SUM(amount) amount, COUNT(*) records FROM (
        SELECT 'Cash In' kind, amount FROM cashbook_cashins WHERE book_id=? AND created_at>=? AND created_at<?
        UNION ALL
        SELECT 'Cash Out' kind, amount FROM cashbook_cashouts WHERE book_id=? AND created_at>=? AND created_at<?
    ) x GROUP BY kind", 'ississ', [$bookId,$from,$end,$bookId,$from,$end]);
    $category = ppRows("SELECT COALESCE(c.name,'Uncategorised') name,
        SUM(COALESCE(t.credit_amount,0)) cash_in, SUM(COALESCE(t.debit_amount,0)) cash_out,
        COUNT(*) records FROM cashbook_transactions t
        LEFT JOIN cashbook_categories c ON c.id=t.category_id AND c.book_id=t.book_id
        WHERE t.book_id=? AND t.created_at>=? AND t.created_at<?
        GROUP BY t.category_id,c.name ORDER BY (SUM(COALESCE(t.credit_amount,0)) + SUM(COALESCE(t.debit_amount,0))) DESC", 'iss', [$bookId,$from,$end]);
    $items = ppRows("SELECT COALESCE(i.name,'Unassigned item') name, COALESCE(i.units,'') units,
        SUM(COALESCE(t.credit_amount,0)) cash_in, SUM(COALESCE(t.debit_amount,0)) cash_out,
        SUM(CASE WHEN t.credit_amount>0 THEN COALESCE(t.quantity,0) ELSE 0 END) qty_sold,
        SUM(CASE WHEN t.debit_amount>0 THEN COALESCE(t.quantity,0) ELSE 0 END) qty_purchased
        FROM cashbook_transactions t LEFT JOIN cashbook_items i ON i.id=t.item_id AND i.book_id=t.book_id
        WHERE t.book_id=? AND t.created_at>=? AND t.created_at<?
        GROUP BY t.item_id,i.name,i.units ORDER BY (SUM(COALESCE(t.credit_amount,0)) + SUM(COALESCE(t.debit_amount,0))) DESC", 'iss', [$bookId,$from,$end]);
    $stock = ppRows("SELECT i.name, i.units,
        SUM(COALESCE(s.quantity_in,0)) qty_in, SUM(COALESCE(s.quantity_out,0)) qty_out,
        SUM(CASE WHEN s.transaction_type='stock_in' THEN COALESCE(s.total_cost,0) ELSE 0 END) purchase_cost,
        SUM(CASE WHEN s.transaction_type='stock_in' AND s.total_cost IS NULL THEN 1 ELSE 0 END) missing_cost_rows
        FROM cashbook_stocks s JOIN cashbook_items i ON i.id=s.item_id AND i.book_id=s.book_id
        WHERE s.book_id=? AND s.created_at>=? AND s.created_at<?
        GROUP BY s.item_id,i.name,i.units ORDER BY i.name", 'iss', [$bookId,$from,$end]);
    $customers = ppRows("SELECT c.id,c.name,
        COALESCE(SUM(CASE WHEN l.created_at<? THEN COALESCE(l.debit_amount,0)-COALESCE(l.credit_amount,0) ELSE 0 END),0) opening,
        COALESCE(SUM(CASE WHEN l.created_at>=? AND l.created_at<? THEN COALESCE(l.debit_amount,0) ELSE 0 END),0) charges,
        COALESCE(SUM(CASE WHEN l.created_at>=? AND l.created_at<? THEN COALESCE(l.credit_amount,0) ELSE 0 END),0) credits,
        COALESCE(SUM(CASE WHEN l.created_at<? THEN COALESCE(l.debit_amount,0)-COALESCE(l.credit_amount,0) ELSE 0 END),0) closing
        FROM cashbook_customers c LEFT JOIN cashbook_customer_ledger l ON l.customer_id=c.id AND l.book_id=c.book_id
        WHERE c.book_id=? GROUP BY c.id,c.name ORDER BY closing DESC", 'ssssssi', [$from,$from,$end,$from,$end,$end,$bookId]);
    // Closing must include all ledger movements up to period end, not just this period.
    // Query above intentionally uses period-end cutoff in the closing expression below.
    return compact('cash','category','items','stock','customers');
}
