const search = document.getElementById('deptSearch');
const subTitle = document.querySelector('.dept-panel-sub');
const allItems = () => document.querySelectorAll('.dept-item');

// Delegate clicks on list items
document.getElementById('deptList').addEventListener('click', e => {
    const li = e.target.closest('.dept-item');
    if (!li) return;
    selectDept(li, li.dataset.id, li.dataset.label);
});

// Search filter
search?.addEventListener('input', () => {
    const q = search.value.toLowerCase();
    let visible = 0;
    allItems().forEach(li => {
        const match = li.dataset.name.includes(q);
        li.style.display = match ? '' : 'none';
        if (match) visible++;
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
function selectDept(el, id, name) {
    allItems().forEach(li => li.classList.remove('dept-item--active'));
    el.classList.add('dept-item--active');

    document.getElementById('deptDetailEmpty').style.display   = 'none';
    document.getElementById('deptDetailContent').style.display = 'flex';
    document.getElementById('detailName').textContent = name;
    document.getElementById('detailId').textContent   = 'ID · ' + id;

    document.getElementById('detailDeleteForm').action =
        '/processing-system/public/departments/' + id + '/delete';
    document.getElementById('detailEditBtn').onclick = () => openEdit(id, name);
}

// Delete confirm
document.getElementById('detailDeleteForm').addEventListener('submit', e => {
    if (!confirm('Delete this department?')) e.preventDefault();
});

// Edit modal
function openEdit(id, name) {
    document.getElementById('editName').value = name;
    document.getElementById('editForm').action =
        '/processing-system/public/departments/' + id + '/update';
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
    document.getElementById('editModal').addEventListener(
        'shown.bs.modal',
        () => document.getElementById('editName').focus(),
        { once: true }
    );
}