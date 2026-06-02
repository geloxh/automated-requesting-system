(function () {
    const navBtns = document.querySelectorAll('.settings-nav-item');
    const panels  = document.querySelectorAll('.settings-panel');

    // Restore last active tab from sessionStorage
    const saved = sessionStorage.getItem('aps_settings_tab');
    if (saved) activate(saved);

    navBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            activate(tab);
            sessionStorage.setItem('aps_settings_tab', tab);
        });
    });

    function activate(tab) {
        navBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
        panels.forEach(p  => p.classList.toggle('active', p.id === 'tab-' + tab));
    }

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
            const inp = btn.closest('.input-password-wrap').querySelector('input');
            const isText = inp.type === 'text';
            inp.type = isText ? 'password' : 'text';
            btn.querySelector('i').className = isText ? 'ti ti-eye' : 'ti ti-eye-off';
        });
    });
})();