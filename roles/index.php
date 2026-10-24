<?php
/** KashHub roles: business member assignments + system-wide permission editor. */
require_once __DIR__ . '/../assets/functions.php';
if (!isVerified() || empty($_SESSION['auth']->id)) { http_response_code(403); exit('Login required.'); }
$actorId = (int)$_SESSION['auth']->id;
$businessId = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);
$businessId = ($businessId !== false && $businessId !== null && $businessId > 0) ? $businessId : 0;

// Check global administration against system-scoped assignments, not business role names.
function krGlobalAdmin(int $userId): bool {
    $res = prepared_statements("SELECT ru.user_id FROM cashbook_role_user ru JOIN cashbook_roles r ON r.id=ru.role_id WHERE ru.user_id=? AND ru.business_id=0 AND LOWER(r.name) IN ('superadministrator','superadmin') LIMIT 1", 'i', [$userId]);
    if ($res && $res->num_rows) return true;
    // Migration support: existing legacy administrator designation.
    $res = prepared_statements("SELECT u.id FROM cashbook_users u JOIN cashbook_roles r ON r.id=u.role_id WHERE u.id=? AND LOWER(r.name) IN ('superadministrator','superadmin') LIMIT 1", 'i', [$userId]);
    return $res && $res->num_rows > 0;
}
function krBusinessOwner(int $userId, int $businessId): bool {
    $res = prepared_statements('SELECT id FROM cashbook_business_profile WHERE id=? AND user_id=? LIMIT 1', 'ii', [$businessId,$userId]);
    return $res && $res->num_rows > 0;
}
function krHtml($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function krFetch(string $sql, string $types='', array $args=[]): array {
    $result = prepared_statements($sql, $types, $args);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
$isAdmin = krGlobalAdmin($actorId);
if ($businessId === 0 && !$isAdmin) { http_response_code(403); exit('System administrator access required.'); }
if ($businessId > 0 && !$isAdmin && !krBusinessOwner($actorId,$businessId)) { http_response_code(403); exit('Only the business owner or system administrator can manage members.'); }
if (empty($_SESSION['kh_csrf'])) $_SESSION['kh_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['kh_csrf'];
$roles = krFetch('SELECT id,name FROM cashbook_roles ORDER BY name');
$roles = array_values(array_filter($roles, function($role) use ($businessId) {
    $name = strtolower(trim($role['name']));
    return $businessId === 0 ? in_array($name,['superadministrator','superadmin'],true) : !in_array($name,['superadministrator','superadmin','owner'],true);
}));
$members = $businessId === 0 ? krFetch('SELECT id,name,email FROM cashbook_users ORDER BY name') : krFetch('SELECT id,name,email FROM cashbook_users WHERE business_id=? ORDER BY name','i',[$businessId]);
$assignments = krFetch('SELECT user_id,role_id FROM cashbook_role_user WHERE business_id=?','i',[$businessId]);
$assignedLookup=[];
foreach ($assignments as $item) $assignedLookup[(int)$item['user_id'].':'.(int)$item['role_id']] = true;
$legacyAdmins=[];
if ($businessId===0) foreach (krFetch("SELECT u.id FROM cashbook_users u JOIN cashbook_roles r ON r.id=u.role_id WHERE LOWER(r.name) IN ('superadministrator','superadmin')") as $row) $legacyAdmins[(int)$row['id']]=true;
$permissionRoles = $isAdmin ? krFetch('SELECT id,name FROM cashbook_roles ORDER BY name') : [];
$selectedRole = (int)($_GET['role_id'] ?? ($permissionRoles[0]['id'] ?? 0));
if ($isAdmin && $permissionRoles && !in_array($selectedRole,array_map(fn($r)=>(int)$r['id'],$permissionRoles),true)) $selectedRole=(int)$permissionRoles[0]['id'];
$permissions = $isAdmin ? krFetch('SELECT id,permission_key,module,description FROM cashbook_permissions ORDER BY module,permission_key') : [];
$selectedPermissions = $isAdmin && $selectedRole ? krFetch('SELECT permission_id FROM cashbook_role_permissions WHERE role_id=?','i',[$selectedRole]) : [];
$checked = array_fill_keys(array_map(fn($r)=>(int)$r['permission_id'],$selectedPermissions),true);
pageHeader('Roles & Access');
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div><h3 class="mb-1"><i class="fa fa-user-shield text-success mr-2"></i> Roles & Access</h3><p class="text-muted mb-0"><?= $businessId ? 'Assign roles to members of this business.' : 'Manage system administrator assignments and role permissions.' ?></p></div>
        <a class="btn btn-outline-secondary btn-flat" href="../"><i class="fa fa-arrow-left mr-1"></i> Back</a>
    </div>
    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Changes saved. Affected users should sign in again to refresh access.</div><?php endif; ?>
    <div id="roleAlert" role="status" aria-live="polite"></div>
    <div class="card shadow-sm mb-4">
        <div class="card-header"><strong><i class="fa fa-users mr-1"></i> <?= $businessId ? 'Business Member Assignments' : 'System Administrator Assignments' ?></strong></div>
        <div class="card-body">
            <p class="text-muted small"><?= $businessId ? 'Owner status is determined by the business profile and cannot be assigned here.' : 'Superadministrator is system-wide. Existing legacy administrators are shown for migration.' ?></p>
            <div class="table-responsive"><table class="table table-bordered table-hover table-sm" style="min-width:650px">
                <thead class="thead-light"><tr><th>Member</th><?php foreach($roles as $role): ?><th class="text-center"><?=krHtml(ucwords(str_replace('_',' ',$role['name'])))?></th><?php endforeach; ?></tr></thead>
                <tbody><?php foreach($members as $member): ?><tr><td><strong><?=krHtml($member['name'])?></strong><small class="text-muted d-block"><?=krHtml($member['email'])?><?php if(isset($legacyAdmins[(int)$member['id']])): ?> · Legacy administrator<?php endif; ?></small></td>
                <?php foreach($roles as $role): $assigned=isset($assignedLookup[(int)$member['id'].':'.(int)$role['id']]); ?>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-flat <?= $assigned?'btn-outline-danger':'btn-outline-success' ?> role-change" data-user="<?=(int)$member['id']?>" data-role="<?=(int)$role['id']?>" data-operation="<?=$assigned?'revoke':'assign'?>"><?= $assigned?'Revoke':'Assign' ?></button><?php if (!$assigned && isset($legacyAdmins[(int)$member['id']])): ?><small class="text-muted d-block">Legacy access active</small><?php endif; ?></td>
                <?php endforeach; ?></tr><?php endforeach; ?>
                <?php if(!$members): ?><tr><td colspan="<?=count($roles)+1?>" class="text-center text-muted p-4">No members found.</td></tr><?php endif; ?></tbody>
            </table></div>
        </div>
    </div>
    <?php if ($isAdmin): ?>
    <div class="card shadow-sm mb-4" id="role-permissions">
        <div class="card-header"><strong><i class="fa fa-lock mr-1"></i> Role Permission Definitions</strong></div>
        <div class="card-body">
            <div class="alert alert-info">These permission definitions are shared across all businesses. Only system administrators can edit them.</div>
            <form method="get" class="form-inline mb-3"><input type="hidden" name="business_id" value="<?=$businessId?>"><label for="roleSelect" class="mr-2">Role</label><select class="form-control mr-2" id="roleSelect" name="role_id" onchange="this.form.submit()">
                <?php foreach($permissionRoles as $role): ?><option value="<?=(int)$role['id']?>" <?=(int)$role['id']===$selectedRole?'selected':''?>><?=krHtml(ucwords(str_replace('_',' ',$role['name'])))?></option><?php endforeach; ?>
            </select><noscript><button class="btn btn-secondary btn-flat">Load</button></noscript></form>
            <?php if (!$permissions): ?><div class="alert alert-warning">Permission definitions are missing. Initialize the RBAC tables before editing permissions.</div><?php endif; ?>
            <form method="post" action="save/index.php">
                <input type="hidden" name="csrf" value="<?=krHtml($csrf)?>"><input type="hidden" name="action" value="permissions"><input type="hidden" name="business_id" value="<?=$businessId?>"><input type="hidden" name="role_id" value="<?=$selectedRole?>">
                <div class="row"><?php foreach($permissions as $permission): ?><div class="col-lg-4 col-md-6 mb-3"><label class="d-flex align-items-start"><input class="mt-1 mr-2" type="checkbox" name="permission_ids[]" value="<?=(int)$permission['id']?>" <?=isset($checked[(int)$permission['id']])?'checked':''?>><span><strong><?=krHtml($permission['description'])?></strong><small class="text-muted d-block"><?=krHtml($permission['permission_key'])?></small></span></label></div><?php endforeach; ?></div>
                <div class="text-right"><button type="submit" class="btn btn-success btn-flat" <?=(!$selectedRole || !$permissions)?'disabled':''?>><i class="fa fa-save mr-1"></i> Save Role Permissions</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
(() => {
    const alertBox=document.getElementById('roleAlert');
    document.querySelectorAll('.role-change').forEach(button => button.addEventListener('click',async () => {
        const operation=button.dataset.operation;
        if(!confirm(`${operation==='revoke'?'Revoke':'Assign'} this role for the selected member?`)) return;
        const payload=new URLSearchParams({action:'assignment',csrf:<?=json_encode($csrf)?>,business_id:<?=json_encode($businessId)?>,user_id:button.dataset.user,role_id:button.dataset.role,operation});
        button.disabled=true;
        try {
            const response=await fetch('save/index.php',{method:'POST',body:payload,credentials:'same-origin',headers:{'Accept':'application/json'}});
            const raw=await response.text();
            let result;
            try { result=JSON.parse(raw); }
            catch (_) {
                const pageTitle=(raw.match(/<title[^>]*>([^<]*)<\/title>/i)||[])[1];
                throw new Error(`Role endpoint returned HTML (HTTP ${response.status})${pageTitle ? ': '+pageTitle : ''}. Check that roles/save/index.php is deployed and inspect the server PHP error log.`);
            }
            if(!response.ok || !result.status) throw new Error(result.message || 'Unable to update role.');
            window.location.reload();
        } catch(error) { alertBox.className='alert alert-danger';alertBox.textContent=error.message;button.disabled=false; }
    }));
})();
</script>
<?php pageFooter(); ?>
