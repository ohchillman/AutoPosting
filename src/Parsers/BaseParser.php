<?php

namespace App\Parsers;

use App\Core\LogManager;
use App\Core\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Базовый класс для парсеров новостей
 */
abstract class BaseParser
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
    public function __construct($sourceUrl)
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
     * @return string|null HTML-контент или null в случае ошибки
     */
    protected function fetchContent($url)
    {
        try {
            $response = $this->client->get($url);
            return (string) $response->getBody();
        } catch (RequestException $e) {
            $this->logger->error('Error fetching content: ' . $e->getMessage(), [
                'url' => $url,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Проверка актуальности новости
     * 
     * @param \DateTime $publishDate Дата публикации новости
     * @param int $maxAgeDays Максимальный возраст новости в днях
     * @return bool
     */
    protected function isRelevantByDate(\DateTime $publishDate, $maxAgeDays = 1)
    {
        $now = new \DateTime();
        $diff = $now->diff($publishDate);
        return $diff->days <= $maxAgeDays;
    }

    /**
     * Проверка релевантности новости по ключевым словам
     * 
     * @param string $title Заголовок новости
     * @param string $content Содержимое новости
     * @param array $keywords Массив ключевых слов
     * @return bool
     */
    protected function isRelevantByKeywords($title, $content, array $keywords)
    {
        $text = strtolower($title . ' ' . $content);
        foreach ($keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Абстрактный метод для парсинга новостей
     * 
     * @return array Массив новостей
     */
    abstract public function parseNews();
}
