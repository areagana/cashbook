<?php
/** KashHub roles: central authorization helpers. Location: cashbook/roles/bootstrap.php */
require_once __DIR__ . '/../assets/functions.php';
if (!isVerified() || !isset(auth()->id)) { http_response_code(401); exit('Authentication required.'); }

function krEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function krDb(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    global $server;
    $stmt = $server->prepare($sql);
    if (!$stmt) throw new RuntimeException($server->error);
    if ($types !== '' && !$stmt->bind_param($types, ...$params)) throw new RuntimeException($stmt->error);
    if (!$stmt->execute()) throw new RuntimeException($stmt->error);
    return $stmt;
}
function krRows(string $sql, string $types = '', array $params = []): array
{
    $stmt = krDb($sql, $types, $params);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
function krToken(): string
{
    if (empty($_SESSION['kashhub_roles_csrf'])) $_SESSION['kashhub_roles_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['kashhub_roles_csrf'];
}
function krRole(int $roleId): ?array
{
    return krRows('SELECT id,name FROM cashbook_roles WHERE id=? LIMIT 1', 'i', [$roleId])[0] ?? null;
}
function krIsSystemRole(array $role): bool
{
    return strtolower(trim($role['name'])) === 'superadministrator';
}
/** Explicit system role, or legacy role during migration (until migrated). */
function krIsSuperadmin(int $userId): bool
{
    $rows = krRows("SELECT ru.id FROM cashbook_role_user ru JOIN cashbook_roles r ON r.id=ru.role_id WHERE ru.user_id=? AND ru.business_id=0 AND LOWER(r.name)='superadministrator' LIMIT 1", 'i', [$userId]);
    if ($rows) return true;
    // Compatibility only: a legacy superadmin can bootstrap the first assignments.
    return (bool)krRows("SELECT u.id FROM cashbook_users u JOIN cashbook_roles r ON r.id=u.role_id WHERE u.id=? AND LOWER(r.name)='superadministrator' LIMIT 1", 'i', [$userId]);
}
function krOwnsBusiness(int $userId, int $businessId): bool
{
    return (bool)krRows('SELECT id FROM cashbook_business_profile WHERE id=? AND user_id=? LIMIT 1', 'ii', [$businessId,$userId]);
}
function krBusinessMember(int $userId, int $businessId): bool
{
    return krOwnsBusiness($userId,$businessId) || (bool)krRows('SELECT id FROM cashbook_users WHERE id=? AND business_id=? LIMIT 1','ii',[$userId,$businessId]);
}
function krAssigned(int $userId, int $roleId, int $businessId): bool
{
    return (bool)krRows('SELECT id FROM cashbook_role_user WHERE user_id=? AND role_id=? AND business_id=? LIMIT 1','iii',[$userId,$roleId,$businessId]);
}
/** Owner retains ownership rights even if legacy role_id is superadministrator. */
function krCanManageBusinessRoles(int $actorId, int $businessId): bool
{
    // Temporary bridge: superadministrator may manage roles in any existing business.
    if ($businessId <= 0) return false;
    if (krIsSuperadmin($actorId)) return (bool)krRows('SELECT id FROM cashbook_business_profile WHERE id=? LIMIT 1','i',[$businessId]);
    return krOwnsBusiness($actorId,$businessId);
}
function krCanManageSystemRoles(int $actorId): bool
{
    return krIsSuperadmin($actorId);
}
/** Use in modules to test a business role without losing owner privileges. */
function krHasBusinessRole(int $userId, int $businessId, string $roleName): bool
{
    if (strtolower($roleName)==='owner') return krOwnsBusiness($userId,$businessId);
    return (bool)krRows('SELECT ru.id FROM cashbook_role_user ru JOIN cashbook_roles r ON r.id=ru.role_id WHERE ru.user_id=? AND ru.business_id=? AND LOWER(r.name)=LOWER(?) LIMIT 1','iis',[$userId,$businessId,$roleName]);
}
