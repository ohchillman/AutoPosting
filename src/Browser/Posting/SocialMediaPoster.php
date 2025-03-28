<?php

namespace App\Browser\Posting;

use App\Browser\SeleniumAutomation;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriver;
use App\Core\LogManager;

/**
 * Базовый класс для автоматизации постинга в социальных сетях
 */
abstract class SocialMediaPoster
{
    /**
     * @var SeleniumAutomation Объект для автоматизации действий в браузере
     */
    protected $automation;
    
    /**
     * @var LogManager Логгер
     */
    protected $logger;
    
    /**
     * @var array Учетные данные аккаунта
     */
    protected $credentials;
    
    /**
     * @var bool Флаг авторизации
     */
    protected $isLoggedIn = false;
    
    /**
     * Конструктор
     * 
     * @param WebDriver $driver Экземпляр WebDriver
     * @param array $credentials Учетные данные аккаунта
     */
    public function __construct(WebDriver $driver, array $credentials)
    {
        $this->automation = new SeleniumAutomation($driver);
        $this->logger = LogManager::getInstance();
        $this->credentials = $credentials;
    }
    
    /**
     * Авторизация в социальной сети
     * 
     * @return bool Результат авторизации
     */
    abstract public function login(): bool;
    
    /**
     * Публикация контента
     * 
     * @param string $content Текст публикации
     * @param array $media Массив путей к медиа-файлам
     * @param array $options Дополнительные параметры
     * @return bool Результат публикации
     */
    abstract public function post(string $content, array $media = [], array $options = []): bool;
    
    /**
     * Проверка статуса авторизации
     * 
     * @return bool Статус авторизации
     */
    abstract public function checkLoginStatus(): bool;
    
    /**
     * Выход из аккаунта
     * 
     * @return bool Результат операции
     */
    abstract public function logout(): bool;
    
    /**
     * Получение информации об аккаунте
     * 
     * @return array Информация об аккаунте
     */
    abstract public function getAccountInfo(): array;
    
    /**
     * Форматирование контента для публикации
     * 
     * @param string $content Исходный контент
     * @param array $options Дополнительные параметры
     * @return string Отформатированный контент
     */
    protected function formatContent(string $content, array $options = []): string
    {
        // Базовое форматирование, может быть переопределено в дочерних классах
        return $content;
    }
    
    /**
     * Обработка ошибок
     * 
     * @param string $message Сообщение об ошибке
     * @param array $context Контекст ошибки
     */
    protected function handleError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
        
        // Сделать скриншот для отладки
        $screenshotPath = sys_get_temp_dir() . '/error_' . time() . '.png';
        $this->automation->takeScreenshot($screenshotPath);
        
        $this->logger->info('Error screenshot saved', ['path' => $screenshotPath]);
    }
}
