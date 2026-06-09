/**
 * app.js
 * Global UI behaviour — loaded at the bottom of every page via base.php.
 */

// ── Notification panel ──────────────────────────────────────────
function toggleNotif() {
    document.getElementById('notifPanel').classList.toggle('open');
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('#notifPanel') && !e.target.closest('#notifBtn')) {
        document.getElementById('notifPanel')?.classList.remove('open');
    }
});

// ── Sidebar toggle (desktop collapse + mobile overlay) ──────────
var sidebarToggle = document.getElementById('sidebarToggle');
var sidebar = document.getElementById('sidebar');
var SIDEBAR_KEY = 'sidebar_collapsed';

// Restore sidebar collapsed state.
// base.php adds the 'collapsed' class server-side when sidebar_collapsed=1
// in the DB, which avoids the localStorage-only flash. The toggle button
// still writes to localStorage so manual toggles survive page navigation.
if (sidebar && window.innerWidth > 900) {
    // If user toggled manually this session, localStorage takes precedence.
    // Otherwise the server-rendered class is already set correctly.
    var lsVal = localStorage.getItem(SIDEBAR_KEY);
    if (lsVal === 'true') {
        sidebar.classList.add('collapsed');
    } else if (lsVal === 'false') {
        sidebar.classList.remove('collapsed');
    }
    // lsVal === null means first visit or cleared — DB value (class already set) wins.
}

if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (window.innerWidth <= 900) {
            sidebar.classList.toggle('open');
        } else {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem(SIDEBAR_KEY, sidebar.classList.contains('collapsed'));
            updateToggleIcon();
        }
    });
}

// close mobile overlay on outside click
document.addEventListener('click', function (e) {
    if (window.innerWidth <= 900 && sidebar &&
        !e.target.closest('#sidebar')) {
        sidebar.classList.remove('open');
    }
});

// swap toggle icon based on collapsed state
function updateToggleIcon() {
    var icon = document.getElementById('sidebarToggleIcon');
    if (!icon || !sidebar) return;
    if (sidebar.classList.contains('collapsed')) {
        icon.className = 'ti ti-layout-sidebar-left-expand';
    } else {
        icon.className = 'ti ti-layout-sidebar-left-collapse';
    }
}

// sync icon on page load
updateToggleIcon();

// on resize: remove collapsed when switching to mobile
window.addEventListener('resize', function () {
    if (!sidebar) return;
    if (window.innerWidth <= 900) {
        sidebar.classList.remove('collapsed');
    }
    updateToggleIcon();
});

// ── Sidebar group dropdown ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var GROUPS_KEY = 'sidebar_groups';
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(GROUPS_KEY) || '{}'); } catch (e) {}

    // restore collapsed groups
    document.querySelectorAll('.sidebar-group').forEach(function (group) {
        if (saved[group.dataset.group] === true) {
            group.classList.add('collapsed');
        }
    });

    // toggle on label click
    document.querySelectorAll('.sidebar-label--toggle').forEach(function (label) {
        label.addEventListener('click', function () {
            var group = document.querySelector('.sidebar-group[data-group="' + label.dataset.toggle + '"]');
            if (!group) return;
            group.classList.toggle('collapsed');
            try {
                var state = JSON.parse(localStorage.getItem(GROUPS_KEY) || '{}');
                state[label.dataset.toggle] = group.classList.contains('collapsed');
                localStorage.setItem(GROUPS_KEY, JSON.stringify(state));
            } catch (e) {}
        });
    });
});

