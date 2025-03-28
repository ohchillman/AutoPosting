<?php
// Включаем отображение всех ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Простая функция для безопасного вывода
function safe_echo($text) {
    echo htmlspecialchars($text);
}

// Начало HTML
echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Минимальная отладка</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            color: #2c3e50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            margin: -15px -15px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 14px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Минимальная отладка системы</h1>
        
        <div class="card">
            <div class="card-header">Системная информация</div>
            <div class="card-body">
                <p><strong>PHP версия:</strong> <?php echo PHP_VERSION; ?></p>
                <p><strong>Операционная система:</strong> <?php echo PHP_OS; ?></p>
                <p><strong>Время сервера:</strong> <?php echo date("Y-m-d H:i:s"); ?></p>
                <p><strong>Директория проекта:</strong> <?php echo __DIR__; ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Журнал приложения</div>
            <div class="card-body">
                <pre><?php
                $logFile = __DIR__ . "/../logs/app.log";
                if (file_exists($logFile)) {
                    $logs = file_get_contents($logFile);
                    $logs = explode("\n", $logs);
                    $logs = array_slice($logs, max(0, count($logs) - 30));
                    foreach ($logs as $log) {
                        echo htmlspecialchars($log) . "\n";
                    }
                } else {
                    echo "Файл журнала не найден: " . $logFile;
                }
                ?></pre>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Проверка файловой системы</div>
            <div class="card-body">
                <pre><?php
                $directories = [
                    "Корневая директория" => __DIR__ . "/..",
                    "Директория src" => __DIR__ . "/../src",
                    "Директория logs" => __DIR__ . "/../logs",
                    "Директория public" => __DIR__,
                    "Директория vendor" => __DIR__ . "/../vendor"
                ];
                
                foreach ($directories as $name => $path) {
                    echo htmlspecialchars($name . ": ");
                    if (is_dir($path)) {
                        echo "Существует\n";
                    } else {
                        echo "Не существует\n";
                    }
                }
                ?></pre>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Проверка PHP расширений</div>
            <div class="card-body">
                <pre><?php
                $requiredExtensions = [
                    "pdo",
                    "pdo_mysql",
                    "curl",
                    "json",
                    "mbstring",
                    "xml"
                ];
                
                foreach ($requiredExtensions as $ext) {
                    echo htmlspecialchars($ext . ": ");
                    if (extension_loaded($ext)) {
                        echo "Загружено\n";
                    } else {
                        echo "Не загружено\n";
                    }
                }
                ?></pre>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Переменные окружения</div>
            <div class="card-body">
                <pre><?php
                $envFile = __DIR__ . "/../.env";
                if (file_exists($envFile)) {
                    $env = file_get_contents($envFile);
                    // Скрываем пароли и токены
                    $env = preg_replace('/(_KEY|_SECRET|PASSWORD)=.*/', '$1=***HIDDEN***', $env);
                    echo htmlspecialchars($env);
                } else {
                    echo "Файл .env не найден: " . $envFile;
                }
                ?></pre>
            </div>
        </div>
    </div>
</body>
</html>';
