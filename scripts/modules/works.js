import { escapeHtml, showError } from '../utils.js';

let currentPage = 1;
let isLoading = false;
let hasMore = true;

// Функция для загрузки работ
async function loadWorks(page = 1) {
    if (isLoading) return;
    
    isLoading = true;
    updateLoadMoreButton();
    
    try {
        console.log(`Загружаем работы, страница ${page}`);
        const response = await fetch(`/api/works/get.php?page=${page}`);
        
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
            hasMore = result.data && result.data.length === 20;
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

// Функция для отрисовки работ
function renderWorks(works) {
    const container = document.getElementById('works-container');
    
    if (!works || works.length === 0) {
        container.innerHTML = '<p class="text-muted">Работ пока нет.</p>';
        return;
    }
    
    container.innerHTML = works.map(work => createWorkHTML(work)).join('');
}

// Функция для дозагрузки работ
function appendWorks(works) {
    const container = document.getElementById('works-container');
    
    if (!works || works.length === 0) {
        hasMore = false;
        updateLoadMoreButton();
        return;
    }
    
    const newHTML = works.map(work => createWorkHTML(work)).join('');
    container.insertAdjacentHTML('beforeend', newHTML);
}

// Создание HTML для одной работы
function createWorkHTML(work) {
    const imagesHTML = work.images && work.images.length > 0 
        ? work.images.map(img => `
            <div class="slider-slide">
                <img src="${escapeHtml(img.image_path)}" alt="${escapeHtml(work.title)}">
            </div>
        `).join('')
        : '<div class="slider-slide"><img src="images/placeholder-work.jpg" alt="Нет изображения"></div>';

    const bulletsHTML = work.images && work.images.length > 1
        ? work.images.map((_, index) => `
            <div class="slider-bullet ${index === 0 ? 'active' : ''}"></div>
        `).join('')
        : '';

    return `
        <div class="accordion-item">
            <div class="accordion-header">
                <div class="accordion-title">
                    <h3>${escapeHtml(work.title)}</h3>
                    <p>${escapeHtml(work.description)}</p>
                </div>
                <div class="accordion-year">
                    <span>${escapeHtml(work.year)}</span>
                </div>
                <div class="accordion-arrow">
                    <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 1L7 7L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="accordion-body">
                <div class="slider">
                    <div class="slider-container">
                        ${imagesHTML}
                    </div>
                    ${work.images && work.images.length > 1 ? `
                        <div class="slider-arrows">
                            <button class="slider-arrow prev">‹</button>
                            <button class="slider-arrow next">›</button>
                        </div>
                        <div class="slider-pagination">
                            ${bulletsHTML}
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

// Обновление кнопки "Загрузить ещё"
function updateLoadMoreButton() {
    const loadMoreBtn = document.getElementById('load-more-works');
    if (loadMoreBtn) {
        if (!hasMore) {
            loadMoreBtn.style.display = 'none';
        } else {
            loadMoreBtn.style.display = 'block';
            loadMoreBtn.disabled = isLoading;
            loadMoreBtn.textContent = isLoading ? 'Загрузка...' : 'Загрузить ещё';
        }
    }
}

// Обработчик кнопки "Загрузить ещё"
function loadMoreWorks() {
    if (!isLoading && hasMore) {
        loadWorks(currentPage + 1);
    }
}

// Инициализация модуля работ
function initWorks() {
    const loadMoreBtn = document.getElementById('load-more-works');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreWorks);
    }
    
    // Загружаем первую страницу работ
    loadWorks(1);
}

export { initWorks, loadWorks, loadMoreWorks };