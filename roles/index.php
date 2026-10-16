<?php
require_once __DIR__.'/bootstrap.php';
$actorId=(int)auth()->id;
$businessId=(int)($_GET['business_id']??0);
$systemView=$businessId===0;
$authorized=$systemView?krCanManageSystemRoles($actorId):krCanManageBusinessRoles($actorId,$businessId);
if (!$authorized) { http_response_code(403); exit('Only the business owner or system administrator may manage these roles.'); }
$roles=krRows('SELECT id,name FROM cashbook_roles ORDER BY name');
if ($systemView) {
    $members=krRows('SELECT id,name FROM cashbook_users ORDER BY name');
    $roles=array_values(array_filter($roles,fn($r)=>krIsSystemRole($r)));
    $heading='System Administrator Assignment';
} else {
    $members=krRows('SELECT id,name FROM cashbook_users WHERE business_id=? ORDER BY name','i',[$businessId]);
    $roles=array_values(array_filter($roles,fn($r)=>!krIsSystemRole($r) && strtolower($r['name'])!=='owner'));
    $heading='Business Member Roles';
}
$assignments=krRows('SELECT user_id,role_id FROM cashbook_role_user WHERE business_id=?','i',[$businessId]);
$legacyAdmins=[];
if ($systemView) {
    foreach (krRows("SELECT u.id FROM cashbook_users u JOIN cashbook_roles r ON r.id=u.role_id WHERE LOWER(r.name)='superadministrator'") as $admin) {
        $legacyAdmins[(int)$admin['id']]=true;
    }
}
$lookup=[];
foreach ($assignments as $assignment) $lookup[$assignment['user_id'].':'.$assignment['role_id']]=true;
pageHeader('Role Management');
?>
<div class="container-fluid py-4">
 <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
  <div><h3 class="mb-1"><i class="fa fa-user-shield text-success mr-2"></i><?=krEscape($heading)?></h3><p class="text-muted mb-0">Role assignments are stored in cashbook_role_user. Business ownership remains independent.</p></div>
  <a href="../" class="btn btn-outline-secondary btn-flat">Back</a>
 </div>
 <?php if ($systemView): ?><div class="alert alert-warning">Select a member and click Assign under Superadministrator to grant system-wide access. Existing legacy administrators remain active. Revoke an assignment to remove newly granted access; legacy administrators must be migrated before their original role can be revoked.</div><?php else: ?><div class="alert alert-info">Only members of this business appear here. Business owners and Superadministrators can manage assignments during this transition. Ownership remains tied to the business profile.</div><?php endif; ?>
 <div id="roleAlert" role="status"></div>
 <div class="card shadow-sm"><div class="card-body table-responsive">
  <table class="table table-bordered table-hover table-sm" style="min-width:650px">
   <thead class="thead-light"><tr><th>Member</th><?php foreach($roles as $role): ?><th class="text-center"><?=krEscape($role['name'])?></th><?php endforeach; ?></tr></thead>
   <tbody><?php foreach($members as $member): ?><tr><td><strong><?=krEscape($member['name'])?></strong><small class="text-muted d-block">User #<?=(int)$member['id']?><?php if ($systemView && isset($legacyAdmins[(int)$member['id']])): ?> · Legacy Superadministrator<?php endif; ?></small></td>
    <?php foreach($roles as $role): $assigned=isset($lookup[$member['id'].':'.$role['id']]); ?>
    <td class="text-center"><button type="button" class="btn btn-sm btn-flat <?=$assigned?'btn-outline-danger':'btn-outline-success'?> role-change" data-user="<?=(int)$member['id']?>" data-role="<?=(int)$role['id']?>" data-operation="<?=$assigned?'revoke':'assign'?>"><?=$assigned?'Revoke':'Assign'?></button><?php if ($systemView && isset($legacyAdmins[(int)$member['id']]) && !$assigned): ?><small class="d-block text-muted">Already active (legacy)</small><?php endif; ?></td>
    <?php endforeach; ?></tr><?php endforeach; ?>
   <?php if (!$members): ?><tr><td colspan="<?=count($roles)+1?>" class="text-center text-muted py-4">No members found.</td></tr><?php endif; ?></tbody>
  </table>
 </div></div>
</div>
<script>
document.querySelectorAll('.role-change').forEach(button => button.addEventListener('click', async () => {
 const operation=button.dataset.operation;
 if(!confirm((operation==='revoke'?'Revoke':'Assign')+' this role for the selected user?')) return;
 const data=new URLSearchParams({csrf:<?=json_encode(krToken())?>,business_id:<?=json_encode($businessId)?>,user_id:button.dataset.user,role_id:button.dataset.role,operation});
 button.disabled=true;
 try {
  const response=await fetch('save/index.php',{method:'POST',body:data,credentials:'same-origin'});
  const result=await response.json();
  if(!response.ok || !result.status) throw new Error(result.message || 'Update failed.');
  window.location.reload();
 } catch(error) { document.getElementById('roleAlert').textContent=error.message; document.getElementById('roleAlert').className='alert alert-danger'; button.disabled=false; }
}));
</script>
<?php pageFooter(); ?>
