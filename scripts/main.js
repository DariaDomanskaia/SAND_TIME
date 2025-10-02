// Загружаем услуги при загрузке страницы
document.addEventListener('DOMContentLoaded', loadServices);

// Функция для загрузки услуг
async function loadServices() {
    try {
        console.log("я пытаюсь сделать запрос");
        const response = await fetch('/api/services/get.php');
        const result = await response.json();
        
        if (result.success) {
            renderServices(result.data);
        } else {
            showError('Не удалось загрузить услуги');
        }
    } catch (error) {
        showError('Ошибка сети');
    }
}

// Функция для отрисовки услуг
function renderServices(services) {
    const container = document.getElementById('services-container');
    
    if (services.length === 0) {
        container.innerHTML = '<p>Услуг пока нет.</p>';
        return;
    }
    
    container.innerHTML = services.map(service => `
        <a href="#" class="service">
            <div class="service-info">
                <div class="service-desc">
                    <h4 class="service-name">${escapeHtml(service.title)}</h4>
                    <p>${escapeHtml(service.description)}</p>
                </div>
                <button class="service-btn">
                    Сделать заявку
                    <div class="arrow-right-btn">
                        <svg width="21" height="8" viewBox="0 0 21 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20.2286 4.05766C20.4238 3.86239 20.4238 3.54581 20.2286 3.35055L17.0466 0.168569C16.8513 -0.0266931 16.5347 -0.0266931 16.3395 0.168569C16.1442 0.363831 16.1442 0.680414 16.3395 0.875676L19.1679 3.7041L16.3395 6.53253C16.1442 6.72779 16.1442 7.04437 16.3395 7.23964C16.5347 7.4349 16.8513 7.4349 17.0466 7.23964L20.2286 4.05766ZM-4.37114e-08 4.2041L19.875 4.2041L19.875 3.2041L4.37114e-08 3.2041L-4.37114e-08 4.2041Z" fill="white"/>
                        </svg>
                    </div>
                </button>
            </div>
            <div class="service-img">
                <img src="${escapeHtml(service.image_path)}" alt="${escapeHtml(service.title)}">
            </div>
        </a>
    `).join('');
}

// Защита от XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Показать ошибку
function showError(message) {
    const container = document.getElementById('services-container');
    container.innerHTML = `<p class="error">${message}</p>`;
}

