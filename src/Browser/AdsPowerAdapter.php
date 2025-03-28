<?php

namespace App\Browser;

use GuzzleHttp\Client;
use Selenium\WebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Адаптер для антидетект браузера AdsPower
 */
class AdsPowerAdapter extends BrowserAdapter
{
    /**
     * @var string Базовый URL API AdsPower
     */
    protected $apiBaseUrl;
    
    /**
     * @var Client HTTP клиент
     */
    protected $client;
    
    /**
     * Конструктор
     * 
     * @param array $config Конфигурация адаптера
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        
        $this->apiBaseUrl = $config['api_base_url'] ?? 'http://local.adspower.net:50325/api/v1';
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false
        ]);
    }
    
    /**
     * Запуск профиля браузера
     * 
     * @param string $profileId Идентификатор профиля
     * @return array Данные для подключения к браузеру
     */
    public function startProfile(string $profileId): array
    {
        $this->logInfo('Starting AdsPower profile', ['profile_id' => $profileId]);
        
        try {
            $url = "{$this->apiBaseUrl}/browser/start?user_id={$profileId}";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            if ($data['code'] !== 0) {
                $this->logError('Failed to start AdsPower profile', [
                    'profile_id' => $profileId,
                    'error' => $data['msg']
                ]);
                throw new \Exception("Failed to start AdsPower profile: {$data['msg']}");
            }
            
            $this->logInfo('AdsPower profile started successfully', [
                'profile_id' => $profileId,
                'selenium_url' => $data['data']['ws']['selenium']
            ]);
            
            return [
                'selenium_url' => $data['data']['ws']['selenium'],
                'webdriver_path' => $data['data']['webdriver']
            ];
        } catch (\Exception $e) {
            $this->logError('Exception when starting AdsPower profile', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Остановка профиля браузера
     * 
     * @param string $profileId Идентификатор профиля
     * @return bool Результат операции
     */
    public function stopProfile(string $profileId): bool
    {
        $this->logInfo('Stopping AdsPower profile', ['profile_id' => $profileId]);
        
        try {
            $url = "{$this->apiBaseUrl}/browser/stop?user_id={$profileId}";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            if ($data['code'] !== 0) {
                $this->logError('Failed to stop AdsPower profile', [
                    'profile_id' => $profileId,
                    'error' => $data['msg']
                ]);
                return false;
            }
            
            $this->logInfo('AdsPower profile stopped successfully', [
                'profile_id' => $profileId
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Exception when stopping AdsPower profile', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение Selenium WebDriver для профиля
     * 
     * @param string $profileId Идентификатор профиля
     * @param array $connectionData Данные для подключения к браузеру
     * @return WebDriver Экземпляр WebDriver
     */
    public function getSeleniumDriver(string $profileId, array $connectionData): WebDriver
    {
        $this->logInfo('Creating Selenium WebDriver for AdsPower profile', [
            'profile_id' => $profileId,
            'selenium_url' => $connectionData['selenium_url']
        ]);
        
        try {
            $options = new ChromeOptions();
            $options->addArguments(['--disable-notifications']);
            $options->setExperimentalOption('debuggerAddress', $connectionData['selenium_url']);
            
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
            
            // Используем путь к WebDriver, предоставленный AdsPower
            $driver = RemoteWebDriver::create(
                $connectionData['webdriver_path'],
                $capabilities
            );
            
            $this->logInfo('Selenium WebDriver created successfully', [
                'profile_id' => $profileId
            ]);
            
            return $driver;
        } catch (\Exception $e) {
            $this->logError('Exception when creating Selenium WebDriver for AdsPower profile', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Проверка статуса профиля
     * 
     * @param string $profileId Идентификатор профиля
     * @return bool Статус профиля (true - активен, false - неактивен)
     */
    public function checkProfileStatus(string $profileId): bool
    {
        $this->logInfo('Checking AdsPower profile status', ['profile_id' => $profileId]);
        
        try {
            $url = "{$this->apiBaseUrl}/browser/active?user_id={$profileId}";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            $isActive = ($data['code'] === 0 && isset($data['data']['status']) && $data['data']['status'] === 'Active');
            
            $this->logInfo('AdsPower profile status checked', [
                'profile_id' => $profileId,
                'is_active' => $isActive
            ]);
            
            return $isActive;
        } catch (\Exception $e) {
            $this->logError('Exception when checking AdsPower profile status', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение списка профилей
     * 
     * @return array Список профилей
     */
    public function getProfiles(): array
    {
        $this->logInfo('Getting AdsPower profiles list');
        
        try {
            $url = "{$this->apiBaseUrl}/user/list";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            if ($data['code'] !== 0) {
                $this->logError('Failed to get AdsPower profiles list', [
                    'error' => $data['msg']
                ]);
                return [];
            }
            
            $profiles = [];
            
            if (isset($data['data']['list']) && is_array($data['data']['list'])) {
                foreach ($data['data']['list'] as $profile) {
                    $profiles[] = [
                        'id' => $profile['user_id'],
                        'name' => $profile['name'] ?? 'Unknown',
                        'status' => $profile['status'] ?? 'Unknown'
                    ];
                }
            }
            
            $this->logInfo('AdsPower profiles list retrieved successfully', [
                'count' => count($profiles)
            ]);
            
            return $profiles;
        } catch (\Exception $e) {
            $this->logError('Exception when getting AdsPower profiles list', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
