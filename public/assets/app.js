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
});
