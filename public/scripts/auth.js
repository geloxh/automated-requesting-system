lucide.createIcons();

/**
 * Generic function to handle password visibility toggling.
 * ensures elements inside the listener to ensure we have the current DOM elements,
 * as Lucide replaces icons with SVG elements.
 */
function setupPasswordToggle(buttonId, inputId, iconId) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.addEventListener('click', function() {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input && icon) {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
                lucide.createIcons();
            }
        });
    }
}

// Initialize toggles for password fields across Login, Register, and Reset Password views
setupPasswordToggle('toggleBtn', 'password', 'eyeIcon');
setupPasswordToggle('toggleBtnConfirm', 'password_confirmation', 'eyeIconConfirm');

// Sliding panel toggle
const cont = document.querySelector('.cont');
const imgBtn = document.querySelector('.img__btn');
if (cont && imgBtn) {
    imgBtn.addEventListener('click', function () {
        cont.classList.toggle('s--signup');
    });
}
