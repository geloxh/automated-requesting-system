
-- 1. Roles (admin, approver, staff)
CREATE TABLE roles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    CONSTRAINT chk_role_name CHECK (name IN ('admin', 'approver', 'staff'))
);

INSERT INTO roles (name, description) VALUES
('admin',    'Full system access'),
('approver', 'Can approve/reject forms'),
('staff',    'Can submit forms only');

-- 2. Employees / Users
CREATE TABLE employees (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    employee_code   VARCHAR(20) UNIQUE NOT NULL,           -- e.g. EMP-00123
    full_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(150) UNIQUE NOT NULL,
    password_hash   VARCHAR(255),                          
    role_id         INT NOT NULL,
    department      VARCHAR(50),
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

CREATE INDEX idx_employees_email   ON employees(email);
CREATE INDEX idx_employees_role    ON employees(role_id);

-- 3. Forms 
CREATE TABLE forms (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    form_type       VARCHAR(50) NOT NULL,                    -- 'leave_request', 'vehicle_request', etc.
    status          VARCHAR(20) NOT NULL DEFAULT 'draft',
    submitted_by    INT NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    data            JSON NOT NULL DEFAULT (JSON_OBJECT()),   
    
    FOREIGN KEY (submitted_by) REFERENCES employees(id) ON DELETE CASCADE,
    
    CONSTRAINT chk_form_status CHECK (status IN (
        'draft', 'submitted', 'in_approval', 'approved', 'rejected', 'cancelled'
    ))
);

CREATE INDEX idx_forms_submitted_by ON forms(submitted_by);
CREATE INDEX idx_forms_type_status  ON forms(form_type, status);
CREATE INDEX idx_forms_data         ON forms USING BTREE ((JSON_EXTRACT(data, '$."amount"')));  

-- 4. Approvals
CREATE TABLE approvals (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    form_id         BIGINT NOT NULL,
    approver_id     INT NOT NULL,
    sequence        SMALLINT NOT NULL CHECK (sequence > 0),   -- 1 = first, 2 = second...
    status          VARCHAR(20) NOT NULL DEFAULT 'pending',
    remarks         TEXT,
    assigned_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at     TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (form_id)    REFERENCES forms(id)    ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES employees(id) ON DELETE RESTRICT,
    
    CONSTRAINT chk_approval_status CHECK (status IN ('pending', 'approved', 'rejected')),
    CONSTRAINT uk_approval_form_sequence UNIQUE (form_id, sequence),
    CONSTRAINT uk_approval_form_approver UNIQUE (form_id, approver_id) -- optional
);

CREATE INDEX idx_approvals_form     ON approvals(form_id);
CREATE INDEX idx_approvals_approver ON approvals(approver_id);
CREATE INDEX idx_approvals_sequence ON approvals(form_id, sequence);

-- 5. Audit Logs
CREATE TABLE audit_logs (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    performed_by    INT NULL,                                 -- NULL if deleted user
    action          VARCHAR(50) NOT NULL,                    -- 'form_created', 'approval_decided', ...
    entity_type     VARCHAR(20) NOT NULL,                    -- 'form', 'approval'
    entity_id       BIGINT NOT NULL,
    performed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    old_values      JSON,
    new_values      JSON,
    ip_address      VARCHAR(45),                             -- supports IPv4 + IPv6
    user_agent      TEXT,
    
    FOREIGN KEY (performed_by) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_performed_by  ON audit_logs(performed_by);
CREATE INDEX idx_audit_entity        ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_performed_at  ON audit_logs(performed_at DESC);

CREATE VIEW form_approval_status AS
SELECT 
    f.id AS form_id,
    f.form_type,
    f.status AS form_status,
    COUNT(a.id) AS total_steps,
    COUNT(CASE WHEN a.status = 'approved' THEN 1 END) AS approved_steps,
    MIN(CASE WHEN a.status = 'pending' THEN a.sequence END) AS next_step_sequence,
    JSON_ARRAYAGG(a.approver_id ORDER BY a.sequence) AS approver_chain
FROM forms f
LEFT JOIN approvals a ON a.form_id = f.id
GROUP BY f.id, f.form_type, f.status;

-- Submit form
INSERT INTO forms (form_type, submitted_by, data) 
VALUES ('leave_request', 5, 
        '{"start_date": "2025-04-01", "end_date": "2025-04-05", "days": 5, "reason": "annual leave"}');

-- Create approval chain
INSERT INTO approvals (form_id, approver_id, sequence) VALUES
(LAST_INSERT_ID(), 12, 1),
(LAST_INSERT_ID(), 15, 2);

-- Approver acts
UPDATE approvals 
SET status = 'approved', 
    remarks = 'Looks good', 
    approved_at = CURRENT_TIMESTAMP
WHERE form_id = 123 AND sequence = 1;



