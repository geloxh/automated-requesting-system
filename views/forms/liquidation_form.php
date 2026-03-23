<h5 class="form-title">Request for Liquidation</h5>

<form method="POST" action="/processing-system/public/forms/liquidation">
    <?= \App\Helpers\Csrf::field(); ?>

    <div class="form-card">
        <div class="form-section-title">Applicant Details</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Name</label><input type="text" name="employee_name" required></div>
            <div class="form-group"><label>Department</label><input type="text" name="department" required></div>
            <div class="form-group"><label>Pages</label><input type="text" name="page_no" placeholder="No. of attachments"></div>
            <div class="form-group"><label>Date</label><input type="date" name="request_date" required></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Advance Details</div>
        <div class="form-grid g-3">
            <div class="form-group"><label>Advance Date</label><input type="date" name="advance_date"></div>
            <div class="form-group"><label>Advance Type</label><input type="text" name="advance_type"></div>
            <div class="form-group"><label>Advance Amount</label><input type="number" step="0.01" name="advance_amount" id="advance_amount"></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Expense Details</div>
        <div class="table-scroll">
            <table class="form-table" id="liquidation-table">
                <thead><tr><th>No.</th><th>Date</th><th>SI/OR #</th><th>Even</th><th>Particulars</th><th>Person/Place</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><input type="number" name="item_no[]"></td>
                        <td><input type="date" name="item_date[]"></td>
                        <td><input type="text" name="invoice_number[]"></td>
                        <td><input type="text" name="even[]"></td>
                        <td><input type="text" name="particulars[]"></td>
                        <td><input type="text" name="person_place[]"></td>
                        <td><input type="number" step="0.01" name="amount[]" class="row-amount"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">✕</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-ghost btn-sm btn-add-row" id="add-row">+ Add Row</button>
        <div class="form-grid g-4 mt-1">
            <div class="form-group"><label>Total Amount</label><input type="number" step="0.01" name="total_amount" id="total_amount" readonly></div>
            <div class="form-group"><label>Balance / Refund</label><input type="number" step="0.01" name="balance" id="balance" readonly></div>
            <div class="form-group g-span-2"><label>Total Amount (in words)</label><input type="text" name="amount_words"></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Approval</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Prepared By</label><input type="text" name="prepared_by"></div>
            <div class="form-group"><label>Confirmed By</label><input type="text" name="confirmed_by"></div>
            <div class="form-group"><label>Checked By</label><input type="text" name="checked_by"></div>
            <div class="form-group"><label>Approved By</label><input type="text" name="approved_by"></div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

<script>
initTable({ tableId: 'liquidation-table', addBtnId: 'add-row', recalc: 'amount-only', totalId: 'total_amount', balanceId: 'balance', advanceId: 'advance_amount' });
</script>
