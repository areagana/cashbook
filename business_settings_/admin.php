<?php
require_once __DIR__.'/../functions.php'; require_once __DIR__.'/bootstrap.php';
khFeatureRequireAdmin(); if (!khFeatureTableReady()) exit('Install schema.sql first.');
$error='';$success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 khFeatureCheckCsrf();
 try {
  $action=(string)($_POST['action']??'');
  if ($action==='type') {
   $key=strtolower(trim((string)($_POST['key']??''))); $name=trim((string)($_POST['name']??''));
   if (!preg_match('/^[a-z0-9_]{2,60}$/',$key)||$name===''||strlen($name)>120) throw new RuntimeException('Invalid business type');
   prepared_statements('INSERT INTO cashbook_business_types(type_key,name) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)','ss',[$key,$name]); khFeatureAudit('type_upsert',0,$key);
  } elseif ($action==='allocation') {
   $type=(int)($_POST['type_id']??0);$module=(string)($_POST['module']??'');$enabled=isset($_POST['enabled'])?1:0;
   if (!khFeatureQuery('SELECT id FROM cashbook_business_types WHERE id=?','i',[$type]) || !khFeatureQuery('SELECT id FROM cashbook_modules WHERE module_key=? AND is_core=0','s',[$module])) throw new RuntimeException('Invalid allocation');
   prepared_statements('INSERT INTO cashbook_business_type_modules(business_type_id,module_key,enabled) VALUES (?,?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled)','isi',[$type,$module,$enabled]);khFeatureAudit('allocation',0,"$type:$module:$enabled");
  } elseif ($action==='review') {
   $id=(int)($_POST['request_id']??0);$status=(string)($_POST['status']??'');
   if (!in_array($status,['approved','rejected'],true)) throw new RuntimeException('Invalid decision');
   $req=khFeatureQuery("SELECT * FROM cashbook_business_module_requests WHERE id=? AND status='pending'",'i',[$id]);
   if (!$req) throw new RuntimeException('Request no longer pending');$req=$req[0];
   global $server;$server->begin_transaction();
   try {
    if ($status==='approved') prepared_statements('INSERT INTO cashbook_business_module_overrides(business_id,module_key,enabled,approved_by) VALUES (?,?,1,?) ON DUPLICATE KEY UPDATE enabled=1,approved_by=VALUES(approved_by)','isi',[(int)$req['business_id'],$req['module_key'],khFeatureUserId()]);
    prepared_statements('UPDATE cashbook_business_module_requests SET status=?,reviewed_by=?,review_note=?,reviewed_at=NOW() WHERE id=? AND status=\'pending\'','sisi',[$status,khFeatureUserId(),trim((string)($_POST['note']??'')),$id]);
    khFeatureAudit('request_'.$status,(int)$req['business_id'],$req['module_key']);$server->commit();
   } catch(Throwable $e) {$server->rollback();throw $e;}
  } else throw new RuntimeException('Unknown action');
  $success='Saved successfully';
 } catch(Throwable $e) {$error=$e->getMessage();}
}
$types=khFeatureQuery('SELECT * FROM cashbook_business_types ORDER BY name');
$modules=khFeatureQuery('SELECT * FROM cashbook_modules ORDER BY is_core DESC,name');
$alloc=khFeatureQuery('SELECT tm.*,t.name AS type_name,m.name AS module_name FROM cashbook_business_type_modules tm JOIN cashbook_business_types t ON t.id=tm.business_type_id JOIN cashbook_modules m ON m.module_key=tm.module_key ORDER BY t.name,m.name');
$requests=khFeatureQuery("SELECT r.*,b.name AS business_name,m.name AS module_name FROM cashbook_business_module_requests r LEFT JOIN cashbook_business_profile b ON b.id=r.business_id LEFT JOIN cashbook_modules m ON m.module_key=r.module_key WHERE r.status='pending' ORDER BY r.id DESC LIMIT 100");
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>KashHub Feature Administration</title><link rel="stylesheet" href="assets/style.css"></head><body><main><h1>Business types & features</h1><p>Superadministrator controls business type defaults and approves extra modules.</p><?php if($error):?><div class="error"><?=khFeatureEscape($error)?></div><?php endif;if($success):?><div class="success"><?=khFeatureEscape($success)?></div><?php endif;?><section><h2>Add or rename business type</h2><form method="post"><input type="hidden" name="csrf" value="<?=khFeatureCsrf()?>"><input type="hidden" name="action" value="type"><label>Key <input name="key" required pattern="[a-z0-9_]+" placeholder="pharmacy"></label><label>Name <input name="name" required placeholder="Pharmacy"></label><button>Save type</button></form></section><section><h2>Allocate modules to business types</h2><form method="post"><input type="hidden" name="csrf" value="<?=khFeatureCsrf()?>"><input type="hidden" name="action" value="allocation"><label>Business type <select name="type_id"><?php foreach($types as $t):?><option value="<?=(int)$t['id']?>"><?=khFeatureEscape($t['name'])?></option><?php endforeach;?></select></label><label>Module <select name="module"><?php foreach($modules as $m):if($m['is_core'])continue;?><option value="<?=khFeatureEscape($m['module_key'])?>"><?=khFeatureEscape($m['name'])?></option><?php endforeach;?></select></label><label><input type="checkbox" name="enabled" checked> Enabled</label><button>Save allocation</button></form><table><tr><th>Business type</th><th>Module</th><th>Enabled</th></tr><?php foreach($alloc as $a):?><tr><td><?=khFeatureEscape($a['type_name'])?></td><td><?=khFeatureEscape($a['module_name'])?></td><td><?=$a['enabled']?'Yes':'No'?></td></tr><?php endforeach;?></table></section><section><h2>Pending module requests</h2><?php foreach($requests as $r):?><form method="post" class="request"><input type="hidden" name="csrf" value="<?=khFeatureCsrf()?>"><input type="hidden" name="action" value="review"><input type="hidden" name="request_id" value="<?=(int)$r['id']?>"><strong><?=khFeatureEscape($r['business_name'])?> — <?=khFeatureEscape($r['module_name'])?></strong><p><?=khFeatureEscape($r['reason'])?></p><label>Review note <input name="note"></label><button name="status" value="approved">Approve</button><button name="status" value="rejected" class="secondary">Reject</button></form><?php endforeach;if(!$requests)echo '<p>No pending requests.</p>';?></section></main></body></html>
