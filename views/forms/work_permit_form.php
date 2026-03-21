<?php
define('BASE_LOADED', true);
use App\Middleware\AuthMiddleware;
AuthMiddleware::require();

ob_start(); ?>

<h5 class="mb-4">Work Permit Form</h5>

<form method="POST" action="/processing-system/public/forms/work-permit" class="card p-4 bg-white shadow-sm">

    <h6 class="mb-3 text-secondary">Gatepass Information</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Unit Owner / Tenant</label>
            <input type="text" name="unit_owner" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Name of Bearer</label>
            <input type="text" name="bearer_name" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Floor / Unit No.</label>
            <input type="text" name="unit_no" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Type of Service</label>
            <select name="service_type" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="delivery">Delivery</option>
                <option value="pull_out">Pull-Out</option>
            </select>
        </div>
    </div>

    <h6 class="mb-3 text-secondary">Gatepass Details</h6>
    <div class="table-responsive mb-3">
        <table class="table table-bordered align-middle" id="gatepass-table">
            <thead class="table-light">
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
                    <td><input type="number" name="quantity[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="description[]" class="form-control form-control-sm"></td>
                    <td><input type="time" name="time[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="remarks[]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">✕</button></td>
                </tr>
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-4" id="add-row">+ Add Row</button>

    <h6 class="mb-3 text-secondary">Approval</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Approved By</label>
            <input type="text" name="approved_by" class="form-control" placeholder="Authorized Signatory">
        </div>
        <div class="col-md-3">
            <label class="form-label">Noted By</label>
            <input type="text" name="noted_by" class="form-control" placeholder="Property Manager/Engineer">
        </div>
        <div class="col-md-3">
            <label class="form-label">Released By</label>
            <input type="text" name="released_by" class="form-control" placeholder="Building Security">
        </div>
        <div class="col-md-3">
            <label class="form-label">Released Details</label>
            <input type="text" name="released_details" class="form-control" placeholder="Date and time of release">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

<script>
document.getElementById('add-row').addEventListener('click', () => {
    const tbody = document.querySelector('#gatepass-table tbody');
    const row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(row);
});

document.addEventListener('click', e => {
    if (e.target.classList.contains('remove-row')) {
        const tbody = document.querySelector('#gatepass-table tbody');
        if (tbody.rows.length > 1) e.target.closest('tr').remove();
    }
});
</script>

<?php
$content   = ob_get_clean();
$pageTitle = 'Work Permit';
require __DIR__ . '/../layouts/base.php';