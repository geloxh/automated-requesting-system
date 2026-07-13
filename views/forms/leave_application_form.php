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
        : url('forms/leave/create');
    $fieldVal = fn(string $k, string $def = '') =>
        htmlspecialchars($data[$k] ?? $def);
?>
<form method="POST" action="<?= $formAction ?>">
    <div class="page-heading">Leave Application</div>
    <div class="page-subheading">Fill in the details below. Save as draft to continue later, or submit directly for approval.</div>
    <?= \App\Helpers\Csrf::field(); ?>

    <div class="form-card">
        <div class="form-section-title">Leave Details</div>
        <div class="form-grid g-4">
            <div class="form-group"><label>Name</label><input type="text" name="employee_name" value="<?= htmlspecialchars($currentUser ?? '') ?>" readonly required></div>
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
            <div class="form-group"><label>ID No.</label><input type="text" name="id_no" value="<?= $fieldVal('id_no') ?>"></div>
            <div class="form-group"><label>Date Filed</label><input type="date" name="date" required value="<?= $fieldVal('date') ?>"></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Leave Period</div>
        <div class="form-grid g-3">
            <div class="form-group"><label>From</label><input type="date" name="from_date" value="<?= $fieldVal('from_date') ?>"></div>
            <div class="form-group"><label>To</label><input type="date" name="to_date" value="<?= $fieldVal('to_date') ?>"></div>
            <div class="form-group"><label>Number of Leave Days</label><input type="number" name="num_of_leave" value="<?= $fieldVal('num_of_leave') ?>"></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Leave Description</div>
        <div class="form-grid g-4">
            <div class="form-group">
                <label>Type of Leave</label>
                <select name="leave_type" required>
                    <option value="">Select type</option>
                    <option value="vacation" <?= ($data['leave_type'] ?? '') === 'vacation' ? 'selected' : '' ?>>Vacation</option>
                    <option value="sick" <?= ($data['leave_type'] ?? '') === 'sick' ? 'selected' : '' ?>>Sick</option>
                    <option value="parental" <?= ($data['leave_type'] ?? '') === 'parental' ? 'selected' : '' ?>>Parental</option>
                    <option value="other" <?= ($data['leave_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>In case of Vacation</label>
                <select name="vacation_leave">
                    <option value="">Select Location</option>
                    <option value="local" <?= ($data['vacation_leave'] ?? '') === 'local' ? 'selected' : '' ?>>Local</option>
                    <option value="abroad" <?= ($data['vacation_leave'] ?? '') === 'abroad' ? 'selected' : '' ?>>Abroad</option>
                </select>
            </div>
            <div class="form-group">
                <label>In case of Sick</label>
                <select name="sick_leave">
                    <option value="">Select Recovery</option>
                    <option value="hospital" <?= ($data['sick_leave'] ?? '') === 'hospital' ? 'selected' : '' ?>>Hospital</option>
                    <option value="out_patient" <?= ($data['sick_leave'] ?? '') === 'out_patient' ? 'selected' : '' ?>>Out Patient</option>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Term</label>
                <select name="payment_term" required>
                    <option value="">Select Pay Option</option>
                    <option value="paid" <?= ($data['payment_term'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid Leave</option>
                    <option value="unpaid" <?= ($data['payment_term'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Unpaid Leave</option>
                </select>
            </div>
        </div>
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