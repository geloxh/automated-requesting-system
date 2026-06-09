/**
 * show.php script file
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Back button ─────────────────────────────────────────────
    const backBtn = document.getElementById('btn-back');
    if (backBtn) {
        backBtn.addEventListener('click', () => {
            if (history.length > 1) {
                history.back();
            } else {
                window.location.href = backBtn.dataset.fallbackUrl || '/automated-requesting-system/public/approvals';
            }
        });
    }
    
    const rejectBtn = document.getElementById('btn-reject');
    const remarksField = document.getElementById('remarksField');
    const remarksHint = document.getElementById('remarks-hint');

    const successAlert = document.querySelector('.alert-success');

    if (rejectBtn && remarksField) {
        // Require remarks and confirm before rejecting
        rejectBtn.addEventListener('click', function (e) {
            if (remarksField.value.trim() === '') {
                e.preventDefault();
                remarksField.classList.add('input-error');
                remarksField.focus();
                remarksField.setCustomValidity('Please enter a rejection reason.');
                remarksField.reportValidity();
                return;
            }
            // If remarks are present, then ask for confirmation
            if (!confirm('Are you sure you want to reject this form? This cannot be undone.')) {
                e.preventDefault();
            }
        });

        // Clear error state once user starts typing
        remarksField.addEventListener('input', function () {
            remarksField.classList.remove('input-error');
            remarksField.setCustomValidity('');
        });

        if (remarksHint) {
            rejectBtn.addEventListener('mouseenter', function () {
                remarksHint.textContent = '(required for rejection)';
            });
            rejectBtn.addEventListener('mouseleave', function () {
                remarksHint.textContent = '(required if rejecting)';
            });
        }
    }

    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity .4s ease';
            successAlert.style.opacity = '0';
            setTimeout(() => successAlert.remove(), 400);
        }, 4000);
    }
});