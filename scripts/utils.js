// Защита от XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

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

// Показать ошибку
function showError(message) {
    const container = document.getElementById('services-container');
    container.innerHTML = `<p class="error">${message}</p>`;
}

// Обработчик ошибок для изображений
function initImageErrorHandling() {
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG') {
            console.warn('Ошибка загрузки изображения:', e.target.src);
            if (e.target.id === 'author-photo') {
                e.target.src = 'images/placeholder-author.jpg';
            }
            // Для изображений работ
            if (e.target.closest('.slider-slide')) {
                e.target.src = 'images/placeholder-work.jpg';
            }
        }
    }, true);
}

export { escapeHtml, safeHtml, showError, initImageErrorHandling };

// Обработчик ошибок для изображений (глобальный)
/*document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG') {
            console.warn('Ошибка загрузки изображения:', e.target.src);
            if (e.target.id === 'author-photo') {
                e.target.src = 'images/placeholder-author.jpg';
            }
        }
    }, true);
});*/