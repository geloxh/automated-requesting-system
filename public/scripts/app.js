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
var sidebar       = document.getElementById('sidebar');
var SIDEBAR_KEY   = 'sidebar_collapsed';

// Restore desktop collapsed state on load
if (sidebar && window.innerWidth > 900) {
    if (localStorage.getItem(SIDEBAR_KEY) === 'true') {
        sidebar.classList.add('collapsed');
    }
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

// ── Topbar search ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var topbarSearch = document.getElementById('topbarSearch');
    if (topbarSearch) {
        topbarSearch.addEventListener('input', function () {
            var target = document.querySelector('[data-search-input]');
            if (target) {
                target.value = topbarSearch.value;
                target.dispatchEvent(new Event('input'));
            }
        });
    }
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
            var id   = item.dataset.notifId;
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
