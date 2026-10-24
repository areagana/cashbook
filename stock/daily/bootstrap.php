<?php
/** KashHub daily stock tracker — shared book-scoped helpers. */
require_once __DIR__ . '/../../assets/functions.php';
if (!isVerified()) { http_response_code(401); exit('Authentication required.'); }
function dsEsc($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function dsQuery(string $sql, string $types='', array $params=[]): mysqli_stmt {
    global $server;
    $stmt=$server->prepare($sql);
    if (!$stmt) throw new RuntimeException('Database query preparation failed: '.$server->error);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) throw new RuntimeException('Database operation failed: '.$stmt->error);
    return $stmt;
}
function dsRows(string $sql,string $types='',array $params=[]): array {
    return dsQuery($sql,$types,$params)->get_result()->fetch_all(MYSQLI_ASSOC);
}
function dsContext(): array {
    $encrypted=(string)($_REQUEST['bkid'] ?? '');
    try { $bookId=(int)encryptor('decrypt',$encrypted); } catch (Throwable $e) { $bookId=0; }
    $book=$bookId>0 ? bookFind($bookId) : null;
    if (!$book) { http_response_code(404); exit('Business book not found.'); }
    if (!dsCanStartSession($bookId)) {
        http_response_code(403); exit('Daily stock tracking access denied for this book.');
    }
    return [$encrypted,$bookId,$book,(int)auth()->id];
}
/** Stock personnel with either stock management or stocktaking access can open daily sessions. */
function dsCanStartSession(int $bookId): bool {
    if ($bookId <= 0 || !function_exists('khCan')) return false;
    return khCan('stocktake.view', $bookId) || khCan('stock.view', $bookId);
}
/** Owner or system superadministrator reviews. Other reviewers may be added via a dedicated permission later. */
function dsCanReview(int $bookId): bool {
    $book=bookFind($bookId);
    if (!$book || !dsCanStartSession($bookId)) return false;
    return (function_exists('khOwnsBusiness') && khOwnsBusiness((int)$book->business_id))
        || (function_exists('khIsSuperAdmin') && khIsSuperAdmin());
}
function dsToken(): string {
    if (empty($_SESSION['kh_daily_stock_csrf'])) $_SESSION['kh_daily_stock_csrf']=bin2hex(random_bytes(32));
    return $_SESSION['kh_daily_stock_csrf'];
}
function dsSession(int $id,int $bookId,bool $lock=false): array {
    $rows=dsRows('SELECT * FROM cashbook_daily_stocktakes WHERE id=? AND book_id=?'.($lock?' FOR UPDATE':''),'ii',[$id,$bookId]);
    if (!$rows) throw new RuntimeException('Daily stock session not found.');
    return $rows[0];
}
function dsLines(int $id,int $bookId): array {
    return dsRows('SELECT l.*,i.name,i.units FROM cashbook_daily_stocktake_items l JOIN cashbook_items i ON i.id=l.item_id AND i.book_id=l.book_id WHERE l.daily_id=? AND l.book_id=? ORDER BY i.name,i.id','ii',[$id,$bookId]);
}
function dsExpected(int $bookId): array {
    // Matches the CURRENT stock balance source used by the existing monthly stocktaking module.
    // Snapshot at session creation; NOT a historical reconstruction of a past date.
    return dsRows('SELECT i.id,i.name,i.units,COALESCE(sb.balance,0) expected_qty FROM cashbook_items i LEFT JOIN (SELECT book_id,item_id,MAX(balance) balance FROM cashbook_item_stock_balances GROUP BY book_id,item_id) sb ON sb.book_id=i.book_id AND sb.item_id=i.id WHERE i.book_id=? ORDER BY i.name','i',[$bookId]);
}
