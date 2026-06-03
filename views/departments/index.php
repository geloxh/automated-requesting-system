<link rel="stylesheet" href="/processing-system/public/stylesheets/department.css">

<div class="dept-shell">

    <!-- Left panel -->
    <div class="dept-panel">
        <div class="dept-panel-head">
            <div>
                <div class="dept-panel-title">Departments</div>
                <div class="dept-panel-sub"><?= count($departments) ?> total</div>
            </div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="ti ti-plus"></i> New
            </button>
        </div>

        <div class="dept-search-wrap">
            <i class="ti ti-search"></i>
            <input type="search" id="deptSearch" placeholder="Search departments…">
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="dept-flash dept-flash--success">
                <i class="ti ti-circle-check"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="dept-flash dept-flash--danger">
                <i class="ti ti-circle-x"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <ul class="dept-list" id="deptList">
            <?php if (empty($departments)): ?>
                <li class="dept-empty">
                    <i class="ti ti-building empty-state-icon"></i>
                    <span>No departments yet.</span>
                </li>
            <?php else: ?>
                <?php $i = 1; foreach ($departments as $dept): ?>
                <li class="dept-item"
                    data-name="<?= strtolower(htmlspecialchars($dept['name'])) ?>"
                    data-id="<?= $dept['id'] ?>"
                    data-label="<?= htmlspecialchars($dept['name'], ENT_QUOTES) ?>">
                    <div class="dept-item-icon"><i class="ti ti-building"></i></div>
                    <div class="dept-item-body">
                        <span class="dept-item-name"><?= htmlspecialchars($dept['name']) ?></span>
                        <span class="dept-item-meta">#<?= $i++ ?></span>
                    </div>
                    <i class="ti ti-chevron-right dept-item-arrow"></i>
                </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Right panel -->
    <div class="dept-detail" id="deptDetail">
        <div class="dept-detail-empty" id="deptDetailEmpty">
            <i class="ti ti-building-skyscraper dept-placeholder-icon"></i>
            <p>Select a department</p>
        </div>
        <div class="dept-detail-content dept-hidden" id="deptDetailContent">
            <div class="dept-detail-icon-wrap">
                <div class="dept-detail-icon"><i class="ti ti-building"></i></div>
            </div>
            <div class="dept-detail-name" id="detailName"></div>
            <div class="dept-detail-id" id="detailId"></div>
            <div class="dept-detail-actions">
                <button class="btn btn-ghost btn-sm" id="detailEditBtn">
                    <i class="ti ti-pencil"></i> Edit
                </button>
                <form method="POST" id="detailDeleteForm">
                    <?= \App\Helpers\Csrf::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="ti ti-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="/processing-system/public/departments/create" class="modal-content">
            <?= \App\Helpers\Csrf::field() ?>
            <div class="modal-header">
                <h5 class="modal-title">New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="label">Department Name</label>
                <input type="text" name="name" id="addName" placeholder="e.g. Finance" required autofocus>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" id="editForm" class="modal-content">
            <?= \App\Helpers\Csrf::field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Rename Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="label">Department Name</label>
                <input type="text" name="name" id="editName" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script src="/processing-system/public/scripts/department.js"></script>
