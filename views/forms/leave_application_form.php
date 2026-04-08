<h5 class="form-title">Leave Application Form</h5>

<form method="POST" action="/processing-system/public/forms/leave/create">
    <?= \App\Helpers\Csrf::field(); ?>

    <div class="form-card">
        <div class="form-section-title">Leave Details</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Name</label><input type="text" name="employee_name" required></div>
            <div class="form-group"><label>Department</label><input type="text" name="department" required></div>
            <div class="form-group"><label>ID No.</label><input type="text" name="id_no"></div>
            <div class="form-group"><label>Date Filed</label><input type="date" name="date" required></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Leave Period</div>
        <div class="form-grid g-3">
            <div class="form-group"><label>From</label><input type="date" name="from_date"></div>
            <div class="form-group"><label>To</label><input type="date" name="to_date"></div>
            <div class="form-group"><label>Number of Leave Days</label><input type="number" name="num_of_leave"></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Leave Description</div>
        <div class="form-grid g-4">
            <div class="form-group">
                <label>Type of Leave</label>
                <select name="leave_type" required>
                    <option value="">Select type</option>
                    <option value="vacation">Vacation</option>
                    <option value="sick">Sick</option>
                    <option value="parental">Parental</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>In case of Vacation</label>
                <select name="vacation_leave">
                    <option value="">Select Location</option>
                    <option value="local">Local</option>
                    <option value="abroad">Abroad</option>
                </select>
            </div>
            <div class="form-group">
                <label>In case of Sick</label>
                <select name="sick_leave">
                    <option value="">Select Recovery</option>
                    <option value="hospital">Hospital</option>
                    <option value="out_patient">Out Patient</option>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Term</label>
                <select name="payment_term" required>
                    <option value="">Select Pay Option</option>
                    <option value="paid">Paid Leave</option>
                    <option value="unpaid">Unpaid Leave</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Approval</div>
        <div class="form-grid g-3">
            <div class="form-group"><label>Applicant</label><input type="text" name="prepared_by"></div>
            <div class="form-group"><label>Checked By</label><input type="text" name="checked_by"></div>
            <div class="form-group"><label>Approved By</label><input type="text" name="approved_by"></div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>