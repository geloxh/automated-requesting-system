-- ============================================================
-- FINANCE HEAD ROLE
-- The application code has always assumed role_id = 8 is "Finance Head"
-- (FormController::FINANCE_HEAD_ROLE, the Evaluation Approval pipeline
-- stage, EmployeeController's Finance Head dropdown, the role label maps)
-- but that row was never actually inserted into the `roles` table — so it
-- can't appear in the Add/Edit Employee "Role" dropdown, and no employee
-- can be assigned it.
--
-- IMPORTANT — READ BEFORE RUNNING:
-- Run this first to see your current state:
--     SELECT * FROM roles ORDER BY id;
-- This migration only inserts a row if id 8 is completely unused. If your
-- roles table already has something else at id 8, DO NOT run this — the
-- INSERT below will simply no-op (it won't overwrite or renumber anything),
-- but that means the app's hardcoded role_id = 8 assumption is genuinely
-- wrong for your database and needs to be resolved by changing the code's
-- constant instead of the data. Let me know what's actually at id 8 if so.
--
-- ============================================================

INSERT INTO roles (id, name, description)
SELECT 8, 'FinanceHead',
       'Reviews and signs off on the Evaluation Approval stage for Advance Payment, Request for Payment, Reimbursement, and Liquidation forms'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE id = 8);