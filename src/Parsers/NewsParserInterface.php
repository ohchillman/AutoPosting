<?php

namespace App\Parsers;

/**
 * Интерфейс для парсеров новостей
 */
interface NewsParserInterface
{
    /**
     * Получение списка новостей с источника
     * 
     * @return array Массив новостей
     */
    public function getNews(): array;
    
    /**
     * Проверка новости на актуальность
     * 
     * @param array $news Данные новости
     * @return bool
     */
    public function isRelevant(array $news): bool;
    
    /**
     * Получение полного текста новости
     * 
     * @param string $url URL новости
     * @return string
     */
    public function getFullContent(string $url): string;
}
