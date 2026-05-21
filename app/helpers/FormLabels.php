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
            'checker_approved' => 'info',
            'department_reviewed' => 'info',
            'process_approved' => 'info',
            'finance_reviewed' => 'warning',
            'final_approved' => 'success',
            'completed' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'dark',
        ];

        /** Badge class for a single status string. Defaults to 'secondary'. */
        public static function badgeClass(string $status): string {
            return self::BADGE_MAP[$status] ?? 'secondary';
        }

        /** Full badge map (for views that pass it to a template variable). */
        public static function allBadges(): array {
            return self::BADGE_MAP;
        }

        private const STEP_LABELS_ADMIN = [
            1 => 'Submitted',
            2 => 'Checker Approval',
            3 => 'Review Approval',
            4 => 'Grant Approval Request',
            5 => 'Completed',
        ];

        private const STEP_LABELS_FINANCE = [
            1 => 'Submitted',
            2 => 'Checker Approval',
            3 => 'Process Approval',
            4 => 'Evaluation Approval',
            5 => 'Grant Approval Request',
            6 => 'Completed',
        ];

        private const ADMIN_FORMS = ['overtime_authorization', 'leave_application', 'vehicle_request'];

        private const LABELS = [
            'advance_payment' => 'Advance Payment',
            'overtime_authorization' => 'Overtime Authorization',
            'request_for_payment' => 'Request for Payment',
            'leave_application' => 'Leave Application',
            'reimbursement' => 'Reimbursement',
            'liquidation' => 'Liquidation',
            'vehicle_request' => 'Vehicle Request',
        ];

        public static function stepLabel(int $sequence, string $formType = ''): string {
            $map = in_array($formType, self::ADMIN_FORMS, true)
                ? self::STEP_LABELS_ADMIN
                : self::STEP_LABELS_FINANCE;
            return $map[$sequence] ?? 'Step ' . $sequence;
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

        /**
         * Check if a form type belongs to the Administrative category.
         */
        public static function isAdminForm(string $type): bool {
            return in_array($type, self::ADMIN_FORMS, true);
        }

        /**
         * Returns a context-aware verb for approval action buttons based on the sequence.
         */
        public static function verb(int $sequence): string {
            $verbs = [
                2 => 'Check',
                3 => 'Review',
                4 => 'Process',
                5 => 'Evaluate',
                6 => 'Approve',
            ];
            return $verbs[$sequence] ?? 'Review';
        }

        /**
         * Resolve the human-readable pipeline stage.
         * 
         * @param string $status The raw form status
         * @param int|null $currentStep The earliest pending sequence number
         * @param string $formType The type of form to resolve correct labels
         */
        public static function currentStage(string $status, ?int $currentStep, string $formType = ''): string {
            if ($status === 'draft') return 'Draft';
            if ($status === 'rejected') return 'Rejected';
            if ($status === 'completed') return 'Completed';
            if ($status === 'cancelled') return 'Cancelled';

            // Use the pending sequence label if available
            if ($currentStep) {
                return self::stepLabel((int)$currentStep, $formType);
            }

            return ucwords(str_replace('_', ' ', $status));
        }
    }