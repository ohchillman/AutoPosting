<?php

namespace App\Posting;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Класс для публикации контента в Threads через Dolphin Anty
 */
class ThreadsPoster extends AbstractSocialMediaPoster
{
    /**
     * Базовый URL API Dolphin Anty
     * @var string
     */
    private $apiBaseUrl;
    
    /**
     * API ключ Dolphin Anty
     * @var string
     */
    private $apiKey;
    
    /**
     * ID профиля Dolphin Anty
     * @var string
     */
    private $profileId;
    
    /**
     * Загрузка учетных данных аккаунта
     */
    protected function loadAccountCredentials()
    {
        $this->apiBaseUrl = $this->config->get('dolphin.api_url');
        $this->apiKey = $this->config->get('dolphin.api_key');
        
        // Получение ID профиля из идентификатора аккаунта
        // Предполагается, что accountId имеет формат "threads_profileId"
        $parts = explode('_', $this->accountId);
        $this->profileId = $parts[1] ?? '';
        
        if (!$this->checkCredentials()) {
            $this->logger->error('Dolphin Anty credentials not properly configured', ['account_id' => $this->accountId]);
        }
    }
    
    /**
     * Проверка учетных данных
     * 
     * @return bool Результат проверки
     */
    protected function checkCredentials(): bool
    {
        return !empty($this->apiBaseUrl) && !empty($this->apiKey) && !empty($this->profileId);
    }
    
    /**
     * Форматирование контента для Threads
     * 
     * @param string $content Исходный контент
     * @param array $options Дополнительные параметры
     * @return string Отформатированный контент
     */
    protected function formatContent(string $content, array $options = []): string
    {
        // Threads имеет ограничение в 500 символов
        $maxLength = 500;
        
        if (strlen($content) > $maxLength) {
            $content = substr($content, 0, $maxLength - 3) . '...';
        }
        
        return $content;
    }
    
    /**
     * Публикация контента в Threads через Dolphin Anty
     * 
     * @param string $content Контент для публикации
     * @param array $media Медиа-файлы для публикации (опционально)
     * @param array $options Дополнительные параметры
     * @return bool Результат публикации
     */
    public function post(string $content, array $media = [], array $options = []): bool
    {
        if (!$this->checkCredentials()) {
            return false;
        }
        
        try {
            // Форматирование контента
            $formattedContent = $this->formatContent($content, $options);
            
            // Подготовка данных для запроса
            $data = [
                'profileId' => $this->profileId,
                'action' => 'post_threads',
                'params' => [
                    'text' => $formattedContent
                ]
            ];
            
            // Добавление медиа, если есть
            if (!empty($media)) {
                $data['params']['media'] = $media;
            }
            
            // Отправка запроса к API Dolphin Anty
            $response = $this->client->post($this->apiBaseUrl . '/browser_profiles/start_automation', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => $data
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from Dolphin Anty API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return false;
            }
            
            // Проверка успешности запуска автоматизации
            $success = isset($responseData['success']) && $responseData['success'] === true;
            
            if ($success) {
                // Получение ID задачи автоматизации
                $taskId = $responseData['data']['taskId'] ?? '';
                
                if (!empty($taskId)) {
                    // Ожидание завершения задачи
                    $taskResult = $this->waitForTaskCompletion($taskId);
                    $success = $taskResult['success'] ?? false;
                } else {
                    $success = false;
                }
            }
            
            // Логирование результата
            $this->logPostResult($success, $formattedContent, $responseData);
            
            return $success;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to Dolphin Anty API', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error in Threads posting process', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Ожидание завершения задачи автоматизации
     * 
     * @param string $taskId ID задачи
     * @return array Результат выполнения задачи
     */
    private function waitForTaskCompletion(string $taskId): array
    {
        $maxAttempts = 30;
        $attempt = 0;
        $delay = 5; // секунд
        
        while ($attempt < $maxAttempts) {
            try {
                // Запрос статуса задачи
                $response = $this->client->get($this->apiBaseUrl . '/automation_tasks/' . $taskId, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey
                    ]
                ]);
                
                $responseBody = (string) $response->getBody();
                $responseData = json_decode($responseBody, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logger->error('Error decoding response from Dolphin Anty API', [
                        'error' => json_last_error_msg(),
                        'response' => $responseBody
                    ]);
                    return ['success' => false];
                }
                
                // Проверка статуса задачи
                $status = $responseData['data']['status'] ?? '';
                
                if ($status === 'completed') {
                    return [
                        'success' => true,
                        'data' => $responseData['data']
                    ];
                } else if ($status === 'failed') {
                    $this->logger->error('Automation task failed', [
                        'task_id' => $taskId,
                        'error' => $responseData['data']['error'] ?? 'Unknown error'
                    ]);
                    return ['success' => false];
                }
                
                // Ожидание перед следующей попыткой
                sleep($delay);
                $attempt++;
                
            } catch (\Exception $e) {
                $this->logger->error('Error checking automation task status', [
                    'error' => $e->getMessage(),
                    'task_id' => $taskId
                ]);
                return ['success' => false];
            }
        }
        
        $this->logger->error('Timeout waiting for automation task completion', [
            'task_id' => $taskId
        ]);
        return ['success' => false];
    }
    
    /**
     * Проверка статуса аккаунта
     * 
     * @return bool Результат проверки
     */
    public function checkAccountStatus(): bool
    {
        if (!$this->checkCredentials()) {
            return false;
        }
        
        try {
            // Запрос информации о профиле
            $response = $this->client->get($this->apiBaseUrl . '/browser_profiles/' . $this->profileId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ]
            ]);
            
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from Dolphin Anty API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return false;
            }
            
            // Проверка успешности запроса
            return isset($responseData['success']) && $responseData['success'] === true;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to Dolphin Anty API', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error checking Threads account status', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение информации об аккаунте
     * 
     * @return array Информация об аккаунте
     */
    public function getAccountInfo(): array
    {
        if (!$this->checkCredentials()) {
            return [];
        }
        
        try {
            // Запрос информации о профиле
            $response = $this->client->get($this->apiBaseUrl . '/browser_profiles/' . $this->profileId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ]
            ]);
            
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from Dolphin Anty API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return [];
            }
            
            if (!isset($responseData['success']) || $responseData['success'] !== true) {
                return [];
            }
            
            return $responseData['data'] ?? [];
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to Dolphin Anty API', [
                'error' => $e->getMessage()
            ]);
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Error getting Threads account info', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
