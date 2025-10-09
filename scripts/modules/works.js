import { escapeHtml, showError } from '../utils.js';

let currentPage = 1;
let isLoading = false;
let hasMore = true;
const WORKS_PER_PAGE = 5; // Показываем по 5 работ за раз
const MAX_WORKS = 20; // Максимум 20 работ


// Функция для загрузки работ
async function loadWorks(page = 1, limit = WORKS_PER_PAGE) {
    if (isLoading) return;
    
    isLoading = true;
    updateLoadMoreButton();
    
    try {
        console.log(`Загружаем работы, страница ${page}, лимит ${limit}`);
        const response = await fetch(`/api/works/get.php?page=${page}&limit=${limit}`);
        
        if (!response.ok) {
            throw new Error(`Ошибка HTTP! статус: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Данные работ получены:', result);
        
        if (result.success) {
            if (page === 1) {
                renderWorks(result.data);
            } else {
                appendWorks(result.data);
            }
            
            currentPage = page;
            hasMore = result.pagination.has_more && getTotalLoadedWorks() < MAX_WORKS;
            
            // Скрываем кнопку если достигли максимума или больше нет работ
            if (!hasMore) {
                hideLoadMoreButton();
            }
        } else {
            throw new Error(result.message || 'Не удалось загрузить работы');
        }
    } catch (error) {
        console.error('Ошибка загрузки работ:', error);
        showError('Ошибка загрузки работ', 'works-container');
    } finally {
        isLoading = false;
        updateLoadMoreButton();
    }
}

// Получаем общее количество загруженных работ
function getTotalLoadedWorks() {
    const container = document.getElementById('works-container');
    return container ? container.querySelectorAll('.accordion-item').length : 0;
}

// Функция для отрисовки работ
function renderWorks(works) {
    const container = document.getElementById('works-container');
    
    if (!works || works.length === 0) {
        container.innerHTML = '<div class="alert alert-info text-center"><p>Работ пока нет.</p></div>';
        hideLoadMoreButton();
        return;
    }
    
    container.innerHTML = works.map(work => createWorkHTML(work)).join('');
    
    // Инициализируем аккордеон и слайдеры для работ
    initWorksAccordion();
    initWorksSliders();
}

// Функция для дозагрузки работ
function appendWorks(works) {
    const container = document.getElementById('works-container');
    
    if (!works || works.length === 0) {
        hasMore = false;
        hideLoadMoreButton();
        return;
    }
    
    const newHTML = works.map(work => createWorkHTML(work)).join('');
    container.insertAdjacentHTML('beforeend', newHTML);
    
    // Инициализируем слайдеры только для новых работ
    initWorksSliders();
}

// Создание HTML для одной работы
function createWorkHTML(work) {
    if (!work.images || work.images.length < 3) {
        console.warn('Работа', work.id, 'имеет недостаточно изображений:', work.images ? work.images.length : 0);
        return '';
    }

    // Создаем слайды по 3 изображения в каждом
    const slides = [];
    for (let i = 0; i < work.images.length; i += 3) {
        const slideImages = work.images.slice(i, i + 3).map(img => `
            <img src="/${escapeHtml(img.image_path)}" alt="${escapeHtml(work.title)}" class="slider-image"
                 onerror="this.src='/images/Photos/Accordion-Img1.png'">
        `).join('');
        
        slides.push(`
            <div class="slider-slide">
                ${slideImages}
            </div>
        `);
    }

    // Создаем пагинацию
    const bulletsHTML = slides.map((_, index) => `
        <span class="slider-bullet ${index === 0 ? 'active' : ''}" data-slide="${index}"></span>
    `).join('');

    return `
    <div class="accordion-item">
        <div class="accordion-header">
            <div class="accordion-item-info">
                <h4 class="accordion-title">${escapeHtml(work.title)}</h4>
                <p class="accordion-desc">${escapeHtml(work.description)}</p>
                <p class="accordion-desc-year">${escapeHtml(work.description)}, ${escapeHtml(work.year)} г.</p>
                <p class="year">${escapeHtml(work.year)}</p>
            </div>
            <button class="accordion-btn default">
                <!-- Стрелка вниз -->
                <svg width="22" height="12" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L11 11L21 1" stroke="#151514" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <button class="accordion-btn active">
                <!-- Стрелка вверх -->
                <svg width="22" height="12" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 11L11 1L1 11" stroke="#151514" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </div>
        <div class="accordion-body">
            <div class="slider">
                <div class="slider-container">
                    ${slides.join('')}
                </div>
                ${slides.length > 1 ? `
                <div class="slider-controls">
                    <button class="slider-arrow prev">
                        <svg width="22" height="12" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 1L11 11L21 1" stroke="#151514" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <div class="slider-pagination">
                        ${bulletsHTML}
                    </div>
                    <button class="slider-arrow next">
                        <svg width="22" height="12" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 1L11 11L21 1" stroke="#151514" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                ` : ''}
            </div>
        </div>
    </div>
`;
}

// Обновление кнопки "Показать ещё"
function updateLoadMoreButton() {
    const loadMoreBtn = document.querySelector('.btn-show-more');
    if (loadMoreBtn) {
        loadMoreBtn.disabled = isLoading;
        loadMoreBtn.innerHTML = isLoading ? 
            'Загрузка...' : 
            `Показать ещё
            <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_810_1671)">
                    <path d="M23.5 4V10H17.5" stroke="#E75512" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M1.5 20V14H7.5" stroke="#E75512" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M4.01 9C4.51717 7.56678 5.37913 6.2854 6.51547 5.27542C7.6518 4.26543 9.02547 3.55976 10.5083 3.22426C11.9911 2.88875 13.5348 2.93434 14.9952 3.35677C16.4556 3.77921 17.7853 4.56471 18.86 5.64L23.5 10M1.5 14L6.14 18.36C7.21475 19.4353 8.54437 20.2208 10.0048 20.6432C11.4652 21.0657 13.0089 21.1112 14.4917 20.7757C15.9745 20.4402 17.3482 19.7346 18.4845 18.7246C19.6209 17.7146 20.4828 16.4332 20.99 15"
                        stroke="#E75512" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </g>
                <defs>
                    <clipPath id="clip0_810_1671">
                        <rect width="24" height="24" fill="white" transform="translate(0.5)" />
                    </clipPath>
                </defs>
            </svg>`;
    }
}

// Обработчик кнопки "Показать ещё"
function loadMoreWorks() {
    if (!isLoading && hasMore) {
        loadWorks(currentPage + 1);
    }
}

// Показ кнопки "Показать ещё"
function showLoadMoreButton() {
    const loadMoreBtn = document.querySelector('.btn-show-more');
    if (loadMoreBtn) {
        loadMoreBtn.style.display = 'block';
    }
}

// Скрытие кнопки "Показать ещё"
function hideLoadMoreButton() {
    const loadMoreBtn = document.querySelector('.btn-show-more');
    if (loadMoreBtn) {
        loadMoreBtn.style.display = 'none';
    }
}

// Инициализация аккордеона работ
function initWorksAccordion() {
    const accordion = document.getElementById('works-accordion');
    if (!accordion) return;

    // Защита от повторной инициализации
    if (accordion.hasAttribute('data-initialized')) {
        return;
    }
    accordion.setAttribute('data-initialized', 'true');

    // ИНИЦИАЛИЗАЦИЯ - закрываем все аккордеоны
    accordion.querySelectorAll('.accordion-item').forEach(item => {
        item.classList.remove('active');
        
        const body = item.querySelector('.accordion-body');
        if (body) {
            body.style.maxHeight = '0';
            body.style.opacity = '0';
        }
        
        // Устанавливаем начальное состояние кнопок
        const defaultBtn = item.querySelector('.accordion-btn.default');
        const activeBtn = item.querySelector('.accordion-btn.active');
        if (defaultBtn) defaultBtn.style.display = 'block';
        if (activeBtn) activeBtn.style.display = 'none';
    });

    accordion.addEventListener('click', function (event) {
        const header = event.target.closest('.accordion-header');
        const btnDefault = event.target.closest('.accordion-btn.default');
        const btnActive = event.target.closest('.accordion-btn.active');
        
        if (!header && !btnDefault && !btnActive) return;

        // Останавливаем всплытие чтобы не сработал общий accordion.js
        event.stopPropagation();
        event.preventDefault();

        const item = (header || btnDefault || btnActive).closest('.accordion-item');
        if (!item) return;

        const body = item.querySelector('.accordion-body');
        if (!body) return;

        const isActive = item.classList.contains('active');

        // Закрываем ВСЕ аккордеоны в works-accordion
        const allWorksItems = accordion.querySelectorAll('.accordion-item');
        allWorksItems.forEach(otherItem => {
            if (otherItem !== item) {
                otherItem.classList.remove('active');
                const otherBody = otherItem.querySelector('.accordion-body');
                if (otherBody) {
                    otherBody.style.maxHeight = '0';
                    otherBody.style.opacity = '0';
                }
                // Закрываем кнопки других элементов
                const otherDefaultBtn = otherItem.querySelector('.accordion-btn.default');
                const otherActiveBtn = otherItem.querySelector('.accordion-btn.active');
                if (otherDefaultBtn) otherDefaultBtn.style.display = 'block';
                if (otherActiveBtn) otherActiveBtn.style.display = 'none';
            }
        });

        // Переключаем текущий аккордеон
        if (isActive) {
            // Закрываем
            item.classList.remove('active');
            body.style.maxHeight = '0';
            body.style.opacity = '0';
            const defaultBtn = item.querySelector('.accordion-btn.default');
            const activeBtn = item.querySelector('.accordion-btn.active');
            if (defaultBtn) defaultBtn.style.display = 'block';
            if (activeBtn) activeBtn.style.display = 'none';
        } else {
            // Открываем
            item.classList.add('active');
            body.style.maxHeight = body.scrollHeight + 'px';
            body.style.opacity = '1';
            const defaultBtn = item.querySelector('.accordion-btn.default');
            const activeBtn = item.querySelector('.accordion-btn.active');
            if (defaultBtn) defaultBtn.style.display = 'none';
            if (activeBtn) activeBtn.style.display = 'block';
        }
    });
}

// Инициализация слайдеров работ
function initWorksSliders() {
    const sliders = document.querySelectorAll('#works-accordion .slider');

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
}

// Инициализация модуля работ
function initWorks() {
    const loadMoreBtn = document.querySelector('.btn-show-more');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreWorks);
    }
    
    // Загружаем первую страницу работ (5 работ)
    loadWorks(1);
}

export { initWorks, loadWorks, loadMoreWorks };