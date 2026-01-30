document.addEventListener('DOMContentLoaded', () => {
    const homeroomSelect = document.getElementById('homeroom-select');
    const homeroomClass = document.getElementById('homeroom-class');
    if (homeroomSelect && homeroomClass) {
        const toggle = () => {
            homeroomClass.style.display = homeroomSelect.value === 'yes' ? 'block' : 'none';
        };
        homeroomSelect.addEventListener('change', toggle);
        toggle();
    }

    const loginForm = document.querySelector('[data-login-form]');
    const slugSelect = document.querySelector('[data-slug-select]');
    if (loginForm && slugSelect) {
        const updateAction = () => {
            const slug = slugSelect.value || '';
            loginForm.setAttribute('action', '/' + slug);
        };
        slugSelect.addEventListener('change', updateAction);
        updateAction();
    }
});