// Функция для загрузки данных автора
async function loadAuthor() {
    try {
        console.log('Загружаем данные автора...');
        
        const response = await fetch('api/author/get.php');
        
        if (!response.ok) {
            throw new Error(`Ошибка HTTP! статус: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Данные автора получены:', result);
        
        if (result.success && result.data) {
            renderAuthor(result.data);
        } else {
            renderAuthorPlaceholder();
        }
    } catch (error) {
        console.error('Ошибка загрузки данных автора:', error);
        renderAuthorPlaceholder();
    }
}

// Функция для отрисовки данных автора (С УЧЕТОМ HTML-ТЕГОВ В ТЕКСТЕ)
function renderAuthor(author) {
    const container = document.getElementById('author-container');
    const loadingElement = document.getElementById('author-loading');
    const descriptionElement = document.getElementById('author-description');
    const photoElement = document.getElementById('author-photo');
    
    // Скрываем спиннер загрузки
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }
    
    // Устанавливаем фотографию автора
    if (author.photo_path) {
        photoElement.src = author.photo_path;
        photoElement.alt = 'Фотография автора';
        photoElement.onerror = function() {
            this.src = 'images/placeholder-author.jpg';
        };
    } else {
        photoElement.src = 'images/placeholder-author.jpg';
        photoElement.alt = 'Фотография не добавлена';
    }
    
    // Устанавливаем описание автора - РАЗРЕШАЕМ HTML-ТЕГИ
    if (author.description) {
        // Разбиваем текст на параграфы по двойным переносам строк
        const paragraphs = author.description.split(/\n\s*\n/);
        
        let descriptionHTML = '';
        
        if (paragraphs.length > 1) {
            // Если есть двойные переносы - создаем отдельные параграфы
            paragraphs.forEach(paragraph => {
                if (paragraph.trim()) {
                    descriptionHTML += `<p>${paragraph.trim()}</p>`;
                }
            });
        } else {
            // Если нет двойных переносов - один параграф с сохранением <br>
            descriptionHTML = `<p>${author.description}</p>`;
        }
        
        descriptionElement.innerHTML = descriptionHTML + `
            <div class="author-link">
                <button class="main-btn">
                    Ссылки на публикации
                    <div class="arrow-right-btn">
                        <svg width="21" height="8" viewBox="0 0 21 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20.2286 4.05766C20.4238 3.86239 20.4238 3.54581 20.2286 3.35055L17.0466 0.168569C16.8513 -0.0266931 16.5347 -0.0266931 16.3395 0.168569C16.1442 0.363831 16.1442 0.680414 16.3395 0.875676L19.1679 3.7041L16.3395 6.53253C16.1442 6.72779 16.1442 7.04437 16.3395 7.23964C16.5347 7.4349 16.8513 7.4349 17.0466 7.23964L20.2286 4.05766ZM-4.37114e-08 4.2041L19.875 4.2041L19.875 3.2041L4.37114e-08 3.2041L-4.37114e-08 4.2041Z" fill="white"/>
                        </svg>
                    </div>
                </button>
            </div>
        `;
    } else {
        descriptionElement.innerHTML = `
            <p class="text-muted">Информация об авторе пока не добавлена.</p>
            <div class="author-link">
                <button class="main-btn">
                    Ссылки на публикации
                    <div class="arrow-right-btn">
                        <svg width="21" height="8" viewBox="0 0 21 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20.2286 4.05766C20.4238 3.86239 20.4238 3.54581 20.2286 3.35055L17.0466 0.168569C16.8513 -0.0266931 16.5347 -0.0266931 16.3395 0.168569C16.1442 0.363831 16.1442 0.680414 16.3395 0.875676L19.1679 3.7041L16.3395 6.53253C16.1442 6.72779 16.1442 7.04437 16.3395 7.23964C16.5347 7.4349 16.8513 7.4349 17.0466 7.23964L20.2286 4.05766ZM-4.37114e-08 4.2041L19.875 4.2041L19.875 3.2041L4.37114e-08 3.2041L-4.37114e-08 4.2041Z" fill="white"/>
                        </svg>
                    </div>
                </button>
            </div>
        `;
    }
    
    // Показываем контент автора
    container.querySelector('.author-info').style.display = 'block';
    container.querySelector('.author-img').style.display = 'block';
}

// Функция для показа заглушки при ошибке
function renderAuthorPlaceholder() {
    const container = document.getElementById('author-container');
    const loadingElement = document.getElementById('author-loading');
    
    if (loadingElement) {
        loadingElement.innerHTML = `
            <p class="text-muted">Информация об авторе временно недоступна.</p>
            <button onclick="loadAuthor()" class="btn btn-warning mt-2">Попробовать снова</button>
        `;
    }
}


// Защита от XSS
// Безопасная обработка HTML (разрешаем только безопасные теги)
function safeHtml(text) {
    if (text === null || text === undefined) return '';
    
    // Разрешаем только безопасные теги: br, strong, em, i, b
    const allowedTags = {
        'br': true,
        'strong': true,
        'em': true,
        'i': true,
        'b': true
    };
    
    // Экранируем все HTML, затем разрешаем безопасные теги
    const div = document.createElement('div');
    div.textContent = text;
    let escaped = div.innerHTML;
    
    // Восстанавливаем разрешенные теги
    escaped = escaped.replace(/&lt;(\/?(br|strong|em|i|b))&gt;/g, '<$1>');
    
    return escaped;
}

// Загружаем данные автора при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, начинаем загрузку данных автора...');
    loadAuthor();
});

// Обработчик ошибок для изображений (глобальный)
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG') {
            console.warn('Ошибка загрузки изображения:', e.target.src);
            if (e.target.id === 'author-photo') {
                e.target.src = 'images/placeholder-author.jpg';
            }
        }
    }, true);
});



// ACCORDION

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

// PORTFOLIO

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

// PRICE

document.addEventListener('DOMContentLoaded', function () {
    const requirementsElements = document.querySelectorAll('.requirements');

    requirementsElements.forEach(requirements => {
        requirements.addEventListener('click', function () {
            const priceCard = this.closest('.price-card');
            priceCard.classList.toggle('active');
        });
    });
});


// NEWS

const newsGrid = document.getElementById('newsGrid');
const showMoreBtn = document.getElementById('showMoreBtn');

showMoreBtn.addEventListener('click', function () {
    const isExpanded = newsGrid.classList.toggle('more');
});