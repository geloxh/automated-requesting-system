<div class="page-header">
    <h5>Departments <span class="badge badge-secondary"><?= count($departments) ?></span></h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="ti ti-plus"></i> Add Department
    </button>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="form-card">
    <div class="filter-bar">
        <input type="search" id="deptSearch" placeholder="Search departments...">
        <span class="filter-count" id="deptCount"></span>
    </div>
    <table class="table">
        <thead><tr data-name="<?= strtolower(htmlspecialchars($dept['name'])) ?>"><th>#</th><th>Name</th><th></th></tr></thead>
        <tbody>
        <?php $i = 1; foreach ($departments as $dept): ?>
            <tr data-name="<?= strtolower(htmlspecialchars($dept['name'])) ?>">
                <td class="muted" style="width:40px"><?= $i++ ?></td>
                <td><?= htmlspecialchars($dept['name']) ?></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-ghost"
                        onclick="openEdit(<?= $dept['id'] ?>, '<?= htmlspecialchars($dept['name'], ENT_QUOTES) ?>')">
                        <i class="ti ti-pencil"></i> Edit
                    </button>
                    <form method="POST"
                        action="/processing-system/public/departments/<?= $dept['id'] ?>/delete"
                        style="display:inline"
                        onsubmit="return confirm('Delete this department?')">
                        <?= \App\Helpers\Csrf::field() ?>
                        <button class="btn btn-sm btn-danger"><i class="ti ti-trash"></i> Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($departments)): ?>
            <tr data-name="<?= strtolower(htmlspecialchars($dept['name'])) ?>"><td colspan="3" class="text-center text-muted">No departments yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/processing-system/public/departments/create" class="modal-content">
            <?= \App\Helpers\Csrf::field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="text" name="name" class="form-control" placeholder="Department name" required autofocus>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editForm" class="modal-content">
            <?= \App\Helpers\Csrf::field() ?>
            <div class="modal-header"><h5 class="modal-title">Edit Department</h5></div>
            <div class="modal-body">
                <input type="text" name="name" id="editName" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script src="/processing-system/public/scripts/department.js"></script>