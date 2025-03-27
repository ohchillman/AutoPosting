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
        if (empty($this->webhookUrl) || empty($this->apiKey)) {
            $this->logger->error('Make.com integration is not properly configured');
            return '';
        }
        
        try {
            // Подготовка данных для отправки в Make.com
            $data = [
                'api_key' => $this->apiKey,
                'content' => $content,
                'tone_of_voice' => $toneOfVoice,
                'options' => $options
            ];
            
            // Отправка запроса в Make.com
            $response = $this->client->post($this->webhookUrl, [
                'json' => $data
            ]);
            
            // Получение и обработка ответа
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding response from Make.com', [
                    'error' => json_last_error_msg(),
                    'response' => $responseBody
                ]);
                return '';
            }
            
            if (!isset($responseData['rewritten_content'])) {
                $this->logger->error('Invalid response format from Make.com', [
                    'response' => $responseData
                ]);
                return '';
            }
            
            $rewrittenContent = $responseData['rewritten_content'];
            
            // Проверка качества переписанного контента
            if (!$this->checkQuality($rewrittenContent)) {
                $this->logger->warning('Rewritten content failed quality check', [
                    'tone_of_voice' => $toneOfVoice
                ]);
                return '';
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
            return '';
        } catch (\Exception $e) {
            $this->logger->error('Error in content rewriting process', [
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
}
