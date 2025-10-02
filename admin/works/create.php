<?php
session_start();
require_once '../includes/db.php';

// CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Проверяем лимит работ перед созданием новой
$stmt = $pdo->query("SELECT COUNT(*) as total FROM works");
$totalWorks = $stmt->fetch()['total'];

if ($totalWorks >= 20) {
    throw new Exception('Достигнут лимит в 20 работ. Удалите некоторые работы перед добавлением новых.');
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Валидация
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $year = trim($_POST['year']);
        
        // Проверки
        if (mb_strlen($title) > 70) throw new Exception('Заголовок не более 70 символов');
        if (mb_strlen($description) > 120) throw new Exception('Описание не более 120 символов');
        if (!preg_match('/^\d{4}$/', $year)) throw new Exception('Год должен быть 4 цифры');
        
        // Проверка изображений
        $images = $_FILES['images'] ?? [];
        $imageCount = count(array_filter($images['name']));
        
        if ($imageCount < 3) throw new Exception('Минимум 3 изображения');
        if ($imageCount > 9) throw new Exception('Максимум 9 изображений');
        
        // Сохранение работы и изображений...
        // (полный код будет в следующем сообщении)
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!-- Форма будет содержать: -->
<!-- - Поле заголовка (70 символов) -->
<!-- - Поле описания (120 символов) --> 
<!-- - Поле года (4 цифры) -->
<!-- - Мультизагрузка изображений (3-9 штук, 3MB, 416×305px) -->
<!-- - Валидация и предпросмотр -->