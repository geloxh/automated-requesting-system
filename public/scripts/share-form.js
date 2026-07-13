(function () {
    var modal = document.getElementById('shareModal');
    if (!modal) return;

    var FORM_ID = window.SHARE_FORM_ID;
    var BASE = window.ARS_BASE || '';
    var csrf = function () {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    };

    var listEl = document.getElementById('shareRecipientList');
    var searchEl = document.getElementById('shareRecipientSearch');
    var noteEl = document.getElementById('shareNote');
    var confirmBtn = document.getElementById('btn-share-confirm');
    var errorEl = document.getElementById('shareModalError');

    var users = [];
    var loaded = false;
    var selectedId = null;

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.hidden = false;
    }
    function clearError() {
        errorEl.hidden = true;
        errorEl.textContent = '';
    }

    function renderList(filter) {
        var term = (filter || '').trim().toLowerCase();
        var filtered = users.filter(function (u) {
            return !term
                || u.full_name.toLowerCase().indexOf(term) !== -1
                || (u.department || '').toLowerCase().indexOf(term) !== -1;
        });

        listEl.innerHTML = '';
        if (!filtered.length) {
            var empty = document.createElement('div');
            empty.className = 'share-recipient-empty';
            empty.textContent = 'No matching people.';
            listEl.appendChild(empty);
            return;
        }

        filtered.forEach(function (u) {
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'share-recipient-row' + (selectedId === u.id ? ' share-recipient-row--selected' : '');
            row.innerHTML =
                '<img class="share-recipient-avatar" src="' + u.avatar + '" alt="">' +
                '<span class="share-recipient-name">' + escapeHtml(u.full_name) +
                (u.department ? ' <span class="share-recipient-dept">· ' + escapeHtml(u.department) + '</span>' : '') +
                '</span>';
            row.addEventListener('click', function () {
                selectedId = u.id;
                confirmBtn.disabled = false;
                renderList(searchEl.value);
            });
            listEl.appendChild(row);
        });
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function loadUsers() {
        if (loaded) return;
        listEl.innerHTML = '<div class="share-recipient-empty">Loading…</div>';
        fetch(BASE + '/chat/users', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                users = Array.isArray(res) ? res : [];
                loaded = true;
                renderList('');
            })
            .catch(function () {
                listEl.innerHTML = '<div class="share-recipient-empty">Could not load people. Try again.</div>';
            });
    }

    // Reset + load whenever the modal is opened
    document.addEventListener('click', function (e) {
        if (e.target.closest('#btn-share-trigger')) {
            selectedId = null;
            confirmBtn.disabled = true;
            clearError();
            noteEl.value = '';
            searchEl.value = '';
            loadUsers();
        }
    });

    searchEl.addEventListener('input', function () {
        renderList(searchEl.value);
    });

    confirmBtn.addEventListener('click', function () {
        if (!selectedId || !FORM_ID) return;
        clearError();
        confirmBtn.disabled = true;

        var fd = new FormData();
        fd.append('csrf_token', csrf());
        fd.append('receiver_id', selectedId);
        fd.append('form_id', FORM_ID);
        fd.append('message', noteEl.value.trim());

        var base = BASE;
        fetch(base + '/chat/share-form', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                if (!res.ok) {
                    showError(res.data.error || 'Could not share this form. Please try again.');
                    confirmBtn.disabled = false;
                    return;
                }
                window.location.href = base + '/chat?with=' + selectedId;
            })
            .catch(function () {
                showError('Network error. Please try again.');
                confirmBtn.disabled = false;
            });
    });
})();