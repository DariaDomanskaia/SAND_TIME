<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

try {
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    
    if ($category) {
        // Загрузка по конкретной категории
        $stmt = $pdo->prepare("
            SELECT * FROM portfolio 
            WHERE category = ? AND is_active = TRUE 
            ORDER BY sort_order ASC, created_at DESC
        ");
        $stmt->execute([$category]);
    } else {
        // Загрузка всех изображений
        $stmt = $pdo->prepare("
            SELECT * FROM portfolio 
            WHERE is_active = TRUE 
            ORDER BY category, sort_order ASC, created_at DESC
        ");
        $stmt->execute();
    }
    
    $portfolio = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $portfolio
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при загрузке портфолио'
    ]);
}
?>