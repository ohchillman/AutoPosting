<?php

namespace App\Posting;

/**
 * Интерфейс для системы автопостинга в социальные сети
 */
interface SocialMediaPosterInterface
{
    /**
     * Публикация контента в социальной сети
     * 
     * @param string $content Контент для публикации
     * @param array $media Медиа-файлы для публикации (опционально)
     * @param array $options Дополнительные параметры
     * @return bool Результат публикации
     */
    public function post(string $content, array $media = [], array $options = []): bool;
    
    /**
     * Проверка статуса аккаунта
     * 
     * @return bool Результат проверки
     */
    public function checkAccountStatus(): bool;
    
    /**
     * Получение информации об аккаунте
     * 
     * @return array Информация об аккаунте
     */
    public function getAccountInfo(): array;
}
