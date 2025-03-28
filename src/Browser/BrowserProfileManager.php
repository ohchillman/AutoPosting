<?php

namespace App\Browser;

use App\Core\Config;
use App\Core\LogManager;
use App\Core\Database;

/**
 * Менеджер профилей браузеров
 */
class BrowserProfileManager
{
    /**
     * @var LogManager Логгер
     */
    protected $logger;
    
    /**
     * @var Config Конфигурация
     */
    protected $config;
    
    /**
     * @var Database База данных
     */
    protected $db;
    
    /**
     * @var array Адаптеры браузеров
     */
    protected $adapters = [];
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->db = Database::getInstance();
        
        $this->initAdapters();
    }
    
    /**
     * Инициализация адаптеров браузеров
     */
    protected function initAdapters(): void
    {
        // Инициализация AdsPower адаптера
        if ($this->config->get('browsers.adspower.enabled', false)) {
            $this->adapters['adspower'] = new AdsPowerAdapter([
                'api_base_url' => $this->config->get('browsers.adspower.api_base_url', 'http://local.adspower.net:50325/api/v1')
            ]);
            $this->logger->info('AdsPower adapter initialized');
        }
        
        // Инициализация Dolphin адаптера
        if ($this->config->get('browsers.dolphin.enabled', false)) {
            $this->adapters['dolphin'] = new DolphinAdapter([
                'api_base_url' => $this->config->get('browsers.dolphin.api_base_url', 'http://localhost:3001/v1.0'),
                'api_token' => $this->config->get('browsers.dolphin.api_token', ''),
                'chromedriver_path' => $this->config->get('browsers.dolphin.chromedriver_path', '/usr/local/bin/chromedriver')
            ]);
            $this->logger->info('Dolphin adapter initialized');
        }
    }
    
    /**
     * Получение адаптера браузера по типу
     * 
     * @param string $browserType Тип браузера ('adspower' или 'dolphin')
     * @return BrowserAdapter|null Адаптер браузера или null, если адаптер не найден
     */
    public function getAdapter(string $browserType): ?BrowserAdapter
    {
        if (!isset($this->adapters[$browserType])) {
            $this->logger->error('Browser adapter not found', ['browser_type' => $browserType]);
            return null;
        }
        
        return $this->adapters[$browserType];
    }
    
    /**
     * Получение списка профилей из базы данных
     * 
     * @return array Список профилей
     */
    public function getProfiles(): array
    {
        $this->logger->info('Getting browser profiles from database');
        
        try {
            $query = "SELECT * FROM browser_profiles";
            $stmt = $this->db->prepare($query);
            
            if ($stmt === false) {
                $this->logger->error('Failed to prepare statement for getting browser profiles');
                return [];
            }
            
            if (!$stmt->execute()) {
                $this->logger->error('Failed to execute statement for getting browser profiles');
                return [];
            }
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->logger->error('Exception when getting browser profiles from database', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Получение профиля по ID
     * 
     * @param int $id ID профиля в базе данных
     * @return array|null Данные профиля или null, если профиль не найден
     */
    public function getProfileById(int $id): ?array
    {
        $this->logger->info('Getting browser profile by ID', ['id' => $id]);
        
        try {
            $query = "SELECT * FROM browser_profiles WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            if ($stmt === false) {
                $this->logger->error('Failed to prepare statement for getting browser profile by ID', ['id' => $id]);
                return null;
            }
            
            if (!$stmt->bindParam(':id', $id, \PDO::PARAM_INT)) {
                $this->logger->error('Failed to bind parameter for getting browser profile by ID', ['id' => $id]);
                return null;
            }
            
            if (!$stmt->execute()) {
                $this->logger->error('Failed to execute statement for getting browser profile by ID', ['id' => $id]);
                return null;
            }
            
            $profile = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$profile) {
                $this->logger->error('Browser profile not found', ['id' => $id]);
                return null;
            }
            
            return $profile;
        } catch (\Exception $e) {
            $this->logger->error('Exception when getting browser profile by ID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Создание нового профиля в базе данных
     * 
     * @param array $profileData Данные профиля
     * @return int|null ID созданного профиля или null в случае ошибки
     */
    public function createProfile(array $profileData): ?int
    {
        $this->logger->info('Creating new browser profile', $profileData);
        
        try {
            $query = "INSERT INTO browser_profiles (name, browser_type, profile_id, social_account_id, is_active) 
                      VALUES (:name, :browser_type, :profile_id, :social_account_id, :is_active)";
            
            $stmt = $this->db->prepare($query);
            
            if ($stmt === false) {
                $this->logger->error('Failed to prepare statement for creating browser profile');
                return null;
            }
            
            if (!$stmt->bindParam(':name', $profileData['name'], \PDO::PARAM_STR) ||
                !$stmt->bindParam(':browser_type', $profileData['browser_type'], \PDO::PARAM_STR) ||
                !$stmt->bindParam(':profile_id', $profileData['profile_id'], \PDO::PARAM_STR) ||
                !$stmt->bindParam(':social_account_id', $profileData['social_account_id'], \PDO::PARAM_INT) ||
                !$stmt->bindParam(':is_active', $profileData['is_active'], \PDO::PARAM_BOOL)) {
                
                $this->logger->error('Failed to bind parameters for creating browser profile');
                return null;
            }
            
            if (!$stmt->execute()) {
                $this->logger->error('Failed to execute statement for creating browser profile');
                return null;
            }
            
            $profileId = $this->db->lastInsertId();
            
            $this->logger->info('Browser profile created successfully', ['id' => $profileId]);
            
            return $profileId;
        } catch (\Exception $e) {
            $this->logger->error('Exception when creating browser profile', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Обновление профиля в базе данных
     * 
     * @param int $id ID профиля
     * @param array $profileData Данные профиля
     * @return bool Результат операции
     */
    public function updateProfile(int $id, array $profileData): bool
    {
        $this->logger->info('Updating browser profile', ['id' => $id, 'data' => $profileData]);
        
        try {
            $query = "UPDATE browser_profiles SET 
                      name = :name, 
                      browser_type = :browser_type, 
                      profile_id = :profile_id, 
                      social_account_id = :social_account_id, 
                      is_active = :is_active,
                      last_used = :last_used
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            if ($stmt === false) {
                $this->logger->error('Failed to prepare statement for updating browser profile', ['id' => $id]);
                return false;
            }
            
            if (!$stmt->bindParam(':id', $id, \PDO::PARAM_INT) ||
                !$stmt->bindParam(':name', $profileData['name'], \PDO::PARAM_STR) ||
                !$stmt->bindParam(':browser_type', $profileData['browser_type'], \PDO::PARAM_STR) ||
                !$stmt->bindParam(':profile_id', $profileData['profile_id'], \PDO::PARAM_STR) ||
                !$stmt->bindParam(':social_account_id', $profileData['social_account_id'], \PDO::PARAM_INT) ||
                !$stmt->bindParam(':is_active', $profileData['is_active'], \PDO::PARAM_BOOL) ||
                !$stmt->bindParam(':last_used', $profileData['last_used'], \PDO::PARAM_STR)) {
                
                $this->logger->error('Failed to bind parameters for updating browser profile', ['id' => $id]);
                return false;
            }
            
            if (!$stmt->execute()) {
                $this->logger->error('Failed to execute statement for updating browser profile', ['id' => $id]);
                return false;
            }
            
            $this->logger->info('Browser profile updated successfully', ['id' => $id]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Exception when updating browser profile', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Удаление профиля из базы данных
     * 
     * @param int $id ID профиля
     * @return bool Результат операции
     */
    public function deleteProfile(int $id): bool
    {
        $this->logger->info('Deleting browser profile', ['id' => $id]);
        
        try {
            $query = "DELETE FROM browser_profiles WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            if ($stmt === false) {
                $this->logger->error('Failed to prepare statement for deleting browser profile', ['id' => $id]);
                return false;
            }
            
            if (!$stmt->bindParam(':id', $id, \PDO::PARAM_INT)) {
                $this->logger->error('Failed to bind parameter for deleting browser profile', ['id' => $id]);
                return false;
            }
            
            if (!$stmt->execute()) {
                $this->logger->error('Failed to execute statement for deleting browser profile', ['id' => $id]);
                return false;
            }
            
            $this->logger->info('Browser profile deleted successfully', ['id' => $id]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Exception when deleting browser profile', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Запуск профиля браузера
     * 
     * @param int $id ID профиля в базе данных
     * @return array|null Данные для подключения к браузеру или null в случае ошибки
     */
    public function startProfile(int $id): ?array
    {
        $this->logger->info('Starting browser profile', ['id' => $id]);
        
        $profile = $this->getProfileById($id);
        
        if (!$profile) {
            return null;
        }
        
        $adapter = $this->getAdapter($profile['browser_type']);
        
        if (!$adapter) {
            return null;
        }
        
        try {
            $connectionData = $adapter->startProfile($profile['profile_id']);
            
            // Обновляем время последнего использования профиля
            $this->updateProfile($id, array_merge($profile, [
                'last_used' => date('Y-m-d H:i:s')
            ]));
            
            return [
                'profile' => $profile,
                'connection_data' => $connectionData
            ];
        } catch (\Exception $e) {
            $this->logger->error('Exception when starting browser profile', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Остановка профиля браузера
     * 
     * @param int $id ID профиля в базе данных
     * @return bool Результат операции
     */
    public function stopProfile(int $id): bool
    {
        $this->logger->info('Stopping browser profile', ['id' => $id]);
        
        $profile = $this->getProfileById($id);
        
        if (!$profile) {
            return false;
        }
        
        $adapter = $this->getAdapter($profile['browser_type']);
        
        if (!$adapter) {
            return false;
        }
        
        try {
            return $adapter->stopProfile($profile['profile_id']);
        } catch (\Exception $e) {
            $this->logger->error('Exception when stopping browser profile', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение Selenium WebDriver для профиля
     * 
     * @param int $id ID профиля в базе данных
     * @param array $connectionData Данные для подключения к браузеру
     * @return WebDriver|null Экземпляр WebDriver или null в случае ошибки
     */
    public function getSeleniumDriver(int $id, array $connectionData): ?WebDriver
    {
        $this->logger->info('Getting Selenium WebDriver for browser profile', ['id' => $id]);
        
        $profile = $this->getProfileById($id);
        
        if (!$profile) {
            return null;
        }
        
        $adapter = $this->getAdapter($profile['browser_type']);
        
        if (!$adapter) {
            return null;
        }
        
        try {
            return $adapter->getSeleniumDriver($profile['profile_id'], $connectionData);
        } catch (\Exception $e) {
            $this->logger->error('Exception when getting Selenium WebDriver for browser profile', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Проверка статуса профиля
     * 
     * @param int $id ID профиля в базе данных
     * @return bool Статус профиля (true - активен, false - неактивен)
     */
    public function checkProfileStatus(int $id): bool
    {
        $this->logger->info('Checking browser profile status', ['id' => $id]);
        
        $profile = $this->getProfileById($id);
        
        if (!$profile) {
            return false;
        }
        
        $adapter = $this->getAdapter($profile['browser_type']);
        
        if (!$adapter) {
            return false;
        }
        
        try {
            return $adapter->checkProfileStatus($profile['profile_id']);
        } catch (\Exception $e) {
            $this->logger->error('Exception when checking browser profile status', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение списка доступных профилей из антидетект браузеров
     * 
     * @return array Список профилей
     */
    public function getAvailableProfiles(): array
    {
        $this->logger->info('Getting available browser profiles from antidetect browsers');
        
        $profiles = [];
        
        foreach ($this->adapters as $browserType => $adapter) {
            try {
                $browserProfiles = $adapter->getProfiles();
                
                foreach ($browserProfiles as $profile) {
                    $profiles[] = array_merge($profile, [
                        'browser_type' => $browserType
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Exception when getting available browser profiles', [
                    'browser_type' => $browserType,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $profiles;
    }
    
    /**
     * Синхронизация профилей из антидетект браузеров с базой данных
     * 
     * @return bool Результат операции
     */
    public function syncProfiles(): bool
    {
        $this->logger->info('Synchronizing browser profiles with database');
        
        try {
            $availableProfiles = $this->getAvailableProfiles();
            $dbProfiles = $this->getProfiles();
            
            // Создаем индекс существующих профилей по browser_type и profile_id
            $existingProfiles = [];
            foreach ($dbProfiles as $profile) {
                $key = $profile['browser_type'] . '_' . $profile['profile_id'];
                $existingProfiles[$key] = $profile;
            }
            
            // Добавляем новые профили
            foreach ($availableProfiles as $profile) {
                $key = $profile['browser_type'] . '_' . $profile['id'];
                
                if (!isset($existingProfiles[$key])) {
                    $this->createProfile([
                        'name' => $profile['name'],
                        'browser_type' => $profile['browser_type'],
                        'profile_id' => $profile['id'],
                        'social_account_id' => null,
                        'is_active' => $profile['status'] === 'Active'
                    ]);
                }
            }
            
            $this->logger->info('Browser profiles synchronized successfully');
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Exception when synchronizing browser profiles', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
