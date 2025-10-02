<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

try {
    // Получаем параметры пагинации
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 20; // Максимум 20 работ
    $offset = ($page - 1) * $limit;
    
    // Получаем общее количество работ
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM works");
    $totalWorks = $countStmt->fetch()['total'];
    $totalPages = ceil($totalWorks / $limit);
    
    // Получаем работы с изображениями
    $stmt = $pdo->prepare("
        SELECT w.*, 
               GROUP_CONCAT(wi.image_path ORDER BY wi.sort_order, wi.id) as images
        FROM works w 
        LEFT JOIN work_images wi ON w.id = wi.work_id 
        GROUP BY w.id 
        ORDER BY w.sort_order DESC, w.year DESC, w.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $works = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Форматируем изображения в массив
    foreach ($works as &$work) {
        $work['images'] = $work['images'] ? explode(',', $work['images']) : [];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $works,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_works' => $totalWorks,
            'has_more' => $page < $totalPages
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при загрузке работ'
    ]);
}
?>