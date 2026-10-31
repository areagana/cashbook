<?php
/** KashHub: move a transaction to another customer within the same book. */
require_once __DIR__ . '/../assets/functions.php';
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function transferReply(bool $ok, string $message): void
{
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isVerified()) {
    http_response_code(403);
    transferReply(false, 'Unauthorized request.');
}

$transactionId = filter_var($_POST['transaction_id'] ?? null, FILTER_VALIDATE_INT);
$fromCustomer = filter_var($_POST['from_customer_id'] ?? null, FILTER_VALIDATE_INT);
$toCustomer = filter_var($_POST['to_customer_id'] ?? null, FILTER_VALIDATE_INT);
$reason = trim((string) ($_POST['reason'] ?? ''));
$token = (string) ($_POST['csrf_token'] ?? '');

if (!hash_equals((string) ($_SESSION['kh_transfer_csrf'] ?? ''), $token) || !$token) {
    http_response_code(403);
    transferReply(false, 'Security token expired. Reload the statement.');
}
if (!$transactionId || !$fromCustomer || !$toCustomer || $fromCustomer === $toCustomer || mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
    transferReply(false, 'Choose a different customer and enter a reason (5–500 characters).');
}

// Only privileged roles may reassign financial transactions.
if (!hasRole(['owner', 'partner', 'superadministrator'])) {
    http_response_code(403);
    transferReply(false, 'You do not have permission to transfer transactions.');
}

$server->begin_transaction();
try {
    $result = prepared_statements(
        'SELECT id, book_id, customer_id, invoice_id FROM cashbook_transactions WHERE id=? FOR UPDATE',
        'i', [$transactionId]
    );
    $transaction = $result->fetch_assoc();
    if (!$transaction || (int) $transaction['customer_id'] !== $fromCustomer) {
        throw new RuntimeException('Transaction was changed or cannot be found.');
    }
    $bookId = (int) $transaction['book_id'];
    if (!empty($transaction['invoice_id'])) {
        throw new RuntimeException('Invoice-linked transactions must be reassigned through the invoice workflow.');
    }

    // Book membership is required, even for users with privileged roles.
    $actorId = (int) (auth()->id ?? 0);
    $membership = prepared_statements(
        'SELECT id FROM cashbook_book_users WHERE book_id=? AND user_id=? LIMIT 1',
        'ii', [$bookId, $actorId]
    );
    $owner = prepared_statements(
        'SELECT bp.id FROM cashbook_books b JOIN cashbook_business_profile bp ON bp.id=b.business_id WHERE b.id=? AND bp.user_id=? LIMIT 1',
        'ii', [$bookId, $actorId]
    );
    if (!$membership->num_rows && !$owner->num_rows && !hasRole(['superadministrator'])) {
        throw new RuntimeException('You cannot manage this business book.');
    }

    $customers = prepared_statements(
        'SELECT id FROM cashbook_customers WHERE book_id=? AND id IN (?,?) FOR UPDATE',
        'iii', [$bookId, $fromCustomer, $toCustomer]
    );
    if ($customers->num_rows !== 2) {
        throw new RuntimeException('Both customers must belong to the same book.');
    }

    $ledgerResult = prepared_statements(
        'SELECT * FROM cashbook_customer_ledger WHERE transaction_id=? AND book_id=? AND customer_id=? FOR UPDATE',
        'iii', [$transactionId, $bookId, $fromCustomer]
    );
    if ($ledgerResult->num_rows !== 1) {
        throw new RuntimeException('Expected exactly one matching customer ledger entry.');
    }
    $ledger = $ledgerResult->fetch_assoc();
    if (!empty($ledger['invoice_id'])) {
        throw new RuntimeException('Invoice ledger entries cannot be transferred here.');
    }

    prepared_statements(
        'UPDATE cashbook_transactions SET customer_id=? WHERE id=? AND book_id=? AND customer_id=?',
        'iiii', [$toCustomer, $transactionId, $bookId, $fromCustomer]
    );
    prepared_statements(
        'UPDATE cashbook_customer_ledger SET customer_id=? WHERE id=? AND book_id=? AND customer_id=?',
        'iiii', [$toCustomer, (int) $ledger['id'], $bookId, $fromCustomer]
    );

    // Existing KashHub function recalculates each ledger and the cached customer balance.
    rebuildCustomerLedgerBalances($fromCustomer, $bookId);
    rebuildCustomerLedgerBalances($toCustomer, $bookId);

    prepared_statements(
        'INSERT INTO cashbook_customer_transfers (book_id, transaction_id, from_customer_id, to_customer_id, transferred_by, reason, created_at) VALUES (?,?,?,?,?,?,NOW())',
        'iiiiis', [$bookId, $transactionId, $fromCustomer, $toCustomer, $actorId, $reason]
    );
    $server->commit();
    transferReply(true, 'Transaction transferred. Both customer balances have been rebuilt.');
} catch (Throwable $exception) {
    $server->rollback();
    error_log('Customer transfer failed: ' . $exception->getMessage());
    http_response_code(400);
    transferReply(false, $exception->getMessage());
}
