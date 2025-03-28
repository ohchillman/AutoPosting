<?php

namespace App\Browser;

use Selenium\WebDriver;

/**
 * Абстрактный класс для адаптеров антидетект браузеров
 */
abstract class BrowserAdapter
{
    /**
     * @var array Конфигурация адаптера
     */
    protected $config;
    
    /**
     * @var \App\Core\LogManager Логгер
     */
    protected $logger;
    
    /**
     * Конструктор
     * 
     * @param array $config Конфигурация адаптера
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->logger = \App\Core\LogManager::getInstance();
    }
    
    /**
     * Запуск профиля браузера
     * 
     * @param string $profileId Идентификатор профиля
     * @return array Данные для подключения к браузеру
     */
    abstract public function startProfile(string $profileId): array;
    
    /**
     * Остановка профиля браузера
     * 
     * @param string $profileId Идентификатор профиля
     * @return bool Результат операции
     */
    abstract public function stopProfile(string $profileId): bool;
    
    /**
     * Получение Selenium WebDriver для профиля
     * 
     * @param string $profileId Идентификатор профиля
     * @param array $connectionData Данные для подключения к браузеру
     * @return WebDriver Экземпляр WebDriver
     */
    abstract public function getSeleniumDriver(string $profileId, array $connectionData): WebDriver;
    
    /**
     * Проверка статуса профиля
     * 
     * @param string $profileId Идентификатор профиля
     * @return bool Статус профиля (true - активен, false - неактивен)
     */
    abstract public function checkProfileStatus(string $profileId): bool;
    
    /**
     * Получение списка профилей
     * 
     * @return array Список профилей
     */
    abstract public function getProfiles(): array;
    
    /**
     * Логирование ошибки
     * 
     * @param string $message Сообщение об ошибке
     * @param array $context Контекст ошибки
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
    
    /**
     * Логирование информации
     * 
     * @param string $message Информационное сообщение
     * @param array $context Контекст сообщения
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }
}
