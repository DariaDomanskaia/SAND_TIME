<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Получаем работы с изображениями
    $stmt = $pdo->prepare("
        SELECT w.*, 
               GROUP_CONCAT(wi.image_path ORDER BY wi.sort_order, wi.id) as images_paths,
               GROUP_CONCAT(wi.id ORDER BY wi.sort_order, wi.id) as image_ids
        FROM works w
        LEFT JOIN work_images wi ON w.id = wi.work_id
        GROUP BY w.id
        ORDER BY w.sort_order, w.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $works = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Форматируем данные
    $formattedWorks = [];
    foreach ($works as $work) {
        $images = [];
        if ($work['images_paths']) {
            $imagePaths = explode(',', $work['images_paths']);
            $imageIds = explode(',', $work['image_ids']);
            
            foreach ($imagePaths as $index => $path) {
                $images[] = [
                    'id' => $imageIds[$index],
                    'image_path' => $path
                ];
            }
        }
        
        $formattedWorks[] = [
            'id' => $work['id'],
            'title' => $work['title'],
            'description' => $work['description'],
            'year' => $work['year'],
            'images' => $images
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedWorks,
        'pagination' => [
            'page' => $page,
            'limit' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка загрузки работ: ' . $e->getMessage()
    ]);
}