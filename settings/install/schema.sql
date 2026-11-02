-- Manual installer for hosts without PHP CLI. Execute once from phpMyAdmin.
CREATE TABLE IF NOT EXISTS `cashbook_business_settings` (
 `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
 `business_id` INT(11) NOT NULL,
 `setting_key` VARCHAR(100) NOT NULL,
 `setting_value` TEXT NULL,
 `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY `uq_business_setting` (`business_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
