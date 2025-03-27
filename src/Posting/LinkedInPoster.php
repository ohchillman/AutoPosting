<?php

namespace App\Posting;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Класс для публикации контента в LinkedIn через API
 */
class LinkedInPoster extends AbstractSocialMediaPoster
{
    /**
     * Базовый URL API LinkedIn
     * @var string
     */
    private $apiBaseUrl = 'https://api.linkedin.com/v2';
    
    /**
     * Загрузка учетных данных аккаунта
     */
    protected function loadAccountCredentials()
    {
        $this->accountCredentials = [
            'client_id' => $this->config->get('social.linkedin.client_id'),
            'client_secret' => $this->config->get('social.linkedin.client_secret'),
            'access_token' => $this->config->get('social.linkedin.access_token')
        ];
        
        if (!$this->checkCredentials()) {
            $this->logger->error('LinkedIn credentials not properly configured', ['account_id' => $this->accountId]);
        }
    }
    
    /**
     * Проверка учетных данных
     * 
     * @return bool Результат проверки
     */
    protected function checkCredentials(): bool
    {
        return !empty($this->accountCredentials['client_id']) && 
               !empty($this->accountCredentials['client_secret']) && 
               !empty($this->accountCredentials['access_token']);
    }
    
    /**
     * Форматирование контента для LinkedIn
     * 
     * @param string $content Исходный контент
     * @param array $options Дополнительные параметры
     * @return string Отформатированный контент
     */
    protected function formatContent(string $content, array $options = []): string
    {
        // LinkedIn имеет ограничение в 3000 символов для постов
        $maxLength = 3000;
        
        if (strlen($content) > $maxLength) {
            $content = substr($content, 0, $maxLength - 3) . '...';
        }
        
        return $content;
    }
    
    /**
     * Публикация контента в LinkedIn
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
            $this->logger->warning('LinkedIn API rate limit reached', ['account_id' => $this->accountId]);
            return false;
        }
        
        try {
            // Форматирование контента
            $formattedContent = $this->formatContent($content, $options);
            
            // Получение ID автора (person URN)
            $personUrn = $this->getPersonUrn();
            
            if (empty($personUrn)) {
                $this->logger->error('Failed to get LinkedIn person URN', ['account_id' => $this->accountId]);
                return false;
            }
            
            // Подготовка данных для запроса
            $data = [
                'author' => 'urn:li:person:' . $personUrn,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $formattedContent
                        ],
                        'shareMediaCategory' => 'NONE'
                    ]
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
                ]
            ];
            
            // Добавление медиа, если есть
            if (!empty($media)) {
                $mediaAssets = $this->uploadMedia($media);
                
                if (!empty($mediaAssets)) {
                    $data['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'IMAGE';
                    $data['specificContent']['com.linkedin.ugc.ShareContent']['media'] = $mediaAssets;
                }
            }
            
            // Отправка запроса к API LinkedIn
            $response = $this->client->post($this->apiBaseUrl . '/ugcPosts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accountCredentials['access_token'],
                    'Content-Type' => 'application/json',
                    'X-Restli-Protocol-Version' => '2.0.0'
                ],
                'json' => $data
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from LinkedIn API', [
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
            $this->logger->error('Error sending request to LinkedIn API', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error in LinkedIn posting process', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение URN пользователя LinkedIn
     * 
     * @return string URN пользователя
     */
    private function getPersonUrn(): string
    {
        try {
            // Отправка запроса к API LinkedIn для получения информации о пользователе
            $response = $this->client->get($this->apiBaseUrl . '/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accountCredentials['access_token'],
                    'X-Restli-Protocol-Version' => '2.0.0'
                ]
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from LinkedIn API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return '';
            }
            
            // Извлечение ID пользователя из URN
            if (isset($responseData['id'])) {
                return $responseData['id'];
            }
            
            return '';
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to LinkedIn API', [
                'error' => $e->getMessage()
            ]);
            return '';
        } catch (\Exception $e) {
            $this->logger->error('Error getting LinkedIn person URN', [
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
    
    /**
     * Загрузка медиа-файлов в LinkedIn
     * 
     * @param array $media Массив путей к медиа-файлам
     * @return array Массив объектов медиа для публикации
     */
    private function uploadMedia(array $media): array
    {
        $mediaAssets = [];
        
        foreach ($media as $mediaPath) {
            try {
                // В реальном приложении здесь должна быть реализация загрузки медиа через LinkedIn API
                // Для упрощения примера возвращаем заглушку
                $mediaAssets[] = [
                    'status' => 'READY',
                    'description' => [
                        'text' => 'Image description'
                    ],
                    'media' => 'urn:li:image:' . md5($mediaPath)
                ];
            } catch (\Exception $e) {
                $this->logger->error('Error uploading media to LinkedIn', [
                    'error' => $e->getMessage(),
                    'media_path' => $mediaPath
                ]);
            }
        }
        
        return $mediaAssets;
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
            // Отправка запроса к API LinkedIn для проверки статуса аккаунта
            $response = $this->client->get($this->apiBaseUrl . '/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accountCredentials['access_token'],
                    'X-Restli-Protocol-Version' => '2.0.0'
                ]
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from LinkedIn API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return false;
            }
            
            // Проверка успешности запроса
            return isset($responseData['id']);
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to LinkedIn API', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error checking LinkedIn account status', [
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
            // Отправка запроса к API LinkedIn для получения информации об аккаунте
            $response = $this->client->get($this->apiBaseUrl . '/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accountCredentials['access_token'],
                    'X-Restli-Protocol-Version' => '2.0.0'
                ],
                'query' => [
                    'projection' => '(id,firstName,lastName,profilePicture,headline,vanityName)'
                ]
            ]);
            
            // Обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from LinkedIn API', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return [];
            }
            
            return $responseData;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to LinkedIn API', [
                'error' => $e->getMessage()
            ]);
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Error getting LinkedIn account info', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
