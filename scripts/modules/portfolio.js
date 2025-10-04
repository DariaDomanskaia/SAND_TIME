document.addEventListener('DOMContentLoaded', function () {
    const showMoreBtn = document.querySelector('.show-more-btn');
    const navItems = document.querySelectorAll('.portfolio-nav-item');
    const toggleButtons = document.querySelectorAll('.toggle-btn');
    const portfolioContents = document.querySelectorAll('.portfolio-content');

    // Обработчик переключателя контента
    toggleButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Убираем активный класс у всех кнопок переключателя
            toggleButtons.forEach(btn => btn.classList.remove('active'));

            // Добавляем активный класс к текущей кнопке
            this.classList.add('active');

            // Получаем тип контента
            const contentType = this.getAttribute('data-type');

            // Находим активный контент категории
            const activeCategory = document.querySelector('.portfolio-content.active');

            // Скрываем все типы контента в активной категории
            const contentTypes = activeCategory.querySelectorAll('.content-type');
            contentTypes.forEach(content => content.classList.remove('active'));

            // Показываем выбранный тип контента
            const activeContent = activeCategory.querySelector(`.content-type[data-content-type="${contentType}"]`);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });

    // Обработчик навигационных ссылок
    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();

            // Убираем активный класс у всех пунктов меню
            navItems.forEach(navItem => navItem.classList.remove('active'));

            // Добавляем активный класс к текущему пункту
            this.classList.add('active');

            // Скрываем весь контент
            portfolioContents.forEach(content => content.classList.remove('active'));

            // Показываем соответствующий контент
            const category = this.getAttribute('data-category');
            const activeContent = document.getElementById(`${category}-content`);
            if (activeContent) {
                activeContent.classList.add('active');

                // Показываем активный тип контента (фото или видео)
                const activeToggle = document.querySelector('.toggle-btn.active');
                const contentType = activeToggle.getAttribute('data-type');
                const contentTypes = activeContent.querySelectorAll('.content-type');

                contentTypes.forEach(content => content.classList.remove('active'));
                const currentContent = activeContent.querySelector(`.content-type[data-content-type="${contentType}"]`);
                if (currentContent) {
                    currentContent.classList.add('active');
                }
            }
        });
    });

    // Обработчик кнопки "Показать еще"
    showMoreBtn.addEventListener('click', function () {
        const activeContent = document.querySelector('.portfolio-content.active');
        const activeType = activeContent.querySelector('.content-type.active');
        const hiddenItems = activeType.querySelector('.hidden-items');

        if (hiddenItems) {
            hiddenItems.classList.toggle('active');

            if (hiddenItems.classList.contains('active')) {
                showMoreBtn.innerHTML = 'Скрыть <i class="fas fa-angle-up"></i>';
            } else {
                showMoreBtn.innerHTML = 'Показать еще <i class="fas fa-smile"></i>';
            }
        }
    });
});