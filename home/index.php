<?php
/**
 * KashHub legacy Home URL.
 * Business Home is the single landing page for business/book management.
 * Keep this redirect for existing bookmarks and navigation links.
 */
require_once(__DIR__ . '/../assets/functions.php');

if (!isVerified()) {
    header('Location: ../login/', true, 302);
    exit;
}else {
    // Redirect to the business home page for verified users.
    header('Location: ../business/', true, 302);
    exit;
}
