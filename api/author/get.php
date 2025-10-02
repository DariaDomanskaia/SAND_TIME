<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

try {
    $stmt = $pdo->query("SELECT * FROM author LIMIT 1");
    $author = $stmt->fetch();
    
    if ($author) {
        echo json_encode([
            'success' => true,
            'data' => $author
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'Данные автора не заполнены'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при загрузке данных автора'
    ]);
}
?>