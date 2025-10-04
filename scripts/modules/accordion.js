document.addEventListener('DOMContentLoaded', function () {
    const accordion = document.querySelector('.accordion');
    const accordionItems = document.querySelectorAll('.accordion-item');
    const accordionBodies = document.querySelectorAll('.accordion-body');

    // Инициализация: закрываем все аккордеоны при загрузке
    accordionBodies.forEach(body => {
        body.style.maxHeight = '0';
        body.style.opacity = '0';
    });

    accordion.addEventListener('click', function (event) {
        // Проверяем, был ли клик по заголовку аккордеона или его дочерним элементам
        const header = event.target.closest('.accordion-header');
        if (!header) return;

        const item = header.closest('.accordion-item');
        const body = item.querySelector('.accordion-body');
        const isActive = item.classList.contains('active');

        // Закрываем все аккордеоны
        accordionItems.forEach(accordionItem => {
            accordionItem.classList.remove('active');
            const accordionBody = accordionItem.querySelector('.accordion-body');
            accordionBody.style.maxHeight = '0';
            accordionBody.style.opacity = '0';
        });

        // Если аккордеон не был активен, открываем его
        if (!isActive) {
            item.classList.add('active');
            // Устанавливаем небольшую задержку для плавной анимации
            setTimeout(() => {
                body.style.maxHeight = '500px';
                body.style.opacity = '1';
            }, 10);
        }
    });

    // Инициализация слайдеров
    function initSliders() {
        const sliders = document.querySelectorAll('.slider');

        sliders.forEach(slider => {
            const container = slider.querySelector('.slider-container');
            const slides = slider.querySelectorAll('.slider-slide');
            const bullets = slider.querySelectorAll('.slider-bullet');
            const prevBtn = slider.querySelector('.slider-arrow.prev');
            const nextBtn = slider.querySelector('.slider-arrow.next');

            let currentSlide = 0;

            // Функция для переключения слайдов
            function goToSlide(index) {
                if (index < 0) index = slides.length - 1;
                if (index >= slides.length) index = 0;

                currentSlide = index;
                container.style.transform = `translateX(-${currentSlide * 100}%)`;

                // Обновляем пагинацию
                bullets.forEach((bullet, i) => {
                    bullet.classList.toggle('active', i === currentSlide);
                });
            }

            // Обработчики событий для стрелок
            prevBtn.addEventListener('click', () => goToSlide(currentSlide - 1));
            nextBtn.addEventListener('click', () => goToSlide(currentSlide + 1));

            // Обработчики событий для пагинации
            bullets.forEach((bullet, index) => {
                bullet.addEventListener('click', () => goToSlide(index));
            });

            // Инициализация первого слайда
            goToSlide(0);
        });
    }

    // Инициализируем слайдеры после загрузки DOM
    initSliders();
});