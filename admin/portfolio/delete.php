<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$workId = $input['id'] ?? 0;

if (!$workId) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID']);
    exit;
}

try {
    // Сначала получаем путь к файлу для удаления
    $stmt = $pdo->prepare("SELECT image_path FROM portfolio WHERE id = ?");
    $stmt->execute([$workId]);
    $work = $stmt->fetch();
    
    if ($work) {
        // Удаляем файл изображения
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $work['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Удаляем запись из БД
        $stmt = $pdo->prepare("DELETE FROM portfolio WHERE id = ?");
        $stmt->execute([$workId]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>