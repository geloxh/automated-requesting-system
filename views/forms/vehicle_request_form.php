<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
<?php
    // Edit mode: $form is set when coming from edit(), absent on create
    $isEdit = isset($form);
    $data = $data ?? [];
    $formAction = $isEdit
        ? url('forms/' . (int)$form['id'] . '/update')
        : url('forms/vehicle-request/create');
    $fieldVal   = fn(string $k, string $def = '') =>
        htmlspecialchars($data[$k] ?? $def);
?>
<form method="POST" action="<?= $formAction ?>">
    <div class="page-heading">Vehicle Request</div>
    <div class="page-subheading">Fill in the details below. Save as draft to continue later, or submit directly for approval.</div>
    <?= \App\Helpers\Csrf::field(); ?>

    <div class="form-card">
        <div class="form-section-title">Applicant Details</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Car / Plate Number</label><input type="text" name="car_available" required value="<?= $fieldVal('car_available') ?>"></div>
            <div class="form-group"><label>Date</label><input type="date" name="date" required value="<?= $fieldVal('date') ?>"></div>
            <div class="form-group"><label>Applicant</label><input type="text" name="employee_name" value="<?= htmlspecialchars($currentUser ?? '') ?>" readonly required></div>
            <div class="form-group">
                <label>Department</label>
                <div class="input-select">
                    <input type="text" name="department" list="dept-list" autocomplete="off" required value="<?= $fieldVal('department') ?>">
                    <datalist id="dept-list">
                        <?php foreach ($departments ?? [] as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>
            <div class="form-group"><label>Total Mileage</label><input type="number" name="total_mileage" value="<?= $fieldVal('total_mileage') ?>"></div>
            <div class="form-group"><label>Schedule Time</label><input type="text" name="schedule_time" placeholder="Departure and arrival time" value="<?= $fieldVal('schedule_time') ?>"></div>
            <div class="form-group">
                <label>Type of Trip</label>
                <select name="trip_type" required>
                    <option value="">-- Select --</option>
                    <option value="journey" <?= ($data['trip_type'] ?? '') === 'journey' ? 'selected' : '' ?>>Journey</option>
                    <option value="round" <?= ($data['trip_type'] ?? '') === 'round' ? 'selected' : '' ?>>Round Trip</option>
                    <option value="single" <?= ($data['trip_type'] ?? '') === 'single' ? 'selected' : '' ?>>Single</option>
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
        <div class="form-group mt-1"><label>Notes</label><input type="text" name="notes" value="<?= $fieldVal('notes') ?>"></div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Approval Pipeline</div>
        <div class="pipeline-preview">
            <div class="pipeline-step"><i class="ti ti-send"></i><span>You submit</span></div>
            <i class="ti ti-chevron-right pipeline-arrow"></i>
            <div class="pipeline-step"><i class="ti ti-user-check"></i><span>Checker Approval</span></div>
            <i class="ti ti-chevron-right pipeline-arrow"></i>
            <div class="pipeline-step"><i class="ti ti-building-bank"></i><span>Review Approval</span></div>
            <i class="ti ti-chevron-right pipeline-arrow"></i>
            <div class="pipeline-step"><i class="ti ti-circle-check"></i><span>Grant Approval</span></div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
    <button type="draft" class="btn btn-light">Save as Draft</button>
</form>