<?php
/** KashHub RBAC. Include via assets/functions.php. Default-deny on missing sessions. */
function khPermissionCatalog() { return [
        'dashboard.view' => 'Dashboard',
        'home.view' => 'Home',
        'customers.view' => 'Customers',
        'creditors.view' => 'Creditors',
        'stock.view' => 'Stock overview',
        'stocktake.view' => 'Stocktaking',
        'items.view' => 'Items',
        'purchases.view' => 'Purchases',
        'users.view' => 'Users',
        'roles.manage' => 'Roles & access',
        'categories.view' => 'Categories',
        'paymodes.view' => 'Payment modes',
        'invoices.view' => 'Invoices',
        'reports.view' => 'Reports',
        'routes.view' => 'Routes',
        'route_managers.view' => 'Route managers',
        'books.view' => 'Books',
        'books.create' => 'Create books',
        'business.edit' => 'Edit business profile',
        'transactions.view_own' => 'View own transactions',
        'transactions.view_all' => 'View all transactions in assigned books',
        'transactions.summary' => 'View full book financial balances',
        'transactions.create' => 'Create transactions',
        'transactions.edit' => 'Edit transactions',
        'transactions.delete' => 'Delete transactions',
        'stocktake.delete' => 'Delete stocktakes',
        'reports.export' => 'Export reports',
        'system.admin' => 'System administration',
    ]; }
function khRoleNames() { return array_values(array_unique(array_map('strtolower', array_map('trim', $_SESSION['role_names'] ?? [])))); }

/** Only system-scoped assignments (business_id=0) confer superadmin privileges. */
function khIsSuperAdmin() {
    if (!isVerified() || empty($_SESSION['auth']->id)) return false;
    $userId = (int)$_SESSION['auth']->id;
    static $cached = [];
    if (isset($cached[$userId])) return $cached[$userId];
    $res = prepared_statements("SELECT ru.id FROM cashbook_role_user ru JOIN cashbook_roles r ON r.id=ru.role_id WHERE ru.user_id=? AND ru.business_id=0 AND LOWER(r.name) IN ('superadministrator','superadmin') LIMIT 1", 'i', [$userId]);
    if ($res && $res->num_rows) return $cached[$userId] = true;
    // Existing system administrators using the original cashbook_users.role_id.
    $legacy = prepared_statements("SELECT u.id FROM cashbook_users u JOIN cashbook_roles r ON r.id=u.role_id WHERE u.id=? AND LOWER(r.name) IN ('superadministrator','superadmin') LIMIT 1", 'i', [$userId]);
    return $cached[$userId] = (bool)($legacy && $legacy->num_rows);
}

function khOwnsBusiness($businessId) {
    $businessId = (int)$businessId;
    $userId = (int)($_SESSION['auth']->id ?? 0);
    if (!$businessId || !$userId) return false;
    $res = prepared_statements('SELECT id FROM cashbook_business_profile WHERE id=? AND user_id=? LIMIT 1', 'ii', [$businessId,$userId]);
    return (bool)($res && $res->num_rows);
}

/** A staff member must belong to the business or be explicitly linked to its book. */
function khBelongsToBook($userId, $bookId, $businessId) {
    $res = prepared_statements('SELECT id FROM cashbook_book_users WHERE user_id=? AND book_id=? LIMIT 1', 'ii', [(int)$userId,(int)$bookId]);
    return (bool)($res && $res->num_rows);
}

