<?php

namespace App\Posting;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Класс для публикации контента в YouTube Blog через API
 */
class YouTubePoster extends AbstractSocialMediaPoster
{
    /**
     * Базовый URL API YouTube
     * @var string
     */
    private $apiBaseUrl = 'https://www.googleapis.com/youtube/v3';
    
    /**
     * Загрузка учетных данных аккаунта
     */
    protected function loadAccountCredentials()
    {
        $this->accountCredentials = [
            'api_key' => $this->config->get('social.youtube.api_key'),
            'client_id' => $this->config->get('social.youtube.client_id'),
            'client_secret' => $this->config->get('social.youtube.client_secret'),
            'refresh_token' => $this->config->get('social.youtube.refresh_token')
        ];
        
        if (!$this->checkCredentials()) {
            $this->logger->error('YouTube credentials not properly configured', ['account_id' => $this->accountId]);
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
               !empty($this->accountCredentials['client_id']) && 
               !empty($this->accountCredentials['client_secret']) && 
               !empty($this->accountCredentials['refresh_token']);
    }
    
    /**
     * Получение токена доступа через refresh token
     * 
     * @return string Токен доступа
     */
    private function getAccessToken(): string
    {
        try {
            $response = $this->client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $this->accountCredentials['client_id'],
                    'client_secret' => $this->accountCredentials['client_secret'],
                    'refresh_token' => $this->accountCredentials['refresh_token'],
                    'grant_type' => 'refresh_token'
                ]
            ]);
            
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from Google OAuth API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return '';
            }
            
            return $responseData['access_token'] ?? '';
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error getting access token from Google OAuth API', [
                'error' => $e->getMessage()
            ]);
            return '';
        } catch (\Exception $e) {
            $this->logger->error('Error in access token retrieval process', [
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
    
    /**
     * Форматирование контента для YouTube
     * 
     * @param string $content Исходный контент
     * @param array $options Дополнительные параметры
     * @return string Отформатированный контент
     */
    protected function formatContent(string $content, array $options = []): string
    {
        // YouTube имеет ограничение в 5000 символов для описания
        $maxLength = 5000;
        
        if (strlen($content) > $maxLength) {
            $content = substr($content, 0, $maxLength - 3) . '...';
        }
        
        return $content;
    }
    
    /**
     * Публикация контента в YouTube как пост в сообществе
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
            $this->logger->warning('YouTube API rate limit reached', ['account_id' => $this->accountId]);
            return false;
        }
        
        try {
            // Получение токена доступа
            $accessToken = $this->getAccessToken();
            
            if (empty($accessToken)) {
                $this->logger->error('Failed to get YouTube access token', ['account_id' => $this->accountId]);
                return false;
            }
            
            // Форматирование контента
            $formattedContent = $this->formatContent($content, $options);
            
            // Получение ID канала
            $channelId = $this->getChannelId($accessToken);
            
            if (empty($channelId)) {
                $this->logger->error('Failed to get YouTube channel ID', ['account_id' => $this->accountId]);
                return false;
            }
            
            // Подготовка данных для запроса
            $data = [
                'snippet' => [
                    'channelId' => $channelId,
                    'topLevelComment' => [
                        'snippet' => [
                            'textOriginal' => $formattedContent
                        ]
                    ]
                ]
            ];
            
            // Отправка запроса к API YouTube для создания поста в сообществе
            $response = $this->client->post($this->apiBaseUrl . '/commentThreads', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'part' => 'snippet'
                ],
                'json' => $data
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from YouTube API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return false;
            }
            
            // Проверка успешности публикации
            $success = isset($responseData['id']);
            
            // Логирование результата
            $this->logPostResult($success, $formattedContent, $responseData);
            
            return $success;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to YouTube API', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error in YouTube posting process', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение ID канала YouTube
     * 
     * @param string $accessToken Токен доступа
     * @return string ID канала
     */
    private function getChannelId(string $accessToken): string
    {
        try {
            // Отправка запроса к API YouTube для получения информации о канале
            $response = $this->client->get($this->apiBaseUrl . '/channels', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken
                ],
                'query' => [
                    'part' => 'id',
                    'mine' => 'true'
                ]
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from YouTube API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return '';
            }
            
            // Извлечение ID канала
            if (isset($responseData['items'][0]['id'])) {
                return $responseData['items'][0]['id'];
            }
            
            return '';
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to YouTube API', [
                'error' => $e->getMessage()
            ]);
            return '';
        } catch (\Exception $e) {
            $this->logger->error('Error getting YouTube channel ID', [
                'error' => $e->getMessage()
            ]);
            return '';
        }
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
            // Получение токена доступа
            $accessToken = $this->getAccessToken();
            
            if (empty($accessToken)) {
                return false;
            }
            
            // Отправка запроса к API YouTube для проверки статуса аккаунта
            $response = $this->client->get($this->apiBaseUrl . '/channels', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken
                ],
                'query' => [
                    'part' => 'id',
                    'mine' => 'true'
                ]
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from YouTube API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return false;
            }
            
            // Проверка успешности запроса
            return isset($responseData['items']) && count($responseData['items']) > 0;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to YouTube API', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error checking YouTube account status', [
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
            // Получение токена доступа
            $accessToken = $this->getAccessToken();
            
            if (empty($accessToken)) {
                return [];
            }
            
            // Отправка запроса к API YouTube для получения информации о канале
            $response = $this->client->get($this->apiBaseUrl . '/channels', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken
                ],
                'query' => [
                    'part' => 'snippet,statistics',
                    'mine' => 'true'
                ]
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from YouTube API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return [];
            }
            
            if (!isset($responseData['items'][0])) {
                return [];
            }
            
            return $responseData['items'][0];
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to YouTube API', [
                'error' => $e->getMessage()
            ]);
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Error getting YouTube account info', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
