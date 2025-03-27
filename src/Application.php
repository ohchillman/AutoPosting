<?php

namespace App;

use App\Controllers\AutomationController;

/**
 * Основной класс приложения
 */
class Application
{
    /**
     * Запуск приложения
     */
    public function run()
    {
        // Обработка запроса
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Маршрутизация
        if ($uri === '/' || $uri === '') {
            $controller = new AutomationController();
            echo $controller->index();
        } elseif ($uri === '/automation/run' && $method === 'POST') {
            $controller = new AutomationController();
            echo $controller->run($_POST);
        } else {
            // Страница не найдена
            header('HTTP/1.0 404 Not Found');
            echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена</title>
    <link rel="stylesheet" href="/vendor/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h1>404 - Страница не найдена</h1>
            <p>Запрашиваемая страница не существует.</p>
            <a href="/" class="btn btn-primary">Вернуться на главную</a>
        </div>
    </div>
</body>
</html>';
        }
    }
}
