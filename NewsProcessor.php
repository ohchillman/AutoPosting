<?php

namespace App\Parsers;

use DOMDocument;
use DOMXPath;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Класс для обработки и фильтрации новостей
 */
class NewsProcessor
{
    private $logger;
    private $config;
    private $relevanceKeywords = [];
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = \App\Core\LogManager::getInstance();
        $this->config = \App\Core\Config::getInstance();
        $this->initializeKeywords();
    }
    
    /**
     * Инициализация ключевых слов для определения релевантности
     */
    private function initializeKeywords()
    {
        // В реальном приложении эти ключевые слова могут быть загружены из конфигурации
        $this->relevanceKeywords = [
            'технологии',
            'инновации',
            'разработка',
            'программирование',
            'искусственный интеллект',
            'машинное обучение',
            'блокчейн',
            'криптовалюта',
            'стартап',
            'финтех'
        ];
    }
    
    /**
     * Фильтрация новостей по релевантности
     * 
     * @param array $news Массив новостей
     * @return array Отфильтрованный массив новостей
     */
    public function filterByRelevance(array $news): array
    {
        $filteredNews = [];
        
        foreach ($news as $item) {
            if ($this->isRelevantByKeywords($item)) {
                $filteredNews[] = $item;
            }
        }
        
        $this->logger->info('News filtered by relevance', [
            'total' => count($news),
            'filtered' => count($filteredNews)
        ]);
        
        return $filteredNews;
    }
    
    /**
     * Проверка новости на релевантность по ключевым словам
     * 
     * @param array $news Данные новости
     * @return bool
     */
    private function isRelevantByKeywords(array $news): bool
    {
        $title = $news['title'] ?? '';
        $description = $news['description'] ?? '';
        $content = $title . ' ' . $description;
        
        foreach ($this->relevanceKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Удаление дубликатов новостей
     * 
     * @param array $news Массив новостей
     * @return array Массив без дубликатов
     */
    public function removeDuplicates(array $news): array
    {
        $uniqueNews = [];
        $titles = [];
        
        foreach ($news as $item) {
            $title = $item['title'] ?? '';
            
            // Проверяем, не встречался ли уже такой заголовок
            if (!in_array($title, $titles) && !empty($title)) {
                $titles[] = $title;
                $uniqueNews[] = $item;
            }
        }
        
        $this->logger->info('Duplicates removed', [
            'total' => count($news),
            'unique' => count($uniqueNews)
        ]);
        
        return $uniqueNews;
    }
    
    /**
     * Очистка HTML-тегов из текста
     * 
     * @param string $content Текст с HTML-тегами
     * @return string Очищенный текст
     */
    public function cleanHtml(string $content): string
    {
        // Удаляем HTML-теги
        $cleanText = strip_tags($content);
        
        // Заменяем множественные пробелы и переносы строк на одиночные
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);
        
        // Удаляем пробелы в начале и конце строки
        $cleanText = trim($cleanText);
        
        return $cleanText;
    }
    
    /**
     * Извлечение изображений из контента
     * 
     * @param string $html HTML-контент
     * @return array Массив URL изображений
     */
    public function extractImages(string $html): array
    {
        $images = [];
        
        if (empty($html)) {
            return $images;
        }
        
        // Создаем DOM-документ
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Находим все изображения
        $imgNodes = $xpath->query('//img');
        
        foreach ($imgNodes as $img) {
            $src = $img->getAttribute('src');
            
            // Проверяем, является ли URL относительным
            if (!empty($src) && strpos($src, 'http') !== 0) {
                // Пропускаем маленькие иконки и рекламные баннеры
                if (strpos($src, 'icon') !== false || strpos($src, 'banner') !== false) {
                    continue;
                }
                
                // Преобразуем относительный URL в абсолютный
                // Для этого нужно знать базовый URL страницы
                // В реальном приложении здесь будет более сложная логика
                $src = 'https://example.com' . $src;
            }
            
            if (!empty($src)) {
                $images[] = $src;
            }
        }
        
        return $images;
    }
    
    /**
     * Сортировка новостей по дате (от новых к старым)
     * 
     * @param array $news Массив новостей
     * @return array Отсортированный массив
     */
    public function sortByDate(array $news): array
    {
        usort($news, function($a, $b) {
            $dateA = strtotime($a['date'] ?? '');
            $dateB = strtotime($b['date'] ?? '');
            
            if ($dateA === false || $dateB === false) {
                return 0;
            }
            
            return $dateB - $dateA; // От новых к старым
        });
        
        return $news;
    }
}
