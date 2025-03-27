<?php

namespace App\Parsers;

use DOMDocument;
use DOMXPath;

/**
 * Конкретная реализация парсера для первого источника новостей
 */
class Source1NewsParser extends AbstractNewsParser
{
    /**
     * Получение списка новостей с источника
     * 
     * @return array Массив новостей
     */
    public function getNews(): array
    {
        $html = $this->getHtml($this->sourceUrl);
        if (empty($html)) {
            return [];
        }
        
        $news = [];
        
        // Создаем DOM-документ
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Находим все блоки новостей (пример селектора, нужно адаптировать под конкретный сайт)
        $newsItems = $xpath->query('//div[contains(@class, "news-item")]');
        
        foreach ($newsItems as $item) {
            // Извлекаем заголовок
            $titleNode = $xpath->query('.//h2[contains(@class, "news-title")]', $item)->item(0);
            $title = $titleNode ? trim($titleNode->textContent) : '';
            
            // Извлекаем ссылку
            $linkNode = $xpath->query('.//a[contains(@class, "news-link")]', $item)->item(0);
            $link = $linkNode ? $linkNode->getAttribute('href') : '';
            
            // Извлекаем дату
            $dateNode = $xpath->query('.//span[contains(@class, "news-date")]', $item)->item(0);
            $date = $dateNode ? trim($dateNode->textContent) : '';
            
            // Извлекаем краткое описание
            $descNode = $xpath->query('.//div[contains(@class, "news-description")]', $item)->item(0);
            $description = $descNode ? trim($descNode->textContent) : '';
            
            // Формируем элемент новости
            $newsItem = [
                'title' => $title,
                'link' => $link,
                'date' => $date,
                'description' => $description,
                'source' => 'Source1',
            ];
            
            // Проверяем на актуальность
            if ($this->isRelevant($newsItem)) {
                $news[] = $newsItem;
            }
        }
        
        return $news;
    }
    
    /**
     * Получение полного текста новости
     * 
     * @param string $url URL новости
     * @return string
     */
    public function getFullContent(string $url): string
    {
        $html = $this->getHtml($url);
        if (empty($html)) {
            return '';
        }
        
        // Создаем DOM-документ
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Находим контент новости (пример селектора, нужно адаптировать под конкретный сайт)
        $contentNode = $xpath->query('//div[contains(@class, "news-content")]')->item(0);
        
        if ($contentNode) {
            return trim($contentNode->textContent);
        }
        
        return '';
    }
}
