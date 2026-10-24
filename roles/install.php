<?php
require_once __DIR__.'/../assets/functions.php';
if (!isVerified() || !canManageSystemRolesNav()) { http_response_code(403); exit('System administrator access required'); }
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit('POST required'); }
if (empty($_SESSION['kh_install_csrf']) || !hash_equals($_SESSION['kh_install_csrf'],(string)($_POST['csrf']??''))) { http_response_code(403); exit('Invalid token'); }
try { khCreatePermissionTables(); echo 'RBAC tables created and permissions seeded. Remove install.php after installation.'; }
catch(Throwable $e) { error_log($e->getMessage()); http_response_code(500); echo 'Installation failed. Check server logs.'; }
