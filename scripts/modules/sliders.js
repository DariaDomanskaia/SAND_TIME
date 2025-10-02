// Инициализация слайдеров
function initSliders() {
    const sliders = document.querySelectorAll('.slider');

    sliders.forEach(slider => {
        const container = slider.querySelector('.slider-container');
        const slides = slider.querySelectorAll('.slider-slide');
        const bullets = slider.querySelectorAll('.slider-bullet');
        const prevBtn = slider.querySelector('.slider-arrow.prev');
        const nextBtn = slider.querySelector('.slider-arrow.next');

        // Если нет слайдов или только один - скрываем навигацию
        if (!slides || slides.length <= 1) {
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            return;
        }

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
        if (prevBtn) {
            prevBtn.addEventListener('click', () => goToSlide(currentSlide - 1));
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => goToSlide(currentSlide + 1));
        }

        // Обработчики событий для пагинации
        bullets.forEach((bullet, index) => {
            bullet.addEventListener('click', () => goToSlide(index));
        });

        // Инициализация первого слайда
        goToSlide(0);
    });

    console.log('Слайдеры инициализированы');
}

export { initSliders };