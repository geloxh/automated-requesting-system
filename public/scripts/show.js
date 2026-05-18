/**
 * show.php script file
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Back button ─────────────────────────────────────────────
    const backBtn = document.getElementById('btn-back');
    if (backBtn) backBtn.addEventListener('click', () => history.back());
    
    const rejectBtn = document.getElementById('btn-reject');
    const remarksField = document.getElementById('remarksField');
    const remarksHint = document.getElementById('remarks-hint');

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
            if (!confirm('Are you sure you want to reject this form? This cannot be undone.')) {
                e.preventDefault();
            }
        });

        // Clear error state once user starts typing
        remarksField.addEventListener('input', function () {
            remarksField.classList.remove('input-error');
            remarksField.setCustomValidity('');
        });

        // Update hint text on hover
        rejectBtn.addEventListener('mouseenter', function () {
            if (remarksHint) remarksHint.textContent = '(required for rejection)';
        });
        rejectBtn.addEventListener('mouseleave', function () {
            if (remarksHint) remarksHint.textContent = '(required if rejecting)';
        });
    }
});