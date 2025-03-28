<?php

namespace App\Core;

use Dotenv\Dotenv;

/**
 * Класс для работы с конфигурацией приложения
 */
class Config
{
    private static $instance = null;
    private $config = [];
    private $settingsManager = null;

    /**
     * Приватный конструктор для реализации паттерна Singleton
     */
    private function __construct()
    {
        // Загрузка переменных окружения для обратной совместимости
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../config');
        $dotenv->load();
        
        // Инициализация менеджера настроек
        try {
            $this->settingsManager = new SettingsManager();
            // Загрузка настроек из базы данных через менеджер настроек
            $this->config = $this->settingsManager->getAllSettings();
        } catch (\Exception $e) {
            // Если не удалось инициализировать менеджер настроек, используем только .env
            $this->loadConfigFromEnv();
        }
    }

    /**
     * Получение экземпляра конфигурации
     * 
     * @return Config
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Загрузка конфигурации из переменных окружения
     * Используется только если не удалось инициализировать менеджер настроек
     */
    private function loadConfigFromEnv()
    {
        // Общие настройки
        $this->config['app'] = [
            'env' => $_ENV['APP_ENV'] ?? 'development',
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
        ];

        // Настройки базы данных
        $this->config['database'] = [
            'connection' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'social_media_automation',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
        ];

        // Настройки парсера новостей
        $this->config['parser'] = [
            'sources' => [
                $_ENV['PARSER_SOURCE_1'] ?? 'https://example.com/news',
                $_ENV['PARSER_SOURCE_2'] ?? 'https://example2.com/news',
                $_ENV['PARSER_SOURCE_3'] ?? 'https://example3.com/news',
                $_ENV['PARSER_SOURCE_4'] ?? 'https://example4.com/news',
            ],
        ];

        // Настройки Make.com
        $this->config['make'] = [
            'api_key' => $_ENV['MAKE_API_KEY'] ?? '',
            'webhook_url' => $_ENV['MAKE_WEBHOOK_URL'] ?? '',
        ];

        // Настройки социальных сетей
        $this->config['social'] = [
            'twitter' => [
                'api_key' => $_ENV['TWITTER_API_KEY'] ?? '',
                'api_secret' => $_ENV['TWITTER_API_SECRET'] ?? '',
                'access_token' => $_ENV['TWITTER_ACCESS_TOKEN'] ?? '',
                'access_secret' => $_ENV['TWITTER_ACCESS_SECRET'] ?? '',
            ],
            'linkedin' => [
                'client_id' => $_ENV['LINKEDIN_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['LINKEDIN_CLIENT_SECRET'] ?? '',
                'access_token' => $_ENV['LINKEDIN_ACCESS_TOKEN'] ?? '',
            ],
            'youtube' => [
                'api_key' => $_ENV['YOUTUBE_API_KEY'] ?? '',
                'client_id' => $_ENV['YOUTUBE_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['YOUTUBE_CLIENT_SECRET'] ?? '',
                'refresh_token' => $_ENV['YOUTUBE_REFRESH_TOKEN'] ?? '',
            ],
        ];

        // Настройки Dolphin Anty
        $this->config['dolphin'] = [
            'api_key' => $_ENV['DOLPHIN_API_KEY'] ?? '',
            'api_url' => $_ENV['DOLPHIN_API_URL'] ?? 'https://api.dolphin-anty.com/v1',
        ];

        // Настройки прокси
        $this->config['proxy'] = [
            'server' => $_ENV['PROXY_SERVER'] ?? '',
            'port' => $_ENV['PROXY_PORT'] ?? '',
            'username' => $_ENV['PROXY_USERNAME'] ?? '',
            'password' => $_ENV['PROXY_PASSWORD'] ?? '',
        ];
    }

    /**
     * Получение значения конфигурации по ключу
     * 
     * @param string $key Ключ конфигурации в формате 'section.key'
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // Если доступен менеджер настроек, используем его
        if ($this->settingsManager !== null) {
            return $this->settingsManager->get($key, $default);
        }
        
        // Иначе используем локальный кэш настроек
        $keys = explode('.', $key);
        
        if (count($keys) === 1) {
            return $this->config[$keys[0]] ?? $default;
        }
        
        if (count($keys) === 2) {
            $section = $keys[0];
            $subKey = $keys[1];
            
            if (isset($this->config[$section][$subKey])) {
                return $this->config[$section][$subKey];
            }
        }
        
        if (count($keys) === 3) {
            $section = $keys[0];
            $subSection = $keys[1];
            $subKey = $keys[2];
            
            if (isset($this->config[$section][$subSection][$subKey])) {
                return $this->config[$section][$subSection][$subKey];
            }
        }
        
        return $default;
    }
    
    /**
     * Сохранение настройки
     * 
     * @param string $key Ключ настройки в формате 'section.key' или 'section.subsection.key'
     * @param mixed $value Значение настройки
     * @return bool Результат операции
     */
    public function set($key, $value): bool
    {
        // Если менеджер настроек недоступен, нельзя сохранить настройки
        if ($this->settingsManager === null) {
            return false;
        }
        
        $keys = explode('.', $key);
        
        if (count($keys) === 2) {
            // Формат 'section.key'
            $section = $keys[0];
            $subKey = $keys[1];
            
            return $this->settingsManager->saveSettings($section, [$subKey => $value]);
        } else if (count($keys) === 3) {
            // Формат 'section.subsection.key'
            $section = $keys[0] . '.' . $keys[1];
            $subKey = $keys[2];
            
            return $this->settingsManager->saveSettings($section, [$subKey => $value]);
        }
        
        return false;
    }
    
    /**
     * Обновление локального кэша настроек
     * 
     * @return bool Результат операции
     */
    public function refreshSettings(): bool
    {
        if ($this->settingsManager === null) {
            return false;
        }
        
        try {
            $this->config = $this->settingsManager->getAllSettings();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
