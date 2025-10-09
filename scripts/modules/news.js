function initNews() {
    const newsGrid = document.getElementById('newsGrid');
    const showMoreBtn = document.getElementById('showMoreBtn');

    if (!newsGrid || !showMoreBtn) return;

    showMoreBtn.addEventListener('click', function () {
        const isExpanded = newsGrid.classList.toggle('more');
    });

    console.log('Новости инициализированы');
}

export { initNews };