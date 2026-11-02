<?php
/**
 * KashHub - one-time recovery of missing business-scoped owner roles.
 * Run from CLI: php kashhub_repair_existing_owners.php          (preview)
 *               php kashhub_repair_existing_owners.php --apply  (repair)
 * Place in cashbook/maintenance/ and adjust the require path if necessary.
 * Never grants ownership based on cashbook_users.role_id alone.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__ . '/assets/functions.php'; // Put this script in the cashbook root.

$apply = in_array('--apply', $argv, true);
$ownerRole = prepared_statements(
    "SELECT id FROM cashbook_roles WHERE LOWER(TRIM(name)) = 'owner' LIMIT 1"
);
$ownerRoleId = (int)($ownerRole->fetch_assoc()['id'] ?? 0);
if ($ownerRoleId <= 0) {
    fwrite(STDERR, "Owner role not found in cashbook_roles. No changes made.\n");
    exit(1);
}

$owners = prepared_statements(
    'SELECT bp.id AS business_id, bp.user_id, u.email
     FROM cashbook_business_profile bp
     INNER JOIN cashbook_users u ON u.id = bp.user_id
     WHERE bp.user_id IS NOT NULL AND bp.user_id > 0
     ORDER BY bp.id'
);

$missing = [];
while ($owner = $owners->fetch_assoc()) {
    $userId = (int)$owner['user_id'];
    $businessId = (int)$owner['business_id'];
    $check = prepared_statements(
        'SELECT ru.id FROM cashbook_role_user ru
         INNER JOIN cashbook_roles r ON r.id = ru.role_id
         WHERE ru.user_id = ? AND ru.business_id = ?
           AND LOWER(TRIM(r.name)) = ? LIMIT 1',
        'iis', [$userId, $businessId, 'owner']
    );
    if ($check->num_rows === 0) {
        $missing[] = ['user_id' => $userId, 'business_id' => $businessId, 'email' => $owner['email']];
    }
}

printf("Mode: %s | Missing owner assignments: %d\n", $apply ? 'APPLY' : 'PREVIEW', count($missing));
foreach ($missing as $row) {
    printf("User %d (%s) => business %d\n", $row['user_id'], $row['email'], $row['business_id']);
}

if (!$apply || !$missing) {
    echo $apply ? "No repairs needed.\n" : "Preview only. Re-run with --apply after reviewing.\n";
    exit;
}

global $server;
$server->begin_transaction();
try {
    $updated = 0;
    foreach ($missing as $row) {
        // Repeat the check within the transaction for safe re-runs.
        $check = prepared_statements(
            'SELECT id FROM cashbook_role_user WHERE user_id=? AND business_id=? AND role_id=? LIMIT 1',
            'iii', [$row['user_id'], $row['business_id'], $ownerRoleId]
        );
        if ($check->num_rows) continue;
        prepared_statements(
            'INSERT INTO cashbook_role_user (user_id, role_id, business_id) VALUES (?, ?, ?)',
            'iii', [$row['user_id'], $ownerRoleId, $row['business_id']]
        );
        $updated++;
    }
    $server->commit();
    printf("Repaired %d business-owner role assignments. Users should log out and log back in.\n", $updated);
} catch (Throwable $error) {
    $server->rollback();
    error_log('KashHub owner role recovery failed: ' . $error->getMessage());
    fwrite(STDERR, "Recovery failed; transaction rolled back. Check server error log.\n");
    exit(1);
}
