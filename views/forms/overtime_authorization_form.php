<h5 class="form-title">Overtime Authorization</h5>

<form method="POST" action="/processing-system/public/forms/overtime">
    <?= \App\Helpers\Csrf::field(); ?>

    <div class="form-card">
        <div class="form-section-title">Applicant Details</div>
        <div class="form-grid g-3">
            <div class="form-group"><label>Employee Name</label><input type="text" name="employee_name" required></div>
            <div class="form-group">
                <label>Department</label>
                <div class="input-select">
                    <input type="text" name="department" list="dept-list" autocomplete="off" required>
                    <datalist id="dept-list">
                        <?php foreach ($departments ?? [] as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>
            <div class="form-group"><label>Date</label><input type="date" name="request_date" required></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">OT Request Details</div>
        <div class="table-scroll">
            <table class="form-table" id="items-table"
                data-recalc="items"
                data-add-btn-id="add-row"
                data-total-id="total_amount"
            >
                <thead><tr><th>Date</th><th>Reason/s</th><th>Hours Covered</th><th>Total Hours</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><input type="date" name="ot_date[]" required></td>
                        <td><input type="text" name="reason[]"></td>
                        <td><input type="text" name="hours_covered[]" placeholder="e.g. 5:00PM–8:00PM"></td>
                        <td><input type="number" name="hours_total[]" step="0.1" class="ot-hours"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">✕</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-ghost btn-sm btn-add-row" id="add-row">+ Add Row</button>
        <div class="form-grid g-4 mt-1">
            <div class="form-group"><label>Total Hours Rendered</label><input type="number" name="total_hours" id="total_hours" step="0.1" readonly></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Approval</div>
        <div class="form-grid g-3">
            <div class="form-group"><label>Prepared By</label><input type="text" name="prepared_by" placeholder="Name and Signature"></div>
            <div class="form-group"><label>Approved By</label><input type="text" name="approved_by" placeholder="Immediate Head"></div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>