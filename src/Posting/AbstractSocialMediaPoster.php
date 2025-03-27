<?php

namespace App\Posting;

use App\Core\LogManager;
use App\Core\Config;
use GuzzleHttp\Client;

/**
 * Абстрактный класс для системы автопостинга в социальные сети
 */
abstract class AbstractSocialMediaPoster implements SocialMediaPosterInterface
{
    protected $logger;
    protected $config;
    protected $client;
    protected $accountId;
    protected $accountCredentials;
    
    /**
     * Конструктор
     * 
     * @param string $accountId Идентификатор аккаунта
     */
    public function __construct(string $accountId)
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false
        ]);
        $this->accountId = $accountId;
        $this->loadAccountCredentials();
    }
    
    /**
     * Загрузка учетных данных аккаунта
     */
    abstract protected function loadAccountCredentials();
    
    /**
     * Проверка наличия необходимых учетных данных
     * 
     * @return bool Результат проверки
     */
    protected function checkCredentials(): bool
    {
        if (empty($this->accountCredentials)) {
            $this->logger->error('Account credentials not loaded', ['account_id' => $this->accountId]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Форматирование контента для публикации
     * 
     * @param string $content Исходный контент
     * @param array $options Дополнительные параметры
     * @return string Отформатированный контент
     */
    protected function formatContent(string $content, array $options = []): string
    {
        // Базовое форматирование, может быть переопределено в дочерних классах
        return $content;
    }
    
    /**
     * Проверка лимитов API
     * 
     * @return bool Результат проверки
     */
    protected function checkApiLimits(): bool
    {
        // Базовая проверка, должна быть реализована в дочерних классах
        return true;
    }
    
    /**
     * Логирование результата публикации
     * 
     * @param bool $success Результат публикации
     * @param string $content Опубликованный контент
     * @param array $response Ответ API
     */
    protected function logPostResult(bool $success, string $content, array $response = []): void
    {
        if ($success) {
            $this->logger->info('Content successfully posted', [
                'account_id' => $this->accountId,
                'content_length' => strlen($content),
                'response' => $response
            ]);
        } else {
            $this->logger->error('Failed to post content', [
                'account_id' => $this->accountId,
                'content_length' => strlen($content),
                'response' => $response
            ]);
        }
    }
}
