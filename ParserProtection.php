<?php

namespace App\Parsers;

use DOMDocument;
use DOMXPath;
use Exception;
use App\Core\LogManager;

/**
 * Класс для защиты от блокировок при парсинге
 */
class ParserProtection
{
    private $logger;
    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36'
    ];
    
    private $delays = [1, 2, 3, 5, 8]; // Задержки в секундах (последовательность Фибоначчи)
    private $maxRetries = 3;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
    }
    
    /**
     * Получение случайного User-Agent
     * 
     * @return string
     */
    public function getRandomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }
    
    /**
     * Выполнение запроса с защитой от блокировок
     * 
     * @param callable $requestFunction Функция выполнения запроса
     * @return mixed Результат запроса
     * @throws Exception
     */
    public function executeWithProtection(callable $requestFunction)
    {
        $retries = 0;
        $lastException = null;
        
        while ($retries < $this->maxRetries) {
            try {
                // Добавляем случайную задержку перед запросом
                $delay = $this->delays[array_rand($this->delays)];
                sleep($delay);
                
                // Выполняем запрос
                return $requestFunction();
            } catch (Exception $e) {
                $lastException = $e;
                $retries++;
                
                $this->logger->warning('Request failed, retrying', [
                    'retry' => $retries,
                    'max_retries' => $this->maxRetries,
                    'error' => $e->getMessage()
                ]);
                
                // Увеличиваем задержку с каждой попыткой
                sleep($retries * 2);
            }
        }
        
        // Если все попытки неудачны, выбрасываем последнее исключение
        throw $lastException ?? new Exception('Failed to execute request after ' . $this->maxRetries . ' retries');
    }
    
    /**
     * Проверка на наличие CAPTCHA или других защит
     * 
     * @param string $html HTML-контент
     * @return bool
     */
    public function hasCaptcha(string $html): bool
    {
        $captchaPatterns = [
            'captcha',
            'recaptcha',
            'robot check',
            'are you a robot',
            'security check',
            'verify you are human'
        ];
        
        foreach ($captchaPatterns as $pattern) {
            if (stripos($html, $pattern) !== false) {
                $this->logger->warning('Captcha detected', ['pattern' => $pattern]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Обход простых защит от парсинга
     * 
     * @param string $html HTML-контент
     * @return string Обработанный HTML
     */
    public function bypassProtection(string $html): string
    {
        if (empty($html)) {
            return '';
        }
        
        // Если обнаружена CAPTCHA, логируем это и возвращаем пустую строку
        if ($this->hasCaptcha($html)) {
            $this->logger->error('Cannot bypass CAPTCHA protection');
            return '';
        }
        
        // Создаем DOM-документ для обработки HTML
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Удаляем скрипты, которые могут блокировать парсинг
        $scripts = $xpath->query('//script');
        foreach ($scripts as $script) {
            $script->parentNode->removeChild($script);
        }
        
        // Удаляем мета-теги, которые могут блокировать парсинг
        $metaTags = $xpath->query('//meta[@name="robots"]');
        foreach ($metaTags as $tag) {
            $tag->parentNode->removeChild($tag);
        }
        
        return $dom->saveHTML();
    }
}
