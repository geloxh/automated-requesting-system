<h5 class="form-title">Request for Payment</h5>

<form method="POST" action="/processing-system/public/forms/leave/create" class="card p-4 bg-white shadow-sm">
    <?= \App\Helpers\Csrf::field(); ?>

    <div class="form-card">
        <div class="form-section-title">Applicant Details</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Applicant</label><input type="text" name="employee_name" required></div>
            <div class="form-group"><label>Department</label><input type="text" name="department" required></div>
            <div class="form-group"><label>Pages</label><input type="text" name="page_no" placeholder="No. of attachments"></div>
            <div class="form-group"><label>Date</label><input type="date" name="date" required></div>
        </div>
        <div class="form-group mt-1">
            <label>Project Name</label><input type="text" name="project_name">
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Payment Details</div>
        <div class="form-grid g-4">
            <div class="form-group">
                <label>Type of Payment</label>
                <select name="payment_type" required>
                    <option value="">-- Select --</option>
                    <option>Cash</option>
                    <option>Bank Transfer</option>
                    <option>Cheque</option>
                </select>
            </div>
            <div class="form-group"><label>Payee</label><input type="text" name="payee" required></div>
            <div class="form-group"><label>Account Name</label><input type="text" name="account_name"></div>
            <div class="form-group"><label>Bank Name</label><input type="text" name="bank_name"></div>
            <div class="form-group"><label>Bank Account No.</label><input type="text" name="bank_account_no"></div>
            <div class="form-group"><label>Address</label><input type="text" name="address"></div>
        </div>
        <div class="form-group mt-1">
            <label>Purpose</label><textarea name="purpose" rows="2"></textarea>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Item Details</div>
        <div class="table-scroll">
            <table class="form-table" id="items-table"
                data-recalc="items"
                data-add-btn-id="add-row"
                data-total-id="total_amount"
            >
                <thead><tr><th>Item</th><th>Description</th><th>Unit Price</th><th>Quantity</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="item[]"></td>
                        <td><input type="text" name="description[]"></td>
                        <td><input type="number" step="0.01" name="unit_price[]" class="unit-price"></td>
                        <td><input type="number" name="quantity[]" class="qty"></td>
                        <td><input type="number" step="0.01" name="amount[]" class="row-amount" readonly></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">✕</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-ghost btn-sm btn-add-row" id="add-row">+ Add Row</button>
        <div class="form-grid g-4 mt-1">
            <div class="form-group"><label>Total Amount</label><input type="number" step="0.01" name="total_amount" id="total_amount" readonly></div>
            <div class="form-group g-span-2"><label>Total Amount (in words)</label><input type="text" name="amount_words"></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Approval</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Prepared By</label><input type="text" name="prepared_by"></div>
            <div class="form-group"><label>Checked By</label><input type="text" name="checked_by"></div>
            <div class="form-group"><label>Approved By</label><input type="text" name="approved_by"></div>
            <div class="form-group"><label>Received By</label><input type="text" name="receive_by"></div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>