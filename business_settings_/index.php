<?php
require_once __DIR__.'/../functions.php';require_once __DIR__.'/bootstrap.php';
$businessId=khFeatureBusinessId();khFeatureRequireOwner($businessId);if(!khFeatureTableReady())exit('Install schema.sql first.');
$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 khFeatureCheckCsrf();try{
  $action=(string)($_POST['action']??'');
  if($action==='select_type'){
   $id=(int)($_POST['type_id']??0);if(!khFeatureQuery('SELECT id FROM cashbook_business_types WHERE id=? AND active=1','i',[$id]))throw new RuntimeException('Invalid business type');
   prepared_statements('UPDATE cashbook_business_profile SET business_type_id=? WHERE id=? AND user_id=?','iii',[$id,$businessId,khFeatureUserId()]);khFeatureAudit('type_selected',$businessId,(string)$id);
  }elseif($action==='request'){
   $key=(string)($_POST['module']??'');$reason=trim((string)($_POST['reason']??''));
   if(!khFeatureQuery('SELECT id FROM cashbook_modules WHERE module_key=? AND is_core=0 AND active=1','s',[$key]))throw new RuntimeException('Invalid module');
   if(khFeatureEnabled($key,$businessId))throw new RuntimeException('This module is already enabled');
   if(khFeatureQuery("SELECT id FROM cashbook_business_module_requests WHERE business_id=? AND module_key=? AND status='pending' LIMIT 1",'is',[$businessId,$key]))throw new RuntimeException('Request already pending');
   prepared_statements('INSERT INTO cashbook_business_module_requests(business_id,module_key,reason,requested_by) VALUES (?,?,?,?)','issi',[$businessId,$key,$reason,khFeatureUserId()]);khFeatureAudit('module_requested',$businessId,$key);
  }else throw new RuntimeException('Unknown action');$success='Saved successfully';
 }catch(Throwable $e){$error=$e->getMessage();}
}
$types=khFeatureQuery('SELECT * FROM cashbook_business_types WHERE active=1 ORDER BY name');
$modules=khFeatureQuery('SELECT * FROM cashbook_modules WHERE active=1 ORDER BY is_core DESC,name');
$profile=khFeatureQuery('SELECT business_type_id,name FROM cashbook_business_profile WHERE id=?','i',[$businessId]);$selected=(int)($profile[0]['business_type_id']??0);
$requests=khFeatureQuery('SELECT r.*,m.name FROM cashbook_business_module_requests r JOIN cashbook_modules m ON m.module_key=r.module_key WHERE r.business_id=? ORDER BY r.id DESC LIMIT 30','i',[$businessId]);
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Business Settings | KashHub</title><link rel="stylesheet" href="assets/style.css"></head><body><main><h1>Business Settings</h1><p>Choose your business nature. KashHub assigns the modules configured by the system administrator.</p><?php if($error):?><div class="error"><?=khFeatureEscape($error)?></div><?php endif;if($success):?><div class="success"><?=khFeatureEscape($success)?></div><?php endif;?><section><h2>Nature of business</h2><form method="post"><input type="hidden" name="csrf" value="<?=khFeatureCsrf()?>"><input type="hidden" name="action" value="select_type"><select name="type_id" required><option value="">Select business type</option><?php foreach($types as $t):?><option value="<?=(int)$t['id']?>" <?=$selected===(int)$t['id']?'selected':''?>><?=khFeatureEscape($t['name'])?></option><?php endforeach;?></select><button>Save business type</button></form><small>Changing business type changes feature availability; existing records are not deleted.</small></section><section><h2>My available features</h2><div class="features"><?php foreach($modules as $m):$enabled=khFeatureEnabled($m['module_key'],$businessId);?><div class="feature"><strong><?=khFeatureEscape($m['name'])?></strong><span class="<?=$enabled?'on':'off'?>"><?=$enabled?'Available':'Not included'?></span></div><?php endforeach;?></div></section><section><h2>Request additional feature</h2><form method="post"><input type="hidden" name="csrf" value="<?=khFeatureCsrf()?>"><input type="hidden" name="action" value="request"><label>Feature <select name="module" required><?php foreach($modules as $m):if($m['is_core']||khFeatureEnabled($m['module_key'],$businessId))continue;?><option value="<?=khFeatureEscape($m['module_key'])?>"><?=khFeatureEscape($m['name'])?></option><?php endforeach;?></select></label><label>Reason <textarea name="reason" maxlength="2000" placeholder="How will this feature help your business?"></textarea></label><button>Submit request</button></form></section><section><h2>Request history</h2><table><tr><th>Feature</th><th>Status</th><th>Admin response</th></tr><?php foreach($requests as $r):?><tr><td><?=khFeatureEscape($r['name'])?></td><td><?=khFeatureEscape($r['status'])?></td><td><?=khFeatureEscape($r['review_note'])?></td></tr><?php endforeach;?></table></section></main></body></html>
