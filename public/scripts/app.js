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

// ── Mobile sidebar toggle ───────────────────────────────────────
var sidebarToggle = document.getElementById('sidebarToggle');
var sidebar = document.getElementById('sidebar');

if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () {
        sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#sidebar') && !e.target.closest('#sidebarToggle')) {
            sidebar.classList.remove('open');
        }
    });
}

function checkMobile() {
    if (sidebarToggle) {
        sidebarToggle.style.display = window.innerWidth <= 900 ? 'flex' : '';
    }
}
checkMobile();
window.addEventListener('resize', checkMobile);

// ── Sidebar group dropdown + popup ─────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var STORAGE_KEY = 'sidebar_groups';

    // Restore collapsed state from localStorage
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch(e) {}

    document.querySelectorAll('.sidebar-group').forEach(function (group) {
        var key = group.dataset.group;
        if (saved[key] === true) group.classList.add('collapsed');
    });

    // Toggle on label click
    document.querySelectorAll('.sidebar-label--toggle').forEach(function (label) {
        label.addEventListener('click', function () {
            var key   = label.dataset.toggle;
            var group = document.querySelector('.sidebar-group[data-group="' + key + '"]');
            if (!group) return;
            group.classList.toggle('collapsed');
            // Persist
            try {
                var state = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
                state[key] = group.classList.contains('collapsed');
                localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
            } catch(e) {}
        });
    });

    // Popup flyout — show when group is collapsed and label is hovered
    document.querySelectorAll('.sidebar-group').forEach(function (group) {
        var key    = group.dataset.group;
        var popup  = document.getElementById('popup-' + key);
        var label  = group.querySelector('.sidebar-label--toggle');
        if (!popup || !label) return;

        var hideTimer;

        function showPopup() {
            if (!group.classList.contains('collapsed')) return;
            clearTimeout(hideTimer);
            var rect = label.getBoundingClientRect();
            popup.style.top = rect.top + 'px';
            popup.classList.add('visible');
        }

        function hidePopup() {
            hideTimer = setTimeout(function () {
                popup.classList.remove('visible');
            }, 120);
        }

        label.addEventListener('mouseenter', showPopup);
        label.addEventListener('mouseleave', hidePopup);
        popup.addEventListener('mouseenter', function () { clearTimeout(hideTimer); });
        popup.addEventListener('mouseleave', hidePopup);
    });
});

// ── Topbar search — filter any data table on the page ──────────
// Mirrors the search term into the first [data-search-input] on the page
// so the global search bar drives the table filter without a page reload.
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
    if (notifBtn) {
        notifBtn.addEventListener('click', toggleNotif);
    }

    // Mark all read
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

    // Clicking a notification item → mark read + navigate
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

    // Poll for new notifications every 60s, invalidate session cache server-side
    function pollNotifications() {
        fetch('/processing-system/public/notifications/unread')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var dot = document.getElementById('notifDot');
                if (dot) dot.style.display = data.unread_count > 0 ? '' : 'none';
                // Also update the sidebar badge count if the element exists
                var badge = document.querySelector('.badge-count');
                if (badge && data.pending_count !== undefined) {
                    badge.textContent = data.pending_count;
                    badge.style.display = data.pending_count > 0 ? '' : 'none';
                }
            })
            .catch(function () { /* ignore network errors */ });
    }
    setInterval(pollNotifications, 60000);
});

// ── Default today's date on date inputs ────────────────────────
document.querySelectorAll('input[type="date"]:not([value])').forEach(function (el) {
    if (!el.closest('[data-no-default]')) {
        el.value = new Date().toISOString().split('T')[0];
    }
});

document.querySelectorAll('.activity-icon-dynamic[data-bg]').forEach(el => {
    el.style.setProperty('--icon-bg', el.dataset.bg);
    el.style.setProperty('--icon-color', el.dataset.color);
});

