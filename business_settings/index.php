<?php
require_once __DIR__.'/../assets/functions.php';require_once __DIR__.'/bootstrap.php';
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
$suggestions=khFeatureSuggestionReady()?khFeatureQuery('SELECT * FROM cashbook_business_feature_suggestions WHERE business_id=? ORDER BY id DESC LIMIT 30','i',[$businessId]):[];
$requests=khFeatureQuery('SELECT r.*,m.name FROM cashbook_business_module_requests r JOIN cashbook_modules m ON m.module_key=r.module_key WHERE r.business_id=? ORDER BY r.id DESC LIMIT 30','i',[$businessId]);
?>
<?php pageHeader('Business Settings'); ?>
<div class="container-fluid py-4 kh-business-settings">
 <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div><nav aria-label="breadcrumb"><ol class="breadcrumb bg-transparent p-0 mb-2"><li class="breadcrumb-item">Business Management</li><li class="breadcrumb-item active">Business Settings</li></ol></nav><h2 class="mb-1">Business Settings</h2><p class="text-muted">Choose your business nature and explore features allocated by KashHub.</p></div>
  <a class="btn btn-outline-secondary" href="../" onclick="if(history.length>1){event.preventDefault();history.back();}"><i class="fas fa-arrow-left mr-1"></i> Back</a>
 </div>
 <?php if($error):?><div class="alert alert-danger"><?=khFeatureEscape($error)?></div><?php endif;?>
 <?php if($success):?><div class="alert alert-success"><?=khFeatureEscape($success)?></div><?php endif;?>
 <div class="card mb-4"><div class="card-body"><h5>Nature of business</h5><form method="post" class="form-row align-items-end"><input type="hidden" name="csrf" value="<?=khFeatureEscape(khFeatureCsrf())?>"><input type="hidden" name="action" value="select_type"><div class="form-group col-md-8"><label>Business type</label><select class="form-control" name="type_id" required><option value="">Select business type</option><?php foreach($types as $t):?><option value="<?=(int)$t['id']?>" <?=$selected===(int)$t['id']?'selected':''?>><?=khFeatureEscape($t['name'])?></option><?php endforeach;?></select></div><div class="form-group col-md-4"><button class="btn btn-success btn-block">Save business type</button></div></form><small class="text-muted">Changing the business type changes available features; your records are not deleted.</small></div></div>
 <div class="card mb-4"><div class="card-body"><h5>My available features</h5><div class="row"><?php foreach($modules as $m):$enabled=khFeatureEnabled($m['module_key'],$businessId);?><div class="col-md-6 col-lg-4 mb-2"><div class="kh-feature-tile"><strong><?=khFeatureEscape($m['name'])?></strong><span class="badge <?=$enabled?'badge-success':'badge-secondary'?>"><?=$enabled?'Available':'Not included'?></span></div></div><?php endforeach;?></div></div></div>
 <div class="card mb-4"><div class="card-body"><h5>Request an additional feature</h5><p class="text-muted small">Choose an existing KashHub feature, or suggest a feature that is not yet available.</p>
 <form method="post" id="khRequestForm"><input type="hidden" name="csrf" value="<?=khFeatureEscape(khFeatureCsrf())?>"><input type="hidden" name="action" id="khRequestAction" value="request"><div class="form-group"><label for="khFeatureChoice">Feature to request</label><select class="form-control" id="khFeatureChoice" name="module" required><option value="">Select a feature</option><?php foreach($modules as $m):if($m['is_core']||khFeatureEnabled($m['module_key'],$businessId))continue;?><option value="<?=khFeatureEscape($m['module_key'])?>"><?=khFeatureEscape($m['name'])?></option><?php endforeach;?><option value="__other__">Other — suggest a new feature not listed</option></select></div>
 <div id="khExistingRequest"><div class="form-group"><label for="khReason">How will it help your business?</label><textarea class="form-control" id="khReason" name="reason" maxlength="2000" rows="3" placeholder="Explain how you would use this module"></textarea></div></div>
 <div id="khNewRequest" hidden><div class="form-group"><label for="khFeatureName">Name of proposed feature</label><input class="form-control" id="khFeatureName" name="feature_name" maxlength="120" placeholder="e.g. Delivery tracking"></div><div class="form-group"><label for="khDetails">Describe the feature and why you need it</label><textarea class="form-control" id="khDetails" name="details" maxlength="2000" rows="4" placeholder="What should it do? Who will use it?"></textarea></div><p class="small text-muted">New feature suggestions are reviewed by the KashHub team. Submission does not automatically activate a feature.</p></div>
 <button class="btn btn-success" type="submit">Submit feature request</button></form></div></div>
 <div class="card mb-4"><div class="card-body"><h5>Existing module requests</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Feature</th><th>Status</th><th>Admin response</th></tr></thead><tbody><?php foreach($requests as $r):?><tr><td><?=khFeatureEscape($r['name'])?></td><td><?=khFeatureEscape($r['status'])?></td><td><?=khFeatureEscape($r['review_note']??'')?></td></tr><?php endforeach;?><?php if(!$requests):?><tr><td colspan="3" class="text-muted">No requests yet.</td></tr><?php endif;?></tbody></table></div></div></div>
 <div class="card"><div class="card-body"><h5>New feature suggestions</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Proposed feature</th><th>Status</th><th>Admin response</th></tr></thead><tbody><?php foreach($suggestions as $r):?><tr><td><?=khFeatureEscape($r['feature_name'])?></td><td><?=khFeatureEscape($r['status'])?></td><td><?=khFeatureEscape($r['review_note']??'')?></td></tr><?php endforeach;?><?php if(!$suggestions):?><tr><td colspan="3" class="text-muted">No suggestions yet.</td></tr><?php endif;?></tbody></table></div></div></div>
</div>
<script>(function(){const select=document.getElementById('khFeatureChoice'),action=document.getElementById('khRequestAction'),old=document.getElementById('khExistingRequest'),newBox=document.getElementById('khNewRequest'),name=document.getElementById('khFeatureName'),details=document.getElementById('khDetails');function sync(){const other=select.value==='__other__';action.value=other?'suggest':'request';old.hidden=other;newBox.hidden=!other;name.required=other;details.required=other;select.name=other?'unused_module':'module';}select.addEventListener('change',sync);sync();})();</script>
<?php pageFooter(); ?>
