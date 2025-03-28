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
     * Конструктор
     * 
     * @param string $apiUrl URL API Octo Browser (по умолчанию https://app.octobrowser.net)
     * @param string $apiKey API ключ Octo Browser
     */
    public function __construct(string $apiUrl = 'https://app.octobrowser.net', string $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }
    
    /**
     * Запускает профиль браузера
     * 
     * @param string $profileId ID профиля в браузере
     * @return array Информация о запущенном профиле (порт, URL и т.д.)
     */
    public function launchProfile(string $profileId): array
    {
        $endpoint = '/api/v2/automation/start';
        $data = [
            'uuid' => $profileId,
            'automation' => true
        ];
        
        $response = $this->sendRequest('POST', $endpoint, $data);
        
        if (!isset($response['success']) || !$response['success']) {
            throw new \Exception('Не удалось запустить профиль Octo Browser: ' . ($response['message'] ?? 'Неизвестная ошибка'));
        }
        
        return [
            'status' => $response['success'],
            'wsEndpoint' => $response['data']['ws']['endpoint'] ?? null,
            'port' => $response['data']['port'] ?? null,
            'profileId' => $profileId,
            'debuggerAddress' => $response['data']['selenium']['seleniumRemoteDebugAddress'] ?? null
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
        $endpoint = '/api/v2/automation/stop';
        $data = [
            'uuid' => $profileId
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
        $endpoint = '/api/v2/profiles/list';
        $data = [];
        
        if (!empty($filters)) {
            $data = array_merge($data, $filters);
        }
        
        $response = $this->sendRequest('GET', $endpoint, $data);
        
        if (!isset($response['success']) || !$response['success']) {
            throw new \Exception('Не удалось получить список профилей Octo Browser: ' . ($response['message'] ?? 'Неизвестная ошибка'));
        }
        
        $profiles = [];
        
        foreach ($response['data'] ?? [] as $profile) {
            $profiles[] = [
                'id' => $profile['uuid'],
                'name' => $profile['title'],
                'browserId' => $profile['uuid'],
                'type' => 'octo',
                'status' => $profile['status'] === 'ACTIVE' ? 'active' : 'inactive',
                'proxy' => isset($profile['proxy']) ? $this->formatProxy($profile['proxy']) : null,
                'notes' => $profile['notes'] ?? '',
                'lastUsed' => $profile['lastActivity'] ?? '-'
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
        $endpoint = '/api/v2/profiles/new';
        
        $data = [
            'title' => $profileData['name'] ?? 'New Profile',
            'os' => $profileData['platform'] ?? 'win',
            'browser' => $profileData['browser'] ?? 'chrome',
            'notes' => $profileData['notes'] ?? ''
        ];
        
        // Добавляем прокси, если указан
        if (!empty($profileData['proxy'])) {
            $data['proxy'] = $this->parseProxyString($profileData['proxy']);
        }
        
        $response = $this->sendRequest('POST', $endpoint, $data);
        
        if (!isset($response['success']) || !$response['success']) {
            throw new \Exception('Не удалось создать профиль Octo Browser: ' . ($response['message'] ?? 'Неизвестная ошибка'));
        }
        
        return $response['data']['uuid'] ?? '';
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
        $endpoint = '/api/v2/profiles/update';
        
        $data = [
            'uuid' => $profileId
        ];
        
        if (isset($profileData['name'])) {
            $data['title'] = $profileData['name'];
        }
        
        if (isset($profileData['notes'])) {
            $data['notes'] = $profileData['notes'];
        }
        
        // Обновляем прокси, если указан
        if (!empty($profileData['proxy'])) {
            $data['proxy'] = $this->parseProxyString($profileData['proxy']);
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
        $endpoint = '/api/v2/profiles/remove';
        $data = [
            'uuid' => $profileId
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
            $endpoint = '/api/v2/profiles/list';
            $response = $this->sendRequest('GET', $endpoint, ['limit' => 1]);
            
            return isset($response['success']) && $response['success'];
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Парсит строку прокси в формат для API
     * 
     * @param string $proxyString Строка прокси в формате host:port:username:password
     * @return array Данные прокси в формате для API
     */
    private function parseProxyString(string $proxyString): array
    {
        $proxyParts = explode(':', $proxyString);
        $proxyData = [
            'type' => 'HTTP',
            'host' => $proxyParts[0],
            'port' => (int)($proxyParts[1] ?? 0)
        ];
        
        if (count($proxyParts) >= 4) {
            $proxyData['username'] = $proxyParts[2];
            $proxyData['password'] = $proxyParts[3];
        }
        
        return $proxyData;
    }
    
    /**
     * Форматирует данные прокси из API в строку
     * 
     * @param array $proxyData Данные прокси из API
     * @return string Строка прокси
     */
    private function formatProxy(array $proxyData): string
    {
        $proxy = $proxyData['host'] . ':' . $proxyData['port'];
        
        if (!empty($proxyData['username']) && !empty($proxyData['password'])) {
            $proxy .= ':' . $proxyData['username'] . ':' . $proxyData['password'];
        }
        
        return $proxy;
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
            'X-Octo-Api-Token: ' . $this->apiKey
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
        
        if ($httpCode === 429) {
            // Обработка ограничения запросов (rate limit)
            $retryAfter = 60; // По умолчанию ждем 60 секунд
            throw new \Exception('Rate limit exceeded. Retry after ' . $retryAfter . ' seconds.');
        } elseif ($httpCode >= 400) {
            throw new \Exception('HTTP Error: ' . $httpCode);
        }
        
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response');
        }
        
        return $responseData;
    }
}
