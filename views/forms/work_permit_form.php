<?php
    define('BASE_LOADED', true);
    use App\Middleware\AuthMiddleware;
    AuthMiddleware::require();
    ob_start();
?>

<h5 style="margin-bottom:1.25rem">Work Permit / Gatepass</h5>

<form method="POST" action="/processing-system/public/forms/work-permit">
    <?= \App\Helpers\Csrf::field(); ?>

    <div class="form-card">
        <div class="form-section-title">Gatepass Information</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Unit Owner / Tenant</label><input type="text" name="unit_owner" required></div>
            <div class="form-group"><label>Name of Bearer</label><input type="text" name="bearer_name" required></div>
            <div class="form-group"><label>Floor / Unit No.</label><input type="text" name="unit_no"></div>
            <div class="form-group"><label>Date</label><input type="date" name="date" required></div>
            <div class="form-group">
                <label>Type of Service</label>
                <select name="service_type" required>
                    <option value="">-- Select --</option>
                    <option value="delivery">Delivery</option>
                    <option value="pull_out">Pull-Out</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Gatepass Details</div>
        <div style="overflow-x:auto">
            <table class="form-table" id="gatepass-table">
                <thead>
                    <tr>
                        <th>Quantity</th>
                        <th>Description</th>
                        <th>Time</th>
                        <th>Remarks</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="number" name="quantity[]"></td>
                        <td><input type="text" name="description[]"></td>
                        <td><input type="time" name="time[]"></td>
                        <td><input type="text" name="remarks[]"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">✕</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" id="add-row" style="margin-top:.75rem">+ Add Row</button>
    </div>

    <div class="form-card">
        <div class="form-section-title">Approval</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Approved By</label><input type="text" name="approved_by" placeholder="Authorized Signatory"></div>
            <div class="form-group"><label>Noted By</label><input type="text" name="noted_by" placeholder="Property Manager/Engineer"></div>
            <div class="form-group"><label>Released By</label><input type="text" name="released_by" placeholder="Building Security"></div>
            <div class="form-group"><label>Released Details</label><input type="text" name="released_details" placeholder="Date and time of release"></div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

<script>
initTable({ tableId: 'gatepass-table', addBtnId: 'add-row' });
</script>

<?php
$content   = ob_get_clean();
$pageTitle = 'Work Permit';
require __DIR__ . '/../layouts/base.php';