<?php
// Тестовый скрипт для проверки интеграции с Octo Browser
require_once __DIR__ . '/../vendor/autoload.php';

use App\Browser\OctoBrowserAdapter;
use App\Browser\OctoBrowserTest;

// Получаем API ключ из параметров запроса
$apiKey = $_GET['api_key'] ?? '';

// Проверяем, что API ключ предоставлен
if (empty($apiKey)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'API ключ не предоставлен. Добавьте параметр api_key в URL.'
    ]);
    exit;
}

// Запускаем тестирование
$results = OctoBrowserTest::runTest($apiKey);

// Выводим результаты
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
