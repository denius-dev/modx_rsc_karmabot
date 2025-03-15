<?php

// Включение отображения ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение конфигурации
require_once 'config.php';

// Подключение MODX
require_once 'modx_connection.php';

// Подключение вспомогательных функций
require_once 'functions.php';

// Подключение обработчиков
require_once 'ban_handler.php';
require_once 'karma_handler.php';
require_once 'message_handler.php';

// Получение входящих данных
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update['message'])) {
    handleMessage($update['message']);
}