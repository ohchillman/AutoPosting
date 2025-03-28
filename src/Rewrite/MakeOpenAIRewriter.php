<?php

namespace App\Rewrite;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Класс для рерайта контента через Make.com и OpenAI API
 */
class MakeOpenAIRewriter extends AbstractContentRewriter
{
    /**
     * URL вебхука Make.com
     * @var string
     */
    private $webhookUrl;
    
    /**
     * API ключ для Make.com
     * @var string
     */
    private $apiKey;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->webhookUrl = $this->config->get('make.webhook_url');
        $this->apiKey = $this->config->get('make.api_key');
        
        if (empty($this->webhookUrl)) {
            $this->logger->error('Make.com webhook URL is not configured');
        }
        
        if (empty($this->apiKey)) {
            $this->logger->error('Make.com API key is not configured');
        }
    }
    
    /**
     * Рерайт контента с учетом tone of voice через Make.com и OpenAI
     * 
     * @param string $content Исходный контент
     * @param string $toneOfVoice Тон голоса (стиль)
     * @param array $options Дополнительные параметры
     * @return string Переписанный контент
     */
    public function rewrite(string $content, string $toneOfVoice, array $options = []): string
    {
        $this->logger->info('Starting content rewrite', [
            'content_length' => strlen($content),
            'tone_of_voice' => $toneOfVoice
        ]);
        
        if (empty($this->webhookUrl) || empty($this->apiKey)) {
            $this->logger->error('Make.com integration is not properly configured', [
                'webhook_url_set' => !empty($this->webhookUrl),
                'api_key_set' => !empty($this->apiKey)
            ]);
            
            // Для отладки: возвращаем демо-контент вместо пустой строки
            return $this->generateDemoRewrittenContent($content, $toneOfVoice);
        }
        
        try {
            // Подготовка данных для отправки в Make.com
            $data = [
                'api_key' => $this->apiKey,
                'content' => $content,
                'tone_of_voice' => $toneOfVoice,
                'options' => $options
            ];
            
            $this->logger->info('Sending request to Make.com', [
                'webhook_url' => $this->webhookUrl,
                'content_length' => strlen($content)
            ]);
            
            // Отправка запроса в Make.com
            $response = $this->client->post($this->webhookUrl, [
                'json' => $data
            ]);
            
            // Получение и обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            $this->logger->info('Received response from Make.com', [
                'status_code' => $response->getStatusCode(),
                'response_length' => strlen($responseBody)
            ]);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from Make.com', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return $this->generateDemoRewrittenContent($content, $toneOfVoice);
            }
            
            if (!isset($responseData['rewritten_content'])) {
                $this->logger->error('Invalid response format from Make.com', [
                    'response' => $responseData,
                    'keys' => is_array($responseData) ? array_keys($responseData) : 'null or not an array'
                ]);
                return $this->generateDemoRewrittenContent($content, $toneOfVoice);
            }
            
            $rewrittenContent = $responseData['rewritten_content'];
            
            // Проверка качества переписанного контента
            if (!$this->checkQuality($rewrittenContent)) {
                $this->logger->warning('Rewritten content failed quality check', [
                    'tone_of_voice' => $toneOfVoice,
                    'content_length' => strlen($rewrittenContent)
                ]);
                return $this->generateDemoRewrittenContent($content, $toneOfVoice);
            }
            
            $this->logger->info('Content successfully rewritten', [
                'tone_of_voice' => $toneOfVoice,
                'original_length' => strlen($content),
                'rewritten_length' => strlen($rewrittenContent)
            ]);
            
            return $rewrittenContent;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error sending request to Make.com', [
                'error' => $e->getMessage()
            ]);
            return $this->generateDemoRewrittenContent($content, $toneOfVoice);
        } catch (\Exception $e) {
            $this->logger->error('Error in content rewriting process', [
                'error' => $e->getMessage()
            ]);
            return $this->generateDemoRewrittenContent($content, $toneOfVoice);
        }
    }
    
    /**
     * Генерирует демонстрационный переписанный контент для отладки
     * 
     * @param string $content Исходный контент
     * @param string $toneOfVoice Тон голоса
     * @return string Демонстрационный переписанный контент
     */
    private function generateDemoRewrittenContent(string $content, string $toneOfVoice): string
    {
        $this->logger->info('Generating demo rewritten content', [
            'original_length' => strlen($content),
            'tone_of_voice' => $toneOfVoice
        ]);
        
        // Создаем префикс в зависимости от тона голоса
        $prefix = '';
        switch ($toneOfVoice) {
            case 'Профессиональный':
                $prefix = 'Согласно экспертному мнению, ';
                break;
            case 'Разговорный':
                $prefix = 'Представьте себе! ';
                break;
            case 'Экспертный':
                $prefix = 'Исследования показывают, что ';
                break;
            case 'Образовательный':
                $prefix = 'Важно понимать, что ';
                break;
            case 'Трендовый':
                $prefix = '#ТрендДня ';
                break;
            default:
                $prefix = 'Интересно отметить, что ';
        }
        
        // Сокращаем контент, если он слишком длинный
        $maxLength = 280; // Максимальная длина для Twitter
        if (strlen($content) > $maxLength - strlen($prefix) - 10) {
            $content = substr($content, 0, $maxLength - strlen($prefix) - 13) . '...';
        }
        
        // Добавляем отметку о демо-режиме
        $rewrittenContent = $prefix . $content . ' [DEMO]';
        
        return $rewrittenContent;
    }
}
