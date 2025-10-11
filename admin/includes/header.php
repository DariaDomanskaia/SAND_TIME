<?php
session_start();

// Определяем базовый путь для корректных ссылок
$base_path = '/admin/';

// Определяем активный раздел
$current_script = $_SERVER['SCRIPT_NAME'];
$active_section = '';

if (strpos($current_script, '/works/') !== false) {
    $active_section = 'works';
} elseif (strpos($current_script, '/portfolio/') !== false) {
    $active_section = 'portfolio';
} elseif (basename($current_script) == 'index.php' && strpos($current_script, '/works/') === false && strpos($current_script, '/portfolio/') === false) {
    $active_section = 'services';
} elseif (basename($current_script) == 'author.php') {
    $active_section = 'author';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | SandArt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        .navbar-nav .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            border-radius: 0.375rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= $base_path ?>index.php">SandArt Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_section == 'services') ? 'active' : '' ?>" 
                           href="<?= $base_path ?>index.php">
                           <i class="bi bi-list-check"></i> Услуги
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_section == 'author') ? 'active' : '' ?>" 
                           href="<?= $base_path ?>author.php">
                           <i class="bi bi-person"></i> Об авторе
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_section == 'works') ? 'active' : '' ?>" 
                           href="<?= $base_path ?>works/">
                           <i class="bi bi-card-image"></i> Работы
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_section == 'portfolio') ? 'active' : '' ?>" 
                           href="<?= $base_path ?>portfolio/">
                           <i class="bi bi-images"></i> Портфолио
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                           <i class="bi bi-tags"></i> Цены
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-box-arrow-right"></i> Выйти</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container mt-4">