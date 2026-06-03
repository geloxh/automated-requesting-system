// Search filter
const search = document.getElementById('deptSearch');
const items = () => document.querySelectorAll('.dept-item');
const subTitle = document.querySelector('.dept-panel-sub');

search?.addEventListener('input', () => {
    const q = search.value.toLowerCase();
    let visible = 0;
    items().forEach(li => {
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
    items().forEach(li => li.classList.remove('dept-item--active'));
    el.classList.add('dept-item--active');

    document.getElementById('deptDetailEmpty').style.display   = 'none';
    document.getElementById('deptDetailContent').style.display = 'flex';
    document.getElementById('detailName').textContent = name;
    document.getElementById('detailId').textContent   = 'ID · ' + id;

    document.getElementById('detailDeleteForm').action =
        '/processing-system/public/departments/' + id + '/delete';
    document.getElementById('detailEditBtn').onclick = () => openEdit(id, name);
}

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