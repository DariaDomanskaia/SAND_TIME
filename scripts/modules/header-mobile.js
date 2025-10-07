document.addEventListener('DOMContentLoaded', function () {
    const moreButton = document.querySelector('.more-link-mobile');
    const mobileMenu = document.querySelector('.menu-more-mobile');
    const closeButton = document.querySelector('.close-menu');
    const header = document.querySelector('.header-mobile');
    const body = document.body;
    const menuLinks = document.querySelectorAll('.menu-more-link');

    // Функция закрытия меню
    function closeMenu() {
        mobileMenu.classList.remove('active');
        header.classList.remove('menu-open');
        body.style.overflow = '';
    }

    // Открытие меню
    moreButton.addEventListener('click', function () {
        mobileMenu.classList.add('active');
        header.classList.add('menu-open');
        body.style.overflow = 'hidden';
    });

    // Закрытие меню через крестик
    closeButton.addEventListener('click', closeMenu);

    // Закрытие меню при клике вне области контента
    mobileMenu.addEventListener('click', function (e) {
        if (e.target === mobileMenu) {
            closeMenu();
        }
    });

    // Закрытие меню по ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            closeMenu();
        }
    });

    // Обработка кликов по ссылкам меню
    menuLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // Закрываем меню
            closeMenu();

            // Получаем целевой элемент по якорю
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                // Плавная прокрутка к целевой секции
                setTimeout(() => {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 300); // Небольшая задержка для закрытия меню
            }
        });
    });

    // Также добавляем плавную прокрутку для ссылок в основном меню
    const mainNavLinks = document.querySelectorAll('.nav-main a[href^="#"]');
    mainNavLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});