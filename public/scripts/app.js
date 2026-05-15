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
// Replaces onclick="toggleNotif()" and onclick="clearNotifDot()"
// on elements that can't use external event listeners (e.g. rendered by PHP).
document.addEventListener('DOMContentLoaded', function () {
    var notifBtn = document.getElementById('notifBtn');
    if (notifBtn) {
        notifBtn.addEventListener('click', toggleNotif);
    }

    var markRead = document.querySelector('.notif-mark-read');
    if (markRead) {
        markRead.addEventListener('click', clearNotifDot);
    }
});