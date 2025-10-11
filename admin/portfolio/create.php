<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

$categories = [
    'lightshow' => 'Световые шоу',
    'anniversary' => 'Юбилеи', 
    'wedding' => 'Свадьбы',
    'concert' => 'Концерты',
    'kids' => 'Детям',
    'media' => 'Медиа',
    'corporate' => 'Компаниям'
];

// Получаем категорию из GET параметра если есть
$selectedCategory = $_GET['category'] ?? '';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Добавить изображение в портфолио</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Назад к списку
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="addPortfolioForm" enctype="multipart/form-data">
                        <!-- Категория -->
                        <div class="mb-3">
                            <label for="category" class="form-label">Категория *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Выберите категорию</option>
                                <?php foreach ($categories as $key => $name): ?>
                                    <option value="<?= $key ?>" 
                                        <?= $selectedCategory === $key ? 'selected' : '' ?>>
                                        <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Изображение -->
                        <div class="mb-3">
                            <label for="image" class="form-label">Изображение *</label>
                            <input type="file" class="form-control" id="image" name="image" 
                                   accept="image/jpeg,image/png,image/webp" required>
                            <div class="form-text">
                                <strong>Требования к изображению:</strong><br>
                                • Форматы: JPG, PNG, WebP<br>
                                • Рекомендуемый размер: 800×600px (соотношение 4:3)<br>
                                • Максимальный размер: 2MB<br>
                                • Изображение будет автоматически оптимизировано
                            </div>
                        </div>

                        <!-- Предпросмотр -->
                        <div class="mb-3" id="previewContainer" style="display: none;">
                            <label class="form-label">Предпросмотр:</label>
                            <div class="border rounded p-3 text-center">
                                <img id="imagePreview" src="#" alt="Предпросмотр" 
                                     class="img-fluid rounded" style="max-height: 300px;">
                                <div class="mt-2">
                                    <small id="imageInfo" class="text-muted"></small>
                                </div>
                            </div>
                        </div>

                        <!-- Кнопки -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Добавить в портфолио
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Боковая панель с информацией -->
        <div class="card mt-4">
    <div class="card-header bg-warning bg-opacity-10">
        <h6 class="mb-0">🎯 Особые позиции в сетке</h6>
    </div>
    <div class="card-body">
        <p class="small mb-2"><strong>Позиции 4 и 7</strong> имеют увеличенный размер:</p>
        <ul class="small mb-0">
            <li><strong>Размер:</strong> 865×397px</li>
            <li><strong>Соотношение:</strong> ~2.17:1</li>
            <li><strong>Особенность:</strong> занимают 2 ячейки по ширине</li>
            <li><strong>Важно:</strong> эти позиции нельзя изменить через перетаскивание</li>
        </ul>
    </div>
</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addPortfolioForm');
    const imageInput = document.getElementById('image');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const imageInfo = document.getElementById('imageInfo');

    // Предпросмотр изображения
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Проверка типа файла
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Пожалуйста, выберите изображение в формате JPG, PNG или WebP');
                imageInput.value = '';
                return;
            }

            // Проверка размера файла (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Размер файла не должен превышать 2MB');
                imageInput.value = '';
                return;
            }

            // Показываем предпросмотр
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.style.display = 'block';
                
                // Показываем информацию о файле
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                imageInfo.textContent = `${file.name} (${sizeMB} MB)`;
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    });

    // Обработка отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Базовая валидация
        const category = form.category.value;
        const image = form.image.files[0];
        
        if (!category) {
            showAlert('Пожалуйста, выберите категорию', 'error');
            return;
        }
        
        if (!image) {
            showAlert('Пожалуйста, выберите изображение', 'error');
            return;
        }

        const formData = new FormData(form);
        
        // Показываем индикатор загрузки
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Загрузка...';
        submitBtn.disabled = true;

        fetch('create-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Сначала проверяем статус ответа
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text(); // Сначала читаем как текст
        })
        .then(text => {
            console.log('Raw response:', text);
            
            // Пытаемся распарсить JSON
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showAlert('Изображение успешно добавлено в портфолио!', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    showAlert(data.message || 'Ошибка при добавлении изображения', 'error');
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                showAlert('Ошибка обработки ответа от сервера', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showAlert('Ошибка сети: ' + error.message, 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});

function showAlert(message, type) {
    // Удаляем старые алерты
    const oldAlerts = document.querySelectorAll('.alert');
    oldAlerts.forEach(alert => alert.remove());
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show mt-3`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.card-body').prepend(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}
</script>

<style>
.spinner {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<?php require_once '../includes/footer.php'; ?>