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
        : url('forms/overtime');
    $fieldVal = fn(string $k, string $def = '') =>
        htmlspecialchars($data[$k] ?? $def);
?>
<form method="POST" action="<?= $formAction ?>">
    <div class="page-heading">Overtime Authorization Request</div>
    <div class="page-subheading">Fill in the details below. Save as draft to continue later, or submit directly for approval.</div>
    <?= \App\Helpers\Csrf::field(); ?>
 
    <div class="form-card">
        <div class="form-section-title">Applicant Details</div>
        <div class="form-grid g-3">
            <div class="form-group"><label>Employee Name <span class="req">*</span></label><input type="text" name="employee_name" value="<?= htmlspecialchars($currentUser ?? '') ?>" readonly required></div>
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
            <div class="form-group"><label>Date</label><input type="date" name="request_date" required value="<?= $fieldVal('request_date') ?>"></div>
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
                <thead><tr><th>Date</th><th>Reason/s</th><th>Start</th><th>To</th><th>Total Hours</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><input type="date" name="ot_date[]" required value="<?= $fieldVal('ot_date[]') ?>"></td>
                        <td><input type="text" name="reason[]" value="<?= $fieldVal('reason[]') ?>"></td>
                        <td><input type="time" name="hours_covered[]" value="<?= $fieldVal('hours_covered[]') ?>"></td>
                        <td><input type="time" name="hours_covered[]" value="<?= $fieldVal('hours_covered[]') ?>"></td>
                        <td><input type="number" name="hours_total[]" step="0.1" class="ot-hours" value="<?= $fieldVal('hours_total[]') ?>"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">✕</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-ghost btn-sm btn-add-row" id="add-row">+ Add Row</button>
        <div class="form-grid g-4 mt-1">
            <div class="form-group"><label>Total Hours Rendered</label><input type="number" name="total_hours" id="total_hours" step="0.1" readonly value="<?= $fieldVal('total_hours') ?>"></div>
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