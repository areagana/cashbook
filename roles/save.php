<?php
require_once __DIR__.'/../assets/functions.php';
if ($_SERVER['REQUEST_METHOD']!=='POST' || !isVerified()) { http_response_code(403); exit('Access denied'); }
if (empty($_SESSION['kh_csrf']) || !hash_equals($_SESSION['kh_csrf'],(string)($_POST['csrf']??''))) { http_response_code(419); exit('Invalid security token'); }
$businessId=(int)($_POST['business_id']??0); $roleId=(int)($_POST['role_id']??0);
$system=khIsSuperAdmin();
if (!$system) { http_response_code(403); exit('Only system administrators can edit shared roles'); }
$role=prepared_statements('SELECT id,name FROM cashbook_roles WHERE id=? LIMIT 1','i',[$roleId])->fetch_assoc();
if (!$role) { http_response_code(400); exit('Unknown role'); }
$roleName=strtolower(trim($role['name']));
if (!$system && in_array($roleName,['superadministrator','superadmin'],true)) { http_response_code(403); exit('System roles cannot be modified'); }
$ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['permission_ids']??[])),fn($v)=>$v>0)));
$valid=[];$res=prepared_statements('SELECT id,permission_key FROM cashbook_permissions');
while($p=$res->fetch_assoc()) if($system || $p['permission_key']!=='system.admin') $valid[(int)$p['id']]=true;
foreach($ids as $id) if(!isset($valid[$id])) { http_response_code(400); exit('Invalid permission'); }
global $server;
$server->begin_transaction();
try {
    prepared_statements('DELETE FROM cashbook_role_permissions WHERE role_id=?','i',[$roleId]);
    foreach($ids as $id) prepared_statements('INSERT INTO cashbook_role_permissions(role_id,permission_id) VALUES(?,?)','ii',[$roleId,$id]);
    $server->commit();
} catch(Throwable $e) { $server->rollback(); error_log($e->getMessage()); http_response_code(500); exit('Could not save permissions'); }
header('Location: index.php?business_id='.$businessId.'&role_id='.$roleId.'&saved=1');exit;
