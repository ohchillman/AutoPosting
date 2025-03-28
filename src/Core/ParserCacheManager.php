<?php

namespace App\Core;

use App\Core\Config;
use App\Core\LogManager;

/**
 * Класс для кэширования данных парсера новостей
 */
class ParserCacheManager
{
    private static $instance = null;
    private $logger;
    private $config;
    private $cacheDir;
    private $cacheEnabled;
    private $cacheTTL;
    
    /**
     * Приватный конструктор для реализации паттерна Singleton
     */
    private function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->initCache();
    }
    
    /**
     * Получение экземпляра менеджера кэширования
     * 
     * @return ParserCacheManager
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Инициализация кэша
     */
    private function initCache()
    {
        // Получаем настройки кэширования из конфигурации
        $this->cacheEnabled = $this->config->get('parser.cache_enabled', true);
        $this->cacheTTL = $this->config->get('parser.cache_ttl', 3600); // По умолчанию 1 час
        $this->cacheDir = $this->config->get('parser.cache_dir', __DIR__ . '/../../data/cache');
        
        // Создаем директорию для кэша, если она не существует
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Получение данных из кэша
     * 
     * @param string $key Ключ кэша
     * @return mixed|null Данные из кэша или null, если данные не найдены или устарели
     */
    public function get($key)
    {
        if (!$this->cacheEnabled) {
            return null;
        }
        
        $cacheFile = $this->getCacheFilePath($key);
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        // Проверяем время создания файла
        $fileTime = filemtime($cacheFile);
        if (time() - $fileTime > $this->cacheTTL) {
            // Кэш устарел, удаляем файл
            unlink($cacheFile);
            return null;
        }
        
        // Получаем данные из кэша
        $data = file_get_contents($cacheFile);
        $cachedData = json_decode($data, true);
        
        if ($cachedData === null) {
            // Ошибка декодирования JSON, удаляем файл
            unlink($cacheFile);
            return null;
        }
        
        $this->logger->debug('Cache hit', ['key' => $key]);
        return $cachedData;
    }
    
    /**
     * Сохранение данных в кэш
     * 
     * @param string $key Ключ кэша
     * @param mixed $data Данные для сохранения
     * @return bool Результат операции
     */
    public function set($key, $data)
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        $cacheFile = $this->getCacheFilePath($key);
        
        // Сохраняем данные в кэш
        $jsonData = json_encode($data);
        if ($jsonData === false) {
            $this->logger->error('Failed to encode data for caching', ['key' => $key]);
            return false;
        }
        
        $result = file_put_contents($cacheFile, $jsonData);
        if ($result === false) {
            $this->logger->error('Failed to write cache file', ['key' => $key, 'file' => $cacheFile]);
            return false;
        }
        
        $this->logger->debug('Cache set', ['key' => $key]);
        return true;
    }
    
    /**
     * Удаление данных из кэша
     * 
     * @param string $key Ключ кэша
     * @return bool Результат операции
     */
    public function delete($key)
    {
        $cacheFile = $this->getCacheFilePath($key);
        
        if (file_exists($cacheFile)) {
            $result = unlink($cacheFile);
            if ($result) {
                $this->logger->debug('Cache deleted', ['key' => $key]);
            } else {
                $this->logger->error('Failed to delete cache file', ['key' => $key, 'file' => $cacheFile]);
            }
            return $result;
        }
        
        return true;
    }
    
    /**
     * Очистка всего кэша
     * 
     * @return bool Результат операции
     */
    public function clear()
    {
        $files = glob($this->cacheDir . '/*.cache');
        $success = true;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $result = unlink($file);
                if (!$result) {
                    $this->logger->error('Failed to delete cache file during clear', ['file' => $file]);
                    $success = false;
                }
            }
        }
        
        $this->logger->info('Cache cleared');
        return $success;
    }
    
    /**
     * Получение пути к файлу кэша
     * 
     * @param string $key Ключ кэша
     * @return string Путь к файлу кэша
     */
    private function getCacheFilePath($key)
    {
        // Хешируем ключ для безопасного использования в имени файла
        $hashedKey = md5($key);
        return $this->cacheDir . '/' . $hashedKey . '.cache';
    }
    
    /**
     * Проверка наличия данных в кэше
     * 
     * @param string $key Ключ кэша
     * @return bool Результат проверки
     */
    public function has($key)
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        $cacheFile = $this->getCacheFilePath($key);
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        // Проверяем время создания файла
        $fileTime = filemtime($cacheFile);
        return (time() - $fileTime <= $this->cacheTTL);
    }
    
    /**
     * Получение данных из кэша или выполнение функции для получения данных
     * 
     * @param string $key Ключ кэша
     * @param callable $callback Функция для получения данных
     * @return mixed Данные из кэша или результат выполнения функции
     */
    public function remember($key, callable $callback)
    {
        // Проверяем наличие данных в кэше
        $cachedData = $this->get($key);
        
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Получаем данные через функцию
        $data = $callback();
        
        // Сохраняем данные в кэш
        $this->set($key, $data);
        
        return $data;
    }
    
    /**
     * Получение статистики кэша
     * 
     * @return array Статистика кэша
     */
    public function getStats()
    {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        $count = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                $count++;
            }
        }
        
        return [
            'enabled' => $this->cacheEnabled,
            'ttl' => $this->cacheTTL,
            'count' => $count,
            'total_size' => $totalSize,
            'directory' => $this->cacheDir
        ];
    }
    
    /**
     * Изменение настроек кэширования
     * 
     * @param array $settings Настройки кэширования
     * @return bool Результат операции
     */
    public function updateSettings($settings)
    {
        try {
            if (isset($settings['enabled'])) {
                $this->cacheEnabled = (bool) $settings['enabled'];
                $this->config->set('parser.cache_enabled', $this->cacheEnabled);
            }
            
            if (isset($settings['ttl'])) {
                $this->cacheTTL = (int) $settings['ttl'];
                $this->config->set('parser.cache_ttl', $this->cacheTTL);
            }
            
            $this->logger->info('Cache settings updated', [
                'enabled' => $this->cacheEnabled,
                'ttl' => $this->cacheTTL
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update cache settings', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
