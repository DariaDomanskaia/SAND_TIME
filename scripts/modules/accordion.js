// Инициализация аккордеона
function initAccordion() {
    const accordion = document.querySelector('.accordion');
    if (!accordion) return;

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
                body.style.maxHeight = body.scrollHeight + 'px';
                body.style.opacity = '1';
            }, 10);
        }
    });

    console.log('Аккордеон инициализирован');
}

export { initAccordion };