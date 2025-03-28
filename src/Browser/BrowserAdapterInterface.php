<?php

namespace App\Browser;

/**
 * Интерфейс для адаптеров антидетект браузеров
 */
interface BrowserAdapterInterface
{
    /**
     * Запускает профиль браузера
     * 
     * @param string $profileId ID профиля в браузере
     * @return array Информация о запущенном профиле (порт, URL и т.д.)
     */
    public function launchProfile(string $profileId): array;
    
    /**
     * Закрывает профиль браузера
     * 
     * @param string $profileId ID профиля в браузере
     * @return bool Результат операции
     */
    public function closeProfile(string $profileId): bool;
    
    /**
     * Получает список профилей
     * 
     * @param array $filters Фильтры для списка профилей
     * @return array Список профилей
     */
    public function getProfiles(array $filters = []): array;
    
    /**
     * Создает новый профиль
     * 
     * @param array $profileData Данные профиля
     * @return string ID созданного профиля
     */
    public function createProfile(array $profileData): string;
    
    /**
     * Обновляет профиль
     * 
     * @param string $profileId ID профиля
     * @param array $profileData Новые данные профиля
     * @return bool Результат операции
     */
    public function updateProfile(string $profileId, array $profileData): bool;
    
    /**
     * Удаляет профиль
     * 
     * @param string $profileId ID профиля
     * @return bool Результат операции
     */
    public function deleteProfile(string $profileId): bool;
    
    /**
     * Проверяет соединение с API браузера
     * 
     * @return bool Результат проверки
     */
    public function testConnection(): bool;
}
