<?php

namespace App\Rewrite;

use App\Core\LogManager;
use App\Core\Config;

/**
 * Класс для управления рерайтом контента
 */
class ContentRewriteManager
{
    private $logger;
    private $config;
    private $rewriter;
    private $toneOfVoiceTemplates = [];
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->rewriter = new MakeOpenAIRewriter();
        $this->loadToneOfVoiceTemplates();
    }
    
    /**
     * Загрузка шаблонов тона голоса для разных аккаунтов
     */
    private function loadToneOfVoiceTemplates()
    {
        // В реальном приложении эти данные могут быть загружены из базы данных или конфигурационного файла
        $this->toneOfVoiceTemplates = [
            'twitter_account1' => [
                'name' => 'Профессиональный',
                'description' => 'Формальный, деловой стиль с использованием профессиональной терминологии',
                'instructions' => 'Перепиши текст в формальном деловом стиле. Используй профессиональную терминологию и избегай разговорных выражений. Сохраняй факты и ключевые моменты. Максимальная длина: 280 символов.'
            ],
            'twitter_account2' => [
                'name' => 'Разговорный',
                'description' => 'Неформальный, дружелюбный стиль с использованием разговорных выражений',
                'instructions' => 'Перепиши текст в неформальном, дружелюбном стиле. Используй разговорные выражения и простой язык. Сохраняй факты и ключевые моменты. Максимальная длина: 280 символов.'
            ],
            'linkedin_account1' => [
                'name' => 'Экспертный',
                'description' => 'Профессиональный стиль с акцентом на экспертизу и аналитику',
                'instructions' => 'Перепиши текст в профессиональном стиле с акцентом на экспертизу и аналитику. Используй отраслевую терминологию и приводи аналитические выводы. Сохраняй факты и ключевые моменты. Оптимальная длина: 1000-1500 символов.'
            ],
            'youtube_account1' => [
                'name' => 'Образовательный',
                'description' => 'Информативный стиль с акцентом на обучение и объяснение',
                'instructions' => 'Перепиши текст в информативном стиле с акцентом на обучение и объяснение. Разбивай сложные концепции на простые части. Используй примеры и аналогии. Сохраняй факты и ключевые моменты. Оптимальная длина: 2000-3000 символов.'
            ],
            'threads_account1' => [
                'name' => 'Трендовый',
                'description' => 'Современный стиль с использованием актуальных выражений и хэштегов',
                'instructions' => 'Перепиши текст в современном стиле с использованием актуальных выражений и хэштегов. Будь кратким и запоминающимся. Сохраняй факты и ключевые моменты. Максимальная длина: 500 символов.'
            ]
        ];
        
        $this->logger->info('Loaded ' . count($this->toneOfVoiceTemplates) . ' tone of voice templates');
    }
    
    /**
     * Получение списка доступных шаблонов тона голоса
     * 
     * @return array Список шаблонов
     */
    public function getAvailableToneOfVoiceTemplates(): array
    {
        return $this->toneOfVoiceTemplates;
    }
    
    /**
     * Получение шаблона тона голоса по идентификатору аккаунта
     * 
     * @param string $accountId Идентификатор аккаунта
     * @return array|null Шаблон тона голоса или null, если не найден
     */
    public function getToneOfVoiceTemplate(string $accountId): ?array
    {
        return $this->toneOfVoiceTemplates[$accountId] ?? null;
    }
    
    /**
     * Рерайт контента для конкретного аккаунта
     * 
     * @param string $content Исходный контент
     * @param string $accountId Идентификатор аккаунта
     * @param array $options Дополнительные параметры
     * @return string Переписанный контент
     */
    public function rewriteForAccount(string $content, string $accountId, array $options = []): string
    {
        $template = $this->getToneOfVoiceTemplate($accountId);
        
        if (!$template) {
            $this->logger->error('Tone of voice template not found', ['account_id' => $accountId]);
            return '';
        }
        
        $this->logger->info('Rewriting content for account', [
            'account_id' => $accountId,
            'tone_of_voice' => $template['name']
        ]);
        
        // Добавляем инструкции из шаблона в опции
        $options['instructions'] = $template['instructions'];
        
        // Выполняем рерайт
        $rewrittenContent = $this->rewriter->rewrite($content, $template['name'], $options);
        
        return $rewrittenContent;
    }
    
    /**
     * Рерайт контента для всех аккаунтов
     * 
     * @param string $content Исходный контент
     * @param array $options Дополнительные параметры
     * @return array Массив переписанного контента для каждого аккаунта
     */
    public function rewriteForAllAccounts(string $content, array $options = []): array
    {
        $result = [];
        
        foreach ($this->toneOfVoiceTemplates as $accountId => $template) {
            $rewrittenContent = $this->rewriteForAccount($content, $accountId, $options);
            
            if (!empty($rewrittenContent)) {
                $result[$accountId] = $rewrittenContent;
            }
        }
        
        $this->logger->info('Rewritten content for ' . count($result) . ' accounts');
        
        return $result;
    }
}
