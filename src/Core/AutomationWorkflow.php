<?php

namespace App\Core;

use App\Parsers\NewsParserManager;
use App\Rewrite\ContentRewriteManager;
use App\Posting\SocialMediaPostingManager;

/**
 * Класс для интеграции всех компонентов системы автоматизации
 */
class AutomationWorkflow
{
    private $logger;
    private $config;
    private $parserManager;
    private $rewriteManager;
    private $postingManager;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->parserManager = new NewsParserManager();
        $this->rewriteManager = new ContentRewriteManager();
        $this->postingManager = new SocialMediaPostingManager();
    }
    
    /**
     * Запуск полного рабочего процесса автоматизации
     * 
     * @param array $keywords Ключевые слова для фильтрации новостей
     * @param array $options Дополнительные параметры
     * @return array Результаты выполнения
     */
    public function run(array $keywords = [], array $options = []): array
    {
        $this->logger->info('Starting automation workflow', [
            'keywords' => $keywords,
            'options' => $options
        ]);
        
        $results = [
            'parsed_news' => 0,
            'rewritten_content' => 0,
            'posted_content' => 0,
            'success_rate' => 0,
            'details' => []
        ];
        
        try {
            // Шаг 1: Получение новостей
            $news = $this->parserManager->getAllNews();
            $this->logger->info('Fetched news', ['count' => count($news)]);
            
            // Фильтрация новостей по ключевым словам
            if (!empty($keywords)) {
                $news = $this->parserManager->filterNewsByKeywords($news, $keywords);
                $this->logger->info('Filtered news by keywords', [
                    'keywords' => $keywords,
                    'filtered_count' => count($news)
                ]);
            }
            
            // Фильтрация необработанных новостей
            $unprocessedNews = [];
            foreach ($news as $item) {
                if (!$this->parserManager->isNewsProcessed($item)) {
                    $unprocessedNews[] = $item;
                }
            }
            
            $this->logger->info('Unprocessed news', ['count' => count($unprocessedNews)]);
            $results['parsed_news'] = count($unprocessedNews);
            
            // Обработка каждой новости
            foreach ($unprocessedNews as $newsItem) {
                $newsDetail = [
                    'title' => $newsItem['title'],
                    'source' => $newsItem['source'],
                    'date' => $newsItem['date'],
                    'rewrite_results' => [],
                    'posting_results' => []
                ];
                
                // Получение полного содержимого новости
                $fullContent = $this->parserManager->getNewsContent($newsItem);
                
                if (empty($fullContent)) {
                    $this->logger->warning('Empty content for news item', [
                        'title' => $newsItem['title'],
                        'source' => $newsItem['source']
                    ]);
                    continue;
                }
                
                // Шаг 2: Рерайт контента для всех аккаунтов
                $rewrittenContent = $this->rewriteManager->rewriteForAllAccounts($fullContent, $options);
                $this->logger->info('Rewritten content', ['accounts_count' => count($rewrittenContent)]);
                
                $newsDetail['rewrite_results'] = array_keys($rewrittenContent);
                $results['rewritten_content'] += count($rewrittenContent);
                
                // Шаг 3: Публикация контента во все аккаунты
                $postingResults = $this->postingManager->postToAllAccounts($rewrittenContent);
                $this->logger->info('Posted content', ['results' => $postingResults]);
                
                $newsDetail['posting_results'] = $postingResults;
                $successfulPosts = count(array_filter($postingResults));
                $results['posted_content'] += $successfulPosts;
                
                // Отметка новости как обработанной
                $this->parserManager->markNewsAsProcessed($newsItem);
                
                $results['details'][] = $newsDetail;
            }
            
            // Расчет процента успешных публикаций
            if ($results['rewritten_content'] > 0) {
                $results['success_rate'] = ($results['posted_content'] / $results['rewritten_content']) * 100;
            }
            
            $this->logger->info('Automation workflow completed', [
                'parsed_news' => $results['parsed_news'],
                'rewritten_content' => $results['rewritten_content'],
                'posted_content' => $results['posted_content'],
                'success_rate' => $results['success_rate']
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error in automation workflow', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $results;
    }
    
    /**
     * Проверка статуса всех компонентов системы
     * 
     * @return array Статус компонентов
     */
    public function checkSystemStatus(): array
    {
        $status = [
            'parsers' => false,
            'rewrite' => false,
            'posting' => false,
            'accounts' => []
        ];
        
        // Проверка парсеров
        try {
            $news = $this->parserManager->getAllNews();
            $status['parsers'] = count($news) > 0;
        } catch (\Exception $e) {
            $this->logger->error('Error checking parsers status', [
                'error' => $e->getMessage()
            ]);
        }
        
        // Проверка системы рерайта
        try {
            $templates = $this->rewriteManager->getAvailableToneOfVoiceTemplates();
            $status['rewrite'] = count($templates) > 0;
        } catch (\Exception $e) {
            $this->logger->error('Error checking rewrite system status', [
                'error' => $e->getMessage()
            ]);
        }
        
        // Проверка системы постинга и статуса аккаунтов
        try {
            $accountsStatus = $this->postingManager->checkAllAccountsStatus();
            $status['posting'] = count($accountsStatus) > 0;
            $status['accounts'] = $accountsStatus;
        } catch (\Exception $e) {
            $this->logger->error('Error checking posting system status', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $status;
    }
}
