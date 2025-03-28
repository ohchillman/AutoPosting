<?php

namespace App\Core;

use PDO;
use Exception;

/**
 * Класс для управления настройками системы
 */
class SettingsManager
{
    private $logger;
    private $db;
    private $settings = [];
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->initDatabase();
        $this->loadSettings();
    }
    
    /**
     * Инициализация соединения с базой данных
     */
    private function initDatabase()
    {
        try {
            // Загружаем базовые настройки БД из .env для первоначального подключения
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../config');
            $dotenv->load();
            
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $database = $_ENV['DB_DATABASE'] ?? 'social_media_automation';
            $username = $_ENV['DB_USERNAME'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';
            
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $this->db = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            // Создаем таблицу настроек, если она не существует
            $this->createSettingsTable();
            
        } catch (Exception $e) {
            $this->logger->error('Database connection error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Создание таблицы настроек
     */
    private function createSettingsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section VARCHAR(50) NOT NULL,
            key_name VARCHAR(100) NOT NULL,
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY section_key (section, key_name)
        )";
        
        try {
            $this->db->exec($sql);
            $this->logger->info('Settings table created or already exists');
        } catch (Exception $e) {
            $this->logger->error('Error creating settings table', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Загрузка всех настроек из базы данных
     */
    private function loadSettings()
    {
        try {
            // Сначала загружаем настройки из .env для обратной совместимости
            $this->loadSettingsFromEnv();
            
            // Затем загружаем настройки из базы данных, которые перезаписывают настройки из .env
            $sql = "SELECT section, key_name, value FROM settings";
            $stmt = $this->db->query($sql);
            
            while ($row = $stmt->fetch()) {
                $section = $row['section'];
                $key = $row['key_name'];
                $value = $row['value'];
                
                // Обработка вложенных секций (например, social.twitter)
                $sectionParts = explode('.', $section);
                
                if (count($sectionParts) === 1) {
                    // Простая секция
                    if (!isset($this->settings[$section])) {
                        $this->settings[$section] = [];
                    }
                    $this->settings[$section][$key] = $value;
                } else if (count($sectionParts) === 2) {
                    // Вложенная секция (например, social.twitter)
                    $mainSection = $sectionParts[0];
                    $subSection = $sectionParts[1];
                    
                    if (!isset($this->settings[$mainSection])) {
                        $this->settings[$mainSection] = [];
                    }
                    
                    if (!isset($this->settings[$mainSection][$subSection])) {
                        $this->settings[$mainSection][$subSection] = [];
                    }
                    
                    $this->settings[$mainSection][$subSection][$key] = $value;
                }
            }
            
            $this->logger->info('Settings loaded from database');
            
        } catch (Exception $e) {
            $this->logger->error('Error loading settings', ['error' => $e->getMessage()]);
            // Если не удалось загрузить настройки из БД, используем только настройки из .env
        }
    }
    
    /**
     * Загрузка настроек из .env файла
     */
    private function loadSettingsFromEnv()
    {
        // Общие настройки
        $this->settings['app'] = [
            'env' => $_ENV['APP_ENV'] ?? 'development',
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
        ];

        // Настройки базы данных
        $this->settings['database'] = [
            'connection' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'social_media_automation',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
        ];

        // Настройки парсера новостей
        $this->settings['parser'] = [
            'sources' => [
                $_ENV['PARSER_SOURCE_1'] ?? 'https://example.com/news',
                $_ENV['PARSER_SOURCE_2'] ?? 'https://example2.com/news',
                $_ENV['PARSER_SOURCE_3'] ?? 'https://example3.com/news',
                $_ENV['PARSER_SOURCE_4'] ?? 'https://example4.com/news',
            ],
        ];

        // Настройки Make.com
        $this->settings['make'] = [
            'api_key' => $_ENV['MAKE_API_KEY'] ?? '',
            'webhook_url' => $_ENV['MAKE_WEBHOOK_URL'] ?? '',
        ];

        // Настройки социальных сетей
        $this->settings['social'] = [
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
        $this->settings['dolphin'] = [
            'api_key' => $_ENV['DOLPHIN_API_KEY'] ?? '',
            'api_url' => $_ENV['DOLPHIN_API_URL'] ?? 'https://api.dolphin-anty.com/v1',
        ];

        // Настройки прокси
        $this->settings['proxy'] = [
            'server' => $_ENV['PROXY_SERVER'] ?? '',
            'port' => $_ENV['PROXY_PORT'] ?? '',
            'username' => $_ENV['PROXY_USERNAME'] ?? '',
            'password' => $_ENV['PROXY_PASSWORD'] ?? '',
        ];
        
        $this->logger->info('Settings loaded from .env file');
    }
    
    /**
     * Получение всех настроек
     * 
     * @return array Массив настроек
     */
    public function getAllSettings(): array
    {
        return $this->settings;
    }
    
    /**
     * Получение значения настройки по ключу
     * 
     * @param string $key Ключ настройки в формате 'section.key' или 'section.subsection.key'
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        
        if (count($keys) === 2) {
            // Формат 'section.key'
            $section = $keys[0];
            $subKey = $keys[1];
            
            return $this->settings[$section][$subKey] ?? $default;
        } else if (count($keys) === 3) {
            // Формат 'section.subsection.key'
            $section = $keys[0];
            $subSection = $keys[1];
            $subKey = $keys[2];
            
            return $this->settings[$section][$subSection][$subKey] ?? $default;
        }
        
        return $default;
    }
    
    /**
     * Сохранение настроек
     * 
     * @param string $section Секция настроек
     * @param array $settings Массив настроек
     * @return bool Результат операции
     */
    public function saveSettings(string $section, array $settings): bool
    {
        try {
            // Проверяем, является ли секция вложенной (например, social.twitter)
            $sectionParts = explode('.', $section);
            
            if (count($sectionParts) === 1) {
                // Простая секция
                foreach ($settings as $key => $value) {
                    $this->saveSettingToDb($section, $key, $value);
                    
                    // Обновляем локальный кэш настроек
                    if (!isset($this->settings[$section])) {
                        $this->settings[$section] = [];
                    }
                    $this->settings[$section][$key] = $value;
                }
            } else if (count($sectionParts) === 2) {
                // Вложенная секция (например, social.twitter)
                $mainSection = $sectionParts[0];
                $subSection = $sectionParts[1];
                
                foreach ($settings as $key => $value) {
                    $this->saveSettingToDb($section, $key, $value);
                    
                    // Обновляем локальный кэш настроек
                    if (!isset($this->settings[$mainSection])) {
                        $this->settings[$mainSection] = [];
                    }
                    
                    if (!isset($this->settings[$mainSection][$subSection])) {
                        $this->settings[$mainSection][$subSection] = [];
                    }
                    
                    $this->settings[$mainSection][$subSection][$key] = $value;
                }
            }
            
            $this->logger->info('Settings saved', ['section' => $section]);
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Error saving settings', [
                'section' => $section,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Сохранение настройки в базу данных
     * 
     * @param string $section Секция настройки
     * @param string $key Ключ настройки
     * @param mixed $value Значение настройки
     */
    private function saveSettingToDb(string $section, string $key, $value)
    {
        // Convert arrays to JSON strings before saving
        if (is_array($value)) {
            $value = json_encode($value);
        }
        
        $sql = "INSERT INTO settings (setting_group, setting_key, setting_value) 
                VALUES (:section, :key, :value)
                ON DUPLICATE KEY UPDATE setting_value = :value";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':section' => $section,
            ':key' => $key,
            ':value' => $value
        ]);
    }
    
    /**
     * Тестирование соединения с базой данных
     * 
     * @return bool Результат теста
     */
    public function testDatabaseConnection(): bool
    {
        try {
            $host = $this->settings['database']['host'];
            $port = $this->settings['database']['port'];
            $database = $this->settings['database']['database'];
            $username = $this->settings['database']['username'];
            $password = $this->settings['database']['password'];
            
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $testDb = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Проверяем соединение простым запросом
            $testDb->query("SELECT 1");
            
            return true;
        } catch (Exception $e) {
            $this->logger->error('Database connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Тестирование парсера новостей
     * 
     * @return array Результаты теста
     */
    public function testParser(): array
    {
        $result = [
            'success' => false,
            'message' => 'Не удалось проверить источники новостей',
            'details' => []
        ];
        
        try {
            $sources = $this->settings['parser']['sources'] ?? [];
            $availableSources = 0;
            
            foreach ($sources as $source) {
                if (empty($source) || $source === 'https://example.com/news') {
                    $result['details'][$source] = false;
                    continue;
                }
                
                // Проверяем доступность источника
                $ch = curl_init($source);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $isAvailable = ($httpCode >= 200 && $httpCode < 400);
                $result['details'][$source] = $isAvailable;
                
                if ($isAvailable) {
                    $availableSources++;
                }
            }
            
            if ($availableSources > 0) {
                $result['success'] = true;
                $result['message'] = "Доступно {$availableSources} из " . count($sources) . " источников новостей";
            } else {
                $result['message'] = "Все источники новостей недоступны";
            }
            
        } catch (Exception $e) {
            $this->logger->error('Parser test failed', ['error' => $e->getMessage()]);
            $result['message'] = 'Ошибка при тестировании парсера: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Тестирование соединения с Make.com
     * 
     * @return bool Результат теста
     */
    public function testMakeConnection(): bool
    {
        try {
            $apiKey = $this->settings['make']['api_key'] ?? '';
            $webhookUrl = $this->settings['make']['webhook_url'] ?? '';
            
            if (empty($apiKey) || empty($webhookUrl)) {
                return false;
            }
            
            // Проверяем формат webhook URL
            if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
                return false;
            }
            
            // Отправляем тестовый запрос на webhook
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'test' => true,
                'message' => 'Test connection from Social Media Automation'
            ]));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return ($httpCode >= 200 && $httpCode < 400);
            
        } catch (Exception $e) {
            $this->logger->error('Make.com connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Тестирование соединения с социальными сетями
     * 
     * @return array Результаты тестов
     */
    public function testSocialNetworks(): array
    {
        $results = [
            'twitter' => [
                'success' => false,
                'message' => 'Не настроено соединение с Twitter'
            ],
            'linkedin' => [
                'success' => false,
                'message' => 'Не настроено соединение с LinkedIn'
            ],
            'youtube' => [
                'success' => false,
                'message' => 'Не настроено соединение с YouTube'
            ]
        ];
        
        // Тестирование Twitter
        try {
            $twitterSettings = $this->settings['social']['twitter'] ?? [];
            
            if (!empty($twitterSettings['api_key']) && 
                !empty($twitterSettings['api_secret']) && 
                !empty($twitterSettings['access_token']) && 
                !empty($twitterSettings['access_secret'])) {
                
                // Здесь можно добавить реальную проверку API Twitter
                // Для демонстрации просто проверяем наличие всех необходимых ключей
                $results['twitter']['success'] = true;
                $results['twitter']['message'] = 'Настройки Twitter корректны';
            }
        } catch (Exception $e) {
            $this->logger->error('Twitter test failed', ['error' => $e->getMessage()]);
            $results['twitter']['message'] = 'Ошибка при тестировании Twitter: ' . $e->getMessage();
        }
        
        // Тестирование LinkedIn
        try {
            $linkedinSettings = $this->settings['social']['linkedin'] ?? [];
            
            if (!empty($linkedinSettings['client_id']) && 
                !empty($linkedinSettings['client_secret']) && 
                !empty($linkedinSettings['access_token'])) {
                
                // Здесь можно добавить реальную проверку API LinkedIn
                // Для демонстрации просто проверяем наличие всех необходимых ключей
                $results['linkedin']['success'] = true;
                $results['linkedin']['message'] = 'Настройки LinkedIn корректны';
            }
        } catch (Exception $e) {
            $this->logger->error('LinkedIn test failed', ['error' => $e->getMessage()]);
            $results['linkedin']['message'] = 'Ошибка при тестировании LinkedIn: ' . $e->getMessage();
        }
        
        // Тестирование YouTube
        try {
            $youtubeSettings = $this->settings['social']['youtube'] ?? [];
            
            if (!empty($youtubeSettings['api_key']) && 
                !empty($youtubeSettings['client_id']) && 
                !empty($youtubeSettings['client_secret']) && 
                !empty($youtubeSettings['refresh_token'])) {
                
                // Здесь можно добавить реальную проверку API YouTube
                // Для демонстрации просто проверяем наличие всех необходимых ключей
                $results['youtube']['success'] = true;
                $results['youtube']['message'] = 'Настройки YouTube корректны';
            }
        } catch (Exception $e) {
            $this->logger->error('YouTube test failed', ['error' => $e->getMessage()]);
            $results['youtube']['message'] = 'Ошибка при тестировании YouTube: ' . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Тестирование соединения с Dolphin Anty
     * 
     * @return bool Результат теста
     */
    public function testDolphinConnection(): bool
    {
        try {
            $apiKey = $this->settings['dolphin']['api_key'] ?? '';
            $apiUrl = $this->settings['dolphin']['api_url'] ?? '';
            
            if (empty($apiKey) || empty($apiUrl)) {
                return false;
            }
            
            // Отправляем тестовый запрос к API Dolphin Anty
            $ch = curl_init($apiUrl . '/profiles');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return ($httpCode >= 200 && $httpCode < 400);
            
        } catch (Exception $e) {
            $this->logger->error('Dolphin Anty connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Тестирование соединения через прокси
     * 
     * @return bool Результат теста
     */
    public function testProxyConnection(): bool
    {
        try {
            $proxyServer = $this->settings['proxy']['server'] ?? '';
            $proxyPort = $this->settings['proxy']['port'] ?? '';
            $proxyUsername = $this->settings['proxy']['username'] ?? '';
            $proxyPassword = $this->settings['proxy']['password'] ?? '';
            
            if (empty($proxyServer) || empty($proxyPort)) {
                return false;
            }
            
            // Тестируем соединение через прокси
            $ch = curl_init('https://api.ipify.org');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
            
            if (!empty($proxyUsername) && !empty($proxyPassword)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUsername . ':' . $proxyPassword);
            }
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            return !empty($response) && empty($error);
            
        } catch (Exception $e) {
            $this->logger->error('Proxy connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
