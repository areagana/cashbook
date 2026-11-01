<?php
/* Permission helper for the item register (no item ID required). */
if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['kh_item_csrf'])) { $_SESSION['kh_item_csrf'] = bin2hex(random_bytes(32)); }
if (!function_exists('isItemDeleteAdmin')) {
    function isItemDeleteAdmin(): bool {
        return hasRole(['owner', 'superadministrator']);
    }
}
