/** Processing System DB **/
-- Requires MySQL 8.0+

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, approvals, forms, employees, roles;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- ROLES
-- ============================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    CONSTRAINT chk_role_name CHECK (name IN ('admin', 'approver', 'staff'))
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

-- ============================================================
-- FORMS
-- ============================================================
CREATE TABLE forms (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    form_type VARCHAR(50)  NOT NULL,
    status VARCHAR(20)  NOT NULL DEFAULT 'draft',
    submitted_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    data JSON NOT NULL,
    FOREIGN KEY (submitted_by) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT chk_form_type CHECK (form_type IN (
        'advance_payment',
        'overtime_authorization',
        'request_for_payment',
        'work_permit',
        'leave_application',
        'reimbursement',
        'liquidation',
        'vehicle_request'
    )),
    CONSTRAINT chk_form_status CHECK (status IN (
        'draft', 'submitted', 'in_approval', 'approved', 'rejected', 'cancelled'
    ))
);

CREATE INDEX idx_forms_submitted_by ON forms(submitted_by);
CREATE INDEX idx_forms_type_status  ON forms(form_type, status);
-- Functional index on JSON amount field (MySQL 8.0+)
CREATE INDEX idx_forms_amount ON forms ((CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.amount')) AS DECIMAL(15,2))));

-- ============================================================
-- APPROVALS
-- ============================================================
CREATE TABLE approvals (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT NOT NULL,
    approver_id INT NOT NULL,
    sequence SMALLINT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    remarks TEXT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (form_id) REFERENCES forms(id)     ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES employees(id) ON DELETE RESTRICT,
    CONSTRAINT chk_approval_sequence CHECK (sequence > 0),
    CONSTRAINT chk_approval_status CHECK (status IN ('pending', 'approved', 'rejected')),
    CONSTRAINT uk_approval_form_seq UNIQUE (form_id, sequence)
);

CREATE INDEX idx_approvals_form ON approvals(form_id);
CREATE INDEX idx_approvals_approver ON approvals(approver_id);

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
CREATE INDEX idx_audit_entity       ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_performed_at ON audit_logs(performed_at DESC);

-- ============================================================
-- VIEW: form approval progress
-- ============================================================
CREATE VIEW form_approval_status AS
SELECT
    f.id                                                              AS form_id,
    f.form_type,
    f.status                                                          AS form_status,
    f.submitted_by,
    COUNT(a.id)                                                       AS total_steps,
    COUNT(CASE WHEN a.status = 'approved' THEN 1 END)                AS approved_steps,
    MIN(CASE WHEN a.status = 'pending' THEN a.sequence END)          AS next_pending_sequence,
    JSON_ARRAYAGG(a.approver_id ORDER BY a.sequence)                 AS approver_chain
FROM forms f
LEFT JOIN approvals a ON a.form_id = f.id
GROUP BY f.id, f.form_type, f.status, f.submitted_by;