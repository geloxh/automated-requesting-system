<?php
define('BASE_LOADED', true);
use App\Middleware\AuthMiddleware;
AuthMiddleware::require();

ob_start(); ?>

<h5 class="mb-4">Advance Payment Request Form</h5>

<form method="POST" action="/processing-system/public/forms/advance-payment" class="card p-4 bg-white shadow-sm">

    <h6 class="mb-3 text-secondary">Applicant Details</h6>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Applicant</label>
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
            <input type="date" name="date" class="form-control" required>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label">Project Name</label>
        <input type="text" name="project_name" class="form-control">
    </div>

    <h6 class="mb-3 text-secondary">Payment Details</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Type of Payment</label>
            <select name="payment_type" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="Cash">Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Cheque">Cheque</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Payee</label>
            <input type="text" name="payee" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Account Name</label>
            <input type="text" name="account_name" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Bank Name</label>
            <input type="text" name="bank_name" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Bank Account No.</label>
            <input type="text" name="bank_account_no" class="form-control">
        </div>
        <div class="col-md-5">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Purpose</label>
            <textarea name="purpose" class="form-control" rows="2"></textarea>
        </div>
    </div>

    <h6 class="mb-3 text-secondary">Item Details</h6>
    <div class="table-responsive mb-3">
        <table class="table table-bordered align-middle" id="items-table">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" name="item[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="description[]" class="form-control form-control-sm"></td>
                    <td><input type="number" step="0.01" name="unit_price[]" class="form-control form-control-sm unit-price"></td>
                    <td><input type="number" name="quantity[]" class="form-control form-control-sm qty"></td>
                    <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm row-amount" readonly></td>
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
            <label class="form-label">Checked By</label>
            <input type="text" name="checked_by" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Approved By</label>
            <input type="text" name="approved_by" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Paid By</label>
            <input type="text" name="paid_by" class="form-control">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

<script>
function recalc() {
    let total = 0;
    document.querySelectorAll('#items-table tbody tr').forEach(row => {
        const price = parseFloat(row.querySelector('.unit-price').value) || 0;
        const qty   = parseFloat(row.querySelector('.qty').value) || 0;
        const amt   = price * qty;
        row.querySelector('.row-amount').value = amt.toFixed(2);
        total += amt;
    });
    document.getElementById('total_amount').value = total.toFixed(2);
}

document.addEventListener('input', recalc);

document.getElementById('add-row').addEventListener('click', () => {
    const tbody = document.querySelector('#items-table tbody');
    const row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(row);
});

document.addEventListener('click', e => {
    if (e.target.classList.contains('remove-row')) {
        const tbody = document.querySelector('#items-table tbody');
        if (tbody.rows.length > 1) e.target.closest('tr').remove();
        recalc();
    }
});
</script>

<?php
$content   = ob_get_clean();
$pageTitle = 'Advance Payment';
require __DIR__ . '/../layouts/base.php';