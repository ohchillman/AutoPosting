<?php

// Включаем отображение всех ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Настраиваем логирование ошибок
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/app.log');

// Загружаем автозагрузчик Composer
require __DIR__ . '/../vendor/autoload.php';

// Запускаем приложение
$app = new \App\Application();
$app->run();
