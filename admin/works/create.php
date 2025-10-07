<?php
session_start();
require_once '../includes/db.php';

$base_path = '/admin/';

// Генерируем CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        if ($year < 2000 || $year > date('Y')) {
            throw new Exception('Год должен быть между 2000 и ' . date('Y'));
        }

        // Проверяем лимит работ (максимум 20)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM works");
        $count = $stmt->fetch()['count'];
        
        if ($count >= 20) {
            throw new Exception('Достигнут лимит в 20 работ. Удалите старые работы перед добавлением новых.');
        }

        // Сохраняем работу в БД
        $stmt = $pdo->prepare("INSERT INTO works (title, description, year) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $year]);
        
        $workId = $pdo->lastInsertId();
        
        $success = 'Работа успешно добавлена! ID: ' . $workId;
        
        // Редирект на редактирование для загрузки изображений
        $_SESSION['success'] = $success;
        header('Location: edit.php?id=' . $workId);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Добавить работу</h1>
                <a href="<?= $base_path ?>works/" class="btn btn-outline-secondary">
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
                                <a href="<?= $base_path ?>works/" class="btn btn-sm btn-success">Вернуться к списку</a>
                                <a href="create.php" class="btn btn-sm btn-outline-success">Добавить еще одну работу</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="create.php" method="POST" id="workForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Заголовок *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" 
                                   maxlength="70" required placeholder="Название работы">
                            <div class="form-text">Максимум 70 символов</div>
                            <div class="text-muted text-end">
                                <span id="titleCount">0</span>/70 символов
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" maxlength="120" required
                                      placeholder="Краткое описание работы"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                            <div class="form-text">Максимум 120 символов</div>
                            <div class="text-muted text-end">
                                <span id="descCount">0</span>/120 символов
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="year" class="form-label">Год создания *</label>
                            <input type="text" class="form-control" id="year" name="year" 
                                   value="<?= isset($_POST['year']) ? htmlspecialchars($_POST['year']) : date('Y') ?>" 
                                   maxlength="4" pattern="\d{4}" required placeholder="2024">
                            <div class="form-text">Год из 4 цифр (например: 2024)</div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Информация о изображениях</h6>
                            <p class="mb-1">После сохранения работы вы сможете загрузить изображения на странице редактирования.</p>
                            <p class="mb-0"><strong>Требования:</strong> 3-9 изображений, каждое до 3MB, размер 416×305px</p>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary">Очистить</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Сохранить работу
                            </button>
                        </div>
                    </form>
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

// Валидация года (только цифры, максимум 4)
document.getElementById('year').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^\d]/g, '').substring(0, 4);
});

// Инициализация счетчиков при загрузке
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    const descInput = document.getElementById('description');
    
    // Триггерим события для подсчета начальных значений
    titleInput.dispatchEvent(new Event('input'));
    descInput.dispatchEvent(new Event('input'));
});

// Валидация формы
document.getElementById('workForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const year = document.getElementById('year').value.trim();
    
    if (!title) {
        e.preventDefault();
        alert('Введите заголовок работы');
        document.getElementById('title').focus();
        return;
    }
    
    if (!description) {
        e.preventDefault();
        alert('Введите описание работы');
        document.getElementById('description').focus();
        return;
    }
    
    if (!year || year.length !== 4) {
        e.preventDefault();
        alert('Введите корректный год (4 цифры)');
        document.getElementById('year').focus();
        return;
    }
});
</script>

<?php include '../includes/footer.php'; ?>