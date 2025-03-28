<?php

namespace App\Parsers;

use App\Core\LogManager;
use App\Core\Config;

/**
 * Класс для управления парсерами новостей
 */
class NewsParserManager
{
    private $logger;
    private $config;
    private $parsers = [];
    private $processedNews = [];
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->initParsers();
        $this->loadProcessedNews();
    }
    
    /**
     * Инициализация парсеров новостей
     */
    private function initParsers()
    {
        try {
            // Получаем список источников из конфигурации
            $sources = [
                [
                    'name' => 'Source1',
                    'url' => $_ENV['PARSER_SOURCE_1'] ?? 'https://example.com/news',
                    'parser' => 'Source1NewsParser'
                ],
                // Можно добавить другие источники при необходимости
            ];
            
            foreach ($sources as $source) {
                $parserClass = "\\App\\Parsers\\" . $source['parser'];
                if (class_exists($parserClass)) {
                    $this->parsers[$source['name']] = new $parserClass($source['url']);
                    $this->logger->info('Parser initialized', ['source' => $source['name']]);
                } else {
                    $this->logger->warning('Parser class not found', ['class' => $parserClass]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error initializing parsers', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Загрузка списка обработанных новостей
     */
    private function loadProcessedNews()
    {
        $processedNewsFile = __DIR__ . '/../../data/processed_news.json';
        
        // Создаем директорию, если она не существует
        if (!file_exists(dirname($processedNewsFile))) {
            mkdir(dirname($processedNewsFile), 0777, true);
        }
        
        if (file_exists($processedNewsFile)) {
            $data = file_get_contents($processedNewsFile);
            $this->processedNews = json_decode($data, true) ?: [];
            $this->logger->info('Loaded processed news', ['count' => count($this->processedNews)]);
        } else {
            $this->processedNews = [];
            $this->logger->info('No processed news file found, starting fresh');
        }
    }
    
    /**
     * Сохранение списка обработанных новостей
     */
    private function saveProcessedNews()
    {
        $processedNewsFile = __DIR__ . '/../../data/processed_news.json';
        
        // Создаем директорию, если она не существует
        if (!file_exists(dirname($processedNewsFile))) {
            mkdir(dirname($processedNewsFile), 0777, true);
        }
        
        file_put_contents($processedNewsFile, json_encode($this->processedNews));
        $this->logger->info('Saved processed news', ['count' => count($this->processedNews)]);
    }
    
    /**
     * Получение новостей из всех источников
     * 
     * @return array Массив новостей
     */
    public function getAllNews(): array
    {
        $allNews = [];
        
        foreach ($this->parsers as $sourceName => $parser) {
            try {
                $news = $parser->getNews();
                $this->logger->info('Fetched news', ['source' => $sourceName, 'count' => count($news)]);
                $allNews = array_merge($allNews, $news);
            } catch (\Exception $e) {
                $this->logger->error('Error fetching news', [
                    'source' => $sourceName,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $allNews;
    }
    
    /**
     * Фильтрация новостей по ключевым словам
     * 
     * @param array $news Массив новостей
     * @param array $keywords Ключевые слова для фильтрации
     * @return array Отфильтрованный массив новостей
     */
    public function filterNewsByKeywords(array $news, array $keywords): array
    {
        if (empty($keywords)) {
            return $news;
        }
        
        $filtered = [];
        
        foreach ($news as $item) {
            $title = strtolower($item['title'] ?? '');
            $description = strtolower($item['description'] ?? '');
            $content = $title . ' ' . $description;
            
            foreach ($keywords as $keyword) {
                $keyword = strtolower(trim($keyword));
                if (!empty($keyword) && strpos($content, $keyword) !== false) {
                    $filtered[] = $item;
                    break;
                }
            }
        }
        
        $this->logger->info('Filtered news by keywords', [
            'original_count' => count($news),
            'filtered_count' => count($filtered),
            'keywords' => $keywords
        ]);
        
        return $filtered;
    }
    
    /**
     * Проверка, была ли новость уже обработана
     * 
     * @param array $news Данные новости
     * @return bool
     */
    public function isNewsProcessed(array $news): bool
    {
        $newsId = $this->generateNewsId($news);
        return isset($this->processedNews[$newsId]);
    }
    
    /**
     * Отметка новости как обработанной
     * 
     * @param array $news Данные новости
     */
    public function markNewsAsProcessed(array $news): void
    {
        $newsId = $this->generateNewsId($news);
        $this->processedNews[$newsId] = [
            'title' => $news['title'] ?? '',
            'source' => $news['source'] ?? '',
            'date' => $news['date'] ?? '',
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
        $this->saveProcessedNews();
    }
    
    /**
     * Генерация уникального идентификатора новости
     * 
     * @param array $news Данные новости
     * @return string
     */
    private function generateNewsId(array $news): string
    {
        $title = $news['title'] ?? '';
        $source = $news['source'] ?? '';
        $date = $news['date'] ?? '';
        
        return md5($title . $source . $date);
    }
    
    /**
     * Получение полного содержимого новости
     * 
     * @param array $news Данные новости
     * @return string
     */
    public function getNewsContent(array $news): string
    {
        if (empty($news['link']) || empty($news['source'])) {
            $this->logger->warning('Invalid news data for content retrieval', ['news' => $news]);
            return '';
        }
        
        $sourceName = $news['source'];
        
        if (!isset($this->parsers[$sourceName])) {
            $this->logger->warning('Parser not found for source', ['source' => $sourceName]);
            return '';
        }
        
        try {
            $content = $this->parsers[$sourceName]->getFullContent($news['link']);
            $this->logger->info('Retrieved news content', [
                'source' => $sourceName,
                'title' => $news['title'] ?? '',
                'content_length' => strlen($content)
            ]);
            
            return $content;
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving news content', [
                'source' => $sourceName,
                'title' => $news['title'] ?? '',
                'error' => $e->getMessage()
            ]);
            
            return '';
        }
    }
}
