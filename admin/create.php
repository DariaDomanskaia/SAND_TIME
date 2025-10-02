<?php
session_start();
require_once 'includes/db.php';

$error = '';
$success = '';

// Генерируем CSRF токен если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Проверка CSRF токена
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Недействительный токен безопасности');
        }

        // Проверяем обязательные поля
        if (empty($_POST['title']) || empty($_POST['description'])) {
            throw new Exception('Все поля обязательны для заполнения');
        }

        // Валидация названия
        $title = trim($_POST['title']);
        if (mb_strlen($title) > 30) {
            throw new Exception('Название не должно превышать 30 символов');
        }

        // Валидация описания
        $description = trim($_POST['description']);
        if (mb_strlen($description) > 145) {
            throw new Exception('Описание не должно превышать 145 символов');
        }

        // Проверяем загружено ли изображение
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка загрузки изображения');
        }

        $image = $_FILES['image'];

        // Проверяем тип файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($image['type'], $allowedTypes)) {
            throw new Exception('Разрешены только изображения в формате JPG, PNG или WebP');
        }

        // Проверяем размер файла (3MB)
        if ($image['size'] > 3 * 1024 * 1024) {
            throw new Exception('Размер изображения не должен превышать 3MB');
        }

        // Создаем папку uploads если её нет
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Генерируем уникальное имя файла
        $fileExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;

        // Пытаемся загрузить файл
        if (!move_uploaded_file($image['tmp_name'], $filePath)) {
            throw new Exception('Ошибка при сохранении изображения');
        }

        // Относительный путь для базы данных (от корня сайта)
        $relativePath = 'admin/uploads/' . $fileName;

        // Сохраняем данные в базу данных
        $stmt = $pdo->prepare("INSERT INTO services (title, description, image_path) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $relativePath]);

        $success = 'Услуга успешно добавлена!';
        
        // Очищаем поля формы после успешного сохранения
        $_POST = [];

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Добавить новую услугу</h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Назад к списку
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Ошибка:</strong> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Успех!</strong> <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <div class="mt-2">
                                <a href="index.php" class="btn btn-sm btn-success">Вернуться к списку</a>
                                <a href="create.php" class="btn btn-sm btn-outline-success">Добавить еще одну услугу</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="create.php" method="POST" enctype="multipart/form-data" id="serviceForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Название услуги *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   maxlength="30" required placeholder="Например: Песочное шоу"
                                   value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                            <div class="form-text">Максимум 30 символов</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" maxlength="145" required 
                                      placeholder="Краткое описание услуги"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                            <div class="form-text">Максимум 145 символов</div>
                            <div class="text-muted text-end">
                                <span id="charCount">0</span>/145 символов
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Изображение *</label>
                            <input type="file" class="form-control" id="image" name="image" 
                                   accept="image/jpeg,image/png,image/webp" required>
                            <div class="form-text">
                                Форматы: JPG, PNG, WebP. Максимальный размер: 3MB. Рекомендуемый размер: 324×234px
                            </div>
                            
                            <div class="mt-2" id="imagePreview" style="display: none;">
                                <img id="preview" src="#" alt="Превью" class="img-thumbnail mt-2" style="max-width: 200px;">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary">Очистить</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Добавить услугу
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Подсчет символов в описании
document.getElementById('description').addEventListener('input', function() {
    const text = this.value;
    const charCount = text.length; // JavaScript правильно считает символы
    
    document.getElementById('charCount').textContent = charCount;
    
    // Визуальное предупреждение
    const counter = document.getElementById('charCount');
    if (charCount > 145) {
        counter.style.color = 'red';
        this.value = text.substring(0, 145);
        document.getElementById('charCount').textContent = 145;
        counter.style.color = 'inherit';
    } else if (charCount > 130) {
        counter.style.color = 'orange';
    } else {
        counter.style.color = 'inherit';
    }
});

// Также для названия добавим подсчет
document.getElementById('title').addEventListener('input', function() {
    const charCount = this.value.length;
    const maxLength = 30;
    
    if (charCount > maxLength) {
        this.value = this.value.substring(0, maxLength);
    }
});

document.getElementById('image').addEventListener('change', function(e) {
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    const file = e.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
});

document.getElementById('serviceForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const image = document.getElementById('image').files[0];
    
    if (!title) {
        e.preventDefault();
        alert('Введите название услуги');
        return;
    }
    
    if (!description) {
        e.preventDefault();
        alert('Введите описание услуги');
        return;
    }
    
    if (!image) {
        e.preventDefault();
        alert('Выберите изображение');
        return;
    }
    
    if (image.size > 3 * 1024 * 1024) {
        e.preventDefault();
        alert('Размер изображения не должен превышать 3MB');
        return;
    }
});
</script>

<?php include 'includes/footer.php'; ?>