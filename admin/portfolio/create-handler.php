<?php
require_once '../includes/db.php';

// Устанавливаем заголовок JSON ДО любого вывода
header('Content-Type: application/json');

// Включаем вывод ошибок для отладки (потом уберем)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

// Проверяем, что файл загружен
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки файла. Код ошибки: ' . $_FILES['image']['error']]);
    exit;
}

// Валидация категории
$categories = ['lightshow', 'anniversary', 'wedding', 'concert', 'kids', 'media', 'corporate'];
$category = $_POST['category'] ?? '';

if (empty($category) || !in_array($category, $categories)) {
    echo json_encode(['success' => false, 'message' => 'Неверная категория']);
    exit;
}

// Проверка ограничения 10 изображений на категорию
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM portfolio WHERE category = ? AND is_active = TRUE");
    $stmt->execute([$category]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentCount = $result['count'] ?? 0;
    
    if ($currentCount >= 10) {
        echo json_encode([
            'success' => false, 
            'message' => 'В этой категории уже максимальное количество изображений (10). Удалите некоторые изображения перед добавлением новых.'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка проверки ограничений: ' . $e->getMessage()]);
    exit;
}

$uploadedFile = $_FILES['image'];

// Проверка типа файла
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

// Проверяем MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
finfo_close($finfo);

// Также проверяем расширение файла
$fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

if (!in_array($mimeType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Недопустимый формат файла. Разрешены: JPG, PNG, WebP']);
    exit;
}

// Проверка размера файла (2MB)
if ($uploadedFile['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Размер файла превышает 2MB']);
    exit;
}

try {
    // Создаем папку для загрузок если не существует
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/portfolio/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Не удалось создать папку для загрузок');
        }
    }

    // Проверяем права на запись
    if (!is_writable($uploadDir)) {
        throw new Exception('Нет прав на запись в папку загрузок');
    }

    // Генерируем уникальное имя файла
    $filename = uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $filename;

    // Ресайз и сохранение изображения
    $result = resizeAndSaveImage($uploadedFile['tmp_name'], $filePath, 800, 600);
    
    if (!$result) {
        throw new Exception('Ошибка обработки изображения');
    }

    // Определяем относительный путь для БД
    $relativePath = 'uploads/portfolio/' . $filename;

    // Получаем максимальный sort_order для этой категории
    $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM portfolio WHERE category = ?");
    $stmt->execute([$category]);
    $result = $stmt->fetch();
    $nextOrder = ($result['max_order'] ?? -1) + 1;

    // Сохраняем в БД
    $stmt = $pdo->prepare("
        INSERT INTO portfolio (category, image_path, sort_order) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$category, $relativePath, $nextOrder]);

    echo json_encode([
        'success' => true,
        'message' => 'Изображение успешно добавлено в портфолио!'
    ]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Portfolio upload error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка: ' . $e->getMessage()
    ]);
}

/**
 * Ресайз и сохранение изображения с сохранением пропорций
 */
function resizeAndSaveImage($sourcePath, $destinationPath, $maxWidth, $maxHeight) {
    // Проверяем, что файл существует
    if (!file_exists($sourcePath)) {
        throw new Exception('Исходный файл не найден');
    }

    // Получаем информацию об изображении
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        throw new Exception('Не удалось определить тип изображения');
    }

    list($originalWidth, $originalHeight, $type) = $imageInfo;

    // Определяем тип изображения и создаем ресурс
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            throw new Exception('Неподдерживаемый тип изображения');
    }

    if (!$source) {
        throw new Exception('Не удалось создать ресурс изображения');
    }

    // Вычисляем новые размеры с сохранением пропорций
    $ratio = $originalWidth / $originalHeight;
    
    if ($maxWidth / $maxHeight > $ratio) {
        $newWidth = $maxHeight * $ratio;
        $newHeight = $maxHeight;
    } else {
        $newWidth = $maxWidth;
        $newHeight = $maxWidth / $ratio;
    }

    // Округляем размеры
    $newWidth = round($newWidth);
    $newHeight = round($newHeight);

    // Создаем новое изображение
    $destination = imagecreatetruecolor($newWidth, $newHeight);

    if (!$destination) {
        imagedestroy($source);
        throw new Exception('Не удалось создать новое изображение');
    }

    // Сохраняем прозрачность для PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Ресайз с улучшенным качеством
    $resizeResult = imagecopyresampled(
        $destination, $source, 
        0, 0, 0, 0, 
        $newWidth, $newHeight, 
        $originalWidth, $originalHeight
    );

    if (!$resizeResult) {
        imagedestroy($source);
        imagedestroy($destination);
        throw new Exception('Ошибка при изменении размера изображения');
    }

    // Сохраняем изображение
    $saveResult = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $saveResult = imagejpeg($destination, $destinationPath, 85);
            break;
        case IMAGETYPE_PNG:
            $saveResult = imagepng($destination, $destinationPath, 8);
            break;
        case IMAGETYPE_WEBP:
            $saveResult = imagewebp($destination, $destinationPath, 85);
            break;
    }

    // Очищаем память
    imagedestroy($source);
    imagedestroy($destination);

    if (!$saveResult) {
        throw new Exception('Не удалось сохранить изображение');
    }

    return true;
}
?>