// ── Topbar search ── //
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('topbarSearch');
    const dropdown = document.getElementById('searchDropdown');
    if (!input || !dropdown) return;

    const formLabels = {
        advance_payment: 'Advance Payment',
        overtime_authorization: 'Overtime Auth.',
        request_for_payment: 'Request for Payment',
        leave_application: 'Leave Application',
        reimbursement: 'Reimbursement',
        liquidation: 'Liquidation',
        vehicle_request: 'Vehicle Request',
    };
    const statusColors = {
        draft:'#94a3b8', submitted:'#3b82f6', completed:'#22c55e',
        rejected:'#ef4444', cancelled:'#94a3b8',
        checker_approved:'#f59e0b', process_approved:'#f59e0b',
        department_reviewed:'#f59e0b', finance_reviewed:'#f59e0b',
        final_approved:'#22c55e',
    };

    let debounce, activeIdx = -1, results = [];

    const esc = s => String(s).replace(/&/g,'&').replace(/</g,'<').replace(/>/g,'>');

    function open() { dropdown.classList.remove('dept-hidden'); input.setAttribute('aria-expanded','true'); }
    function close() { dropdown.classList.add('dept-hidden');    input.setAttribute('aria-expanded','false'); activeIdx = -1; }

    function render(items) {
        results = items;
        activeIdx = -1;
        if (!items.length) {
            dropdown.innerHTML = '<div class="search-empty">No results found</div>';
            open(); return;
        }
        dropdown.innerHTML = items.map((r, i) => `
            <a href="/processing-system/public/forms/view/${r.id}"
               class="search-item" role="option" data-idx="${i}">
                <span class="search-item-type">${esc(formLabels[r.form_type] ?? r.form_type)}</span>
                <span class="search-item-id">#${r.id}</span>
                <span class="search-item-status" style="color:${statusColors[r.status] ?? '#94a3b8'}">${esc(r.status)}</span>
                <span class="search-item-who">${esc(r.submitted_by)}</span>
            </a>`).join('');
        open();
    }

    function setActive(idx) {
        dropdown.querySelectorAll('.search-item').forEach((el, i) => {
            el.classList.toggle('search-item--active', i === idx);
        });
        activeIdx = idx;
    }

    input.addEventListener('input', function () {
        clearTimeout(debounce);
        const q = input.value.trim();
        if (q.length < 2) { close(); return; }
        debounce = setTimeout(() => {
            fetch('/processing-system/public/search?q=' + encodeURIComponent(q))
                .then(r => r.json()).then(render).catch(() => close());
        }, 200);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { close(); input.blur(); return; }
        if (dropdown.classList.contains('dept-hidden')) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(activeIdx + 1, results.length - 1)); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); setActive(Math.max(activeIdx - 1, 0)); }
        if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            window.location.href = '/processing-system/public/forms/view/' + results[activeIdx].id;
        }
        // Enter with no selection — navigate to search page
        if (e.key === 'Enter' && activeIdx < 0 && input.value.trim()) {
            window.location.href = '/processing-system/public/my-submissions?q=' + encodeURIComponent(input.value.trim());
        }
    });

    // close on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#topbarSearchWrap')) close();
    });

    // Ctrl+K / Cmd+K global shortcut
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            input.focus();
            input.select();
        }
    });
});

// ── Notification button wiring ──────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var notifBtn = document.getElementById('notifBtn');
    if (notifBtn) notifBtn.addEventListener('click', toggleNotif);

    var markAllBtn = document.getElementById('markAllReadBtn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            fetch('/processing-system/public/notifications/read-all', {
                method: 'POST',
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            }).then(function () {
                document.querySelectorAll('.notif-item--unread').forEach(function (el) {
                    el.classList.remove('notif-item--unread');
                });
                document.querySelectorAll('.notif-dot-sm').forEach(function (d) {
                    d.style.background = '#cbd5e1';
                });
                var dot = document.getElementById('notifDot');
                if (dot) dot.style.display = 'none';
            });
        });
    }

    document.querySelectorAll('.notif-item[data-notif-id]').forEach(function (item) {
        item.addEventListener('click', function () {
            var id = item.dataset.notifId;
            var href = item.dataset.href;
            if (item.classList.contains('notif-item--unread')) {
                item.classList.remove('notif-item--unread');
                fetch('/processing-system/public/notifications/' + id + '/read', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                });
            }
            if (href) window.location.href = href;
        });
    });

    function pollNotifications() {
        fetch('/processing-system/public/notifications/unread')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var dot = document.getElementById('notifDot');
                if (dot) dot.style.display = data.unread_count > 0 ? '' : 'none';
                var badge = document.querySelector('.badge-count');
                if (badge && data.pending_count !== undefined) {
                    badge.textContent = data.pending_count;
                    badge.style.display = data.pending_count > 0 ? '' : 'none';
                }
            })
            .catch(function () {});
    }
    setInterval(pollNotifications, 60000);
});

// ── Default today's date on date inputs ────────────────────────
document.querySelectorAll('input[type="date"]:not([value])').forEach(function (el) {
    if (!el.closest('[data-no-default]')) {
        el.value = new Date().toISOString().split('T')[0];
    }
});

document.querySelectorAll('.activity-icon-dynamic[data-bg]').forEach(function (el) {
    el.style.setProperty('--icon-bg', el.dataset.bg);
    el.style.setProperty('--icon-color', el.dataset.color);
});

document.querySelectorAll('.qf-icon[data-color]').forEach(function (el) {
    el.style.setProperty('--qf-color', el.dataset.color);
});

document.querySelectorAll('.vol-fill[data-pct]').forEach(function (el) {
    el.style.width = el.dataset.pct + '%';
    el.style.background = el.dataset.color;
});

document.addEventListener('DOMContentLoaded', function () {
    var inApproval = ['submitted','checker_approved','process_approved','department_reviewed','finance_reviewed','final_approved'];

    function applyStatusFilter(val) {
        var rows = document.querySelectorAll('table[data-filterable] tbody tr');
        rows.forEach(function (row) {
            if (!val) { row.style.display = ''; return; }
            var s = row.dataset.status || '';
            if (val === 'in_approval') {
                row.style.display = inApproval.includes(s) ? '' : 'none';
            } else if (val === 'approved') {
                row.style.display = (s === 'final_approved' || s === 'completed') ? '' : 'none';
            } else {
                row.style.display = s === val ? '' : 'none';
            }
        });
    }

    var sel = document.getElementById('statusFilter');
    if (sel) {
        sel.addEventListener('change', function () { applyStatusFilter(sel.value); });
        // Apply pre-filter from URL ?status= param on load
        if (sel.value) applyStatusFilter(sel.value);
    }
});