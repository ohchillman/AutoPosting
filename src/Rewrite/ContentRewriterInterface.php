<?php

namespace App\Rewrite;

/**
 * Интерфейс для системы рерайта контента
 */
interface ContentRewriterInterface
{
    /**
     * Рерайт контента с учетом tone of voice
     * 
     * @param string $content Исходный контент
     * @param string $toneOfVoice Тон голоса (стиль)
     * @param array $options Дополнительные параметры
     * @return string Переписанный контент
     */
    public function rewrite(string $content, string $toneOfVoice, array $options = []): string;
    
    /**
     * Проверка качества переписанного контента
     * 
     * @param string $content Переписанный контент
     * @return bool Результат проверки
     */
    public function checkQuality(string $content): bool;
}
