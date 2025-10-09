<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

try {
    // Получаем параметры пагинации
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(20, max(1, intval($_GET['limit']))) : 5;
    $offset = ($page - 1) * $limit;
    
    // Получаем общее количество работ
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM works");
    $totalWorks = $countStmt->fetch()['total'];
    $totalPages = ceil($totalWorks / $limit);
    
    // Получаем работы с изображениями - НОВЫЕ ПЕРВЫЕ!
    $stmt = $pdo->prepare("
        SELECT w.*, 
               GROUP_CONCAT(wi.image_path ORDER BY wi.sort_order, wi.id) as images
        FROM works w 
        LEFT JOIN work_images wi ON w.id = wi.work_id 
        GROUP BY w.id 
        ORDER BY w.created_at DESC, w.id DESC
        LIMIT :limit OFFSET :offset
    ");
    
    // Явно указываем типы параметров
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $works = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Форматируем изображения в массив
    foreach ($works as &$work) {
        if ($work['images']) {
            $work['images'] = array_map(function($imagePath) {
                return ['image_path' => $imagePath];
            }, explode(',', $work['images']));
        } else {
            $work['images'] = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $works,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_works' => $totalWorks,
            'has_more' => $page < $totalPages,
            'limit' => $limit
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при загрузке работ: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сервера'
    ]);
}
?>