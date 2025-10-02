
<?php

// Настройки для OpenServer с нестандартным IP
$host = '127.127.126.31'; // Специфичный IP для OpenServer
$port = 3306;
$dbname = 'sand_art_admin';
$username = 'root';
$password = ''; // Пароль пустой

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Для отладки (можно удалить после настройки)
    error_log("Успешное подключение к MySQL на $host:$port");
    
} catch (PDOException $e) {
    // Пробуем альтернативные настройки
    $alternativeHosts = [
        '127.127.126.31',
        '127.0.0.1',
        'localhost'
    ];
    
    $connected = false;
    
    foreach ($alternativeHosts as $altHost) {
        try {
            $pdo = new PDO("mysql:host=$altHost;port=$port;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $host = $altHost;
            $connected = true;
            error_log("Успешное подключение через $altHost");
            break;
        } catch (PDOException $e2) {
            error_log("Ошибка подключения к $altHost: " . $e2->getMessage());
            continue;
        }
    }
    
    if (!$connected) {
        die("Ошибка подключения к базе данных: " . $e->getMessage() . 
            "<br><br>Проверенные настройки:<br>" .
            "Хост: 127.127.126.31, 127.0.0.1, localhost<br>" .
            "Порт: 3306<br>" .
            "Пользователь: root<br>" .
            "Пароль: (пустой)");
    }
}
?>