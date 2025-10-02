<?php
session_start();
require_once 'includes/db.php';

// Проверяем, передан ли ID услуги
if (empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);

// Проверяем CSRF токен для безопасности
if (empty($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Недействительный токен безопасности';
    header('Location: index.php');
    exit;
}

try {
    // Получаем данные услуги для удаления изображения
    $stmt = $pdo->prepare("SELECT image_path FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();
    
    if ($service) {
        // Удаляем изображение с сервера
        if ($service['image_path'] && file_exists('../' . $service['image_path'])) {
            unlink('../' . $service['image_path']);
        }
        
        // Удаляем запись из базы данных
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = 'Услуга успешно удалена';
    } else {
        $_SESSION['error'] = 'Услуга не найдена';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Ошибка при удалении услуги: ' . $e->getMessage();
}

header('Location: index.php');
exit;
?>