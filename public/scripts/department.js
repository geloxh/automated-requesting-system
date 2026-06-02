function  openEdit(id, name) {
    document.getElementById('editName').value = name;
    document.getElementById('editForm').action = '/processing-system/public/departments/' + id + '/update';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}