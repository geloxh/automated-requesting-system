/**
 * all_requests.js for all_requests.php
 */

const inProgress = ['submitted', 'supervisor_reviewed', 'department_checked', 'checker_approved', 'final_approved'];

function filterByDept(sel) {
    const val = sel.value.toLowerCase();
    document.querySelectorAll('table[data-filterable] tbody tr').forEach(function(row) {
        if (!val) { row.style.display = ''; return; }
        row.style.display = (row.dataset.dept || '').toLowerCase() === val ? '' : 'none';
    });
}

function filterByStatusAll(sel) {
    const val = sel.value;
    document.querySelectorAll('table[data-filterable] tbody tr').forEach (function(row) {
        const s = row.dataset.status || '';
        if (!val) { row.style.display = ''; return; }
        if (val === 'in_progress') { row.style.display = inProgress.includes(s) ? '' : 'none'; return; }
        row.style.display = s === val ? '' : 'none';
    });
}