<?php
/** Load AFTER KashHub functions.php. Schema installation is separate. */
function khFeatureUserId(): int { return (int)($_SESSION['auth']->id ?? 0); }
function khFeatureSuperAdmin(): bool { return function_exists('khIsSuperAdmin') && khIsSuperAdmin(); }
function khFeatureOwner(int $businessId): bool {
    if ($businessId < 1 || khFeatureUserId() < 1) return false;
    $r=prepared_statements('SELECT id FROM cashbook_business_profile WHERE id=? AND user_id=? LIMIT 1','ii',[$businessId,khFeatureUserId()]);
    return $r && $r->num_rows > 0;
}
function khFeatureBusinessId(): int {
    $uid=khFeatureUserId(); if (!$uid) return 0;
    $r=prepared_statements('SELECT id FROM cashbook_business_profile WHERE user_id=? LIMIT 1','i',[$uid]);
    if ($r && ($x=$r->fetch_assoc())) return (int)$x['id'];
    $r=prepared_statements('SELECT business_id FROM cashbook_users WHERE id=? LIMIT 1','i',[$uid]);
    return (int)(($r ? $r->fetch_assoc() : null)['business_id'] ?? 0);
}
function khFeatureRequireAdmin(): void { if (!khFeatureSuperAdmin()) {http_response_code(403);exit('Superadministrator access required');} }
function khFeatureRequireOwner(int $businessId): void { if (!khFeatureOwner($businessId)) {http_response_code(403);exit('Business owner access required');} }
function khFeatureQuery(string $sql,string $types='',array $args=[]): array {
    $r=prepared_statements($sql,$types,$args); return $r instanceof mysqli_result ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
function khFeatureCsrf(): string { if (empty($_SESSION['kh_feature_csrf'])) $_SESSION['kh_feature_csrf']=bin2hex(random_bytes(24)); return $_SESSION['kh_feature_csrf']; }
function khFeatureCheckCsrf(): void { if (!hash_equals(khFeatureCsrf(),(string)($_POST['csrf'] ?? ''))) {http_response_code(419);exit('Invalid form token');} }
function khFeatureTableReady(): bool {
    global $server;
    $r=$server->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='cashbook_business_types' LIMIT 1");
    return $r && $r->num_rows>0;
}
function khFeatureEnabled(string $module,int $businessId): bool {
    if ($businessId<=0 || !preg_match('/^[a-z0-9_]+$/',$module)) return false;
    // Safe migration policy: existing businesses without a type retain their legacy modules.
    if (!khFeatureTableReady()) return true;
    $rows=khFeatureQuery('SELECT b.business_type_id, o.enabled AS override_enabled, tm.enabled AS type_enabled, m.is_core FROM cashbook_business_profile b LEFT JOIN cashbook_business_module_overrides o ON o.business_id=b.id AND o.module_key=? LEFT JOIN cashbook_business_type_modules tm ON tm.business_type_id=b.business_type_id AND tm.module_key=? LEFT JOIN cashbook_modules m ON m.module_key=? WHERE b.id=? LIMIT 1','sssi',[$module,$module,$module,$businessId]);
    if (!$rows || $rows[0]['is_core']===null) return false;
    $r=$rows[0]; if ((int)$r['is_core']===1) return true;
    if (empty($r['business_type_id'])) return true;
    if ($r['override_enabled']!==null) return (bool)$r['override_enabled'];
    return (bool)$r['type_enabled'];
}
function khRequireFeature(string $module,int $businessId): void {
    if (!khFeatureEnabled($module,$businessId)) {http_response_code(403);exit('This feature is unavailable for this business');}
}
function khFeatureEscape($s): string {return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function khFeatureAudit(string $action,int $businessId=0,string $detail=''): void {
    prepared_statements('INSERT INTO cashbook_feature_audit (actor_id,business_id,action,details) VALUES (?,?,?,?)','iiss',[khFeatureUserId(),$businessId,$action,$detail]);
}

/** Check optional feature-suggestions table before querying it. */
function khFeatureSuggestionReady(): bool {
    global $server;
    $r=$server->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='cashbook_business_feature_suggestions' LIMIT 1");
    return $r && $r->num_rows > 0;
}
