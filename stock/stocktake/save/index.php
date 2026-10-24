<?php
/** KashHub stocktake AJAX handler. Location: stock/stocktake/save/index.php */
require_once __DIR__ . '/../bootstrap.php';
[$encryptedBookId, $bookId, $book, $userId] = stContext();
$action = (string)($_REQUEST['action'] ?? '');

/** Review access is separate from permission to perform a physical count. */
function stCanReview(int $bookId): bool
{
    return stocktakeOwnerHasBookAccess((int)auth()->id, $bookId)
        || (hasRole(['superadministrator']) && khCan('stocktake.view', $bookId))
        || (hasRole(['partner']) && khCan('stocktake.view', $bookId));
}

function stRequireReview(int $bookId): void
{
    if (!stCanReview($bookId)) {
        throw new RuntimeException('Only an authorised stocktaking reviewer can perform this action.');
    }
}


// Only the modal detail view is a GET operation. All mutations require POST + CSRF.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'details') {
    try {
        $takeId=(int)($_GET['take_id'] ?? 0);
        $take=stSession($takeId,$bookId);
        $lines=stLines($takeId,$bookId);
        $summary=stSummary($lines);
        $editable=in_array($take['status'],['draft','counting'],true);
        $approver=stCanReview($bookId);
        $canReview=$approver;
        // The modal must use $canReview to omit sensitive columns entirely.
        // Do not return confidential stock figures to counting personnel.
        if (!$canReview) {
            foreach ($lines as &$line) {
                unset($line['expected_qty'], $line['unit_cost']);
            }
            unset($line);
            $summary=['total'=>count($lines),'counted'=>count(array_filter($lines, static fn($line)=>$line['counted_qty']!==null)),
                'variance'=>null,'expected_value'=>null,'counted_value'=>null,'missing_cost'=>null];
        }
        $csrf=stToken();
        include __DIR__ . '/../session_modal.php';
    } catch (Throwable $exception) {
        http_response_code(400);
        echo '<div class="alert alert-danger">'.stEscape($exception->getMessage()).'</div>';
    }
    exit;
}

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>false,'message'=>'Method not allowed.']); exit; }

