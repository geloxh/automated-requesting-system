<h5 class="mb-4">Vehicle Request Form</h5>

<form method="POST" action="/processing-system/public/forms/vehicle-request/create" class="card p-4 bg-white shadow-sm">
    <?= \App\Helpers\Csrf::field(); ?>   
    <h6 class="mb-3 text-secondary">Applicant Details</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Car / Plate Number</label>
            <input type="text" name="car_available" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Applicant</label>
            <input type="text" name="employee_name" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Total Mileage</label>
            <input type="number" name="total_mileage" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Schedule Time</label>
            <input type="text" name="schedule_time" class="form-control" placeholder="Departure and arrival time">
        </div>
        <div class="col-md-3">
            <label class="form-label">Type of Trip</label>
            <select name="trip_type" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="journey">Journey</option>
                <option value="round">Round Trip</option>
                <option value="single">Single</option>
            </select>
        </div>
    </div>

    <h6 class="mb-3 text-secondary">Destination Details</h6>
    <div class="row g-3 mb-4">
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="col-md-6">
            <label class="form-label">Destination <?= $i ?></label>
            <input type="text" name="destination_<?= $i ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Purpose <?= $i ?></label>
            <input type="text" name="purpose_<?= $i ?>" class="form-control">
        </div>
        <?php endfor; ?>
        <div class="col-12">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" class="form-control">
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
            <label class="form-label">Driver</label>
            <input type="text" name="driver" class="form-control">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>