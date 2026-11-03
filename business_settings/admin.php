<?php
require_once __DIR__.'/../assets/functions.php'; require_once __DIR__.'/bootstrap.php';
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
$suggestions=khFeatureSuggestionReady()?khFeatureQuery("SELECT s.*,b.name AS business_name FROM cashbook_business_feature_suggestions s LEFT JOIN cashbook_business_profile b ON b.id=s.business_id WHERE s.status='pending' ORDER BY s.id DESC LIMIT 100"):[];
$requests=khFeatureQuery("SELECT r.*,b.name AS business_name,m.name AS module_name FROM cashbook_business_module_requests r LEFT JOIN cashbook_business_profile b ON b.id=r.business_id LEFT JOIN cashbook_modules m ON m.module_key=r.module_key WHERE r.status='pending' ORDER BY r.id DESC LIMIT 100");
?>
<?php pageHeader('Business Types & Features'); ?>
<div class="container-fluid py-4 kh-business-settings">
 <div class="d-flex justify-content-between align-items-center mb-3"><div><h2>Business types & features</h2><p class="text-muted">Manage default modules and review business requests.</p></div><a href="../" class="btn btn-outline-secondary" onclick="if(history.length>1){event.preventDefault();history.back();}"><i class="fas fa-arrow-left mr-1"></i> Back</a></div>
 <?php if($error):?><div class="alert alert-danger"><?=khFeatureEscape($error)?></div><?php endif;if($success):?><div class="alert alert-success"><?=khFeatureEscape($success)?></div><?php endif;?>
 <div class="card mb-4"><div class="card-body"><h5>Add or rename business type</h5><form method="post" class="form-row"><input type="hidden" name="csrf" value="<?=khFeatureEscape(khFeatureCsrf())?>"><input type="hidden" name="action" value="type"><div class="col-md-4 form-group"><label>Key</label><input class="form-control" name="key" required pattern="[a-z0-9_]+" placeholder="pharmacy"></div><div class="col-md-5 form-group"><label>Name</label><input class="form-control" name="name" required placeholder="Pharmacy"></div><div class="col-md-3 form-group align-self-end"><button class="btn btn-success btn-block">Save type</button></div></form></div></div>
 <div class="card mb-4"><div class="card-body"><h5>Allocate modules to business types</h5><form method="post" class="form-row align-items-end"><input type="hidden" name="csrf" value="<?=khFeatureEscape(khFeatureCsrf())?>"><input type="hidden" name="action" value="allocation"><div class="col-md-4 form-group"><label>Business type</label><select class="form-control" name="type_id"><?php foreach($types as $t):?><option value="<?=(int)$t['id']?>"><?=khFeatureEscape($t['name'])?></option><?php endforeach;?></select></div><div class="col-md-4 form-group"><label>Module</label><select class="form-control" name="module"><?php foreach($modules as $m):if($m['is_core'])continue;?><option value="<?=khFeatureEscape($m['module_key'])?>"><?=khFeatureEscape($m['name'])?></option><?php endforeach;?></select></div><div class="col-md-2 form-group"><label><input type="checkbox" name="enabled" checked> Enabled</label></div><div class="col-md-2 form-group"><button class="btn btn-success btn-block">Save</button></div></form><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Business type</th><th>Module</th><th>Enabled</th></tr></thead><tbody><?php foreach($alloc as $a):?><tr><td><?=khFeatureEscape($a['type_name'])?></td><td><?=khFeatureEscape($a['module_name'])?></td><td><?=$a['enabled']?'Yes':'No'?></td></tr><?php endforeach;?></tbody></table></div></div></div>
 <div class="card mb-4"><div class="card-body"><h5>Pending existing-module requests</h5><?php foreach($requests as $r):?><form method="post" class="kh-admin-request border rounded p-3 mb-2"><input type="hidden" name="csrf" value="<?=khFeatureEscape(khFeatureCsrf())?>"><input type="hidden" name="action" value="review"><input type="hidden" name="request_id" value="<?=(int)$r['id']?>"><strong><?=khFeatureEscape($r['business_name'])?> — <?=khFeatureEscape($r['module_name'])?></strong><p><?=khFeatureEscape($r['reason'])?></p><input class="form-control mb-2" name="note" placeholder="Review note"><button class="btn btn-success btn-sm" name="status" value="approved">Approve</button> <button class="btn btn-outline-danger btn-sm" name="status" value="rejected">Reject</button></form><?php endforeach;if(!$requests):?><p class="text-muted">No pending module requests.</p><?php endif;?></div></div>
 <div class="card"><div class="card-body"><h5>New feature suggestions</h5><p class="text-muted small">These are proposed features not currently registered in KashHub. Marking one as planned does not create or activate a module.</p><?php foreach($suggestions as $s):?><form method="post" class="kh-admin-request border rounded p-3 mb-2"><input type="hidden" name="csrf" value="<?=khFeatureEscape(khFeatureCsrf())?>"><input type="hidden" name="action" value="suggestion_review"><input type="hidden" name="suggestion_id" value="<?=(int)$s['id']?>"><strong><?=khFeatureEscape($s['business_name']??'Unknown business')?> — <?=khFeatureEscape($s['feature_name'])?></strong><p class="mb-2"><?=nl2br(khFeatureEscape($s['details']))?></p><input class="form-control mb-2" name="note" maxlength="2000" placeholder="Response to business owner"><button class="btn btn-success btn-sm" name="status" value="planned">Mark planned</button> <button class="btn btn-outline-primary btn-sm" name="status" value="reviewed">Mark reviewed</button> <button class="btn btn-outline-danger btn-sm" name="status" value="rejected">Reject</button></form><?php endforeach;if(!$suggestions):?><p class="text-muted">No pending feature suggestions.</p><?php endif;?></div></div>
</div>
<?php pageFooter(); ?>
