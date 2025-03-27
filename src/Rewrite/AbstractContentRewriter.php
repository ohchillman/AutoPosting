<?php

namespace App\Rewrite;

use App\Core\LogManager;
use App\Core\Config;
use GuzzleHttp\Client;

/**
 * Абстрактный класс для системы рерайта контента
 */
abstract class AbstractContentRewriter implements ContentRewriterInterface
{
    protected $logger;
    protected $config;
    protected $client;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->client = new Client([
            'timeout' => 60,
            'verify' => false
        ]);
    }
    
    /**
     * Проверка качества переписанного контента
     * 
     * @param string $content Переписанный контент
     * @return bool Результат проверки
     */
    public function checkQuality(string $content): bool
    {
        // Базовая проверка качества
        
        // Проверка на минимальную длину
        if (strlen($content) < 100) {
            $this->logger->warning('Content is too short', ['length' => strlen($content)]);
            return false;
        }
        
        // Проверка на наличие HTML-тегов
        if (strip_tags($content) !== $content) {
            $this->logger->warning('Content contains HTML tags');
            return false;
        }
        
        // Проверка на наличие ключевых фраз, указывающих на проблемы с генерацией
        $errorPhrases = [
            'I cannot',
            'I\'m unable to',
            'As an AI',
            'As a language model',
            'I apologize',
            'I\'m sorry'
        ];
        
        foreach ($errorPhrases as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $this->logger->warning('Content contains error phrases', ['phrase' => $phrase]);
                return false;
            }
        }
        
        return true;
    }
}
