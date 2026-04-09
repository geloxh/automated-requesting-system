<h5 class="form-title">Vehicle Request Form</h5>

<form method="POST" action="/processing-system/public/forms/vehicle-request/create">
    <?= \App\Helpers\Csrf::field(); ?>

    <div class="form-card">
        <div class="form-section-title">Applicant Details</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Car / Plate Number</label><input type="text" name="car_available" required></div>
            <div class="form-group"><label>Date</label><input type="date" name="date" required></div>
            <div class="form-group"><label>Applicant</label><input type="text" name="employee_name" required></div>
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
            <div class="form-group"><label>Total Mileage</label><input type="number" name="total_mileage"></div>
            <div class="form-group"><label>Schedule Time</label><input type="text" name="schedule_time" placeholder="Departure and arrival time"></div>
            <div class="form-group">
                <label>Type of Trip</label>
                <select name="trip_type" required>
                    <option value="">-- Select --</option>
                    <option value="journey">Journey</option>
                    <option value="round">Round Trip</option>
                    <option value="single">Single</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Destination Details</div>
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="form-grid g-2 mt-1">
            <div class="form-group"><label>Destination <?= $i ?></label><input type="text" name="destination_<?= $i ?>"></div>
            <div class="form-group"><label>Purpose <?= $i ?></label><input type="text" name="purpose_<?= $i ?>"></div>
        </div>
        <?php endfor; ?>
        <div class="form-group mt-1"><label>Notes</label><input type="text" name="notes"></div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Approval</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Prepared By</label><input type="text" name="prepared_by"></div>
            <div class="form-group"><label>Confirmed By</label><input type="text" name="confirmed_by"></div>
            <div class="form-group"><label>Checked By</label><input type="text" name="checked_by"></div>
            <div class="form-group"><label>Driver</label><input type="text" name="driver"></div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>