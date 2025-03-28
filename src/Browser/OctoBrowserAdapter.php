<?php

namespace App\Browser;

/**
 * Адаптер для работы с Octo Browser
 */
class OctoBrowserAdapter implements BrowserAdapterInterface
{
    /**
     * URL API Octo Browser
     * 
     * @var string
     */
    private $apiUrl;
    
    /**
     * API ключ Octo Browser
     * 
     * @var string
     */
    private $apiKey;
    
    /**
     * ID пользователя Octo Browser
     * 
     * @var string
     */
    private $userId;
    
    /**
     * ID рабочего пространства Octo Browser
     * 
     * @var string|null
     */
    private $workspaceId;
    
    /**
     * Конструктор
     * 
     * @param string $apiUrl URL API Octo Browser
     * @param string $apiKey API ключ Octo Browser
     * @param string $userId ID пользователя Octo Browser
     * @param string|null $workspaceId ID рабочего пространства Octo Browser
     */
    public function __construct(string $apiUrl, string $apiKey, string $userId, ?string $workspaceId = null)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->userId = $userId;
        $this->workspaceId = $workspaceId;
    }
    
    /**
     * Запускает профиль браузера
     * 
     * @param string $profileId ID профиля в браузере
     * @return array Информация о запущенном профиле (порт, URL и т.д.)
     */
    public function launchProfile(string $profileId): array
    {
        $endpoint = '/api/v1/browser/start';
        $data = [
            'profile_id' => $profileId,
            'automation' => true
        ];
        
        $response = $this->sendRequest('POST', $endpoint, $data);
        
        if (!isset($response['success']) || !$response['success']) {
            throw new \Exception('Не удалось запустить профиль Octo Browser: ' . ($response['message'] ?? 'Неизвестная ошибка'));
        }
        
        return [
            'status' => $response['success'],
            'wsEndpoint' => $response['data']['ws_endpoint'] ?? null,
            'port' => $response['data']['port'] ?? null,
            'profileId' => $profileId,
            'debuggerAddress' => $response['data']['debugger_address'] ?? null
        ];
    }
    
    /**
     * Закрывает профиль браузера
     * 
     * @param string $profileId ID профиля в браузере
     * @return bool Результат операции
     */
    public function closeProfile(string $profileId): bool
    {
        $endpoint = '/api/v1/browser/stop';
        $data = [
            'profile_id' => $profileId
        ];
        
        $response = $this->sendRequest('POST', $endpoint, $data);
        
        return isset($response['success']) && $response['success'];
    }
    
    /**
     * Получает список профилей
     * 
     * @param array $filters Фильтры для списка профилей
     * @return array Список профилей
     */
    public function getProfiles(array $filters = []): array
    {
        $endpoint = '/api/v1/profiles/list';
        $data = [];
        
        if (!empty($this->workspaceId)) {
            $data['workspace_id'] = $this->workspaceId;
        }
        
        if (!empty($filters)) {
            $data = array_merge($data, $filters);
        }
        
        $response = $this->sendRequest('GET', $endpoint, $data);
        
        if (!isset($response['success']) || !$response['success']) {
            throw new \Exception('Не удалось получить список профилей Octo Browser: ' . ($response['message'] ?? 'Неизвестная ошибка'));
        }
        
        $profiles = [];
        
        foreach ($response['data']['profiles'] ?? [] as $profile) {
            $profiles[] = [
                'id' => $profile['id'],
                'name' => $profile['name'],
                'browserId' => $profile['id'],
                'type' => 'octo',
                'status' => $profile['status'] === 'active' ? 'active' : 'inactive',
                'proxy' => isset($profile['proxy']) ? $profile['proxy']['host'] . ':' . $profile['proxy']['port'] : null,
                'notes' => $profile['notes'] ?? '',
                'lastUsed' => $profile['last_used'] ?? '-'
            ];
        }
        
        return $profiles;
    }
    
    /**
     * Создает новый профиль
     * 
     * @param array $profileData Данные профиля
     * @return string ID созданного профиля
     */
    public function createProfile(array $profileData): string
    {
        $endpoint = '/api/v1/profiles/create';
        
        $data = [
            'name' => $profileData['name'] ?? 'New Profile',
            'platform' => $profileData['platform'] ?? 'windows',
            'browser' => $profileData['browser'] ?? 'chrome',
            'notes' => $profileData['notes'] ?? ''
        ];
        
        if (!empty($this->workspaceId)) {
            $data['workspace_id'] = $this->workspaceId;
        }
        
        // Добавляем прокси, если указан
        if (!empty($profileData['proxy'])) {
            $proxyParts = explode(':', $profileData['proxy']);
            
            if (count($proxyParts) >= 2) {
                $data['proxy'] = [
                    'type' => 'http',
                    'host' => $proxyParts[0],
                    'port' => $proxyParts[1]
                ];
                
                if (count($proxyParts) >= 4) {
                    $data['proxy']['username'] = $proxyParts[2];
                    $data['proxy']['password'] = $proxyParts[3];
                }
            }
        }
        
        $response = $this->sendRequest('POST', $endpoint, $data);
        
        if (!isset($response['success']) || !$response['success']) {
            throw new \Exception('Не удалось создать профиль Octo Browser: ' . ($response['message'] ?? 'Неизвестная ошибка'));
        }
        
        return $response['data']['id'] ?? '';
    }
    
    /**
     * Обновляет профиль
     * 
     * @param string $profileId ID профиля
     * @param array $profileData Новые данные профиля
     * @return bool Результат операции
     */
    public function updateProfile(string $profileId, array $profileData): bool
    {
        $endpoint = '/api/v1/profiles/update';
        
        $data = [
            'id' => $profileId
        ];
        
        if (isset($profileData['name'])) {
            $data['name'] = $profileData['name'];
        }
        
        if (isset($profileData['notes'])) {
            $data['notes'] = $profileData['notes'];
        }
        
        // Обновляем прокси, если указан
        if (!empty($profileData['proxy'])) {
            $proxyParts = explode(':', $profileData['proxy']);
            
            if (count($proxyParts) >= 2) {
                $data['proxy'] = [
                    'type' => 'http',
                    'host' => $proxyParts[0],
                    'port' => $proxyParts[1]
                ];
                
                if (count($proxyParts) >= 4) {
                    $data['proxy']['username'] = $proxyParts[2];
                    $data['proxy']['password'] = $proxyParts[3];
                }
            }
        }
        
        $response = $this->sendRequest('POST', $endpoint, $data);
        
        return isset($response['success']) && $response['success'];
    }
    
    /**
     * Удаляет профиль
     * 
     * @param string $profileId ID профиля
     * @return bool Результат операции
     */
    public function deleteProfile(string $profileId): bool
    {
        $endpoint = '/api/v1/profiles/delete';
        $data = [
            'id' => $profileId
        ];
        
        $response = $this->sendRequest('POST', $endpoint, $data);
        
        return isset($response['success']) && $response['success'];
    }
    
    /**
     * Проверяет соединение с API браузера
     * 
     * @return bool Результат проверки
     */
    public function testConnection(): bool
    {
        try {
            $endpoint = '/api/v1/status';
            $response = $this->sendRequest('GET', $endpoint);
            
            return isset($response['success']) && $response['success'];
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Отправляет запрос к API Octo Browser
     * 
     * @param string $method HTTP метод
     * @param string $endpoint Конечная точка API
     * @param array $data Данные запроса
     * @return array Ответ API
     */
    private function sendRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'X-Api-Key: ' . $this->apiKey,
            'X-User-Id: ' . $this->userId
        ];
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new \Exception('HTTP Error: ' . $httpCode);
        }
        
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response');
        }
        
        return $responseData;
    }
}
