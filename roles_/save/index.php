<?php
require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$actorId=(int)auth()->id;
$started=false;
try {
    if ($_SERVER['REQUEST_METHOD']!=='POST') throw new RuntimeException('POST required.');
    if (!hash_equals(krToken(),(string)($_POST['csrf']??''))) throw new RuntimeException('Security token expired.');
    $userId=(int)($_POST['user_id']??0);
    $roleId=(int)($_POST['role_id']??0);
    $businessId=(int)($_POST['business_id']??0);
    $operation=(string)($_POST['operation']??'');
    if ($userId<=0 || $roleId<=0 || $businessId<0 || !in_array($operation,['assign','revoke'],true)) throw new RuntimeException('Invalid assignment request.');
    $role=krRole($roleId);
    if (!$role) throw new RuntimeException('Role not found.');
    if (!krRows('SELECT id FROM cashbook_users WHERE id=? LIMIT 1','i',[$userId])) throw new RuntimeException('User not found.');
    $systemRole=krIsSystemRole($role);
    if ($systemRole) {
        if ($businessId!==0 || !krCanManageSystemRoles($actorId)) throw new RuntimeException('System role assignment requires Superadministrator authorization.');
        if ($userId===$actorId && $operation==='revoke') throw new RuntimeException('You cannot revoke your own system administrator role.');
    } else {
        if ($businessId<=0 || !krCanManageBusinessRoles($actorId,$businessId)) throw new RuntimeException('Only the business owner may manage its roles.');
        if (!krBusinessMember($userId,$businessId)) throw new RuntimeException('User is not a member of this business.');
        if (strtolower($role['name'])==='owner') throw new RuntimeException('Business ownership is established by the business profile, not role assignment.');
    }
    global $server;
    $server->begin_transaction(); $started=true;
    // Serialize superadmin changes so simultaneous requests cannot remove the final admin.
    if ($systemRole) {
        krRows('SELECT id FROM cashbook_roles WHERE id=? FOR UPDATE','i',[$roleId]);
        if ($operation==='revoke') {
            $count=krRows("SELECT COUNT(DISTINCT u.id) AS total FROM cashbook_users u JOIN cashbook_roles r ON r.id=u.role_id LEFT JOIN cashbook_role_user ru ON ru.user_id=u.id AND ru.business_id=0 LEFT JOIN cashbook_roles ar ON ar.id=ru.role_id WHERE LOWER(r.name)='superadministrator' OR LOWER(ar.name)='superadministrator'");
            if ((int)($count[0]['total']??0)<=1) throw new RuntimeException('The last Superadministrator cannot be removed.');
            $legacy=krRows("SELECT r.name FROM cashbook_users u JOIN cashbook_roles r ON r.id=u.role_id WHERE u.id=? LIMIT 1",'i',[$userId]);
            if (strtolower($legacy[0]['name']??'')==='superadministrator') throw new RuntimeException('This user still has the legacy Superadministrator role. Migrate their primary role before revocation.');
        }
    }
    if ($operation==='assign') {
        krDb('INSERT IGNORE INTO cashbook_role_user(user_id,role_id,business_id,assigned_by) VALUES(?,?,?,?)','iiii',[$userId,$roleId,$businessId,$actorId]);
    } else {
        krDb('DELETE FROM cashbook_role_user WHERE user_id=? AND role_id=? AND business_id=?','iii',[$userId,$roleId,$businessId]);
    }
    krDb('INSERT INTO cashbook_role_audit(actor_user_id,target_user_id,role_id,business_id,action) VALUES(?,?,?,?,?)','iiiis',[$actorId,$userId,$roleId,$businessId,$operation]);
    $server->commit(); $started=false;
    echo json_encode(['status'=>true,'message'=>'Role assignment updated.']);
} catch (Throwable $e) {
    if ($started) $server->rollback();
    http_response_code(400);
    echo json_encode(['status'=>false,'message'=>$e->getMessage()]);
}
