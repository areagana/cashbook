<?php
/** KashHub optional module bootstrap. Include after your existing functions.php. */
if (!function_exists('khBusinessSettingsModules')) {
function khBusinessSettingsModules(): array {
    return [
        'routes'=>['label'=>'Routes & Distribution','description'=>'Route allocation, managers and statements','requires'=>['customers']],
        'stock'=>['label'=>'Stock Management','description'=>'Inventory, stocktaking and daily sessions','requires'=>[]],
        'customers'=>['label'=>'Customer Accounts','description'=>'Customer records, balances and statements','requires'=>[]],
        'creditors'=>['label'=>'Suppliers & Creditors','description'=>'Creditors, purchases and payables','requires'=>[]],
        'invoices'=>['label'=>'Sales & Invoicing','description'=>'Invoices, invoice returns and payments','requires'=>['customers']],
    ];
}
function khBusinessSettingsProfiles(): array {
    return [
        'general'=>['label'=>'General Business','modules'=>['customers','creditors','invoices','stock']],
        'retail'=>['label'=>'Retail Shop','modules'=>['customers','stock','invoices','creditors']],
        'distribution'=>['label'=>'Wholesale / Distribution','modules'=>['customers','stock','invoices','creditors','routes']],
        'restaurant'=>['label'=>'Restaurant','modules'=>['stock','creditors']],
        'services'=>['label'=>'Service Business','modules'=>['customers','invoices']],
        'hardware'=>['label'=>'Hardware Store','modules'=>['customers','stock','creditors','invoices']],
    ];
}
function khSettingsBusinessId(): int {
    // Never trust business IDs supplied by query strings or form fields.
    $id = (int)($_SESSION['business_id'] ?? 0);
    if ($id > 0) return $id;
    $userId = (int)($_SESSION['auth']->id ?? 0);
    if (!$userId) return 0;
    $stmt = prepared_statements('SELECT id FROM cashbook_business_profile WHERE user_id=? LIMIT 1','i',[$userId]);
    if ($stmt && ($row=$stmt->fetch_assoc())) return (int)$row['id'];
    $stmt = prepared_statements('SELECT business_id FROM cashbook_users WHERE id=? LIMIT 1','i',[$userId]);
    $row = $stmt ? $stmt->fetch_assoc() : null;
    return (int)($row['business_id'] ?? 0);
}
function khSettingsIsOwner(int $businessId): bool {
    $userId=(int)($_SESSION['auth']->id ?? 0);
    if (!$userId || $businessId <= 0) return false;
    $res=prepared_statements('SELECT id FROM cashbook_business_profile WHERE id=? AND user_id=? LIMIT 1','ii',[$businessId,$userId]);
    return $res && $res->num_rows > 0;
}
function khSettingsTableReady(): bool {
    global $server;
    $r=$server->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='cashbook_business_settings' LIMIT 1");
    return $r && $r->num_rows>0;
}
function khSettingsGet(int $businessId): array {
    $defaults=array_fill_keys(array_keys(khBusinessSettingsModules()),true);
    if ($businessId <= 0 || !khSettingsTableReady()) return $defaults; // legacy businesses remain unchanged until configured
    $res=prepared_statements('SELECT setting_key, setting_value FROM cashbook_business_settings WHERE business_id=?','i',[$businessId]);
    while ($row=$res->fetch_assoc()) {
        if (str_starts_with($row['setting_key'],'module.')) {
            $key=substr($row['setting_key'],7);
            if (array_key_exists($key,$defaults)) $defaults[$key]=($row['setting_value']==='1');
        }
    }
    return $defaults;
}
function khModuleEnabled(string $module, ?int $businessId=null): bool {
    if (!isset(khBusinessSettingsModules()[$module])) return false;
    $businessId=$businessId ?? khSettingsBusinessId();
    if ($businessId <= 0) return false;
    $settings=khSettingsGet($businessId);
    if (!$settings[$module]) return false;
    foreach (khBusinessSettingsModules()[$module]['requires'] as $dependency) {
        if (!$settings[$dependency]) return false;
    }
    return true;
}
function khRequireModule(string $module, ?int $businessId=null): void {
    if (!khModuleEnabled($module,$businessId)) {
        http_response_code(403);
        exit('This module is not enabled for this business. Contact the business owner.');
    }
}
function khSettingsSave(int $businessId, array $requested, string $profile='custom'): void {
    global $server;
    if (!khSettingsIsOwner($businessId)) throw new RuntimeException('Only the registered business owner may change these settings.');
    if (!khSettingsTableReady()) throw new RuntimeException('Run the settings installer first.');
    $modules=khBusinessSettingsModules();
    $values=[];
    foreach ($modules as $key=>$details) $values[$key]=isset($requested[$key]) && $requested[$key]==='1';
    foreach ($modules as $key=>$details) {
        if (!$values[$key]) continue;
        foreach ($details['requires'] as $dep) {
            if (!$values[$dep]) throw new InvalidArgumentException("Enable {$modules[$dep]['label']} before enabling {$details['label']}.");
        }
    }
    $server->begin_transaction();
    try {
        $sql='INSERT INTO cashbook_business_settings (business_id,setting_key,setting_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)';
        foreach ($values as $key=>$value) prepared_statements($sql,'iss',[$businessId,'module.'.$key,$value?'1':'0']);
        prepared_statements($sql,'iss',[$businessId,'business.profile',$profile]);
        $server->commit();
    } catch (Throwable $e) { $server->rollback(); throw $e; }
}
}
