<h5 class="mb-4">Leave Application Form</h5>

<form method="POST" action="/processing-system/public/forms/leave" class="card p-4 bg-white shadow-sm">
    <?= \App\Helpers\Csrf::field(); ?>
    <h6 class="mb-3 text-secondary">Leave Details</h6>
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
            <label class="form-label">ID No.</label>
            <input type="text" name="id_no" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Date Filed</label>
            <input type="date" name="date" class="form-control" required>
        </div>
    </div>

    <h6 class="mb-3 text-secondary">Leave Period</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">From</label>
            <input type="date" name="from_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">To</label>
            <input type="date" name="to_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Number of Leave Days</label>
            <input type="number" name="num_of_leave" class="form-control">
        </div>
    </div>

    <h6 class="mb-3 text-secondary">Leave Description</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Type of Leave</label>
            <select name="leave_type" class="form-select" required>
                <option value="">Select type</option>
                <option value="vacation">Vacation</option>
                <option value="sick">Sick</option>
                <option value="parental">Parental</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">In case of Vacation</label>
            <select name="vacation_leave" class="form-select">
                <option value="">Select Location</option>
                <option value="local">Local</option>
                <option value="abroad">Abroad</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">In case of Sick</label>
            <select name="sick_leave" class="form-select">
                <option value="">Select Recovery</option>
                <option value="hospital">Hospital</option>
                <option value="out_patient">Out Patient</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Payment Term</label>
            <select name="payment_term" class="form-select" required>
                <option value="">Select Pay Option</option>
                <option value="paid">Paid Leave</option>
                <option value="unpaid">Unpaid Leave</option>
            </select>
        </div>
    </div>

    <h6 class="mb-3 text-secondary">Approval</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Applicant</label>
            <input type="text" name="prepared_by" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Checked By</label>
            <input type="text" name="checked_by" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Approved By</label>
            <input type="text" name="approved_by" class="form-control">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>