-- Run manually in phpMyAdmin after backing up the database.
-- Existing cashbook_business_profile data and modules remain unchanged until a type is selected.
CREATE TABLE IF NOT EXISTS cashbook_business_types (
 id INT AUTO_INCREMENT PRIMARY KEY, type_key VARCHAR(60) NOT NULL, name VARCHAR(120) NOT NULL,
 description VARCHAR(255) NULL, active TINYINT(1) NOT NULL DEFAULT 1,
 UNIQUE KEY uq_business_type_key(type_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS cashbook_modules (
 id INT AUTO_INCREMENT PRIMARY KEY, module_key VARCHAR(60) NOT NULL, name VARCHAR(120) NOT NULL,
 description VARCHAR(255) NULL, is_core TINYINT(1) NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1,
 UNIQUE KEY uq_module_key(module_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS cashbook_business_type_modules (
 business_type_id INT NOT NULL, module_key VARCHAR(60) NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1,
 PRIMARY KEY(business_type_id,module_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS cashbook_business_module_overrides (
 business_id INT NOT NULL, module_key VARCHAR(60) NOT NULL, enabled TINYINT(1) NOT NULL,
 approved_by INT NOT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(business_id,module_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS cashbook_business_module_requests (
 id INT AUTO_INCREMENT PRIMARY KEY, business_id INT NOT NULL, module_key VARCHAR(60) NOT NULL,
 reason TEXT NULL, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 requested_by INT NOT NULL, reviewed_by INT NULL, review_note TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, reviewed_at DATETIME NULL,
 KEY idx_business_status(business_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS cashbook_feature_audit (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, actor_id INT NOT NULL, business_id INT NOT NULL DEFAULT 0,
 action VARCHAR(80) NOT NULL, details TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 KEY idx_feature_audit_business(business_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Run only once, manually; if business_type_id exists, skip the next ALTER.
ALTER TABLE cashbook_business_profile ADD COLUMN business_type_id INT NULL DEFAULT NULL;
INSERT IGNORE INTO cashbook_business_types(type_key,name) VALUES
 ('general','General Business'),('retail','Retail Shop'),('distribution','Wholesale & Distribution'),
 ('restaurant','Restaurant'),('services','Professional Services'),('hardware','Hardware Store');
INSERT IGNORE INTO cashbook_modules(module_key,name,is_core) VALUES
 ('cashbook','Cashbook',1),('reports','Reports',1),('customers','Customers',0),
 ('routes','Routes & Distribution',0),('stock','Stock Management',0),
 ('creditors','Creditors & Suppliers',0),('invoices','Sales & Invoicing',0);
-- No default allocations are imposed: superadministrator configures them in admin.php.