$transactionStarted=false;
try {
    if (!hash_equals(stToken(),(string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Security token expired. Refresh and retry.');
    global $server;
    $server->begin_transaction();
    $transactionStarted=true;

    switch ($action) {
        case 'create':
            stRequireReview($bookId);
            $period=trim((string)($_POST['period'] ?? ''));
            $countDate=trim((string)($_POST['count_date'] ?? ''));
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$period)) throw new RuntimeException('Invalid month.');
            $date=DateTimeImmutable::createFromFormat('!Y-m-d',$countDate);
            if (!$date || $date->format('Y-m-d')!==$countDate || substr($countDate,0,7)!==$period) throw new RuntimeException('Count date must be within the selected month.');
            if (stRows('SELECT id FROM cashbook_stocktakes WHERE book_id=? AND period_month=? LIMIT 1','is',[$bookId,$period])) throw new RuntimeException('A session already exists for this month.');
            $items=stRows('SELECT i.id,COALESCE(sb.balance,0) expected_qty FROM cashbook_items i LEFT JOIN (SELECT book_id,item_id,MAX(balance) balance FROM cashbook_item_stock_balances GROUP BY book_id,item_id) sb ON sb.book_id=i.book_id AND sb.item_id=i.id WHERE i.book_id=?','i',[$bookId]);
            if (!$items) throw new RuntimeException('Add inventory items before starting a count.');
            stQuery('INSERT INTO cashbook_stocktakes(book_id,period_month,count_date,created_by) VALUES(?,?,?,?)','issi',[$bookId,$period,$countDate,$userId]);
            $takeId=(int)$server->insert_id;
            foreach ($items as $item) stQuery('INSERT INTO cashbook_stocktake_items(stocktake_id,book_id,item_id,expected_qty) VALUES(?,?,?,?)','iiid',[$takeId,$bookId,(int)$item['id'],(float)$item['expected_qty']]);
            $message='Monthly stock count created. Expected quantities reflect current stock at creation, not a historical month-end reconstruction.';
            break;

        case 'save_counts':
            $takeId=(int)($_POST['take_id'] ?? 0);
            $take=stSession($takeId,$bookId,true);
            if (!in_array($take['status'],['draft','counting'],true)) throw new RuntimeException('This session is locked.');
            $quantities=$_POST['qty'] ?? [];
            $costs=$_POST['cost'] ?? [];
            $canEditCost=stCanReview($bookId);
            $reasons=$_POST['reason'] ?? [];
            if (!is_array($quantities)||!is_array($costs)||!is_array($reasons)) throw new RuntimeException('Invalid stock count data.');
            foreach (stLines($takeId,$bookId) as $line) {
                $key=(string)$line['id'];
                $rawQty=trim((string)($quantities[$key] ?? ''));
                $rawCost=$canEditCost ? trim((string)($costs[$key] ?? '')) : ''; 
                if ($rawQty!=='' && (!is_numeric($rawQty)||!is_finite((float)$rawQty)||(float)$rawQty<0||(float)$rawQty>999999999999999)) throw new RuntimeException('Invalid quantity: '.$line['name']);
                if ($rawCost!=='' && (!is_numeric($rawCost)||!is_finite((float)$rawCost)||(float)$rawCost<0||(float)$rawCost>99999999999999)) throw new RuntimeException('Invalid cost: '.$line['name']);
                $qty=$rawQty===''?null:round((float)$rawQty,3);
                $cost=$rawCost===''?null:round((float)$rawCost,4);
                $reason=mb_substr(trim((string)($reasons[$key] ?? '')),0,255);
                if ($canEditCost) {
                    // Reviewer may update costs; omitted cost fields preserve existing values.
                    if (array_key_exists($key, $costs)) {
                        stQuery('UPDATE cashbook_stocktake_items SET counted_qty=?,unit_cost=?,reason=?,counted_by=?,counted_at=NOW() WHERE id=? AND stocktake_id=? AND book_id=?',
                            'ddsiiii',[$qty,$cost,$reason,$userId,(int)$line['id'],$takeId,$bookId]);
                    } else {
                        stQuery('UPDATE cashbook_stocktake_items SET counted_qty=?,reason=?,counted_by=?,counted_at=NOW() WHERE id=? AND stocktake_id=? AND book_id=?',
                            'dsiiii',[$qty,$reason,$userId,(int)$line['id'],$takeId,$bookId]);
                    }
                } else {
                    // Counting personnel cannot overwrite or erase unit costs.
                    stQuery('UPDATE cashbook_stocktake_items SET counted_qty=?,reason=?,counted_by=?,counted_at=NOW() WHERE id=? AND stocktake_id=? AND book_id=?',
                        'dsiiii',[$qty,$reason,$userId,(int)$line['id'],$takeId,$bookId]);
                }
            }
            stQuery("UPDATE cashbook_stocktakes SET status='counting' WHERE id=? AND book_id=?",'ii',[$takeId,$bookId]);
            $message='Physical counts saved successfully.';
            break;

        case 'submit_review':
            $takeId=(int)($_POST['take_id'] ?? 0);
            $take=stSession($takeId,$bookId,true);
            if (!in_array($take['status'],['draft','counting'],true)) throw new RuntimeException('This session cannot be submitted.');
            $lines=stLines($takeId,$bookId);
            if (!$lines) throw new RuntimeException('No items to review.');
            foreach ($lines as $line) if ($line['counted_qty']===null) throw new RuntimeException('Count every item before review.');
            stQuery("UPDATE cashbook_stocktakes SET status='review' WHERE id=? AND book_id=?",'ii',[$takeId,$bookId]);
            $message='Session submitted for review.';
            break;

        case 'return_corrections':
            stRequireReview($bookId);
            $takeId = (int)($_POST['take_id'] ?? 0);
            $take = stSession($takeId, $bookId, true);
            if ($take['status'] !== 'review') {
                throw new RuntimeException('Only records under review may be returned for corrections.');
            }
            stQuery("UPDATE cashbook_stocktakes SET status='counting' WHERE id=? AND book_id=?", 'ii', [$takeId, $bookId]);
            $message = 'Returned for corrections. Counts may now be edited and submitted again.';
            break;

        case 'delete_session':
            // Owner-only reset. An approved count is safe to replace only if
            // it has never posted any adjustments to the live stock ledger.
            // Ownership is independent of the account's system role.
            if (!stocktakeCanDeleteSession($userId, $bookId)) {
                throw new RuntimeException('Only the verified business owner or a Superadministrator may delete this stock-taking session.');
            }

            $takeId = (int)($_POST['take_id'] ?? 0);
            $reason = trim((string)($_POST['delete_reason'] ?? ''));
            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 500) {
                throw new RuntimeException('Enter a deletion reason of 10 to 500 characters.');
            }

            $take = stSession($takeId, $bookId, true);
            if ($take['status'] === 'posted' || !empty($take['posted_at']) || !empty($take['posted_by'])) {
                throw new RuntimeException('This stock count has already been posted. Reverse its stock movements before attempting a reset.');
            }

            // Do not remove a session with adjustment or period references.
            $adjustments = stRows(
                'SELECT id FROM cashbook_stock_adjustments WHERE stocktake_id=? AND book_id=? LIMIT 1',
                'ii', [$takeId, $bookId]
            );
            if ($adjustments) {
                throw new RuntimeException('This count has stock adjustments. It cannot be deleted until they are safely reversed.');
            }
            $periods = stRows(
                'SELECT id FROM cashbook_stock_periods WHERE stocktake_id=? AND book_id=? LIMIT 1',
                'ii', [$takeId, $bookId]
            );
            if ($periods) {
                throw new RuntimeException('This count is linked to a stock period. Resolve the period before deletion.');
            }

            $lines = stRows(
                'SELECT * FROM cashbook_stocktake_items WHERE stocktake_id=? AND book_id=? ORDER BY id',
                'ii', [$takeId, $bookId]
            );
            $snapshot = json_encode(['session'=>$take, 'items'=>$lines], JSON_THROW_ON_ERROR);
            stQuery(
                'INSERT INTO cashbook_stocktake_deletion_audit (book_id, stocktake_id, period_month, original_status, deleted_by, reason, snapshot_json, deleted_at) VALUES (?,?,?,?,?,?,?,NOW())',
                'iississ', [$bookId, $takeId, $take['period_month'], $take['status'], $userId, $reason, $snapshot]
            );
            stQuery('DELETE FROM cashbook_stocktake_items WHERE stocktake_id=? AND book_id=?', 'ii', [$takeId, $bookId]);
            stQuery('DELETE FROM cashbook_stocktakes WHERE id=? AND book_id=?', 'ii', [$takeId, $bookId]);
            $message = 'Stock-taking session deleted and archived. You can now create a new count for the same month.';
            $takeId = 0;
            break;

        case 'approve':
            stRequireReview($bookId);
            $takeId=(int)($_POST['take_id'] ?? 0);
            $take=stSession($takeId,$bookId,true);
            if ($take['status']!=='review') throw new RuntimeException('Only reviewed sessions may be approved.');
            $lines=stLines($takeId,$bookId);
            if (!$lines) throw new RuntimeException('Cannot approve an empty count.');
            foreach ($lines as $line) if ($line['counted_qty']===null) throw new RuntimeException('All items must be counted.');
            stQuery("UPDATE cashbook_stocktakes SET status='approved',approved_by=?,approved_at=NOW() WHERE id=? AND book_id=?",'iii',[$userId,$takeId,$bookId]);
            $message='Stock count approved and closed. No stock movements were posted.';
            break;

        default: throw new RuntimeException('Unsupported action.');
    }

    $server->commit();
    echo json_encode(['status'=>true,'message'=>$message,'take_id'=>$takeId]);
} catch (Throwable $exception) {
    if ($transactionStarted) $server->rollback();
    http_response_code(400);
    echo json_encode(['status'=>false,'message'=>$exception->getMessage()]);
}
