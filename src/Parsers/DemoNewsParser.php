<?php

namespace App\Parsers;

use App\Core\LogManager;

/**
 * Демонстрационный парсер новостей для тестирования
 */
class DemoNewsParser implements NewsParserInterface
{
    private $logger;
    private $sourceUrl;
    
    /**
     * Конструктор
     * 
     * @param string $sourceUrl URL источника новостей
     */
    public function __construct(string $sourceUrl)
    {
        $this->logger = LogManager::getInstance();
        $this->sourceUrl = $sourceUrl;
    }
    
    /**
     * Получение списка новостей
     * 
     * @return array Массив новостей
     */
    public function getNews(): array
    {
        $this->logger->info('Fetching demo news', ['source' => $this->sourceUrl]);
        
        // Генерируем демонстрационные новости для тестирования
        $news = [];
        
        // Создаем 5 тестовых новостей
        for ($i = 1; $i <= 5; $i++) {
            $news[] = [
                'title' => "Демонстрационная новость #{$i}",
                'description' => "Это тестовая новость для демонстрации работы системы автоматизации. Номер {$i}.",
                'link' => $this->sourceUrl . "/news/{$i}",
                'date' => date('Y-m-d H:i:s', time() - ($i * 3600)), // Каждая новость на час старше предыдущей
                'source' => 'DemoSource',
                'image' => 'https://via.placeholder.com/800x400?text=Demo+News+' . $i
            ];
        }
        
        $this->logger->info('Generated demo news', ['count' => count($news)]);
        
        return $news;
    }
    
    /**
     * Проверка новости на актуальность
     * 
     * @param array $news Данные новости
     * @return bool
     */
    public function isRelevant(array $news): bool
    {
        // Для демонстрационного парсера считаем все новости актуальными
        $this->logger->info('Checking demo news relevance', [
            'title' => $news['title'] ?? 'Unknown',
            'is_relevant' => true
        ]);
        
        return true;
    }
    
    /**
     * Получение полного содержимого новости
     * 
     * @param string $url URL новости
     * @return string Полное содержимое новости
     */
    public function getFullContent(string $url): string
    {
        $this->logger->info('Fetching demo news content', ['url' => $url]);
        
        // Извлекаем номер новости из URL
        $newsId = 1;
        if (preg_match('/\/news\/(\d+)$/', $url, $matches)) {
            $newsId = (int)$matches[1];
        }
        
        // Генерируем демонстрационный контент
        $paragraphs = [
            "Это первый абзац демонстрационной новости #{$newsId}. Здесь содержится вводная информация о событии.",
            "Во втором абзаце мы рассказываем о деталях события. Это важная информация для понимания контекста.",
            "Третий абзац содержит дополнительные сведения и комментарии экспертов по теме новости.",
            "В четвертом абзаце мы подводим итоги и делаем выводы о значимости описанного события.",
            "Пятый абзац содержит информацию о возможном развитии событий в будущем и прогнозы аналитиков."
        ];
        
        // Добавляем случайное количество абзацев в зависимости от номера новости
        $content = "<h1>Демонстрационная новость #{$newsId}</h1>\n\n";
        $content .= "<p><strong>Дата публикации:</strong> " . date('Y-m-d H:i:s', time() - ($newsId * 3600)) . "</p>\n\n";
        
        // Добавляем все абзацы для полного контента
        foreach ($paragraphs as $paragraph) {
            $content .= "<p>{$paragraph}</p>\n\n";
        }
        
        // Добавляем тематические ключевые слова для возможности фильтрации
        $keywords = ['технологии', 'искусственный интеллект', 'инновации', 'цифровизация', 'автоматизация'];
        $selectedKeywords = array_slice($keywords, 0, min(3, $newsId));
        
        $content .= "<p><strong>Ключевые слова:</strong> " . implode(', ', $selectedKeywords) . "</p>\n\n";
        
        $this->logger->info('Generated demo news content', ['url' => $url, 'content_length' => strlen($content)]);
        
        return $content;
    }
}
