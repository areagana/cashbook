<?php
require_once __DIR__.'/../assets/functions.php';
if (!isVerified() || !canManageSystemRolesNav()) { 
    http_response_code(403); exit('System administrator access required'); 
}
$_SESSION['kh_install_csrf']=bin2hex(random_bytes(32));
pageHeader('Install Permissions');
?>
<div class="container p-4"><h3>Install KashHub permissions</h3><p>This creates two permission tables and seeds permission definitions. It does not automatically grant roles access.</p><form method="post" action="install.php"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['kh_install_csrf'],ENT_QUOTES)?>"><button class="btn btn-success">Create tables</button></form></div>
<?php pageFooter(); ?>
