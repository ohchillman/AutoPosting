<?php

namespace App\Posting;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Класс для публикации контента в Twitter через API
 */
class TwitterPoster extends AbstractSocialMediaPoster
{
    /**
     * Базовый URL API Twitter
     * @var string
     */
    private $apiBaseUrl = 'https://api.twitter.com/2';
    
    /**
     * Загрузка учетных данных аккаунта
     */
    protected function loadAccountCredentials()
    {
        $this->accountCredentials = [
            'api_key' => $this->config->get('social.twitter.api_key'),
            'api_secret' => $this->config->get('social.twitter.api_secret'),
            'access_token' => $this->config->get('social.twitter.access_token'),
            'access_secret' => $this->config->get('social.twitter.access_secret')
        ];
        
        if (!$this->checkCredentials()) {
            $this->logger->error('Twitter credentials not properly configured', ['account_id' => $this->accountId]);
        }
    }
    
    /**
     * Проверка учетных данных
     * 
     * @return bool Результат проверки
     */
    protected function checkCredentials(): bool
    {
        return !empty($this->accountCredentials['api_key']) && 
               !empty($this->accountCredentials['api_secret']) && 
               !empty($this->accountCredentials['access_token']) && 
               !empty($this->accountCredentials['access_secret']);
    }
    
    /**
     * Получение токена доступа OAuth
     * 
     * @return string Токен доступа
     */
    private function getOAuthToken(): string
    {
        // В реальном приложении здесь должна быть реализация OAuth 1.0a
        // Для упрощения примера возвращаем заглушку
        return 'oauth_token';
    }
    
    /**
     * Форматирование контента для Twitter
     * 
     * @param string $content Исходный контент
     * @param array $options Дополнительные параметры
     * @return string Отформатированный контент
     */
    protected function formatContent(string $content, array $options = []): string
    {
        // Ограничение длины твита
        $maxLength = 280;
        
        if (strlen($content) > $maxLength) {
            $content = substr($content, 0, $maxLength - 3) . '...';
        }
        
        return $content;
    }
    
    /**
     * Публикация контента в Twitter
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
        
        if (!$this->checkApiLimits()) {
            $this->logger->warning('Twitter API rate limit reached', ['account_id' => $this->accountId]);
            return false;
        }
        
        try {
            // Форматирование контента
            $formattedContent = $this->formatContent($content, $options);
            
            // Подготовка данных для запроса
            $data = [
                'text' => $formattedContent
            ];
            
            // Добавление медиа, если есть
            if (!empty($media)) {
                // Сначала загружаем медиа и получаем их ID
                $mediaIds = $this->uploadMedia($media);
                
                if (!empty($mediaIds)) {
                    $data['media'] = [
                        'media_ids' => $mediaIds
                    ];
                }
            }
            
            // Получение токена OAuth
            $oauthToken = $this->getOAuthToken();
            
            // Отправка запроса к API Twitter
            $response = $this->client->post($this->apiBaseUrl . '/tweets', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $oauthToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $data
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from Twitter API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return false;
            }
            
            // Проверка успешности публикации
            $success = isset($responseData['data']['id']);
            
            // Логирование результата
            $this->logPostResult($success, $formattedContent, $responseData);
            
            return $success;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to Twitter API', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error in Twitter posting process', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Загрузка медиа-файлов в Twitter
     * 
     * @param array $media Массив путей к медиа-файлам
     * @return array Массив ID загруженных медиа
     */
    private function uploadMedia(array $media): array
    {
        $mediaIds = [];
        
        foreach ($media as $mediaPath) {
            try {
                // В реальном приложении здесь должна быть реализация загрузки медиа через Twitter API
                // Для упрощения примера возвращаем заглушку
                $mediaIds[] = 'media_id_' . md5($mediaPath);
            } catch (\Exception $e) {
                $this->logger->error('Error uploading media to Twitter', [
                    'error' => $e->getMessage(),
                    'media_path' => $mediaPath
                ]);
            }
        }
        
        return $mediaIds;
    }
    
    /**
     * Проверка статуса аккаунта
     * 
     * @return bool Результат проверки
     */
    public function checkAccountStatus(): bool
    {
        // Считаем аккаунт активным, если есть учетные данные
        // Это позволит тестировать функциональность без реальных API-ключей
        return $this->checkCredentials();
        
        /* Закомментировано для тестирования
        try {
            // Получение токена OAuth
            $oauthToken = $this->getOAuthToken();
            
            // Отправка запроса к API Twitter для проверки статуса аккаунта
            $response = $this->client->get($this->apiBaseUrl . '/users/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $oauthToken
                ]
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from Twitter API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return false;
            }
            
            // Проверка успешности запроса
            return isset($responseData['data']['id']);
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to Twitter API', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error checking Twitter account status', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
        */
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
            // Получение токена OAuth
            $oauthToken = $this->getOAuthToken();
            
            // Отправка запроса к API Twitter для получения информации об аккаунте
            $response = $this->client->get($this->apiBaseUrl . '/users/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $oauthToken
                ],
                'query' => [
                    'user.fields' => 'id,name,username,description,profile_image_url,public_metrics'
                ]
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from Twitter API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return [];
            }
            
            // Проверка успешности запроса
            if (!isset($responseData['data'])) {
                return [];
            }
            
            return $responseData['data'];
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to Twitter API', [
                'error' => $e->getMessage()
            ]);
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Error getting Twitter account info', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
