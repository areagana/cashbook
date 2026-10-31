<?php
/** Shared creditor statement calculation for on-screen and PDF views. */
function khCreditorStatement(int $creditorId, int $bookId): array
{
    $sql = "SELECT cl.*, ci.name AS item_name, pm.name AS paymode_name
            FROM cashbook_creditor_ledger cl
            LEFT JOIN cashbook_items ci ON ci.id = cl.item_id AND ci.book_id = cl.book_id
            LEFT JOIN cashbook_paymodes pm ON pm.id = cl.paymode_id AND pm.book_id = cl.book_id
            WHERE cl.creditor_id = ? AND cl.book_id = ?
            ORDER BY cl.created_at ASC, cl.id ASC";
    $result = prepared_statements($sql, 'ii', [$creditorId, $bookId]);
    $raw = [];
    while ($record = $result->fetch_assoc()) $raw[] = $record;

    $opening = 0.0;
    $start = 0;
    if ($raw) {
        $first = $raw[0];
        $opening = round((float)$first['balance'] - (float)$first['credit_amount'] + (float)$first['debit_amount'], 2);
        if (abs((float)$first['credit_amount']) < .005 && abs((float)$first['debit_amount']) < .005) {
            $opening = round((float)$first['balance'], 2);
            $start = 1;
        }
    }
    $running = $opening;
    $totalCredit = $totalDebit = 0.0;
    $rows = [];
    $differences = 0;
    foreach (array_slice($raw, $start) as $record) {
        $credit = (float)$record['credit_amount'];
        $debit = (float)$record['debit_amount'];
        $totalCredit += $credit;
        $totalDebit += $debit;
        $running = round($running + $credit - $debit, 2);
        if (abs($running - (float)$record['balance']) > .01) $differences++;
        $description = trim((string)(($record['details'] ?? '') ?: ($record['item_name'] ?? '')));
        if ($description === '' || is_numeric($description)) {
            $description = $credit > 0 ? 'Additional amount owed to creditor' : ($debit > 0 ? 'Payment to creditor' : 'Account adjustment');
        }
        $record['statement_description'] = $description;
        $record['statement_activity'] = $credit > 0 && !$debit ? 'Amount added' : ($debit > 0 && !$credit ? 'Payment made' : 'Adjustment');
        $record['statement_balance'] = $running;
        $rows[] = $record;
    }
    $firstDate = $raw[0]['created_at'] ?? null;
    $lastDate = $raw[count($raw) - 1]['created_at'] ?? null;
    $period = $firstDate ? date('d M Y', strtotime($firstDate)) . ' — ' . date('d M Y', strtotime($lastDate)) : 'No transactions';
    return compact('rows', 'opening', 'running', 'totalCredit', 'totalDebit', 'firstDate', 'lastDate', 'period', 'differences') + ['hasOpening' => (bool)$raw];
}
