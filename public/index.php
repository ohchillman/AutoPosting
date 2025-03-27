<?php
// Точка входа в приложение
require_once __DIR__ . '/vendor/autoload.php';

// Загрузка конфигурации
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Запуск приложения
$app = new App\Application();
$app->run();
