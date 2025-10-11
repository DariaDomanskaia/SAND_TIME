<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$category = $input['category'] ?? '';
$order = $input['order'] ?? [];

if (empty($category) || empty($order)) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    exit;
}

try {
    // Обновляем порядок для каждого элемента
    foreach ($order as $index => $workId) {
        $stmt = $pdo->prepare("
            UPDATE portfolio 
            SET sort_order = ?, updated_at = NOW() 
            WHERE id = ? AND category = ?
        ");
        $stmt->execute([$index, $workId, $category]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>