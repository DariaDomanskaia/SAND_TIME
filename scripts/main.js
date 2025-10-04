import { initImageErrorHandling } from './utils.js';
import { } from './modules/services.js';
import { } from './modules/author.js';
import { } from './modules/works.js';
import { } from './modules/accordion.js';
import { } from './modules/sliders.js';
import { } from './modules/portfolio.js';
import { } from './modules/price.js';
import { } from './modules/news.js';

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function () {
    console.log('Инициализация приложения...');

    // Инициализация обработчиков ошибок
    initImageErrorHandling();

    // Загрузка данных
    loadServices();
    loadAuthor();

    // Инициализация работ (аккордеон + пагинация)
    initWorks();

    // Инициализация общих компонентов
    initAccordion();
    initSliders();

    // Инициализация других модулей
    initPortfolio();
    initPrice();
    initNews();

    console.log('Все модули инициализированы');
});

// Делаем функции глобальными для обратной совместимости
window.loadAuthor = loadAuthor;