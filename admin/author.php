<?php
session_start();
require_once 'includes/db.php';

$error = '';
$success = '';

// Генерируем CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Получаем текущие данные автора
try {
    $stmt = $pdo->query("SELECT * FROM author LIMIT 1");
    $author = $stmt->fetch();
} catch (Exception $e) {
    $author = null;
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Проверка CSRF токена
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Недействительный токен безопасности');
        }

        // Валидация описания
        $description = ($_POST['description']);
        if (empty($description)) {
            throw new Exception('Описание не может быть пустым');
        }
        if (mb_strlen($description, 'UTF-8') > 1000) {
            throw new Exception('Описание не должно превышать 1000 символов');
        }

        // Обработка изображения
        $photoPath = $author['photo_path'] ?? '';

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $_FILES['photo'];

            // Проверяем тип файла
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($photo['type'], $allowedTypes)) {
                throw new Exception('Разрешены только изображения в формате JPG, PNG или WebP');
            }

            // Проверяем размер файла (5MB)
            if ($photo['size'] > 5 * 1024 * 1024) {
                throw new Exception('Размер изображения не должен превышать 5MB');
            }

            // Проверяем размеры изображения
            $imageInfo = getimagesize($photo['tmp_name']);
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                $recommendedWidth = 384;
                $recommendedHeight = 460;
                
                if ($width != $recommendedWidth || $height != $recommendedHeight) {
                    $error .= " Рекомендуемый размер изображения: {$recommendedWidth}×{$recommendedHeight}px. Ваше изображение: {$width}×{$height}px.";
                }
            }

            // Создаем папку uploads если её нет
            $uploadDir = __DIR__ . '/uploads/author/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Генерируем уникальное имя файла
            $fileExtension = pathinfo($photo['name'], PATHINFO_EXTENSION);
            $fileName = 'author_' . uniqid() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;

            // Пытаемся загрузить файл
            if (!move_uploaded_file($photo['tmp_name'], $filePath)) {
                throw new Exception('Ошибка при сохранении изображения');
            }

            // Удаляем старое изображение если оно есть
            if ($author && $author['photo_path'] && file_exists('../' . $author['photo_path'])) {
                unlink('../' . $author['photo_path']);
            }

            $photoPath = 'admin/uploads/author/' . $fileName;
        }

        // Обработка удаления фото
        if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == 'on') {
            if ($author && $author['photo_path'] && file_exists('../' . $author['photo_path'])) {
                unlink('../' . $author['photo_path']);
            }
            $photoPath = '';
        }

        // Сохраняем/обновляем данные в базе
        if ($author) {
            // Обновляем существующую запись
            $stmt = $pdo->prepare("UPDATE author SET description = ?, photo_path = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$description, $photoPath, $author['id']]);
        } else {
            // Создаем новую запись
            $stmt = $pdo->prepare("INSERT INTO author (description, photo_path) VALUES (?, ?)");
            $stmt->execute([$description, $photoPath]);
        }

        $success = 'Данные автора успешно сохранены!' . ($error ? ' Но: ' . $error : '');
        $error = '';

        // Обновляем данные автора для отображения
        $stmt = $pdo->query("SELECT * FROM author LIMIT 1");
        $author = $stmt->fetch();

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
                <h1>Об авторе</h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Назад к услугам
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

                    <form action="author.php" method="POST" enctype="multipart/form-data" id="authorForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Описание *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="8" maxlength="1000" required 
                                      placeholder="Расскажите о себе, своем опыте и творческом подходе"><?= isset($author['description']) ? htmlspecialchars($author['description']) : '' ?></textarea>
                            <div class="form-text">Максимум 1000 символов. Для добавления переноса строки используйте тег &ltbr&gt</div>
                            <div class="text-muted text-end">
                                <span id="charCount"><?= isset($author['description']) ? mb_strlen($author['description'], 'UTF-8') : 0 ?></span>/1000 символов
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="photo" class="form-label">Фотография <?= isset($author['photo_path']) ? '' : '*' ?></label>
                            <input type="file" class="form-control" id="photo" name="photo" 
                                   accept="image/jpeg,image/png,image/webp" <?= isset($author['photo_path']) ? '' : 'required' ?>>
                            <div class="form-text">
                                Форматы: JPG, PNG, WebP. Максимальный размер: 5MB. Рекомендуемый размер: 384×460px
                            </div>
                            
                            <!-- Текущее изображение -->
                            <?php if (isset($author['photo_path']) && $author['photo_path']): ?>
                                <div class="mt-3">
                                    <p><strong>Текущая фотография:</strong></p>
                                    <img src="../<?= htmlspecialchars($author['photo_path']) ?>" 
                                         alt="Текущая фотография" class="img-thumbnail" style="max-width: 200px;">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="removePhoto" name="remove_photo">
                                        <label class="form-check-label" for="removePhoto">
                                            Удалить текущую фотографию
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Превью нового изображения -->
                            <div class="mt-2" id="photoPreview" style="display: none;">
                                <p><strong>Новая фотография:</strong></p>
                                <img id="preview" src="#" alt="Превью" class="img-thumbnail mt-2" style="max-width: 200px;">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary">Очистить</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Сохранить данные
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
    if (charCount > 1000) {
        counter.style.color = 'red';
        this.value = text.substring(0, 1000);
        document.getElementById('charCount').textContent = 1000;
        counter.style.color = 'inherit';
    } else if (charCount > 900) {
        counter.style.color = 'orange';
    } else {
        counter.style.color = 'inherit';
    }
});

// Превью фотографии
document.getElementById('photo').addEventListener('change', function(e) {
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('photoPreview');
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
document.getElementById('authorForm').addEventListener('submit', function(e) {
    const description = document.getElementById('description').value.trim();
    const photo = document.getElementById('photo').files[0];
    const hasExistingPhoto = <?= isset($author['photo_path']) && $author['photo_path'] ? 'true' : 'false' ?>;
    
    if (!description) {
        e.preventDefault();
        alert('Введите описание');
        return;
    }
    
    if (!hasExistingPhoto && !photo) {
        e.preventDefault();
        alert('Загрузите фотографию');
        return;
    }
    
    if (photo && photo.size > 5 * 1024 * 1024) {
        e.preventDefault();
        alert('Размер фотографии не должен превышать 5MB');
        return;
    }
});
</script>

<?php include 'includes/footer.php'; ?>