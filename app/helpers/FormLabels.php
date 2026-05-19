<?php
    namespace App\Helpers;

    /**
     * Single source of truth for form-type → human label mapping.
     *
     * Previously this array was copy-pasted in:
     *   - FormController::show()
     *   - FormController::mySubmissions()
     *   - FormController::allRequests()
     *   - ApprovalController::inbox()
     *   - views/forms/show.php
     *   - views/approvals/inbox.php
     *
     * Use: \App\Helpers\FormLabels::get($formType)
     *      \App\Helpers\FormLabels::all()
     */
    class FormLabels
    {
        /**
         * Sequence number → human label for every approval pipeline step.
         * Single source of truth — used by inbox.php, approval_trail.php,
         * NotificationService, and FormController.
         *
         * Sequence 1 = submission (no approver row)
         * Sequences 2–6 = the five approval stages
         */
        /**
         * Status → Bootstrap/app badge colour class.
         * Single source of truth — replaces the local $badgeMap in every view.
         */
        private const BADGE_MAP = [
            'draft' => 'secondary',
            'submitted' => 'primary',
            'supervisor_reviewed' => 'info',
            'department_checked' => 'info',
            'checker_approved' => 'warning',
            'final_approved' => 'success',
            'completed' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'dark',
            // legacy fallbacks kept for any old rows still in DB
            'in_approval' => 'warning',
            'approved' => 'success',
        ];

        /** Badge class for a single status string. Defaults to 'secondary'. */
        public static function badgeClass(string $status): string {
            return self::BADGE_MAP[$status] ?? 'secondary';
        }

        /** Full badge map (for views that pass it to a template variable). */
        public static function allBadges(): array {
            return self::BADGE_MAP;
        }

        private const STEP_LABELS = [
            1 => 'Submitted',
            2 => 'Supervisor Review',
            3 => 'Department Check',
            4 => 'Checker Approval',
            5 => 'Final Approval',
            6 => 'Completion',
        ];

        private const LABELS = [
            'advance_payment' => 'Advance Payment',
            'overtime_authorization' => 'Overtime Authorization',
            'request_for_payment' => 'Request for Payment',
            'work_permit' => 'Work Permit',
            'leave_application' => 'Leave Application',
            'reimbursement' => 'Reimbursement',
            'liquidation' => 'Liquidation',
            'vehicle_request' => 'Vehicle Request',
        ];

        /**
         * Return the label for a pipeline step sequence number.
         * Falls back to "Step N" if the sequence isn't in the map.
         */
        public static function stepLabel(int $sequence): string {
            return self::STEP_LABELS[$sequence] ?? 'Step ' . $sequence;
        }

        /**
         * Return the full step-label map (for views that loop over it).
         */
        public static function allStepLabels(): array {
            return self::STEP_LABELS;
        }

        /**
         * Return the label for a single form type, falling back to a
         * humanised version of the raw type string if not found.
         */
        public static function get(string $type): string {
            return self::LABELS[$type] ?? ucwords(str_replace('_', ' ', $type));
        }

        /**
         * Return the full label map (for views that need to pass it to templates).
         */
        public static function all(): array {
            return self::LABELS;
        }
    }