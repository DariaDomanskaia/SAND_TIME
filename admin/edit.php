<?php
session_start();
require_once 'includes/db.php';

// Проверяем, передан ли ID услуги
if (empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);
$error = '';
$success = '';

// Получаем данные услуги для отображения в форме
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();
    
    if (!$service) {
        throw new Exception('Услуга не найдена');
    }
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// Генерируем CSRF токен
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
        if (empty($title)) {
            throw new Exception('Название не может быть пустым');
        }
        if (mb_strlen($title, 'UTF-8') > 30) {
            throw new Exception('Название не должно превышать 30 символов');
        }

        // Валидация описания
        $description = trim($_POST['description']);
        if (empty($description)) {
            throw new Exception('Описание не может быть пустым');
        }
        if (mb_strlen($description, 'UTF-8') > 145) {
            throw new Exception('Описание не должно превышать 145 символов');
        }

        // Подготовка данных для обновления
        $updateData = [
            'title' => $title,
            'description' => $description,
            'id' => $id
        ];

        $sql = "UPDATE services SET title = :title, description = :description";

        // Если загружено новое изображение
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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

            // Относительный путь для базы данных
            $relativePath = 'admin/uploads/' . $fileName;
            
            // Удаляем старое изображение
            if ($service['image_path'] && file_exists('../' . $service['image_path'])) {
                unlink('../' . $service['image_path']);
            }

            $sql .= ", image_path = :image_path";
            $updateData['image_path'] = $relativePath;
        }

        $sql .= " WHERE id = :id";

        // Обновляем данные в базе
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);

        $success = 'Услуга успешно обновлена!';
        
        // Обновляем данные услуги для отображения
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();

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
                <h1>Редактировать услугу</h1>
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
                        </div>
                    <?php endif; ?>

                    <form action="edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" id="serviceForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Название услуги *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   maxlength="30" required placeholder="Например: Песочное шоу"
                                   value="<?= htmlspecialchars($service['title']) ?>">
                            <div class="form-text">Максимум 30 символов</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" maxlength="145" required 
                                      placeholder="Краткое описание услуги"><?= htmlspecialchars($service['description']) ?></textarea>
                            <div class="form-text">Максимум 145 символов</div>
                            <div class="text-muted text-end">
                                <span id="charCount"><?= mb_strlen($service['description'], 'UTF-8') ?></span>/145 символов
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Изображение</label>
                            <input type="file" class="form-control" id="image" name="image" 
                                   accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">
                                Оставьте пустым, чтобы сохранить текущее изображение. Форматы: JPG, PNG, WebP. Максимальный размер: 3MB
                            </div>
                            
                            <!-- Текущее изображение -->
                            <?php if ($service['image_path']): ?>
                                <div class="mt-3">
                                    <p><strong>Текущее изображение:</strong></p>
                                    <img src="../<?= htmlspecialchars($service['image_path']) ?>" 
                                         alt="Текущее изображение" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            
                            <!-- Превью нового изображения -->
                            <div class="mt-2" id="imagePreview" style="display: none;">
                                <p><strong>Новое изображение:</strong></p>
                                <img id="preview" src="#" alt="Превью" class="img-thumbnail mt-2" style="max-width: 200px;">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Сохранить изменения
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
    const charCount = text.length;
    
    document.getElementById('charCount').textContent = charCount;
    
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

// Превью изображения
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

// Валидация формы
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
    
    if (image && image.size > 3 * 1024 * 1024) {
        e.preventDefault();
        alert('Размер изображения не должен превышать 3MB');
        return;
    }
});
</script>

<?php include 'includes/footer.php'; ?>