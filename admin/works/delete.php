<?php
session_start();
require_once '../includes/db.php';

// Проверяем ID работы
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Неверный ID работы';
    header('Location: index.php');
    exit;
}

$workId = (int)$_GET['id'];

try {
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Получаем изображения для удаления файлов
    $stmt = $pdo->prepare("SELECT image_path FROM work_images WHERE work_id = ?");
    $stmt->execute([$workId]);
    $images = $stmt->fetchAll();
    
    // Удаляем файлы изображений
    foreach ($images as $image) {
        if (file_exists($image['image_path'])) {
            unlink($image['image_path']);
        }
    }
    
    // Удаляем записи из БД (cascade удалит work_images автоматически)
    $stmt = $pdo->prepare("DELETE FROM works WHERE id = ?");
    $stmt->execute([$workId]);
    
    $pdo->commit();
    
    $_SESSION['success'] = 'Работа и все связанные изображения успешно удалены!';
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Ошибка при удалении работы: ' . $e->getMessage();
}

header('Location: index.php');
exit;
?>