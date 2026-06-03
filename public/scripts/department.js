const search = document.getElementById('deptSearch');
const subTitle = document.querySelector('.dept-panel-sub');
const allItems = () => document.querySelectorAll('.dept-item');

// ── List item click ──────────────────────────────────────────
document.getElementById('deptList').addEventListener('click', e => {
    const li = e.target.closest('.dept-item');
    if (!li) return;
    selectDept(li, li.dataset.id, li.dataset.label, li.dataset.count);
});

// ── ⋯ Overflow menu toggle (top-level, attached once) ────────
const moreBtn = document.getElementById('detailMoreBtn');
const overflowMenu = document.getElementById('deptOverflowMenu');

moreBtn.addEventListener('click', e => {
    e.stopPropagation();
    const open = !overflowMenu.classList.contains('dept-hidden');
    overflowMenu.classList.toggle('dept-hidden', open);
    moreBtn.setAttribute('aria-expanded', String(!open));
});

document.addEventListener('click', () => {
    overflowMenu.classList.add('dept-hidden');
    moreBtn.setAttribute('aria-expanded', 'false');
});

// Keyboard navigation
document.getElementById('deptList').addEventListener('keydown', e => {
    if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) return;
    e.preventDefault();

    const items = [...allItems()].filter(li => li.style.display !== 'none');
    if (!items.length) return;

    const active = document.querySelector('.dept-item--active');
    const idx = items.indexOf(active);

    if (e.key === 'ArrowDown') {
        const next = items[idx + 1] ?? items[0];
        next.focus();
        next.click();
    } else if (e.key === 'ArrowUp') {
        const prev = items[idx - 1] ?? items[items.length - 1];
        prev.focus();
        prev.click();
    } else if ((e.key === 'Enter' || e.key === ' ') && active) {
        active.click();
    }
});

// Search filter
search?.addEventListener('input', () => {
    const q = search.value.toLowerCase();
    let visible = 0;
    allItems().forEach(li => {
        const match = li.dataset.name.includes(q);
        li.style.display = match ? '' : 'none';
        if (match) visible++;

        // if the active item just got hidden, reset the right panel
        if (li.classList.contains('dept-item--active') && !match) {
            li.classList.remove('dept-item--active');
            document.getElementById('deptDetailContent').classList.add('dept-hidden');
            document.getElementById('deptDetailEmpty').classList.remove('dept-hidden');
        }
    });
    subTitle.textContent = visible + ' total';
});

// Auto-dismiss flash
document.querySelectorAll('.dept-flash').forEach(el => {
    el.style.transition = 'opacity .4s ease';
    setTimeout(() => el.style.opacity = '0', 3000);
    setTimeout(() => el.remove(), 3400);
});

// Right-panel select
function selectDept(el, id, name, count = 0) {
    allItems().forEach(li => li.classList.remove('dept-item--active'));
    el.classList.add('dept-item--active');
    
    document.querySelector('.dept-detail-icon').textContent = el.dataset.initial;

    document.getElementById('deptDetailEmpty').classList.add('dept-hidden');
    document.getElementById('deptDetailContent').classList.remove('dept-hidden');
    document.getElementById('detailName').textContent = name;
    document.getElementById('detailId').textContent = 'Since ' + (el.dataset.created ?? '—');

    document.getElementById('detailDeleteForm').action =
        '/processing-system/public/departments/' + id + '/delete';
    document.getElementById('detailEditBtn').onclick = () => openEdit(id, name);

    document.getElementById('detailCount').textContent = count;
    document.getElementById('detailForms').textContent = el.dataset.forms ?? 0;

    const list = document.getElementById('deptMembersList');
    const badge = document.getElementById('deptMembersCount');
    list.innerHTML = '<li class="dept-members-loading"><i class="ti ti-loader-2 spin"></i> Loading...</li>';

   fetch('/processing-system/public/departments/' + id + '/members')
        .then(r => r.json())
        .then(members => {
            badge.textContent = members.length;
            if (!members.length) {
                list.innerHTML = '<li class="dept-members-empty">No members yet.</li>';
                return;
            }
            const roleLabels = {1:'Admin',2:'Approver',3:'Staff',4:'Dept. Head',5:'Checker',6:'Final Approver'};
                list.innerHTML = members.map(m => `
                    <li class="dept-member-item">
                        <div class="dept-member-avatar">${m.full_name.charAt(0).toUpperCase()}</div>
                        <div class="dept-member-body">
                            <span class="dept-member-name">${m.full_name}</span>
                            <span class="dept-member-meta">${m.employee_code} · ${roleLabels[m.role_id] ?? 'User'}</span>
                        </div>
                    </li>`).join('');
        })
        .catch(() => { list.innerHTML = '<li class="dept-members-empty">Failed to load.</li>'; });
}

// Delete confirm
const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
const confirmDeptName = document.getElementById('confirmDeptName');
const deleteForm = document.getElementById('detailDeleteForm');

// Open confirm modal when Delete button clicked
document.getElementById('detailDeleteBtn').addEventListener('click', () => {
    confirmDeptName.textContent = document.getElementById('detailName').textContent;
    confirmModal.show();
});

// Actually submit when user confirms
document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
    confirmModal.hide();
    deleteForm.submit();
});


// Edit modal
function openEdit(id, name) {
    document.getElementById('editName').value        = name;
    document.getElementById('editCurrentName').textContent = name;  // ← subtitle
    document.getElementById('editNameLen').textContent     = name.length; // ← counter
    document.getElementById('editForm').action =
        '/processing-system/public/departments/' + id + '/update';

    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();

    const input = document.getElementById('editName');
    // update counter on type
    input.oninput = () => document.getElementById('editNameLen').textContent = input.value.length;

    document.getElementById('editModal').addEventListener(
        'shown.bs.modal', () => input.select(), { once: true }  // ← select all on open
    );
}

// Auto-select department after page reload
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('deptList');
    const autoSelectId = list?.dataset.autoSelect;
    if (autoSelectId) {
        const li = document.querySelector(`.dept-item[data-id="${autoSelectId}"]`);
        if (li) {
            li.click();
            li.scrollIntoView({ block: 'nearest' });
        }
    }
});
