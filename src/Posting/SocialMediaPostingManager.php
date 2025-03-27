<?php

namespace App\Posting;

use App\Core\LogManager;
use App\Core\Config;

/**
 * Класс для управления публикацией контента в социальные сети
 */
class SocialMediaPostingManager
{
    private $logger;
    private $config;
    private $posters = [];
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->initPosters();
    }
    
    /**
     * Инициализация постеров для всех аккаунтов
     */
    private function initPosters()
    {
        // Инициализация постеров для Twitter
        $this->posters['twitter_account1'] = new TwitterPoster('twitter_account1');
        $this->posters['twitter_account2'] = new TwitterPoster('twitter_account2');
        
        // Инициализация постеров для LinkedIn
        $this->posters['linkedin_account1'] = new LinkedInPoster('linkedin_account1');
        
        // Инициализация постеров для YouTube
        $this->posters['youtube_account1'] = new YouTubePoster('youtube_account1');
        
        // Инициализация постеров для Threads
        $this->posters['threads_account1'] = new ThreadsPoster('threads_account1');
        
        $this->logger->info('Initialized ' . count($this->posters) . ' social media posters');
    }
    
    /**
     * Получение списка всех постеров
     * 
     * @return array Список постеров
     */
    public function getPosters(): array
    {
        return $this->posters;
    }
    
    /**
     * Получение постера по идентификатору аккаунта
     * 
     * @param string $accountId Идентификатор аккаунта
     * @return SocialMediaPosterInterface|null Постер или null, если не найден
     */
    public function getPoster(string $accountId): ?SocialMediaPosterInterface
    {
        return $this->posters[$accountId] ?? null;
    }
    
    /**
     * Публикация контента в конкретный аккаунт
     * 
     * @param string $accountId Идентификатор аккаунта
     * @param string $content Контент для публикации
     * @param array $media Медиа-файлы для публикации (опционально)
     * @param array $options Дополнительные параметры
     * @return bool Результат публикации
     */
    public function postToAccount(string $accountId, string $content, array $media = [], array $options = []): bool
    {
        $poster = $this->getPoster($accountId);
        
        if (!$poster) {
            $this->logger->error('Poster not found for account', ['account_id' => $accountId]);
            return false;
        }
        
        $this->logger->info('Posting content to account', [
            'account_id' => $accountId,
            'content_length' => strlen($content),
            'media_count' => count($media)
        ]);
        
        return $poster->post($content, $media, $options);
    }
    
    /**
     * Публикация контента во все аккаунты
     * 
     * @param array $contentByAccount Массив контента для каждого аккаунта
     * @param array $media Медиа-файлы для публикации (опционально)
     * @param array $options Дополнительные параметры
     * @return array Результаты публикации для каждого аккаунта
     */
    public function postToAllAccounts(array $contentByAccount, array $media = [], array $options = []): array
    {
        $results = [];
        
        foreach ($contentByAccount as $accountId => $content) {
            $result = $this->postToAccount($accountId, $content, $media, $options);
            $results[$accountId] = $result;
        }
        
        $successCount = count(array_filter($results));
        $totalCount = count($results);
        
        $this->logger->info("Posted content to {$successCount} of {$totalCount} accounts");
        
        return $results;
    }
    
    /**
     * Проверка статуса всех аккаунтов
     * 
     * @return array Статусы аккаунтов
     */
    public function checkAllAccountsStatus(): array
    {
        $statuses = [];
        
        foreach ($this->posters as $accountId => $poster) {
            $status = $poster->checkAccountStatus();
            $statuses[$accountId] = $status;
            
            $this->logger->info('Account status check', [
                'account_id' => $accountId,
                'status' => $status ? 'active' : 'inactive'
            ]);
        }
        
        return $statuses;
    }
    
    /**
     * Получение информации о всех аккаунтах
     * 
     * @return array Информация об аккаунтах
     */
    public function getAllAccountsInfo(): array
    {
        $info = [];
        
        foreach ($this->posters as $accountId => $poster) {
            $accountInfo = $poster->getAccountInfo();
            $info[$accountId] = $accountInfo;
        }
        
        return $info;
    }
}
