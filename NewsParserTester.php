<?php

namespace App\Parsers;

use App\Core\LogManager;
use App\Core\Config;
use GuzzleHttp\Client;
use Exception;

/**
 * Класс для тестирования парсеров новостей
 */
class NewsParserTester
{
    private $logger;
    private $config;
    private $parserManager;
    private $processor;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->parserManager = new NewsParserManager();
        $this->processor = new NewsProcessor();
    }
    
    /**
     * Запуск тестирования парсеров
     * 
     * @return array Результаты тестирования
     */
    public function runTests(): array
    {
        $results = [
            'success' => true,
            'tests' => [],
            'errors' => []
        ];
        
        try {
            // Тест получения новостей
            $newsResult = $this->testGetNews();
            $results['tests']['getNews'] = $newsResult;
            
            // Тест фильтрации новостей
            $filterResult = $this->testFilterNews();
            $results['tests']['filterNews'] = $filterResult;
            
            // Тест получения полного содержимого
            $contentResult = $this->testGetContent();
            $results['tests']['getContent'] = $contentResult;
            
            // Тест защиты от блокировок
            $protectionResult = $this->testProtection();
            $results['tests']['protection'] = $protectionResult;
            
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            $this->logger->error('Parser testing failed', ['error' => $e->getMessage()]);
        }
        
        return $results;
    }
    
    /**
     * Тест получения новостей
     * 
     * @return array Результаты теста
     */
    private function testGetNews(): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => []
        ];
        
        try {
            $news = $this->parserManager->getAllNews();
            
            if (empty($news)) {
                $result['message'] = 'No news found';
            } else {
                $result['success'] = true;
                $result['message'] = 'Successfully fetched ' . count($news) . ' news items';
                $result['data'] = array_slice($news, 0, 5); // Первые 5 новостей для примера
            }
        } catch (Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Тест фильтрации новостей
     * 
     * @return array Результаты теста
     */
    private function testFilterNews(): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => []
        ];
        
        try {
            $news = $this->parserManager->getAllNews();
            
            if (empty($news)) {
                $result['message'] = 'No news to filter';
                return $result;
            }
            
            $filtered = $this->processor->filterByRelevance($news);
            $unique = $this->processor->removeDuplicates($filtered);
            $sorted = $this->processor->sortByDate($unique);
            
            $result['success'] = true;
            $result['message'] = sprintf(
                'Filtering results: %d total, %d relevant, %d unique',
                count($news),
                count($filtered),
                count($unique)
            );
            $result['data'] = array_slice($sorted, 0, 5); // Первые 5 новостей для примера
            
        } catch (Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Тест получения полного содержимого новости
     * 
     * @return array Результаты теста
     */
    private function testGetContent(): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => []
        ];
        
        try {
            $news = $this->parserManager->getAllNews();
            
            if (empty($news)) {
                $result['message'] = 'No news to get content';
                return $result;
            }
            
            // Берем первую новость для теста
            $firstNews = $news[0];
            $content = $this->parserManager->getNewsContent($firstNews);
            
            if (empty($content)) {
                $result['message'] = 'Failed to get content for news: ' . $firstNews['title'];
            } else {
                $result['success'] = true;
                $result['message'] = 'Successfully fetched content for: ' . $firstNews['title'];
                $result['data'] = [
                    'title' => $firstNews['title'],
                    'content_length' => strlen($content),
                    'content_preview' => substr($content, 0, 200) . '...'
                ];
            }
            
        } catch (Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Тест защиты от блокировок
     * 
     * @return array Результаты теста
     */
    private function testProtection(): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => []
        ];
        
        try {
            $protection = new ParserProtection();
            
            // Тестируем получение случайного User-Agent
            $userAgent = $protection->getRandomUserAgent();
            
            // Тестируем выполнение запроса с защитой
            $testUrl = 'https://example.com';
            $client = new Client([
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => $userAgent
                ]
            ]);
            
            $response = $protection->executeWithProtection(function() use ($client, $testUrl) {
                return $client->get($testUrl);
            });
            
            $result['success'] = true;
            $result['message'] = 'Protection mechanisms working correctly';
            $result['data'] = [
                'user_agent' => $userAgent,
                'status_code' => $response->getStatusCode()
            ];
            
        } catch (Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $result;
    }
}
