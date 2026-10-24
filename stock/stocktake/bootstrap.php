<?php
/** KashHub stocktake shared bootstrap. Location: stock/stocktake/bootstrap.php */
require_once __DIR__ . '/../../assets/functions.php';
if (!isVerified()) { http_response_code(401); exit('Authentication required.'); }

function stEscape($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function stQuery(string $sql, string $types = '', array $values = []): mysqli_stmt
{
    global $server;
    $statement = $server->prepare($sql);
    if (!$statement) throw new RuntimeException($server->error);
    if ($types !== '') $statement->bind_param($types, ...$values);
    if (!$statement->execute()) throw new RuntimeException($statement->error);
    return $statement;
}
function stRows(string $sql, string $types = '', array $values = []): array
{
    return stQuery($sql, $types, $values)->get_result()->fetch_all(MYSQLI_ASSOC);
}
/** Verify ownership through the actual business profile and book relationship. */
function stocktakeOwnerHasBookAccess(int $userId, int $bookId): bool
{
    if ($userId <= 0 || $bookId <= 0) return false;
    $matches = stRows(
        'SELECT b.id FROM cashbook_books b INNER JOIN cashbook_business_profile bp ON bp.id=b.business_id WHERE b.id=? AND bp.user_id=? LIMIT 1',
        'ii', [$bookId, $userId]
    );
    return !empty($matches);
}

/**
 * Delete permission is granted to the verified business owner OR an effective
 * system Superadministrator (including cashbook_role_user assignments).
 * Never rely on the legacy users.role_id value alone.
 */
function stocktakeCanDeleteSession(int $userId, int $bookId): bool
{
    if ($userId <= 0 || $bookId <= 0) return false;
    // A book-scoped permission avoids granting rights through a role name
    // assigned in a different business. Owners and system admins are handled by khCan().
    return khCan('stocktake.delete', $bookId);
}

/** Verify stocktake access against the current book and its business. */
function stocktakeUserHasBookAccess(int $userId, int $bookId): bool
{
    if ($userId <= 0 || $bookId <= 0) return false;
    if ((int)(auth()->id ?? 0) !== $userId) return false;

    // khCan checks authentication, book/business membership and effective roles.
    return khCan('stocktake.view', $bookId);
}

function stContext(): array
{
    $encrypted = (string)($_REQUEST['bkid'] ?? '');
    try { $bookId = (int)encryptor('decrypt', $encrypted); }
    catch (Throwable $exception) { $bookId = 0; }
    $book = $bookId > 0 ? bookFind($bookId) : null;
    if (!$book) { http_response_code(404); exit('Business book not found.'); }
    // Permission-based access supports custom roles such as Stock Manager.
    // Do not use hasRole() here: a role name alone cannot authorize this book.
    $userId = (int)auth()->id;
    if (!stocktakeUserHasBookAccess($userId, $bookId)) {
        http_response_code(403);
        exit('You do not have access to this business book.');
    }
    return [$encrypted, $bookId, $book, $userId];
}
function stSession(int $id, int $bookId, bool $lock = false): array
{
    $rows = stRows('SELECT * FROM cashbook_stocktakes WHERE id=? AND book_id=?' . ($lock ? ' FOR UPDATE' : ''), 'ii', [$id,$bookId]);
    if (!$rows) throw new RuntimeException('Stock-taking session not found.');
    return $rows[0];
}
function stLines(int $id, int $bookId): array
{
    return stRows('SELECT li.*, i.name, i.units FROM cashbook_stocktake_items li JOIN cashbook_items i ON i.id=li.item_id AND i.book_id=li.book_id WHERE li.stocktake_id=? AND li.book_id=? ORDER BY i.name,i.id','ii',[$id,$bookId]);
}
function stSummary(array $lines): array
{
    $result=['total'=>count($lines),'counted'=>0,'variance'=>0,'expected_value'=>0.0,'counted_value'=>0.0,'missing_cost'=>0];
    foreach ($lines as $line) {
        if ($line['counted_qty'] === null) continue;
        $result['counted']++;
        if (abs((float)$line['counted_qty']-(float)$line['expected_qty'])>0.0005) $result['variance']++;
        if ($line['unit_cost'] === null) { $result['missing_cost']++; continue; }
        $result['expected_value']+=(float)$line['expected_qty']*(float)$line['unit_cost'];
        $result['counted_value']+=(float)$line['counted_qty']*(float)$line['unit_cost'];
    }
    return $result;
}
function stToken(): string
{
    if (empty($_SESSION['kh_stocktake_csrf'])) $_SESSION['kh_stocktake_csrf']=bin2hex(random_bytes(32));
    return $_SESSION['kh_stocktake_csrf'];
}
