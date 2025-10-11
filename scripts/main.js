import { initImageErrorHandling } from './utils.js';
import { loadServices } from './modules/services.js';
import { loadAuthor } from './modules/author.js';
import { initWorks } from './modules/works.js';
import { initAccordion } from './modules/accordion.js';
import { initSliders } from './modules/sliders.js';
import { initPortfolio } from './modules/portfolio.js';
import { initPrice } from './modules/price.js';
import { initNews } from './modules/news.js';

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