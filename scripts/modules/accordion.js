function initAccordion() {
    // Работаем только со статическим аккордеоном (без ID)
    const accordion = document.querySelector('.accordion:not([id])');
    if (!accordion) return;

    const accordionItems = accordion.querySelectorAll('.accordion-item');
    const accordionBodies = accordion.querySelectorAll('.accordion-body');

    // Инициализация: закрываем все аккордеоны при загрузке
    accordionBodies.forEach(body => {
        body.style.maxHeight = '0';
        body.style.opacity = '0';
    });

    accordion.addEventListener('click', function (event) {
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
            setTimeout(() => {
                body.style.maxHeight = body.scrollHeight + 'px';
                body.style.opacity = '1';
            }, 10);
        }
    });
}

export { initAccordion };