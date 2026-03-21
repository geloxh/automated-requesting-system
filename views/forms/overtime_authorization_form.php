<?php
define('BASE_LOADED', true);
use App\Middleware\AuthMiddleware;
AuthMiddleware::require();

ob_start(); ?>

<h5 class="mb-4">Overtime Authorization Form</h5>

<form method="POST" action="/processing-system/public/forms/overtime" class="card p-4 bg-white shadow-sm">

    <h6 class="mb-3 text-secondary">Applicant Details</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Employee Name</label>
            <input type="text" name="employee_name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="request_date" class="form-control" required>
        </div>
    </div>

    <h6 class="mb-3 text-secondary">OT Request Details</h6>
    <div class="table-responsive mb-3">
        <table class="table table-bordered align-middle" id="ot-table">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Reason/s</th>
                    <th>Hours Covered</th>
                    <th>Total Hours</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="date" name="ot_date[]" class="form-control form-control-sm" required></td>
                    <td><input type="text" name="reason[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="hours_covered[]" class="form-control form-control-sm" placeholder="e.g. 5:00PM–8:00PM"></td>
                    <td><input type="number" name="hours_total[]" step="0.1" class="form-control form-control-sm ot-hours"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">✕</button></td>
                </tr>
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="add-row">+ Add Row</button>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Total Hours Rendered</label>
            <input type="number" name="total_hours" id="total_hours" step="0.1" class="form-control" readonly>
        </div>
    </div>

    <h6 class="mb-3 text-secondary">Approval</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Prepared By</label>
            <input type="text" name="prepared_by" class="form-control" placeholder="Name and Signature">
        </div>
        <div class="col-md-4">
            <label class="form-label">Approved By</label>
            <input type="text" name="approved_by" class="form-control" placeholder="Immediate Head">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

<script>
function recalcOT() {
    let total = 0;
    document.querySelectorAll('.ot-hours').forEach(i => total += parseFloat(i.value) || 0);
    document.getElementById('total_hours').value = total.toFixed(1);
}

document.addEventListener('input', recalcOT);

document.getElementById('add-row').addEventListener('click', () => {
    const tbody = document.querySelector('#ot-table tbody');
    const row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(row);
});

document.addEventListener('click', e => {
    if (e.target.classList.contains('remove-row')) {
        const tbody = document.querySelector('#ot-table tbody');
        if (tbody.rows.length > 1) e.target.closest('tr').remove();
        recalcOT();
    }
});
</script>

<?php
$content   = ob_get_clean();
$pageTitle = 'Overtime Authorization';
require __DIR__ . '/../layouts/base.php';