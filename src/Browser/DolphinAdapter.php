<?php

namespace App\Browser;

use GuzzleHttp\Client;
use Selenium\WebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Адаптер для антидетект браузера Dolphin Anty
 */
class DolphinAdapter extends BrowserAdapter
{
    /**
     * @var string Базовый URL API Dolphin
     */
    protected $apiBaseUrl;
    
    /**
     * @var string API токен для авторизации
     */
    protected $apiToken;
    
    /**
     * @var string Путь к ChromeDriver
     */
    protected $chromeDriverPath;
    
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
        
        $this->apiBaseUrl = $config['api_base_url'] ?? 'http://localhost:3001/v1.0';
        $this->apiToken = $config['api_token'] ?? '';
        $this->chromeDriverPath = $config['chromedriver_path'] ?? '/usr/local/bin/chromedriver';
        
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false
        ]);
        
        // Авторизация с использованием токена
        $this->authorize();
    }
    
    /**
     * Авторизация в API Dolphin
     * 
     * @return bool Результат авторизации
     */
    protected function authorize(): bool
    {
        if (empty($this->apiToken)) {
            $this->logError('API token is not provided for Dolphin Anty');
            return false;
        }
        
        try {
            $url = "{$this->apiBaseUrl}/auth/login-with-token";
            $response = $this->client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'token' => $this->apiToken
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['status']) || $data['status'] !== 'OK') {
                $this->logError('Failed to authorize in Dolphin Anty API', [
                    'error' => $data['message'] ?? 'Unknown error'
                ]);
                return false;
            }
            
            $this->logInfo('Successfully authorized in Dolphin Anty API');
            return true;
        } catch (\Exception $e) {
            $this->logError('Exception when authorizing in Dolphin Anty API', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Запуск профиля браузера
     * 
     * @param string $profileId Идентификатор профиля
     * @return array Данные для подключения к браузеру
     */
    public function startProfile(string $profileId): array
    {
        $this->logInfo('Starting Dolphin Anty profile', ['profile_id' => $profileId]);
        
        try {
            $url = "{$this->apiBaseUrl}/browser_profiles/{$profileId}/start?automation=1";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['status']) || $data['status'] !== 'OK') {
                $this->logError('Failed to start Dolphin Anty profile', [
                    'profile_id' => $profileId,
                    'error' => $data['message'] ?? 'Unknown error'
                ]);
                throw new \Exception("Failed to start Dolphin Anty profile: " . ($data['message'] ?? 'Unknown error'));
            }
            
            $this->logInfo('Dolphin Anty profile started successfully', [
                'profile_id' => $profileId,
                'port' => $data['port'],
                'ws_endpoint' => $data['wsEndpoint']
            ]);
            
            return [
                'port' => $data['port'],
                'ws_endpoint' => $data['wsEndpoint']
            ];
        } catch (\Exception $e) {
            $this->logError('Exception when starting Dolphin Anty profile', [
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
        $this->logInfo('Stopping Dolphin Anty profile', ['profile_id' => $profileId]);
        
        try {
            $url = "{$this->apiBaseUrl}/browser_profiles/{$profileId}/stop";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['status']) || $data['status'] !== 'OK') {
                $this->logError('Failed to stop Dolphin Anty profile', [
                    'profile_id' => $profileId,
                    'error' => $data['message'] ?? 'Unknown error'
                ]);
                return false;
            }
            
            $this->logInfo('Dolphin Anty profile stopped successfully', [
                'profile_id' => $profileId
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Exception when stopping Dolphin Anty profile', [
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
        $this->logInfo('Creating Selenium WebDriver for Dolphin Anty profile', [
            'profile_id' => $profileId,
            'port' => $connectionData['port'],
            'ws_endpoint' => $connectionData['ws_endpoint']
        ]);
        
        try {
            $options = new ChromeOptions();
            $options->addArguments(['--disable-notifications']);
            
            // Формируем URL для подключения к браузеру
            $debuggerAddress = "127.0.0.1:{$connectionData['port']}";
            $options->setExperimentalOption('debuggerAddress', $debuggerAddress);
            
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
            
            // Используем путь к ChromeDriver, указанный в конфигурации
            $driver = RemoteWebDriver::create(
                $this->chromeDriverPath,
                $capabilities
            );
            
            $this->logInfo('Selenium WebDriver created successfully', [
                'profile_id' => $profileId
            ]);
            
            return $driver;
        } catch (\Exception $e) {
            $this->logError('Exception when creating Selenium WebDriver for Dolphin Anty profile', [
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
        $this->logInfo('Checking Dolphin Anty profile status', ['profile_id' => $profileId]);
        
        try {
            $url = "{$this->apiBaseUrl}/browser_profiles/{$profileId}";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['status']) || $data['status'] !== 'OK') {
                $this->logError('Failed to check Dolphin Anty profile status', [
                    'profile_id' => $profileId,
                    'error' => $data['message'] ?? 'Unknown error'
                ]);
                return false;
            }
            
            $isActive = isset($data['data']['active']) && $data['data']['active'] === true;
            
            $this->logInfo('Dolphin Anty profile status checked', [
                'profile_id' => $profileId,
                'is_active' => $isActive
            ]);
            
            return $isActive;
        } catch (\Exception $e) {
            $this->logError('Exception when checking Dolphin Anty profile status', [
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
        $this->logInfo('Getting Dolphin Anty profiles list');
        
        try {
            $url = "{$this->apiBaseUrl}/browser_profiles";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['status']) || $data['status'] !== 'OK') {
                $this->logError('Failed to get Dolphin Anty profiles list', [
                    'error' => $data['message'] ?? 'Unknown error'
                ]);
                return [];
            }
            
            $profiles = [];
            
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $profile) {
                    $profiles[] = [
                        'id' => $profile['id'],
                        'name' => $profile['name'] ?? 'Unknown',
                        'status' => $profile['active'] ? 'Active' : 'Inactive'
                    ];
                }
            }
            
            $this->logInfo('Dolphin Anty profiles list retrieved successfully', [
                'count' => count($profiles)
            ]);
            
            return $profiles;
        } catch (\Exception $e) {
            $this->logError('Exception when getting Dolphin Anty profiles list', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
