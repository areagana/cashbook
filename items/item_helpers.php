<?php
/* Shared item statement helpers. No schema changes required. */
require_once __DIR__ . '/../assets/functions.php';
if (!isVerified()) { http_response_code(403); exit('Authentication required.'); }
$bsid = (string)($_REQUEST['bsid'] ?? '');
$book = $bsid !== '' ? bookFind(encryptor('decrypt', $bsid)) : null;
if (!$book || empty($book->id)) { http_response_code(404); exit('Business book not found.'); }
$bookId = (int)$book->id;
$itemId = filter_var($_REQUEST['item_id'] ?? null, FILTER_VALIDATE_INT);
if (!$itemId || $itemId < 1) { http_response_code(400); exit('Invalid item.'); }
function isItemManager(): bool { return hasRole(['owner','partner','superadministrator']); }
function itemDb(): mysqli { global $server; return $server; }
function itemQuery(string $sql, string $types = '', array $args = []): mysqli_result {
    return prepared_statements($sql, $types, $args);
}
function itemOne(string $sql, string $types = '', array $args = []): ?array {
    $result = itemQuery($sql, $types, $args);
    return $result->fetch_assoc() ?: null;
}
function itemEsc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function itemTables(): array {
    $rows = itemQuery("SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'item_id' AND TABLE_NAME LIKE 'cashbook_%'");
    $tables = [];
    while ($row = $rows->fetch_assoc()) $tables[] = $row['TABLE_NAME'];
    return $tables;
}
function itemColumn(string $table, string $column): bool {
    return (bool)itemOne('SELECT 1 AS found FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', 'ss', [$table, $column]);
}
function itemReferences(int $itemId): array {
    $counts = [];
    foreach (itemTables() as $table) {
        // Table names originate from information_schema, never request input.
        $row = itemOne("SELECT COUNT(*) AS n FROM `$table` WHERE item_id = ?", 'i', [$itemId]);
        if ((int)$row['n'] > 0) $counts[$table] = (int)$row['n'];
    }
    return $counts;
}
$item = itemOne('SELECT * FROM cashbook_items WHERE id = ? AND book_id = ?', 'ii', [$itemId, $bookId]);
if (!$item) { http_response_code(404); exit('Item not found in this book.'); }
