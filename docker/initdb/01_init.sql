-- ============================================================
-- docker/initdb/01_init.sql
-- MariaDB runs this automatically on first `docker compose up`
-- (only when the db_data volume is empty / freshly created).
-- Files in /docker-entrypoint-initdb.d run as root against the
-- database named in MARIADB_DATABASE (arsdb).
-- ============================================================

-- 1. Base schema — roles, employees, forms, approvals, etc.
SOURCE /var/www/automated-requesting-system/config/arsdb.sql;

-- 2. Additional feature migrations (order matters for FK deps)
SOURCE /var/www/automated-requesting-system/migrations/settings_table.sql;
SOURCE /var/www/automated-requesting-system/migrations/notification_table.sql;
SOURCE /var/www/automated-requesting-system/migrations/form_approval_status_new.sql;
SOURCE /var/www/automated-requesting-system/migrations/user_settings.sql;