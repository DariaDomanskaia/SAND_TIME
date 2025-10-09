function initPrice() {
    const requirementsElements = document.querySelectorAll('.requirements');

    requirementsElements.forEach(requirements => {
        requirements.addEventListener('click', function () {
            const priceCard = this.closest('.price-card');
            priceCard.classList.toggle('active');
        });
    });

    console.log('Цены инициализированы');
}

export { initPrice };