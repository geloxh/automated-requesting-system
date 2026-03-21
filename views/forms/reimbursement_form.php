<?php
define('BASE_LOADED', true);
use App\Middleware\AuthMiddleware;
AuthMiddleware::require();

ob_start(); ?>

<h5 class="mb-4">Request for Reimbursement Form</h5>

<form method="POST" action="/processing-system/public/forms/reimbursement" class="card p-4 bg-white shadow-sm">

    <h6 class="mb-3 text-secondary">Applicant Details</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="employee_name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Pages</label>
            <input type="text" name="page_no" class="form-control" placeholder="No. of attachments">
        </div>
        <div class="col-md-2">
            <label class="form-label">Date</label>
            <input type="date" name="request_date" class="form-control" required>
        </div>
    </div>

    <h6 class="mb-3 text-secondary">Reimbursement Details</h6>
    <div class="table-responsive mb-3">
        <table class="table table-bordered align-middle" id="reimburse-table">
            <thead class="table-light">
                <tr>
                    <th>No.</th>
                    <th>Date</th>
                    <th>SI/OR #</th>
                    <th>Even</th>
                    <th>Particulars</th>
                    <th>Person/Place</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="number" name="item_no[]" class="form-control form-control-sm"></td>
                    <td><input type="date" name="item_date[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="invoice_number[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="even[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="particulars[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="person_place[]" class="form-control form-control-sm"></td>
                    <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm row-amount"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">✕</button></td>
                </tr>
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="add-row">+ Add Row</button>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Total Amount</label>
            <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control" readonly>
        </div>
        <div class="col-md-5">
            <label class="form-label">Total Amount (in words)</label>
            <input type="text" name="amount_words" class="form-control">
        </div>
    </div>

    <h6 class="mb-3 text-secondary">Approval</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Prepared By</label>
            <input type="text" name="prepared_by" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Confirmed By</label>
            <input type="text" name="confirmed_by" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Checked By</label>
            <input type="text" name="checked_by" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Approved By</label>
            <input type="text" name="approved_by" class="form-control">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

<script>
function recalc() {
    let total = 0;
    document.querySelectorAll('.row-amount').forEach(i => total += parseFloat(i.value) || 0);
    document.getElementById('total_amount').value = total.toFixed(2);
}

document.addEventListener('input', recalc);

document.getElementById('add-row').addEventListener('click', () => {
    const tbody = document.querySelector('#reimburse-table tbody');
    const row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(row);
});

document.addEventListener('click', e => {
    if (e.target.classList.contains('remove-row')) {
        const tbody = document.querySelector('#reimburse-table tbody');
        if (tbody.rows.length > 1) e.target.closest('tr').remove();
        recalc();
    }
});
</script>

<?php
$content   = ob_get_clean();
$pageTitle = 'Reimbursement';
require __DIR__ . '/../layouts/base.php';