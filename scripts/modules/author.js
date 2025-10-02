import { safeHtml, showError } from '../utils.js';

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
export { loadAuthor };