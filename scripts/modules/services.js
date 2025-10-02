import { escapeHtml, showError } from '../utils.js';
// Функция для загрузки услуг
async function loadServices() {
    try {
        console.log("я пытаюсь сделать запрос");
        const response = await fetch('/api/services/get.php');
        const result = await response.json();
        
        if (result.success) {
            renderServices(result.data);
        } else {
            showError('Не удалось загрузить услуги');
        }
    } catch (error) {
        showError('Ошибка сети');
    }
}

// Функция для отрисовки услуг
function renderServices(services) {
    const container = document.getElementById('services-container');
    
    if (services.length === 0) {
        container.innerHTML = '<p>Услуг пока нет.</p>';
        return;
    }
    
    container.innerHTML = services.map(service => `
        <a href="#" class="service">
            <div class="service-info">
                <div class="service-desc">
                    <h4 class="service-name">${escapeHtml(service.title)}</h4>
                    <p>${escapeHtml(service.description)}</p>
                </div>
                <button class="service-btn">
                    Сделать заявку
                    <div class="arrow-right-btn">
                        <svg width="21" height="8" viewBox="0 0 21 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20.2286 4.05766C20.4238 3.86239 20.4238 3.54581 20.2286 3.35055L17.0466 0.168569C16.8513 -0.0266931 16.5347 -0.0266931 16.3395 0.168569C16.1442 0.363831 16.1442 0.680414 16.3395 0.875676L19.1679 3.7041L16.3395 6.53253C16.1442 6.72779 16.1442 7.04437 16.3395 7.23964C16.5347 7.4349 16.8513 7.4349 17.0466 7.23964L20.2286 4.05766ZM-4.37114e-08 4.2041L19.875 4.2041L19.875 3.2041L4.37114e-08 3.2041L-4.37114e-08 4.2041Z" fill="white"/>
                        </svg>
                    </div>
                </button>
            </div>
            <div class="service-img">
                <img src="${escapeHtml(service.image_path)}" alt="${escapeHtml(service.title)}">
            </div>
        </a>
    `).join('');
}
export { loadServices };