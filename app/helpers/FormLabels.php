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
     * Return the label for a single form type, falling back to a
     * humanised version of the raw type string if not found.
     */
    public static function get(string $type): string
    {
        return self::LABELS[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    /**
     * Return the full label map (for views that need to pass it to templates).
     */
    public static function all(): array
    {
        return self::LABELS;
    }
}