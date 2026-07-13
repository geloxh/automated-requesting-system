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
        : url('forms/reimbursement');
    $fieldVal = fn(string $k, string $def = '') =>
        htmlspecialchars($data[$k] ?? $def);
?>
<form method="POST" action="<?= $formAction ?>">
    <div class="page-heading">Reimbursement Request</div>
    <div class="page-subheading">Fill in the details below. Save as draft to continue later, or submit directly for approval.</div>
    <?= \App\Helpers\Csrf::field(); ?>
 
    <div class="form-card">
        <div class="form-section-title">Applicant Details</div>
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
            <div class="form-group"><label>Pages</label><input type="text" name="page_no" placeholder="No. of attachments" value="<?= $fieldVal('page_no') ?>"></div>
            <div class="form-group"><label>Date</label><input type="date" name="request_date" required value="<?= $fieldVal('request_date') ?>"></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Reimbursement Details</div>
        <div class="table-scroll">
            <table class="form-table" id="reimburse-table"
                data-recalc="amount-only"
                data-add-btn-id="add-row"
                data-total-id="total_amount"
            >
                <thead><tr><th>No.</th><th>Date</th><th>SI/OR #</th><th>Even</th><th>Particulars</th><th>Person/Place</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><input type="number" name="item_no[]" value="<?= $fieldVal('item_no[]') ?>"></td>
                        <td><input type="date" name="item_date[]" value="<?= $fieldVal('item_date[]') ?>"></td>
                        <td><input type="text" name="invoice_number[]" value="<?= $fieldVal('invoice_number[]') ?>"></td>
                        <td><input type="text" name="even[]" value="<?= $fieldVal('even[]') ?>"></td>
                        <td><input type="text" name="particulars[]" value="<?= $fieldVal('particulars[]') ?>"></td>
                        <td><input type="text" name="person_place[]" value="<?= $fieldVal('person_place[]') ?>"></td>
                        <td><input type="number" step="0.01" name="amount[]" class="row-amount" value="<?= $fieldVal('amount[]') ?>"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">✕</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-ghost btn-sm btn-add-row" id="add-row">+ Add Row</button>
        <div class="form-grid g-4 mt-1">
            <div class="form-group"><label>Total Amount</label><input type="number" step="0.01" name="total_amount" id="total_amount" readonly value="<?= $fieldVal('total_amount') ?>"></div>
            <div class="form-group g-span-2"><label>Total Amount (in words)</label><input type="text" name="amount_words" value="<?= $fieldVal('amount_words') ?>"></div>
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