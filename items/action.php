<?php
header('Content-Type: application/json; charset=utf-8');
try {
    require_once __DIR__.'/item_helpers.php';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('POST required.');
    if (!isItemManager()) throw new RuntimeException('You do not have permission to change items.');
    $action = (string)($_POST['action'] ?? '');
    $db = itemDb();
    $db->begin_transaction();
    try {
        // Lock the source item during all reference checks and updates.
        $source = itemOne('SELECT id FROM cashbook_items WHERE id = ? AND book_id = ? FOR UPDATE','ii',[$itemId,$bookId]);
        if (!$source) throw new RuntimeException('Source item no longer exists.');
        if ($action === 'preview_delete') {
            if (!isItemDeleteAdmin()) throw new RuntimeException('Only an owner or superadministrator can delete items.');
            $references = itemReferences($itemId);
            $stockCount = (int)(itemOne('SELECT COUNT(*) AS n FROM cashbook_stocks WHERE book_id = ? AND item_id = ?', 'ii', [$bookId, $itemId])['n'] ?? 0);
            $db->commit();
            echo json_encode(['ok'=>true, 'references'=>$references, 'stock_movements'=>$stockCount,
                'mode'=>$references ? 'archive' : 'delete',
                'message'=>$references ? 'This item has related records. It will be archived, not erased; historical records remain intact.' : 'No related records found. This item can be permanently deleted.']);
            exit;
        } elseif ($action === 'delete') {
            if (!isItemDeleteAdmin()) throw new RuntimeException('Only an owner or superadministrator can delete items.');
            if (!isset($_SESSION['kh_item_csrf']) || !hash_equals($_SESSION['kh_item_csrf'], (string)($_POST['csrf'] ?? ''))) {
                throw new RuntimeException('Security token expired. Refresh and try again.');
            }
            $password = (string)($_POST['password'] ?? '');
            if ($password === '') throw new RuntimeException('Enter your account password.');
            $user = itemOne('SELECT password FROM cashbook_users WHERE id = ? LIMIT 1', 'i', [(int)auth()->id]);
            if (!$user || !password_verify($password, (string)$user['password'])) {
                throw new RuntimeException('Incorrect account password.');
            }
            $references = itemReferences($itemId);
            if ($references) {
                if (!itemColumn('cashbook_items', 'archived_at')) {
                    throw new RuntimeException('Archive support is not installed. Run the included migration before deleting referenced items.');
                }
                $saved = prepared_statements('UPDATE cashbook_items SET archived_at = NOW() WHERE id = ? AND book_id = ? AND archived_at IS NULL', 'ii', [$itemId,$bookId]);
                if (!$saved) throw new RuntimeException('Unable to archive item.');
                $message = 'Item archived. Related stock and financial history was preserved.';
            } else {
                $saved = prepared_statements('DELETE FROM cashbook_items WHERE id = ? AND book_id = ?', 'ii', [$itemId,$bookId]);
                if (!$saved) throw new RuntimeException('Unable to delete item.');
                $message = 'Unused item permanently deleted.';
            }
        } elseif ($action === 'transfer') {
            $targetId = filter_var($_POST['target_item_id'] ?? null,FILTER_VALIDATE_INT);
            $transactionId = filter_var($_POST['transaction_id'] ?? null,FILTER_VALIDATE_INT);
            if (!$targetId || !$transactionId || $targetId === $itemId) throw new RuntimeException('Select a different target item and valid transaction.');
            $target = itemOne('SELECT id, units FROM cashbook_items WHERE id = ? AND book_id = ? FOR UPDATE','ii',[$targetId,$bookId]);
            if (!$target) throw new RuntimeException('Target item does not belong to this book.');
            $transaction = itemOne('SELECT id FROM cashbook_transactions WHERE id = ? AND book_id = ? AND item_id = ? FOR UPDATE','iii',[$transactionId,$bookId,$itemId]);
            if (!$transaction) throw new RuntimeException('Transaction does not belong to this item and book.');
            // Stock references may use a numeric transaction id or an independent reference.
            // Block all transfers for stock-linked transactions rather than risk stock corruption.
            $stockLink = itemOne('SELECT COUNT(*) AS n FROM cashbook_stocks WHERE book_id = ? AND item_id = ? AND (reference = ? OR reference = ?)', 'iiss', [$bookId,$itemId,(string)$transactionId,'transaction_'.$transactionId]);
            if ((int)$stockLink['n'] > 0) throw new RuntimeException('Stock-linked transaction: transfer blocked until stock balances are reconciled.');
            // Check for additional stock references with the same numeric reference, regardless of item.
            $otherStock = itemOne('SELECT COUNT(*) AS n FROM cashbook_stocks WHERE book_id = ? AND reference = ?', 'is', [$bookId,(string)$transactionId]);
            if ((int)$otherStock['n'] > 0) throw new RuntimeException('Stock movement references this transaction. Transfer blocked.');
            // Linked transaction detail tables use transaction_id and book_id.
            foreach (['cashbook_cashins','cashbook_cashouts','cashbook_customer_ledger','cashbook_creditor_ledger'] as $table) {
                if (!itemColumn($table,'item_id') || !itemColumn($table,'transaction_id') || !itemColumn($table,'book_id')) continue;
                prepared_statements("UPDATE `$table` SET item_id = ? WHERE book_id = ? AND transaction_id = ? AND item_id = ?",'iiii',[$targetId,$bookId,$transactionId,$itemId]);
            }
            prepared_statements('UPDATE cashbook_transactions SET item_id = ? WHERE id = ? AND book_id = ? AND item_id = ?', 'iiii', [$targetId,$transactionId,$bookId,$itemId]);
            $message = 'Transaction and linked cash/ledger records transferred. Stock-linked transfers remain protected.';
        } else throw new RuntimeException('Unsupported action.');
        $db->commit();
        echo json_encode(['ok'=>true,'message'=>$message]);
    } catch (Throwable $e) { $db->rollback(); throw $e; }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
}
