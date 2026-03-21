/** Processing System DB **/

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, approvals, form_data, forms, employees, roles;
DROP VIEW IF EXISTS form_approval_status;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- ROLES
-- ============================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) UNIQUE NOT NULL,
    description TEXT
);

INSERT INTO roles (name, description) VALUES
('admin', 'Full system access'),
('approver', 'Can approve/reject forms'),
('staff', 'Can submit forms only');

-- ============================================================
-- EMPLOYEES
-- ============================================================
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    department VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

CREATE INDEX idx_employees_email ON employees(email);
CREATE INDEX idx_employees_role  ON employees(role_id);

-- Default admin account (password: Admin@1234)
INSERT INTO employees (employee_code, full_name, email, password_hash, role_id, department) VALUES
('EMP-0001', 'System Admin', 'it@3ehitech.com', '$2y$12$oXUiiZmq9z9xWcoxM1Po6.aTsaXeENvWBDvuarVE4D.rtFAKwE8RK', 1, 'IT Head');

-- ============================================================
-- FORMS
-- ============================================================
CREATE TABLE forms (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    form_type ENUM(
        'advance_payment',
        'overtime_authorization',
        'request_for_payment',
        'work_permit',
        'leave_application',
        'reimbursement',
        'liquidation',
        'vehicle_request'
    ) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','submitted','in_approval','approved','rejected','cancelled')),
    submitted_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (submitted_by) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE INDEX idx_forms_type ON forms(form_type);
CREATE INDEX idx_forms_status ON forms(status);
CREATE INDEX idx_forms_submitted  ON forms(submitted_by);

-- ============================================================
-- FORM DATA  (EAV / JSON per form)
-- ============================================================
CREATE TABLE form_data (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT NOT NULL,
    field_key VARCHAR(100) NOT NULL,
    field_value TEXT,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    UNIQUE KEY uk_form_field (form_id, field_key)
);

CREATE INDEX idx_form_data_form ON form_data(form_id);

-- ============================================================
-- APPROVALS
-- ============================================================
CREATE TABLE approvals (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT NOT NULL,
    approver_id INT NOT NULL,
    sequence SMALLINT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending'
    CHECK (status IN ('pending','approved','rejected','skipped')),
    remarks TEXT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES employees(id) ON DELETE RESTRICT,
    CONSTRAINT uk_approval_form_seq UNIQUE (form_id, sequence)
);

CREATE INDEX idx_approvals_form ON approvals(form_id);
CREATE INDEX idx_approvals_approver ON approvals(approver_id);
CREATE INDEX idx_approvals_status ON approvals(status);

-- ============================================================
-- PASSWORD RESET TOKENS
-- ============================================================
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE INDEX idx_prt_token ON password_reset_tokens(token);
CREATE INDEX idx_prt_employee ON password_reset_tokens(employee_id);

-- ============================================================
-- AUDIT LOGS
-- ============================================================
CREATE TABLE audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    performed_by INT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(20) NOT NULL,
    entity_id BIGINT NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (performed_by) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_performed_by ON audit_logs(performed_by);
CREATE INDEX idx_audit_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_performed_at ON audit_logs(performed_at);

-- ============================================================
-- VIEW: form approval progress
-- ============================================================
CREATE VIEW form_approval_status AS
SELECT
    f.id AS form_id,
    f.form_type,
    f.status AS form_status,
    e.full_name AS submitted_by,
    f.created_at,
    COUNT(a.id) AS total_steps,
    COUNT(CASE WHEN a.status = 'approved' THEN 1 END) AS approved_steps,
    MIN(CASE WHEN a.status = 'pending'  THEN a.sequence END) AS next_pending_sequence,
    GROUP_CONCAT(a.approver_id ORDER BY a.sequence SEPARATOR ',') AS approver_chain
FROM forms f
JOIN employees e ON e.id = f.submitted_by
LEFT JOIN approvals a ON a.form_id = f.id
GROUP BY f.id, f.form_type, f.status, e.full_name, f.created_at;