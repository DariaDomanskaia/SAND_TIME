<?php
session_start();
require_once '../includes/db.php';

$base_path = '/admin/';

// Генерируем CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Проверяем ID работы
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$workId = (int)$_GET['id'];
$error = '';
$success = '';

// Получаем данные работы
try {
    $stmt = $pdo->prepare("SELECT * FROM works WHERE id = ?");
    $stmt->execute([$workId]);
    $work = $stmt->fetch();
    
    if (!$work) {
        throw new Exception('Работа не найдена');
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit;
}

// Получаем изображения работы
try {
    $stmt = $pdo->prepare("SELECT * FROM work_images WHERE work_id = ? ORDER BY sort_order, id");
    $stmt->execute([$workId]);
    $images = $stmt->fetchAll();
} catch (Exception $e) {
    $images = [];
}

// Обработка обновления данных работы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_work'])) {
    try {
        // Проверка CSRF токена
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Недействительный токен безопасности');
        }

        // Валидация данных
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $year = trim($_POST['year'] ?? '');

        // Проверка заголовка
        if (empty($title)) {
            throw new Exception('Заголовок не может быть пустым');
        }
        if (mb_strlen($title, 'UTF-8') > 70) {
            throw new Exception('Заголовок не должен превышать 70 символов');
        }

        // Проверка описания
        if (empty($description)) {
            throw new Exception('Описание не может быть пустым');
        }
        if (mb_strlen($description, 'UTF-8') > 120) {
            throw new Exception('Описание не должно превышать 120 символов');
        }

        // Проверка года
        if (empty($year)) {
            throw new Exception('Год не может быть пустым');
        }
        if (!preg_match('/^\d{4}$/', $year)) {
            throw new Exception('Год должен состоять из 4 цифр');
        }

        // Обновляем данные работы
        $stmt = $pdo->prepare("UPDATE works SET title = ?, description = ?, year = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $description, $year, $workId]);
        
        $_SESSION['success'] = 'Данные работы успешно обновлены!';
        header('Location: edit.php?id=' . $workId);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Обработка загрузки изображений
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_images'])) {
    try {
        // Проверка CSRF токена
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Недействительный токен безопасности');
        }

        // Проверяем есть ли файлы
        if (empty($_FILES['images']['name'][0])) {
            throw new Exception('Выберите хотя бы одно изображение');
        }

        // Проверяем лимит изображений (максимум 9)
        $currentImagesCount = count($images);
        $newImagesCount = count(array_filter($_FILES['images']['name']));
        $totalImagesCount = $currentImagesCount + $newImagesCount;
        
        if ($totalImagesCount > 9) {
            throw new Exception('Максимум 9 изображений на работу. У вас уже ' . $currentImagesCount . ', пытаетесь добавить ' . $newImagesCount);
        }

        // Создаем папку uploads если её нет
        $uploadDir = __DIR__ . '/../uploads/works/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadedCount = 0;

        // Обрабатываем каждое изображение
        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
                continue; // Пропускаем файлы с ошибками
            }

            $fileName = $_FILES['images']['name'][$index];
            $fileType = $_FILES['images']['type'][$index];
            $fileSize = $_FILES['images']['size'][$index];

            // Проверяем тип файла
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($fileType, $allowedTypes)) {
                continue; // Пропускаем невалидные файлы
            }

            // Проверяем размер файла (3MB)
            if ($fileSize > 3 * 1024 * 1024) {
                continue; // Пропускаем слишком большие файлы
            }

            // Генерируем уникальное имя файла
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'work_' . $workId . '_' . uniqid() . '.' . $fileExtension;
            $filePath = $uploadDir . $newFileName;

            // Пытаемся загрузить файл
            if (move_uploaded_file($tmpName, $filePath)) {
                // Сохраняем в базу данных
                $relativePath = 'uploads/works/' . $newFileName;
                
                // Определяем порядок сортировки
                $sortOrder = $currentImagesCount + $uploadedCount;
                
                $stmt = $pdo->prepare("INSERT INTO work_images (work_id, image_path, sort_order) VALUES (?, ?, ?)");
                $stmt->execute([$workId, $relativePath, $sortOrder]);
                
                $uploadedCount++;
            }
        }

        if ($uploadedCount > 0) {
            $_SESSION['success'] = 'Успешно загружено ' . $uploadedCount . ' изображений!';
            header('Location: edit.php?id=' . $workId);
            exit;
        } else {
            throw new Exception('Не удалось загрузить ни одного изображения');
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Обработка удаления изображения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    try {
        $imageId = (int)$_POST['image_id'];
        
        // Получаем путь к файлу для удаления
        $stmt = $pdo->prepare("SELECT image_path FROM work_images WHERE id = ? AND work_id = ?");
        $stmt->execute([$imageId, $workId]);
        $image = $stmt->fetch();
        
        if ($image) {
            // Удаляем файл с сервера
            if (file_exists($image['image_path'])) {
            unlink($image['image_path']);
            }
            
            // Удаляем запись из БД
            $stmt = $pdo->prepare("DELETE FROM work_images WHERE id = ?");
            $stmt->execute([$imageId]);
            
            $_SESSION['success'] = 'Изображение успешно удалено!';
            header('Location: edit.php?id=' . $workId);
            exit;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Редактирование работы</h1>
        <a href="<?= $base_path ?>works/" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Назад к списку
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Левая колонка - данные работы -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Основные данные</h5>
                </div>
                <div class="card-body">
                    <?php if ($error && isset($_POST['update_work'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Ошибка:</strong> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="edit.php?id=<?= $workId ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="update_work" value="1">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Заголовок *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($work['title']) ?>" 
                                   maxlength="70" required>
                            <div class="form-text">Максимум 70 символов</div>
                            <div class="text-muted text-end">
                                <span id="titleCount"><?= mb_strlen($work['title'], 'UTF-8') ?></span>/70 символов
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" maxlength="120" required><?= htmlspecialchars($work['description']) ?></textarea>
                            <div class="form-text">Максимум 120 символов</div>
                            <div class="text-muted text-end">
                                <span id="descCount"><?= mb_strlen($work['description'], 'UTF-8') ?></span>/120 символов
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="year" class="form-label">Год создания *</label>
                            <input type="text" class="form-control" id="year" name="year" 
                                   value="<?= htmlspecialchars($work['year']) ?>" 
                                   maxlength="4" pattern="\d{4}" required>
                            <div class="form-text">Год из 4 цифр</div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Обновить данные
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Правая колонка - управление изображениями -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Управление изображениями</h5>
                </div>
                <div class="card-body">
                    <?php if ($error && (isset($_POST['upload_images']) || isset($_POST['delete_image']))): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Ошибка:</strong> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Форма загрузки изображений -->
                    <div class="mb-4">
                        <h6>Добавить изображения</h6>
                        <form action="edit.php?id=<?= $workId ?>" method="POST" enctype="multipart/form-data" id="uploadForm">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="upload_images" value="1">
                            
                            <div class="mb-3">
                                <label for="images" class="form-label">Выберите изображения</label>
                                <input type="file" class="form-control" id="images" name="images[]" 
                                       accept="image/jpeg,image/png,image/webp" multiple>
                                <div class="form-text">
                                    Можно выбрать несколько файлов. Форматы: JPG, PNG, WebP. Максимальный размер: 3MB. Рекомендуемый размер: 416×305px
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <small>
                                    <i class="bi bi-exclamation-triangle"></i> 
                                    <strong>Требования:</strong> Минимум 3 изображения, максимум 9. Сейчас: <?= count($images) ?> из 9.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-success" id="uploadBtn">
                                <i class="bi bi-upload"></i> Загрузить изображения
                            </button>
                        </form>
                    </div>

                    <!-- Список текущих изображений -->
                    <div>
                        <h6>Текущие изображения (<?= count($images) ?>)</h6>
                        <?php if (empty($images)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Изображения еще не добавлены
                            </div>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($images as $image): ?>
                                <div class="col-6 col-md-4">
                                    <div class="card">
                                        <img src="/<?= htmlspecialchars($image['image_path']) ?>" 
                                             class="card-img-top" 
                                             alt="Изображение работы"
                                             style="height: 100px; object-fit: cover;">
                                        <div class="card-body p-2">
                                            <form action="edit.php?id=<?= $workId ?>" method="POST" class="d-grid">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="delete_image" value="1">
                                                <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Удалить это изображение?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Подсчет символов для заголовка
document.getElementById('title').addEventListener('input', function() {
    const text = this.value;
    const charCount = text.length;
    
    document.getElementById('titleCount').textContent = charCount;
    
    const counter = document.getElementById('titleCount');
    if (charCount > 70) {
        counter.style.color = 'red';
        this.value = text.substring(0, 70);
        document.getElementById('titleCount').textContent = 70;
        counter.style.color = 'inherit';
    } else if (charCount > 60) {
        counter.style.color = 'orange';
    } else {
        counter.style.color = 'inherit';
    }
});

// Подсчет символов для описания
document.getElementById('description').addEventListener('input', function() {
    const text = this.value;
    const charCount = text.length;
    
    document.getElementById('descCount').textContent = charCount;
    
    const counter = document.getElementById('descCount');
    if (charCount > 120) {
        counter.style.color = 'red';
        this.value = text.substring(0, 120);
        document.getElementById('descCount').textContent = 120;
        counter.style.color = 'inherit';
    } else if (charCount > 100) {
        counter.style.color = 'orange';
    } else {
        counter.style.color = 'inherit';
    }
});

// Валидация года
document.getElementById('year').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^\d]/g, '').substring(0, 4);
});

// Валидация формы загрузки изображений
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const files = document.getElementById('images').files;
    const currentImagesCount = <?= count($images) ?>;
    
    if (files.length === 0) {
        e.preventDefault();
        alert('Выберите хотя бы одно изображение');
        return;
    }
    
    if (currentImagesCount + files.length > 9) {
        e.preventDefault();
        alert('Максимум 9 изображений на работу. У вас уже ' + currentImagesCount + ', пытаетесь добавить ' + files.length);
        return;
    }
    
    // Проверяем размер каждого файла
    for (let file of files) {
        if (file.size > 3 * 1024 * 1024) {
            e.preventDefault();
            alert('Файл "' + file.name + '" слишком большой. Максимальный размер: 3MB');
            return;
        }
    }
    
    // Показываем индикатор загрузки
    const uploadBtn = document.getElementById('uploadBtn');
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Загрузка...';
});

// Инициализация счетчиков при загрузке
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    const descInput = document.getElementById('description');
    
    // Триггерим события для подсчета начальных значений
    if (titleInput) titleInput.dispatchEvent(new Event('input'));
    if (descInput) descInput.dispatchEvent(new Event('input'));
});
</script>

<?php include '../includes/footer.php'; ?>