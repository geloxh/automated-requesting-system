-- ============================================================
-- USER-FACING SETTINGS
-- Adds keys for Themes, Notifications, and Storage Path.
-- Run once via phpMyAdmin or: mysql -u root -p processing_system < migrations/user_settings.sql
-- ============================================================

INSERT IGNORE INTO settings (`key`, `value`) VALUES
    -- Themes
    ('theme_color', 'blue'),     
    ('theme_mode', 'light'), 
    ('sidebar_collapsed', '0'),   

    -- Notifications
    ('notify_on_submit', '1'), 
    ('notify_on_approval', '1'),  
    ('notify_on_rejection', '1'),   
    ('notify_on_completion', '1'),  

    -- Storage
    ('upload_path', 'public/uploads'), 
    ('max_file_size_mb', '10'),     
    ('allowed_file_types', 'pdf,jpg,png,docx'); 