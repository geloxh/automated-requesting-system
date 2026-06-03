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

        <ul class="dept-list" 
            id="deptList" 
            role="listbox" 
            aria-label="Departments"
            <?php if (!empty($_SESSION['last_dept_id'])): ?>
                data-auto-select="<?= (int)$_SESSION['last_dept_id'] ?>"
            <?php unset($_SESSION['last_dept_id']); endif; ?>
        >
            <?php if (empty($departments)): ?>
                <li class="dept-empty">
                    <i class="ti ti-building empty-state-icon"></i>
                    <span>No departments yet.</span>
                </li>
            <?php else: ?>
                <?php $i = 1; foreach ($departments as $dept): ?>
                <li class="dept-item"
                    tabindex="0"
                    role="option"
                    data-name="<?= strtolower(htmlspecialchars($dept['name'])) ?>"
                    data-id="<?= $dept['id'] ?>"
                    data-label="<?= htmlspecialchars($dept['name'], ENT_QUOTES) ?>"
                    data-count="<?= $dept['member_count'] ?? 0 ?>"
                    data-forms="<?= $dept['form_count'] ?? 0 ?>"
                    data-initial="<?= htmlspecialchars(strtoupper(substr($dept['name'], 0, 1))) ?>"
                    data-created="<?= date('M Y', strtotime($dept['created_at'])) ?>"
                >
                <div class="dept-item-icon dept-item-icon--letter"><?= strtoupper(substr($dept['name'], 0, 1)) ?></div>
                    <div class="dept-item-body">
                        <span class="dept-item-name"><?= htmlspecialchars($dept['name']) ?></span>
                        <span class="dept-item-meta">
                            <i class="ti ti-users" style="font-size:10px"></i>
                            <?= $dept['member_count'] ?? 0 ?> members
                        </span>
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
            <p>Pick a department to view details</p>
            <button class="btn btn-ghost btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="ti ti-plus"></i> Create one
            </button>
        </div>
        <div class="dept-detail-content dept-hidden" id="deptDetailContent">
            <div class="dept-detail-icon-wrap">
                <div class="dept-detail-icon"></div>
            </div>
            <div class="dept-detail-name" id="detailName"></div>
            <div class="dept-detail-id" id="detailId"></div>

            <div class="dept-detail-stats">
                <div class="dept-stat">
                    <span class="dept-stat-val" id="detailCount">0</span>
                    <span class="dept-stat-label">Members</span>
                </div>
                <div class="dept-stat-divider"></div>
                <div class="dept-stat">
                    <span class="dept-stat-val" id="detailForms">0</span>
                    <span class="dept-stat-label">Active Forms</span>
                </div>
            </div>

            <div class="dept-detail-actions">
                <button class="btn btn-ghost btn-sm" id="detailEditBtn">
                    <i class="ti ti-pencil"></i> Edit
                </button>
                <div class="dept-overflow-wrap">
                    <button class="dept-overflow-btn" id="detailMoreBtn" title="More actions" aria-haspopup="true" aria-expanded="false">
                        <i class="ti ti-dots"></i>
                    </button>
                    <div class="dept-overflow-menu dept-hidden" id="deptOverflowMenu">
                        <button class="dept-overflow-item dept-overflow-item--danger" id="detailDeleteBtn">
                            <i class="ti ti-trash"></i> Delete department
                        </button>
                    </div>
                </div>
            </div>
            <div class="dept-members-wrap" id="deptMembersWrap">
            <div class="dept-members-head">
                <span>Members</span>
                <span class="dept-members-count" id="deptMembersCount"></span>
            </div>
            <ul class="dept-members-list" id="deptMembersList">
                <li class="dept-members-loading"><i class="ti ti-loader-2 spin"></i> Loading…</li>
            </ul>
        </div>;
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
                <input type="text" name="name" id="addName" class="form-control form-control-sm" placeholder="e.g. Finance" required autofocus>
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
                <h5 class="modal-title">partment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="label">Department Name</label>
                <input type="text" name="name" id="editName" class="form-control form-control-sm" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden delete form — submitted by JS -->
<form method="POST" id="detailDeleteForm" style="display:none">
    <?= \App\Helpers\Csrf::field() ?>
</form>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Department?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="font-size:13px">
                This will permanently remove <strong id="confirmDeptName"></strong>.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="/processing-system/public/scripts/department.js"></script>
