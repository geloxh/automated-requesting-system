/**
 * app.js
 * Global UI behaviour — extracted from base.php inline <script>.
 * Loaded at the bottom of every page via base.php.
 */

// ── Notification panel ──────────────────────────────────────────
function toggleNotif() {
    document.getElementById('notifPanel').classList.toggle('open');
}

function clearNotifDot() {
    document.getElementById('notifDot').style.display = 'none';
    document.querySelectorAll('.notif-dot-sm').forEach(function (d) {
        d.style.background = '#cbd5e1';
    });
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('#notifPanel') && !e.target.closest('#notifBtn')) {
        document.getElementById('notifPanel').classList.remove('open');
    }
});

// ── Mobile sidebar toggle ───────────────────────────────────────
var sidebarToggle = document.getElementById('sidebarToggle');
var sidebar       = document.getElementById('sidebar');

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

// ── Show hamburger only on mobile ───────────────────────────────
// CSS sets #sidebarToggle { display: none } by default.
// This function overrides to flex only when the viewport is narrow.
function checkMobile() {
    if (sidebarToggle) {
        sidebarToggle.style.display = window.innerWidth <= 900 ? 'flex' : '';
    }
}
checkMobile();
window.addEventListener('resize', checkMobile);

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

    // Clicking a notification item → mark read + navigate if it has a link
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

    // Poll for new notifications every 60 seconds and update the bell dot
    function pollNotifications() {
        fetch('/processing-system/public/notifications/unread')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var dot = document.getElementById('notifDot');
                if (dot) dot.style.display = data.unread_count > 0 ? '' : 'none';
            })
            .catch(function () { /* ignore network errors */ });
    }
    setInterval(pollNotifications, 60000);
});