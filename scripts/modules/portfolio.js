// portfolio.js
let currentCategory = 'lightshow';
let currentContentType = 'photos';
let currentItems = []; // Все загруженные элементы
let visibleCount = 5;  // Сколько показывать initially
let itemsPerLoad = 5;  // Сколько подгружать по кнопке
const MAX_ITEMS_PER_CATEGORY = 10;

async function initPortfolio() {
    console.log('Инициализация портфолио...');
    
    // Загружаем данные для начальной категории
    await loadPortfolioData('lightshow', 'photos');
    
    // Назначаем обработчики событий
    setupEventListeners();
}

async function loadPortfolioData(category = 'lightshow', contentType = 'photos') {
    console.log('Загружаем портфолио:', category, contentType);
    
    try {
        const response = await fetch(`/api/portfolio/get.php?category=${category}`);
        const data = await response.json();
        console.log('Данные портфолио получены:', data);
        
        // Сохраняем все элементы (ограничиваем максимумом)
        currentItems = data.data.slice(0, MAX_ITEMS_PER_CATEGORY);
        currentCategory = category;
        currentContentType = contentType;
        
        // Сбрасываем счетчик при загрузке новых данных
        visibleCount = Math.min(5, currentItems.length);
        
        // Показываем первые N элементов
        renderPortfolio(currentItems.slice(0, visibleCount), category, contentType);
        updateShowMoreButton();
        
    } catch (error) {
        console.error('Ошибка загрузки портфолио:', error);
    }
}

function renderPortfolio(items, category, contentType = 'photos') {
    console.log('Rendering portfolio for:', category, contentType);
    
    const portfolioContainer = document.getElementById('portfolio-container');
    
    if (!portfolioContainer) {
        console.error('Portfolio container not found!');
        return;
    }
    
    console.log(`Rendering ${items.length} items`);
    
    // Очищаем контейнер
    portfolioContainer.innerHTML = '';
    
    // Создаем контейнер для типа контента
    const contentTypeContainer = document.createElement('div');
    contentTypeContainer.className = `content-type ${contentType === 'photos' ? 'active' : ''}`;
    contentTypeContainer.setAttribute('data-content-type', contentType);
    
    // Создаем элементы
    items.forEach(item => {
        const portfolioItem = document.createElement('div');
        portfolioItem.className = 'portfolio-item';
        
        if (item.image_path) {
            let imagePath = item.image_path;
            if (!imagePath.startsWith('/')) {
                imagePath = '/' + imagePath;
            }
            
            portfolioItem.innerHTML = `
                <img src="${imagePath}" alt="${category}" 
                     loading="lazy"
                     onerror="console.error('Failed to load image:', this.src)">
            `;
        }
        
        contentTypeContainer.appendChild(portfolioItem);
    });
    
    portfolioContainer.appendChild(contentTypeContainer);
}

function setupEventListeners() {
    console.log('Настройка обработчиков событий...');
    
    // Обработчики для навигации по категориям
    document.querySelectorAll('.portfolio-nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.getAttribute('data-category');
            console.log('Переключаем категорию на:', category);
            
            document.querySelectorAll('.portfolio-nav-item').forEach(navItem => {
                navItem.classList.remove('active');
            });
            this.classList.add('active');
            
            loadPortfolioData(category, currentContentType);
        });
    });
    
    // Обработчики для переключателя фото/видео
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            console.log('Переключаем тип контента на:', type);
            
            document.querySelectorAll('.toggle-btn').forEach(toggle => {
                toggle.classList.remove('active');
            });
            this.classList.add('active');
            
            loadPortfolioData(currentCategory, type);
        });
    });

    // ✅ ИСПРАВЛЕННЫЙ СЕЛЕКТОР ДЛЯ КНОПКИ ПОРТФОЛИО
    setupShowMoreButton();
}

function setupShowMoreButton() {
    const showMoreBtn = document.querySelector('#portfolio-btn-show-more');
    
    if (showMoreBtn) {
        console.log('ID кнопки:', showMoreBtn.id);
        console.log('Родительский элемент:', showMoreBtn.parentElement?.className);
        
        // Удаляем все старые обработчики
        const newBtn = showMoreBtn.cloneNode(true);
        showMoreBtn.parentNode.replaceChild(newBtn, showMoreBtn);
        
        // ✅ ДОБАВЛЯЕМ ОБРАБОТЧИК НА НОВУЮ КНОПКУ
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showMoreItems();
        });
        
        console.log('Обработчик добавлен на кнопку портфолио');
        
        // Обновляем состояние кнопки
        updateShowMoreButton();
    } else {
        console.error('❌ Кнопка портфолио не найдена!');
        console.log('Все кнопки btn-show-more:', document.querySelectorAll('.btn-show-more'));
    }
}

function showMoreItems() {
    console.log('=== ФУНКЦИЯ showMoreItems ВЫЗВАНА ===');
    console.log('visibleCount до:', visibleCount);
    console.log('currentItems.length:', currentItems.length);
    
    // Проверяем, есть ли еще элементы для показа
    if (visibleCount >= currentItems.length) {
        console.log('❌ Нет элементов для показа - скрываем кнопку');
        updateShowMoreButton();
        return;
    }
    
    // Увеличиваем счетчик
    const newVisibleCount = Math.min(visibleCount + itemsPerLoad, currentItems.length);
    console.log('visibleCount после:', newVisibleCount);
    
    // Обновляем глобальную переменную
    visibleCount = newVisibleCount;
    
    // Рендерим новые элементы
    renderPortfolio(currentItems.slice(0, visibleCount), currentCategory, currentContentType);
    
    // Обновляем кнопку
    updateShowMoreButton();
}

function updateShowMoreButton() {
    const showMoreBtn = document.querySelector('#portfolio-btn-show-more');
    
    if (!showMoreBtn) {
        return;
    }
    
    console.log('updateShowMoreButton для портфолио:');
    console.log('visibleCount:', visibleCount, 'currentItems.length:', currentItems.length);
    
    // Проверяем, нужно ли скрыть кнопку
    const shouldHideButton = visibleCount >= currentItems.length;
    
    if (shouldHideButton) {
        showMoreBtn.style.display = 'none';
    } else {
        showMoreBtn.style.display = 'flex';
        
        showMoreBtn.innerHTML = `
            Показать ещё 
            <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_810_1671)">
                    <path d="M23.5 4V10H17.5" stroke="#E75512" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M1.5 20V14H7.5" stroke="#E75512" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M4.01 9C4.51717 7.56678 5.37913 6.2854 6.51547 5.27542C7.6518 4.26543 9.02547 3.55976 10.5083 3.22426C11.9911 2.88875 13.5348 2.93434 14.9952 3.35677C16.4556 3.77921 17.7853 4.56471 18.86 5.64L23.5 10M1.5 14L6.14 18.36C7.21475 19.4353 8.54437 20.2208 10.0048 20.6432C11.4652 21.0657 13.0089 21.1112 14.4917 20.7757C15.9745 20.4402 17.3482 19.7346 18.4845 18.7246C19.6209 17.7146 20.4828 16.4332 20.99 15" stroke="#E75512" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <defs>
                    <clipPath id="clip0_810_1671">
                        <rect width="24" height="24" fill="white" transform="translate(0.5)"/>
                    </clipPath>
                </defs>
            </svg>
        `;
    }
}

export { initPortfolio };