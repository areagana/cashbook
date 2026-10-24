<?php
/** KashHub Daily Stock Tracker — AJAX handler. */
require_once __DIR__ . '/../bootstrap.php';
[$encryptedBookId,$bookId,$book,$userId]=dsContext();
$action=(string)($_REQUEST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD']==='GET' && $action==='details') {
    try {
        $session=dsSession((int)($_GET['daily_id']??0),$bookId);
        $lines=dsLines((int)$session['id'],$bookId);
        $reviewer=dsCanReview($bookId);
        $editable=in_array($session['status'],['draft','counting'],true);
        $csrf=dsToken();
        include __DIR__.'/../session_modal.php';
    } catch (Throwable $e) { http_response_code(400); echo '<div class="alert alert-danger">'.dsEsc($e->getMessage()).'</div>'; }
    exit;
}
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit(json_encode(['status'=>false,'message'=>'Method not allowed.'])); }
$started=false;
try {
    if (!hash_equals(dsToken(),(string)($_POST['csrf']??''))) throw new RuntimeException('Security token expired. Refresh the page.');
    global $server;
    $server->begin_transaction(); $started=true;
    $dailyId=(int)($_POST['daily_id']??0);
    switch ($action) {
        case 'create':
            if (!dsCanStartSession($bookId)) throw new RuntimeException('Stock management or stocktaking permission is required to open a daily session.');
            $date=(string)($_POST['count_date']??'');
            $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
            if (!$parsed || $parsed->format('Y-m-d')!==$date || $date!==date('Y-m-d')) {
                throw new RuntimeException('Daily sessions must be opened for today. Past dates require a historical stock reconstruction.');
            }
            if (dsRows('SELECT id FROM cashbook_daily_stocktakes WHERE book_id=? AND count_date=?','is',[$bookId,$date])) throw new RuntimeException('A session already exists for this date.');
            $items=dsExpected($bookId);
            if (!$items) throw new RuntimeException('No stock items exist in this book.');
            dsQuery('INSERT INTO cashbook_daily_stocktakes(book_id,count_date,created_by) VALUES(?,?,?)','isi',[$bookId,$date,$userId]);
            $dailyId=(int)$server->insert_id;
            foreach ($items as $item) dsQuery('INSERT INTO cashbook_daily_stocktake_items(daily_id,book_id,item_id,expected_qty) VALUES(?,?,?,?)','iiid',[$dailyId,$bookId,(int)$item['id'],(float)$item['expected_qty']]);
            $message='Daily session opened with a snapshot of current stock balances.';
            break;
        case 'save_counts':
            $session=dsSession($dailyId,$bookId,true);
            if (!in_array($session['status'],['draft','counting'],true)) throw new RuntimeException('This count is locked.');
            $quantities=$_POST['qty']??[]; $remarks=$_POST['remarks']??[];
            if (!is_array($quantities)||!is_array($remarks)) throw new RuntimeException('Invalid form data.');
            $lines=dsLines($dailyId,$bookId);
            $validIds=array_fill_keys(array_map(static fn($line)=>(string)$line['id'],$lines),true);
            foreach (array_keys($quantities+$remarks) as $id) if (!isset($validIds[(string)$id])) throw new RuntimeException('Unexpected stock item in submission.');
            foreach ($lines as $line) {
                $key=(string)$line['id'];
                // Only submitted rows are updated: partial daily counts are supported.
                if (!array_key_exists($key,$quantities) && !array_key_exists($key,$remarks)) continue;
                $raw=trim((string)($quantities[$key]??''));
                if ($raw!=='' && (!is_numeric($raw)||!is_finite((float)$raw)||(float)$raw<0||(float)$raw>999999999999)) throw new RuntimeException('Invalid quantity for '.$line['name']);
                $qty=$raw===''?null:round((float)$raw,3);
                $note=mb_substr(trim((string)($remarks[$key]??'')),0,255);
                dsQuery('UPDATE cashbook_daily_stocktake_items SET counted_qty=?,remarks=?,counted_by=?,counted_at=NOW() WHERE id=? AND daily_id=? AND book_id=?',
                    'dsiiii',[$qty,$note,$userId,(int)$line['id'],$dailyId,$bookId]);
            }
            dsQuery("UPDATE cashbook_daily_stocktakes SET status='counting' WHERE id=? AND book_id=?",'ii',[$dailyId,$bookId]);
            $message='Daily physical counts saved.';
            break;
        case 'submit_review':
            $session=dsSession($dailyId,$bookId,true);
            if (!in_array($session['status'],['draft','counting'],true)) throw new RuntimeException('This session cannot be submitted.');
            $counted=dsRows('SELECT COUNT(*) total, SUM(counted_qty IS NOT NULL) counted FROM cashbook_daily_stocktake_items WHERE daily_id=? AND book_id=?','ii',[$dailyId,$bookId])[0];
            if (!(int)$counted['counted']) throw new RuntimeException('Count at least one item before submitting.');
            dsQuery("UPDATE cashbook_daily_stocktakes SET status='review',submitted_by=?,submitted_at=NOW() WHERE id=? AND book_id=?",'iii',[$userId,$dailyId,$bookId]);
            $message='Daily count submitted for review.';
            break;
        case 'return_corrections':
            if (!dsCanReview($bookId)) throw new RuntimeException('Reviewer permission required.');
            $session=dsSession($dailyId,$bookId,true);
            if ($session['status']!=='review') throw new RuntimeException('Only submitted counts can be returned.');
            dsQuery("UPDATE cashbook_daily_stocktakes SET status='counting',reviewed_by=NULL,reviewed_at=NULL WHERE id=? AND book_id=?",'ii',[$dailyId,$bookId]);
            $message='Returned to operators for correction.';
            break;
        case 'approve':
            if (!dsCanReview($bookId)) throw new RuntimeException('Reviewer permission required.');
            $session=dsSession($dailyId,$bookId,true);
            if ($session['status']!=='review') throw new RuntimeException('Only submitted counts can be approved.');
            $notes=mb_substr(trim((string)($_POST['review_notes']??'')),0,2000);
            dsQuery("UPDATE cashbook_daily_stocktakes SET status='approved',reviewed_by=?,reviewed_at=NOW(),review_notes=? WHERE id=? AND book_id=?",'isii',[$userId,$notes,$dailyId,$bookId]);
            $message='Daily reconciliation approved. No stock ledger adjustments were posted.';
            break;
        default: throw new RuntimeException('Unsupported action.');
    }
    $server->commit();
    echo json_encode(['status'=>true,'message'=>$message,'daily_id'=>$dailyId]);
} catch (Throwable $e) {
    if ($started) $server->rollback();
    http_response_code(400);
    echo json_encode(['status'=>false,'message'=>$e->getMessage()]);
}
