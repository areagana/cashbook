<?php
require_once __DIR__.'/../assets/functions.php'; // Change this path only if your functions.php is elsewhere.
require_once __DIR__.'/bootstrap.php';
if (empty($_SESSION['auth']->id) || (function_exists('isVerified') && !isVerified())) { http_response_code(403); exit('Please log in.'); }
$businessId=khSettingsBusinessId();
if (!$businessId || !khSettingsIsOwner($businessId)) { http_response_code(403); exit('Business owner access required.'); }
$modules=khBusinessSettingsModules();
$profiles=khBusinessSettingsProfiles();
$error=''; $success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals((string)($_SESSION['kh_settings_csrf']??''),(string)($_POST['csrf']??''))) {
        $error='Invalid security token. Refresh and try again.';
    } else {
        try {
            $profile=(string)($_POST['profile']??'custom');
            if (!isset($profiles[$profile]) && $profile!=='custom') throw new InvalidArgumentException('Invalid business profile.');
            khSettingsSave($businessId,(array)($_POST['modules']??[]),$profile);
            $success='Business settings saved successfully.';
        } catch (Throwable $e) { $error=$e->getMessage(); }
    }
}
$_SESSION['kh_settings_csrf']=bin2hex(random_bytes(24));
$enabled=khSettingsGet($businessId);
$profile='custom';
if (khSettingsTableReady()) {
    $r=prepared_statements("SELECT setting_value FROM cashbook_business_settings WHERE business_id=? AND setting_key='business.profile' LIMIT 1",'i',[$businessId]);
    $profile=($r->fetch_assoc()['setting_value']??'custom');
}
function khSettingsEsc(string $value): string { return htmlspecialchars($value,ENT_QUOTES,'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Business Settings | KashHub</title><link rel="stylesheet" href="assets/settings.css"></head>
<body><main class="wrap"><a href="../" class="back">← Back to KashHub</a><header><h1>Business Settings</h1><p>Configure the modules your business uses. Turning off a module hides access without deleting its records.</p></header>
<?php if ($error): ?><div class="alert error"><?=khSettingsEsc($error)?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?=khSettingsEsc($success)?></div><?php endif; ?>
<?php if (!khSettingsTableReady()): ?><div class="alert error">Settings table not installed. Run the maintenance installer before saving.</div><?php endif; ?>
<form method="post" id="settingsForm"><input type="hidden" name="csrf" value="<?=khSettingsEsc($_SESSION['kh_settings_csrf'])?>">
<section class="panel"><label for="profile"><strong>Business type</strong></label><p class="muted">Choose a preset or customise modules individually.</p><select name="profile" id="profile"><option value="custom">Custom configuration</option><?php foreach($profiles as $key=>$data): ?><option value="<?=khSettingsEsc($key)?>" <?=($profile===$key?'selected':'')?>><?=khSettingsEsc($data['label'])?></option><?php endforeach; ?></select></section>
<section class="panel"><h2>Available modules</h2><?php foreach($modules as $key=>$data): ?><label class="module"><span><strong><?=khSettingsEsc($data['label'])?></strong><small><?=khSettingsEsc($data['description'])?><?php if ($data['requires']): ?> · Requires <?=khSettingsEsc(implode(', ',$data['requires']))?><?php endif; ?></small></span><input type="checkbox" name="modules[<?=khSettingsEsc($key)?>]" value="1" data-module="<?=khSettingsEsc($key)?>" <?=($enabled[$key]?'checked':'')?>></label><?php endforeach; ?></section>
<div class="actions"><button type="submit" <?=(!khSettingsTableReady()?'disabled':'')?>>Save business settings</button></div></form></main>
<script>const presets=<?=json_encode(array_map(fn($p)=>$p['modules'],$profiles),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;const select=document.getElementById('profile');const checks=[...document.querySelectorAll('[data-module]')];select.addEventListener('change',()=>{if(select.value==='custom')return;checks.forEach(c=>c.checked=presets[select.value].includes(c.dataset.module));});checks.forEach(c=>c.addEventListener('change',()=>{select.value='custom';}));</script></body></html>
