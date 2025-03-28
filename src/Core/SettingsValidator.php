<?php

namespace App\Core;

/**
 * Класс для валидации настроек системы
 */
class SettingsValidator
{
    private $logger;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
    }
    
    /**
     * Валидация настроек базы данных
     * 
     * @param array $settings Настройки для валидации
     * @return array Результат валидации [success, message, errors]
     */
    public function validateDatabaseSettings(array $settings): array
    {
        $result = [
            'success' => true,
            'message' => 'Настройки базы данных корректны',
            'errors' => []
        ];
        
        // Проверка обязательных полей
        $requiredFields = ['host', 'port', 'database', 'username'];
        foreach ($requiredFields as $field) {
            if (empty($settings[$field])) {
                $result['success'] = false;
                $result['errors'][] = "Поле '{$field}' обязательно для заполнения";
            }
        }
        
        // Проверка порта
        if (!empty($settings['port']) && !is_numeric($settings['port'])) {
            $result['success'] = false;
            $result['errors'][] = "Порт должен быть числом";
        }
        
        if (!$result['success']) {
            $result['message'] = 'Ошибка в настройках базы данных';
        }
        
        return $result;
    }
    
    /**
     * Валидация настроек парсера новостей
     * 
     * @param array $settings Настройки для валидации
     * @return array Результат валидации [success, message, errors]
     */
    public function validateParserSettings(array $settings): array
    {
        $result = [
            'success' => true,
            'message' => 'Настройки парсера новостей корректны',
            'errors' => []
        ];
        
        // Проверка источников
        if (empty($settings['sources']) || !is_array($settings['sources'])) {
            $result['success'] = false;
            $result['errors'][] = "Необходимо указать хотя бы один источник новостей";
            $result['message'] = 'Ошибка в настройках парсера новостей';
            return $result;
        }
        
        $validSourcesCount = 0;
        foreach ($settings['sources'] as $index => $source) {
            if (empty($source)) {
                continue;
            }
            
            if (!filter_var($source, FILTER_VALIDATE_URL)) {
                $result['errors'][] = "Источник #{$index} не является корректным URL";
            } else {
                $validSourcesCount++;
            }
        }
        
        if ($validSourcesCount === 0) {
            $result['success'] = false;
            $result['errors'][] = "Необходимо указать хотя бы один корректный URL источника новостей";
            $result['message'] = 'Ошибка в настройках парсера новостей';
        }
        
        return $result;
    }
    
    /**
     * Валидация настроек Make.com
     * 
     * @param array $settings Настройки для валидации
     * @return array Результат валидации [success, message, errors]
     */
    public function validateMakeSettings(array $settings): array
    {
        $result = [
            'success' => true,
            'message' => 'Настройки Make.com корректны',
            'errors' => []
        ];
        
        // Проверка API ключа
        if (empty($settings['api_key'])) {
            $result['success'] = false;
            $result['errors'][] = "API ключ обязателен для заполнения";
        }
        
        // Проверка webhook URL
        if (empty($settings['webhook_url'])) {
            $result['success'] = false;
            $result['errors'][] = "Webhook URL обязателен для заполнения";
        } else if (!filter_var($settings['webhook_url'], FILTER_VALIDATE_URL)) {
            $result['success'] = false;
            $result['errors'][] = "Webhook URL должен быть корректным URL";
        }
        
        if (!$result['success']) {
            $result['message'] = 'Ошибка в настройках Make.com';
        }
        
        return $result;
    }
    
    /**
     * Валидация настроек Twitter
     * 
     * @param array $settings Настройки для валидации
     * @return array Результат валидации [success, message, errors]
     */
    public function validateTwitterSettings(array $settings): array
    {
        $result = [
            'success' => true,
            'message' => 'Настройки Twitter корректны',
            'errors' => []
        ];
        
        // Проверка обязательных полей
        $requiredFields = ['api_key', 'api_secret', 'access_token', 'access_secret'];
        foreach ($requiredFields as $field) {
            if (empty($settings[$field])) {
                $result['success'] = false;
                $result['errors'][] = "Поле '{$field}' обязательно для заполнения";
            }
        }
        
        if (!$result['success']) {
            $result['message'] = 'Ошибка в настройках Twitter';
        }
        
        return $result;
    }
    
    /**
     * Валидация настроек LinkedIn
     * 
     * @param array $settings Настройки для валидации
     * @return array Результат валидации [success, message, errors]
     */
    public function validateLinkedInSettings(array $settings): array
    {
        $result = [
            'success' => true,
            'message' => 'Настройки LinkedIn корректны',
            'errors' => []
        ];
        
        // Проверка обязательных полей
        $requiredFields = ['client_id', 'client_secret', 'access_token'];
        foreach ($requiredFields as $field) {
            if (empty($settings[$field])) {
                $result['success'] = false;
                $result['errors'][] = "Поле '{$field}' обязательно для заполнения";
            }
        }
        
        if (!$result['success']) {
            $result['message'] = 'Ошибка в настройках LinkedIn';
        }
        
        return $result;
    }
    
    /**
     * Валидация настроек YouTube
     * 
     * @param array $settings Настройки для валидации
     * @return array Результат валидации [success, message, errors]
     */
    public function validateYouTubeSettings(array $settings): array
    {
        $result = [
            'success' => true,
            'message' => 'Настройки YouTube корректны',
            'errors' => []
        ];
        
        // Проверка обязательных полей
        $requiredFields = ['api_key', 'client_id', 'client_secret', 'refresh_token'];
        foreach ($requiredFields as $field) {
            if (empty($settings[$field])) {
                $result['success'] = false;
                $result['errors'][] = "Поле '{$field}' обязательно для заполнения";
            }
        }
        
        if (!$result['success']) {
            $result['message'] = 'Ошибка в настройках YouTube';
        }
        
        return $result;
    }
    
    /**
     * Валидация настроек Dolphin Anty
     * 
     * @param array $settings Настройки для валидации
     * @return array Результат валидации [success, message, errors]
     */
    public function validateDolphinSettings(array $settings): array
    {
        $result = [
            'success' => true,
            'message' => 'Настройки Dolphin Anty корректны',
            'errors' => []
        ];
        
        // Проверка API ключа
        if (empty($settings['api_key'])) {
            $result['success'] = false;
            $result['errors'][] = "API ключ обязателен для заполнения";
        }
        
        // Проверка API URL
        if (empty($settings['api_url'])) {
            $result['success'] = false;
            $result['errors'][] = "API URL обязателен для заполнения";
        } else if (!filter_var($settings['api_url'], FILTER_VALIDATE_URL)) {
            $result['success'] = false;
            $result['errors'][] = "API URL должен быть корректным URL";
        }
        
        if (!$result['success']) {
            $result['message'] = 'Ошибка в настройках Dolphin Anty';
        }
        
        return $result;
    }
    
    /**
     * Валидация настроек прокси
     * 
     * @param array $settings Настройки для валидации
     * @return array Результат валидации [success, message, errors]
     */
    public function validateProxySettings(array $settings): array
    {
        $result = [
            'success' => true,
            'message' => 'Настройки прокси корректны',
            'errors' => []
        ];
        
        // Если указан сервер, проверяем остальные поля
        if (!empty($settings['server'])) {
            // Проверка порта
            if (empty($settings['port'])) {
                $result['success'] = false;
                $result['errors'][] = "Порт обязателен для заполнения";
            } else if (!is_numeric($settings['port'])) {
                $result['success'] = false;
                $result['errors'][] = "Порт должен быть числом";
            }
            
            // Если указано имя пользователя, должен быть и пароль
            if (!empty($settings['username']) && empty($settings['password'])) {
                $result['success'] = false;
                $result['errors'][] = "Если указано имя пользователя, необходимо указать и пароль";
            }
        }
        
        if (!$result['success']) {
            $result['message'] = 'Ошибка в настройках прокси';
        }
        
        return $result;
    }
}
