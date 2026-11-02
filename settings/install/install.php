<?php
/** Explicit maintenance installer: CLI only, never triggered by normal page requests. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Run via PHP CLI only.'); }
require_once __DIR__.'/../../assets/functions.php';
require_once __DIR__.'/../bootstrap.php';
if (!function_exists('create_Table')) require_once __DIR__.'/../../assets/db_tables.php';
create_Table('cashbook_business_settings',[
 'business_id'=>'INT(11) NOT NULL',
 'setting_key'=>'VARCHAR(100) NOT NULL',
 'setting_value'=>'TEXT NULL',
]);
// Index creation is idempotent. Never modify existing unique indexes.
$r=prepared_statements("SELECT index_name FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='cashbook_business_settings' AND index_name='uq_business_setting' LIMIT 1");
if ($r->num_rows===0) {
    global $server;
    if (!$server->query('ALTER TABLE cashbook_business_settings ADD UNIQUE KEY uq_business_setting (business_id,setting_key)')) throw new RuntimeException('Could not add settings unique index');
}
echo "KashHub business settings installed.\n";
