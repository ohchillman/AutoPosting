<?php

namespace App\Parsers;

use App\Core\LogManager;
use App\Core\Config;
use GuzzleHttp\Client;

/**
 * Абстрактный класс парсера новостей
 */
abstract class AbstractNewsParser implements NewsParserInterface
{
    protected $logger;
    protected $config;
    protected $client;
    protected $sourceUrl;
    
    /**
     * Конструктор
     * 
     * @param string $sourceUrl URL источника новостей
     */
    public function __construct(string $sourceUrl)
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ]
        ]);
        $this->sourceUrl = $sourceUrl;
    }
    
    /**
     * Получение HTML-контента страницы
     * 
     * @param string $url URL страницы
     * @return string HTML-контент
     */
    protected function getHtml(string $url): string
    {
        try {
            $response = $this->client->get($url);
            return (string) $response->getBody();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching URL: ' . $url, ['error' => $e->getMessage()]);
            return '';
        }
    }
    
    /**
     * Проверка новости на актуальность
     * 
     * @param array $news Данные новости
     * @return bool
     */
    public function isRelevant(array $news): bool
    {
        // Базовая проверка на актуальность - новость не старше 24 часов
        if (!isset($news['date'])) {
            return false;
        }
        
        $newsDate = strtotime($news['date']);
        $currentDate = time();
        $dayInSeconds = 24 * 60 * 60;
        
        return ($currentDate - $newsDate) <= $dayInSeconds;
    }
}
