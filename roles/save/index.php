<?php
/** KashHub role assignments and permission updates. */
ob_start();
$ajax = (($_POST['action'] ?? '') === 'assignment');
ini_set('display_errors', '0');
error_reporting(E_ALL);
// Respond with JSON even when bootstrap fails before normal request handling.
register_shutdown_function(function () use (&$ajax) {
    $last = error_get_last();
    if (!$ajax || !$last || !in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
    error_log('KashHub roles fatal: ' . $last['message'] . ' in ' . $last['file'] . ':' . $last['line']);
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>false, 'message'=>'Server error while processing role assignment. Check the PHP error log.']);
});
try {
    require_once __DIR__ . '/../../assets/functions.php';
} catch (Throwable $error) {
    error_log('KashHub roles bootstrap: ' . $error->getMessage());
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>false,'message'=>'Role service could not initialize. Check the PHP error log.']);
    exit;
}
function krReply(int $status, string $message, bool $ok=false): void {
    global $ajax;
    http_response_code($status);
    if ($ajax) { while (ob_get_level()) ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['status'=>$ok,'message'=>$message]); }
    else echo htmlspecialchars($message,ENT_QUOTES,'UTF-8');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isVerified() || empty($_SESSION['auth']->id)) krReply(403,'Login required.');
if (empty($_SESSION['kh_csrf']) || !hash_equals($_SESSION['kh_csrf'],(string)($_POST['csrf']??''))) krReply(419,'Invalid security token.');
try {
$actorId=(int)$_SESSION['auth']->id;
$businessId=filter_var($_POST['business_id'] ?? 0, FILTER_VALIDATE_INT);
if ($businessId === false || $businessId < 0) krReply(400,'Invalid business.');
$roleId=(int)($_POST['role_id']??0);
$action=(string)($_POST['action']??'');
$adminQuery=prepared_statements("SELECT ru.user_id FROM cashbook_role_user ru JOIN cashbook_roles r ON r.id=ru.role_id WHERE ru.user_id=? AND ru.business_id=0 AND LOWER(r.name) IN ('superadministrator','superadmin') LIMIT 1",'i',[$actorId]);
$isAdmin=$adminQuery && $adminQuery->num_rows>0;
if (!$isAdmin) {
    $legacy=prepared_statements("SELECT u.id FROM cashbook_users u JOIN cashbook_roles r ON r.id=u.role_id WHERE u.id=? AND LOWER(r.name) IN ('superadministrator','superadmin') LIMIT 1",'i',[$actorId]);
    $isAdmin=$legacy && $legacy->num_rows>0;
}
$roleResult=prepared_statements('SELECT id,name FROM cashbook_roles WHERE id=? LIMIT 1','i',[$roleId]);
$role=$roleResult ? $roleResult->fetch_assoc() : null;
if (!$role) krReply(400,'Unknown role.');
$roleName=strtolower(trim($role['name']));
$systemRole=in_array($roleName,['superadministrator','superadmin'],true);
if ($action==='assignment') {
    $userId=(int)($_POST['user_id']??0);
    $operation=(string)($_POST['operation']??'');
    if (!$userId || !in_array($operation,['assign','revoke'],true)) krReply(400,'Invalid assignment request.');
    if ($businessId===0 && (!$isAdmin || !$systemRole)) krReply(403,'Only system administrators can assign system roles.');
    if ($businessId>0) {
        if ($systemRole || $roleName==='owner') krReply(403,'This role cannot be assigned at business level.');
        if (!$isAdmin) {
            $owns=prepared_statements('SELECT id FROM cashbook_business_profile WHERE id=? AND user_id=? LIMIT 1','ii',[$businessId,$actorId]);
            if (!$owns || !$owns->num_rows) krReply(403,'Only the business owner may assign business roles.');
        }
        $member=prepared_statements('SELECT id FROM cashbook_users WHERE id=? AND business_id=? LIMIT 1','ii',[$userId,$businessId]);
        if (!$member || !$member->num_rows) krReply(403,'User does not belong to this business.');
    } else {
        $member=prepared_statements('SELECT id FROM cashbook_users WHERE id=? LIMIT 1','i',[$userId]);
        if (!$member || !$member->num_rows) krReply(404,'User not found.');
    }
    if ($userId===$actorId && $operation==='revoke' && $systemRole) krReply(403,'You cannot revoke your own system administrator role.');
    // Protect the last explicitly assigned system administrator.
    if ($businessId===0 && $systemRole && $operation==='revoke') {
        $remaining=prepared_statements("SELECT COUNT(DISTINCT ru.user_id) AS total FROM cashbook_role_user ru JOIN cashbook_roles r ON r.id=ru.role_id WHERE ru.business_id=0 AND LOWER(r.name) IN ('superadministrator','superadmin') AND ru.user_id<>?",'i',[$userId]);
        $count=$remaining ? (int)($remaining->fetch_assoc()['total'] ?? 0) : 0;
        if ($count===0) krReply(403,'Assign another system administrator before revoking the last one.');
    }
    try {
        global $server;
        $server->begin_transaction();
        if ($operation==='assign') {
            $existing=prepared_statements('SELECT id FROM cashbook_role_user WHERE user_id=? AND role_id=? AND business_id=? LIMIT 1','iii',[$userId,$roleId,$businessId]);
            if (!$existing || !$existing->num_rows) prepared_statements('INSERT INTO cashbook_role_user(user_id,role_id,business_id,assigned_by) VALUES(?,?,?,?)','iiii',[$userId,$roleId,$businessId,$actorId]);
        } else prepared_statements('DELETE FROM cashbook_role_user WHERE user_id=? AND role_id=? AND business_id=?','iii',[$userId,$roleId,$businessId]);
        $server->commit();
        krReply(200,'Role assignment updated.',true);
    } catch(Throwable $e) { $server->rollback(); error_log('KashHub role assignment: '.$e->getMessage()); krReply(500,'Could not update role assignment.'); }
}
if ($action==='permissions') {
    if (!$isAdmin) krReply(403,'Only system administrators may edit shared permissions.');
    if (!isset($_POST['permission_ids']) || !is_array($_POST['permission_ids'])) $_POST['permission_ids']=[];
    $ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['permission_ids']??[])),fn($v)=>$v>0)));
    $available=prepared_statements('SELECT id FROM cashbook_permissions');
    if (!$available) krReply(500,'Permission definitions are unavailable.');
    $valid=[];
    while($item=$available->fetch_assoc()) $valid[(int)$item['id']]=true;
    foreach($ids as $id) if (!isset($valid[$id])) krReply(400,'Invalid permission selected.');
    try {
        global $server;
        $server->begin_transaction();
        prepared_statements('DELETE FROM cashbook_role_permissions WHERE role_id=?','i',[$roleId]);
        foreach($ids as $id) prepared_statements('INSERT INTO cashbook_role_permissions(role_id,permission_id) VALUES(?,?)','ii',[$roleId,$id]);
        $server->commit();
        header('Location: ../index.php?business_id='.$businessId.'&role_id='.$roleId.'&saved=1');exit;
    } catch(Throwable $e) { $server->rollback(); error_log('KashHub permission save: '.$e->getMessage()); krReply(500,'Could not save permissions.'); }
}
krReply(400,'Unknown action.');
} catch (Throwable $error) {
    error_log('KashHub roles request: ' . $error->getMessage());
    krReply(500, 'Could not process role request. Check the PHP error log.');
}
