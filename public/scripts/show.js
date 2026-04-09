document.addEventListener('DOMContentLoaded', function () {
    const backBtn = document.getElementById('btn-back');
    if (backBtn) backBtn.addEventListener('click', () => history.back());
    
    const rejectBtn = document.getElementById('btn-reject');
    if (rejectBtn) {
        rejectBtn.addEventListener('click', function (e) {
            if (!confirm('Reject this form?')) e.preventDefault();
        });
    }
});