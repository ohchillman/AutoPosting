<?php

namespace App;

use App\Controllers\AutomationController;
use App\Controllers\SettingsController;

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
        // Получаем текущий путь из URL
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($path, PHP_URL_PATH);
        
        // Получаем параметры запроса
        $params = $_POST ?: $_GET;
        
        // Маршрутизация запросов
        switch (true) {
            // Главная страница
            case $path === '/':
                $controller = new AutomationController();
                echo $controller->index();
                break;
                
            // Страница настроек
            case $path === '/settings':
                $controller = new SettingsController();
                echo $controller->index();
                break;
                
            // Сохранение настроек
            case $path === '/settings/save':
                $controller = new SettingsController();
                echo $controller->save($params);
                break;
                
            // Тестирование настроек
            case $path === '/settings/test':
                $controller = new SettingsController();
                echo $controller->test();
                break;
                
            // Запуск автоматизации
            case $path === '/automation/run':
                $controller = new AutomationController();
                echo $controller->run($params);
                break;
                
            // Проверка статуса системы
            case $path === '/automation/status':
                $controller = new AutomationController();
                echo $controller->status();
                break;
                
            // Страница не найдена
            default:
                header('HTTP/1.0 404 Not Found');
                echo '404 Not Found';
                break;
        }
    }
}
