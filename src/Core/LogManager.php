<?php

namespace App\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Класс для логирования событий системы
 */
class LogManager
{
    private static $instance = null;
    private $logger;

    /**
     * Приватный конструктор для реализации паттерна Singleton
     */
    private function __construct()
    {
        $this->logger = new Logger('social_media_automation');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', Logger::DEBUG));
    }

    /**
     * Получение экземпляра логгера
     * 
     * @return LogManager
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Логирование информационного сообщения
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Контекст сообщения
     */
    public function info($message, array $context = [])
    {
        $this->logger->info($message, $context);
    }

    /**
     * Логирование предупреждения
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Контекст сообщения
     */
    public function warning($message, array $context = [])
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Логирование ошибки
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Контекст сообщения
     */
    public function error($message, array $context = [])
    {
        $this->logger->error($message, $context);
    }

    /**
     * Логирование отладочной информации
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Контекст сообщения
     */
    public function debug($message, array $context = [])
    {
        $this->logger->debug($message, $context);
    }
}
