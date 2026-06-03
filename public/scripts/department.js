function openEdit(id, name) {
    document.getElementById('editName').value = name;
    document.getElementById('editForm').action = '/processing-system/public/departments/' + id + '/update';
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
    document.getElementById('editModal').addEventListener('shown.bs.modal', () => {
        document.getElementById('editName').focus();
    }, { once: true });
}

const search = document.getElementById('deptSearch');
const rows = document.querySelectorAll('tbody tr[data-name]');
const count = document.getElementById('deptCount');

function updateCount() {
    const visible = [...rows].filter(r => r.style.display !== 'none').length;
    count.textContent = visible + ' department' + (visible !== 1 ? 's' : '');
}
updateCount();
search?.addEventListener('input', () => {
    const q = search.value.toLowerCase();
    rows.forEach(r => r.style.display = r.dataset.name.includes(q) ? '' : 'none');
    updateCount();
});

document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => el.style.opacity = '0', 3000);
    setTimeout(() => el.remove(), 3400);
    el.style.transition = 'opacity .4s ease';
});
