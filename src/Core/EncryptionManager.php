<?php

namespace App\Core;

use App\Core\Config;
use App\Core\LogManager;

/**
 * Класс для шифрования и дешифрования API ключей
 */
class EncryptionManager
{
    private static $instance = null;
    private $logger;
    private $config;
    private $encryptionKey;
    
    /**
     * Приватный конструктор для реализации паттерна Singleton
     */
    private function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->initEncryptionKey();
    }
    
    /**
     * Получение экземпляра менеджера шифрования
     * 
     * @return EncryptionManager
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Инициализация ключа шифрования
     */
    private function initEncryptionKey()
    {
        // Проверяем наличие ключа шифрования в конфигурации
        $encryptionKey = $this->config->get('app.encryption_key');
        
        if (empty($encryptionKey)) {
            // Если ключа нет, генерируем новый
            $encryptionKey = $this->generateEncryptionKey();
            
            // Сохраняем ключ в конфигурации
            $this->config->set('app.encryption_key', $encryptionKey);
            
            $this->logger->info('Generated new encryption key');
        }
        
        $this->encryptionKey = $encryptionKey;
    }
    
    /**
     * Генерация нового ключа шифрования
     * 
     * @return string Ключ шифрования
     */
    private function generateEncryptionKey()
    {
        // Генерируем случайный ключ длиной 32 байта (256 бит)
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Шифрование данных
     * 
     * @param string $data Данные для шифрования
     * @return string|false Зашифрованные данные или false в случае ошибки
     */
    public function encrypt($data)
    {
        if (empty($data)) {
            return $data;
        }
        
        try {
            // Генерируем случайный вектор инициализации
            $iv = random_bytes(16);
            
            // Шифруем данные
            $encrypted = openssl_encrypt(
                $data,
                'AES-256-CBC',
                hex2bin($this->encryptionKey),
                0,
                $iv
            );
            
            if ($encrypted === false) {
                $this->logger->error('Encryption failed', ['error' => openssl_error_string()]);
                return false;
            }
            
            // Объединяем вектор инициализации и зашифрованные данные
            $result = base64_encode($iv . $encrypted);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Encryption error', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Дешифрование данных
     * 
     * @param string $data Зашифрованные данные
     * @return string|false Расшифрованные данные или false в случае ошибки
     */
    public function decrypt($data)
    {
        if (empty($data)) {
            return $data;
        }
        
        try {
            // Декодируем данные из base64
            $decoded = base64_decode($data);
            
            // Извлекаем вектор инициализации (первые 16 байт)
            $iv = substr($decoded, 0, 16);
            
            // Извлекаем зашифрованные данные (остальные байты)
            $encrypted = substr($decoded, 16);
            
            // Дешифруем данные
            $decrypted = openssl_decrypt(
                $encrypted,
                'AES-256-CBC',
                hex2bin($this->encryptionKey),
                0,
                $iv
            );
            
            if ($decrypted === false) {
                $this->logger->error('Decryption failed', ['error' => openssl_error_string()]);
                return false;
            }
            
            return $decrypted;
            
        } catch (\Exception $e) {
            $this->logger->error('Decryption error', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Шифрование API ключа
     * 
     * @param string $key API ключ
     * @param string $service Название сервиса
     * @return string|false Зашифрованный API ключ или false в случае ошибки
     */
    public function encryptApiKey($key, $service)
    {
        if (empty($key)) {
            return $key;
        }
        
        try {
            // Добавляем префикс с названием сервиса для дополнительной защиты
            $data = $service . ':' . $key;
            
            return $this->encrypt($data);
            
        } catch (\Exception $e) {
            $this->logger->error('API key encryption error', ['service' => $service, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Дешифрование API ключа
     * 
     * @param string $encryptedKey Зашифрованный API ключ
     * @param string $service Название сервиса
     * @return string|false Расшифрованный API ключ или false в случае ошибки
     */
    public function decryptApiKey($encryptedKey, $service)
    {
        if (empty($encryptedKey)) {
            return $encryptedKey;
        }
        
        try {
            // Дешифруем данные
            $decrypted = $this->decrypt($encryptedKey);
            
            if ($decrypted === false) {
                return false;
            }
            
            // Проверяем префикс с названием сервиса
            $parts = explode(':', $decrypted, 2);
            
            if (count($parts) !== 2 || $parts[0] !== $service) {
                $this->logger->error('Invalid API key format or service mismatch', ['service' => $service]);
                return false;
            }
            
            return $parts[1];
            
        } catch (\Exception $e) {
            $this->logger->error('API key decryption error', ['service' => $service, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Проверка, зашифрован ли API ключ
     * 
     * @param string $key API ключ
     * @return bool Результат проверки
     */
    public function isEncrypted($key)
    {
        if (empty($key)) {
            return false;
        }
        
        // Проверяем, является ли ключ base64-строкой
        return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $key);
    }
    
    /**
     * Шифрование всех API ключей в настройках
     * 
     * @return bool Результат операции
     */
    public function encryptAllApiKeys()
    {
        try {
            $settingsManager = new SettingsManager();
            $settings = $settingsManager->getAllSettings();
            
            // Шифруем API ключи Make.com
            if (isset($settings['make']['api_key']) && !$this->isEncrypted($settings['make']['api_key'])) {
                $encryptedKey = $this->encryptApiKey($settings['make']['api_key'], 'make');
                $settingsManager->saveSettings('make', ['api_key' => $encryptedKey]);
            }
            
            // Шифруем API ключи Twitter
            if (isset($settings['social']['twitter'])) {
                $twitterSettings = $settings['social']['twitter'];
                $encryptedSettings = [];
                
                if (isset($twitterSettings['api_key']) && !$this->isEncrypted($twitterSettings['api_key'])) {
                    $encryptedSettings['api_key'] = $this->encryptApiKey($twitterSettings['api_key'], 'twitter');
                }
                
                if (isset($twitterSettings['api_secret']) && !$this->isEncrypted($twitterSettings['api_secret'])) {
                    $encryptedSettings['api_secret'] = $this->encryptApiKey($twitterSettings['api_secret'], 'twitter');
                }
                
                if (isset($twitterSettings['access_token']) && !$this->isEncrypted($twitterSettings['access_token'])) {
                    $encryptedSettings['access_token'] = $this->encryptApiKey($twitterSettings['access_token'], 'twitter');
                }
                
                if (isset($twitterSettings['access_secret']) && !$this->isEncrypted($twitterSettings['access_secret'])) {
                    $encryptedSettings['access_secret'] = $this->encryptApiKey($twitterSettings['access_secret'], 'twitter');
                }
                
                if (!empty($encryptedSettings)) {
                    $settingsManager->saveSettings('social.twitter', $encryptedSettings);
                }
            }
            
            // Шифруем API ключи LinkedIn
            if (isset($settings['social']['linkedin'])) {
                $linkedinSettings = $settings['social']['linkedin'];
                $encryptedSettings = [];
                
                if (isset($linkedinSettings['client_id']) && !$this->isEncrypted($linkedinSettings['client_id'])) {
                    $encryptedSettings['client_id'] = $this->encryptApiKey($linkedinSettings['client_id'], 'linkedin');
                }
                
                if (isset($linkedinSettings['client_secret']) && !$this->isEncrypted($linkedinSettings['client_secret'])) {
                    $encryptedSettings['client_secret'] = $this->encryptApiKey($linkedinSettings['client_secret'], 'linkedin');
                }
                
                if (isset($linkedinSettings['access_token']) && !$this->isEncrypted($linkedinSettings['access_token'])) {
                    $encryptedSettings['access_token'] = $this->encryptApiKey($linkedinSettings['access_token'], 'linkedin');
                }
                
                if (!empty($encryptedSettings)) {
                    $settingsManager->saveSettings('social.linkedin', $encryptedSettings);
                }
            }
            
            // Шифруем API ключи YouTube
            if (isset($settings['social']['youtube'])) {
                $youtubeSettings = $settings['social']['youtube'];
                $encryptedSettings = [];
                
                if (isset($youtubeSettings['api_key']) && !$this->isEncrypted($youtubeSettings['api_key'])) {
                    $encryptedSettings['api_key'] = $this->encryptApiKey($youtubeSettings['api_key'], 'youtube');
                }
                
                if (isset($youtubeSettings['client_id']) && !$this->isEncrypted($youtubeSettings['client_id'])) {
                    $encryptedSettings['client_id'] = $this->encryptApiKey($youtubeSettings['client_id'], 'youtube');
                }
                
                if (isset($youtubeSettings['client_secret']) && !$this->isEncrypted($youtubeSettings['client_secret'])) {
                    $encryptedSettings['client_secret'] = $this->encryptApiKey($youtubeSettings['client_secret'], 'youtube');
                }
                
                if (isset($youtubeSettings['refresh_token']) && !$this->isEncrypted($youtubeSettings['refresh_token'])) {
                    $encryptedSettings['refresh_token'] = $this->encryptApiKey($youtubeSettings['refresh_token'], 'youtube');
                }
                
                if (!empty($encryptedSettings)) {
                    $settingsManager->saveSettings('social.youtube', $encryptedSettings);
                }
            }
            
            // Шифруем API ключи Dolphin Anty
            if (isset($settings['dolphin']['api_key']) && !$this->isEncrypted($settings['dolphin']['api_key'])) {
                $encryptedKey = $this->encryptApiKey($settings['dolphin']['api_key'], 'dolphin');
                $settingsManager->saveSettings('dolphin', ['api_key' => $encryptedKey]);
            }
            
            // Шифруем данные прокси
            if (isset($settings['proxy'])) {
                $proxySettings = $settings['proxy'];
                $encryptedSettings = [];
                
                if (isset($proxySettings['username']) && !$this->isEncrypted($proxySettings['username'])) {
                    $encryptedSettings['username'] = $this->encryptApiKey($proxySettings['username'], 'proxy');
                }
                
                if (isset($proxySettings['password']) && !$this->isEncrypted($proxySettings['password'])) {
                    $encryptedSettings['password'] = $this->encryptApiKey($proxySettings['password'], 'proxy');
                }
                
                if (!empty($encryptedSettings)) {
                    $settingsManager->saveSettings('proxy', $encryptedSettings);
                }
            }
            
            $this->logger->info('All API keys encrypted successfully');
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Error encrypting API keys', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Получение расшифрованного API ключа из настроек
     * 
     * @param string $key Ключ настройки в формате 'section.key' или 'section.subsection.key'
     * @param string $service Название сервиса
     * @return string|false Расшифрованный API ключ или false в случае ошибки
     */
    public function getDecryptedApiKey($key, $service)
    {
        $config = Config::getInstance();
        $encryptedKey = $config->get($key);
        
        if (empty($encryptedKey)) {
            return $encryptedKey;
        }
        
        if ($this->isEncrypted($encryptedKey)) {
            return $this->decryptApiKey($encryptedKey, $service);
        }
        
        return $encryptedKey;
    }
}
