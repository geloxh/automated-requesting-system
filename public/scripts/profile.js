document.querySelectorAll('.profile-nav-item').forEach(link => {
    link.addEventListener('click', function(e) {
        document.querySelector('.profile-nav-item').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
    });
});