document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();

    function setupPasswordToggle(buttonId, inputId, iconId) {
        const button = document.getElementById(buttonId);
        if (!button) return;
        button.addEventListener('click', function () {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input && icon) {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
                lucide.createIcons();
            }
        });
    }

    setupPasswordToggle('toggleBtn',       'login_password', 'eyeIcon');
    setupPasswordToggle('toggleBtnReg',    'reg_password',   'eyeIconReg');
    setupPasswordToggle('toggleBtnConfirm','reg_confirm',    'eyeIconConfirm');

    const cont   = document.querySelector('.cont');
    const imgBtn = document.querySelector('.img__btn');
    if (cont && imgBtn) {
        imgBtn.addEventListener('click', function () {
            cont.classList.toggle('s--signup');
        });
    }
});