/** Resolve rights for the *current business*, never from another business. */
function khCan($key, $bookId=0, $businessId=0) 
{
    // print_r($key);
    // print_r($_SESSION['auth']);
    if (!isVerified() || empty($_SESSION['auth']->id)) return false;
    if (!array_key_exists($key, khPermissionCatalog())) return false;
    if (khIsSuperAdmin()) return true;
    if ($key === 'system.admin') return false;
    $userId = (int)$_SESSION['auth']->id;
    $bookId = (int)$bookId;
    $businessId = (int)$businessId;
    if ($bookId > 0) {
        $book = bookFind($bookId);
        if (!$book) return false;
        $bookBusinessId = (int)$book->business_id;
        if ($businessId && $businessId !== $bookBusinessId) return false;
        $businessId = $bookBusinessId;
    }
    if ($businessId <= 0) return false;
    $owner = khOwnsBusiness($businessId);
    if ($key === 'roles.manage') return $owner;
    if ($key === 'business.edit' || $key === 'books.create') return $owner;
    if ($owner) return true;
    if ($bookId > 0 && !khBelongsToBook($userId,$bookId,$businessId)) return false;
    // A user must belong to this business before business-scoped rights apply.
    if ($bookId <= 0) {
        $member = prepared_statements('SELECT id FROM cashbook_users WHERE id=? AND business_id=? LIMIT 1', 'ii', [$userId,$businessId]);
        if (!$member || !$member->num_rows) return false;
    }
    static $permissionCache = [];
    $cacheKey = $userId.':'.$businessId;
    if (!isset($permissionCache[$cacheKey])) {
        $permissionCache[$cacheKey] = [];
        $res = prepared_statements('SELECT DISTINCT p.permission_key FROM cashbook_role_user ru 
        JOIN cashbook_role_permissions rp ON rp.role_id=ru.role_id 
        JOIN cashbook_permissions p ON p.id=rp.permission_id 
        JOIN cashbook_roles r ON r.id=ru.role_id WHERE ru.user_id=? AND ru.business_id=? AND LOWER(r.name) NOT IN (\'superadministrator\',\'superadmin\')', 'ii', [$userId,$businessId]);
        if ($res) while ($row=$res->fetch_assoc()) $permissionCache[$cacheKey][$row['permission_key']] = true;
    }
    // print_r($permissionCache[$cacheKey]);
    return isset($permissionCache[$cacheKey][$key]);
}

function khRequirePermission($key,$bookId=0,$businessId=0) {
    if (!khCan($key,$bookId,$businessId)) {
        http_response_code(403);
        exit('Access denied. You do not have permission for this action.');
    }
}

/** Populate session for older navigation checks; khCan queries business-scoped rights. */
function khLoadAccessSession($userId) {
    $userId = (int)$userId;
    $_SESSION['role_ids'] = [];
    $_SESSION['role_names'] = [];
    $_SESSION['assigned_books'] = [];
    $_SESSION['permissions'] = [];
    $_SESSION['owned_businesses'] = [];
    $res = prepared_statements('SELECT DISTINCT r.id,r.name,ru.business_id FROM cashbook_roles r JOIN cashbook_role_user ru ON ru.role_id=r.id WHERE ru.user_id=?', 'i', [$userId]);
    if ($res) while ($row=$res->fetch_assoc()) {
        $_SESSION['role_ids'][] = (int)$row['id'];
        // Never expose a business-scoped superadmin label as a system role.
        $name = strtolower(trim($row['name']));
        if ((int)$row['business_id']===0 || !in_array($name,['superadministrator','superadmin'],true)) $_SESSION['role_names'][] = $name;
    }
    $res = prepared_statements('SELECT DISTINCT book_id FROM cashbook_book_users WHERE user_id=?', 'i', [$userId]);
    if ($res) while ($row=$res->fetch_assoc()) $_SESSION['assigned_books'][] = (int)$row['book_id'];
    $res = prepared_statements('SELECT DISTINCT p.permission_key FROM cashbook_permissions p JOIN cashbook_role_permissions rp ON rp.permission_id=p.id JOIN cashbook_role_user ru ON ru.role_id=rp.role_id WHERE ru.user_id=? AND ru.business_id>0', 'i', [$userId]);
    if ($res) while ($row=$res->fetch_assoc()) $_SESSION['permissions'][] = $row['permission_key'];
    $res = prepared_statements('SELECT id FROM cashbook_business_profile WHERE user_id=?', 'i', [$userId]);
    if ($res) while ($row=$res->fetch_assoc()) $_SESSION['owned_businesses'][] = (int)$row['id'];
    $_SESSION['permissions_loaded_at'] = time();
}
function khCreatePermissionTables() {
    global $server;
    // Uses the application's existing mysqli connection and prepared_statements wrapper.
    // The uploaded functions.php does not contain a table-creation helper.
    $statements=[
        'CREATE TABLE IF NOT EXISTS cashbook_permissions (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,permission_key VARCHAR(100) NOT NULL UNIQUE,module VARCHAR(60) NOT NULL,description VARCHAR(200) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS cashbook_role_permissions (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,role_id INT NOT NULL,permission_id INT UNSIGNED NOT NULL,UNIQUE KEY role_permission (role_id,permission_id),KEY idx_permission (permission_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    ];
    foreach ($statements as $sql) prepared_statements($sql);
    foreach (khPermissionCatalog() as $key=>$description) {
        $module=explode('.',$key)[0];
        prepared_statements('INSERT INTO cashbook_permissions(permission_key,module,description) VALUES(?,?,?) ON DUPLICATE KEY UPDATE module=VALUES(module),description=VALUES(description)','sss',[$key,$module,$description]);
    }
    return true;
}


/** Transaction visibility is always scoped to a specific authorized book. */
function khTransactionScope($bookId) {
    $bookId = (int)$bookId;
    if ($bookId <= 0 || !bookFind($bookId)) return 'none';
    if (khCan('transactions.view_all', $bookId)) return 'all';
    // Existing data-entry roles keep access to their own records without a migration.
    if (khCan('transactions.view_own', $bookId) || khCan('transactions.create', $bookId)) return 'own';
    return 'none';
}

function khCanAccessTransaction($transactionId, $permission) {
    $transactionId = (int)$transactionId;
    $actorId = (int)($_SESSION['auth']->id ?? 0);
    if (!$transactionId || !$actorId) return false;
    $result = prepared_statements(
        'SELECT id,book_id,user_id FROM cashbook_transactions WHERE id=? LIMIT 1',
        'i', [$transactionId]
    );
    $transaction = $result ? $result->fetch_assoc() : null;
    if (!$transaction || !khCan($permission, (int)$transaction['book_id'])) return false;
    if (khTransactionScope((int)$transaction['book_id']) === 'all') return true;
    return khTransactionScope((int)$transaction['book_id']) === 'own'
        && (int)$transaction['user_id'] === $actorId;
}

function khRequireTransactionAccess($transactionId, $permission) {
    if (!khCanAccessTransaction($transactionId, $permission)) {
        http_response_code(403);
        exit('Access denied for this transaction.');
    }
}
