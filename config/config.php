<?php
// Конфигурационный файл для системы автоматизации с антидетект браузерами

return [
    // Общие настройки
    'app_name' => 'Система автоматизации с антидетект браузерами',
    'app_version' => '1.0.0',
    
    // Настройки логирования
    'logging' => [
        'path' => __DIR__ . '/../logs/app.log',
        'level' => 'debug', // debug, info, warning, error, critical
    ],
    
    // Настройки базы данных
    'database' => [
        'host' => 'localhost',
        'dbname' => 'automation_system',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    
    // Настройки браузеров
    'browsers' => [
        // Настройки AdsPower
        'adspower' => [
            'enabled' => true,
            'api_base_url' => 'http://local.adspower.net:50325/api/v1',
        ],
        
        // Настройки Dolphin
        'dolphin' => [
            'enabled' => true,
            'api_base_url' => 'http://localhost:3001/v1.0',
            'api_token' => '', // Заполнить API токен из личного кабинета Dolphin
            'chromedriver_path' => '/usr/local/bin/chromedriver',
        ],
    ],
    
    // Настройки социальных сетей
    'social_media' => [
        // Настройки Twitter
        'twitter' => [
            'enabled' => true,
            'login_url' => 'https://twitter.com/login',
            'home_url' => 'https://twitter.com/home',
        ],
        
        // Настройки LinkedIn
        'linkedin' => [
            'enabled' => true,
            'login_url' => 'https://www.linkedin.com/login',
            'home_url' => 'https://www.linkedin.com/feed/',
        ],
    ],
    
    // Настройки Selenium
    'selenium' => [
        'timeout' => 30, // Таймаут ожидания элементов в секундах
        'screenshot_path' => __DIR__ . '/../screenshots',
    ],
];
