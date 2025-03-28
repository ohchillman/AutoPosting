<?php

namespace App\Parsers;

use App\Core\LogManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Универсальный парсер новостей для работы с различными источниками
 */
class GenericNewsParser implements NewsParserInterface
{
    private $logger;
    private $sourceUrl;
    private $client;
    
    /**
     * Конструктор
     * 
     * @param string $sourceUrl URL источника новостей
     */
    public function __construct(string $sourceUrl)
    {
        $this->logger = LogManager::getInstance();
        $this->sourceUrl = $sourceUrl;
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false
        ]);
    }
    
    /**
     * Получение списка новостей
     * 
     * @return array Массив новостей
     */
    public function getNews(): array
    {
        $this->logger->info('Fetching news from generic parser', ['source' => $this->sourceUrl]);
        
        try {
            // В реальном приложении здесь должен быть код для парсинга конкретного источника
            // Для тестирования возвращаем демо-новости
            return $this->generateDemoNews();
        } catch (GuzzleException $e) {
            $this->logger->error('Error fetching news from source', [
                'source' => $this->sourceUrl,
                'error' => $e->getMessage()
            ]);
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Error processing news from source', [
                'source' => $this->sourceUrl,
                'error' => $e->getMessage()
            ]);
            return [];
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
        // Проверяем, что новость не старше 7 дней
        if (empty($news['date'])) {
            return true;
        }
        
        $newsDate = strtotime($news['date']);
        $weekAgo = time() - (7 * 24 * 60 * 60);
        
        $this->logger->info('Checking news relevance', [
            'title' => $news['title'] ?? 'Unknown',
            'date' => $news['date'],
            'is_relevant' => ($newsDate >= $weekAgo)
        ]);
        
        return $newsDate >= $weekAgo;
    }
    
    /**
     * Получение полного содержимого новости
     * 
     * @param string $url URL новости
     * @return string Полное содержимое новости
     */
    public function getFullContent(string $url): string
    {
        $this->logger->info('Fetching full content from generic parser', ['url' => $url]);
        
        try {
            // В реальном приложении здесь должен быть код для парсинга содержимого новости
            // Для тестирования возвращаем демо-контент
            return $this->generateDemoContent($url);
        } catch (GuzzleException $e) {
            $this->logger->error('Error fetching content from URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return '';
        } catch (\Exception $e) {
            $this->logger->error('Error processing content from URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
    
    /**
     * Генерация демонстрационных новостей для тестирования
     * 
     * @return array Массив новостей
     */
    private function generateDemoNews(): array
    {
        $news = [];
        $sourceName = parse_url($this->sourceUrl, PHP_URL_HOST) ?: 'generic-source';
        
        // Создаем 3-7 тестовых новостей
        $count = rand(3, 7);
        for ($i = 1; $i <= $count; $i++) {
            $news[] = [
                'title' => "Новость из {$sourceName} #{$i}",
                'description' => "Это автоматически сгенерированная новость из источника {$sourceName}. Номер {$i}.",
                'link' => $this->sourceUrl . "/article/{$i}",
                'date' => date('Y-m-d H:i:s', time() - ($i * rand(1800, 7200))), // Случайный интервал между новостями
                'source' => $sourceName,
                'image' => 'https://via.placeholder.com/800x400?text=' . urlencode($sourceName . '+News+' . $i)
            ];
        }
        
        $this->logger->info('Generated generic news', ['count' => count($news), 'source' => $sourceName]);
        
        return $news;
    }
    
    /**
     * Генерация демонстрационного контента для тестирования
     * 
     * @param string $url URL новости
     * @return string Демонстрационный контент
     */
    private function generateDemoContent(string $url): string
    {
        // Извлекаем номер статьи из URL
        $articleId = 1;
        if (preg_match('/\/article\/(\d+)$/', $url, $matches)) {
            $articleId = (int)$matches[1];
        }
        
        $sourceName = parse_url($this->sourceUrl, PHP_URL_HOST) ?: 'generic-source';
        
        // Генерируем демонстрационный контент
        $paragraphs = [
            "Это первый абзац новости из источника {$sourceName}. Здесь содержится основная информация о событии.",
            "Во втором абзаце представлены детали и факты, связанные с описываемым событием или новостью.",
            "Третий абзац содержит комментарии экспертов и аналитиков по теме публикации.",
            "В четвертом абзаце рассматривается влияние события на отрасль и возможные последствия.",
            "Пятый абзац представляет историческую справку и контекст для лучшего понимания новости.",
            "Шестой абзац содержит дополнительную информацию, статистику и интересные факты по теме.",
            "В заключительном абзаце подводятся итоги и делаются прогнозы на будущее развитие ситуации."
        ];
        
        // Формируем контент с разным количеством абзацев в зависимости от ID статьи
        $paragraphCount = min(count($paragraphs), max(3, $articleId + 2));
        $selectedParagraphs = array_slice($paragraphs, 0, $paragraphCount);
        
        $content = "<h1>Новость из {$sourceName} #{$articleId}</h1>\n\n";
        $content .= "<p><strong>Дата публикации:</strong> " . date('Y-m-d H:i:s', time() - ($articleId * 3600)) . "</p>\n\n";
        
        foreach ($selectedParagraphs as $paragraph) {
            $content .= "<p>{$paragraph}</p>\n\n";
        }
        
        // Добавляем тематические ключевые слова
        $keywords = ['технологии', 'искусственный интеллект', 'инновации', 'цифровизация', 'автоматизация', 'бизнес', 'наука'];
        shuffle($keywords);
        $selectedKeywords = array_slice($keywords, 0, min(4, $articleId));
        
        $content .= "<p><strong>Ключевые слова:</strong> " . implode(', ', $selectedKeywords) . "</p>\n\n";
        
        $this->logger->info('Generated generic content', ['url' => $url, 'content_length' => strlen($content)]);
        
        return $content;
    }
}
