const newsGrid = document.getElementById('newsGrid');
const showMoreBtn = document.getElementById('showMoreBtn');

showMoreBtn.addEventListener('click', function () {
    const isExpanded = newsGrid.classList.toggle('more');
});