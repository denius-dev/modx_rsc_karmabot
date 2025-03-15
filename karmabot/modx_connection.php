<?php

// Подключение MODX
// Указываем полный путь до файлов по серверу
require_once '.../config.core.php';
require_once '.../core/model/modx/modx.class.php';

// Инициализация MODX
$modx = new modX();
if (!$modx->initialize('web')) {
    exit("MODX initialization failed");
}