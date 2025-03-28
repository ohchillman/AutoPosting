<?php

namespace App\Browser;

/**
 * Тестирование интеграции с Octo Browser
 */
class OctoBrowserTest
{
    /**
     * Запускает тестирование интеграции с Octo Browser
     * 
     * @param string $apiKey API ключ Octo Browser
     * @return array Результаты тестирования
     */
    public static function runTest(string $apiKey): array
    {
        $results = [
            'success' => false,
            'connection' => false,
            'profiles' => false,
            'details' => []
        ];
        
        try {
            // Создаем адаптер
            $adapter = new OctoBrowserAdapter('https://app.octobrowser.net', $apiKey);
            
            // Тестируем соединение
            $connectionTest = $adapter->testConnection();
            $results['connection'] = $connectionTest;
            $results['details'][] = 'Соединение с API: ' . ($connectionTest ? 'Успешно' : 'Ошибка');
            
            if ($connectionTest) {
                // Получаем список профилей
                $profiles = $adapter->getProfiles(['limit' => 5]);
                $results['profiles'] = !empty($profiles);
                $results['details'][] = 'Получение профилей: ' . (!empty($profiles) ? 'Успешно (' . count($profiles) . ' профилей)' : 'Ошибка или нет профилей');
                
                // Если есть профили, тестируем дополнительные функции
                if (!empty($profiles)) {
                    $results['details'][] = 'Список профилей: ' . json_encode(array_map(function($profile) {
                        return ['id' => $profile['id'], 'name' => $profile['name']];
                    }, $profiles));
                    
                    // Создаем тестовый профиль
                    $testProfileName = 'Test Profile ' . date('Y-m-d H:i:s');
                    $testProfileId = $adapter->createProfile([
                        'name' => $testProfileName,
                        'platform' => 'win',
                        'browser' => 'chrome',
                        'notes' => 'Тестовый профиль, создан автоматически'
                    ]);
                    
                    $results['details'][] = 'Создание профиля: ' . (!empty($testProfileId) ? 'Успешно (ID: ' . $testProfileId . ')' : 'Ошибка');
                    
                    if (!empty($testProfileId)) {
                        // Обновляем профиль
                        $updateResult = $adapter->updateProfile($testProfileId, [
                            'name' => $testProfileName . ' (обновлен)',
                            'notes' => 'Тестовый профиль, обновлен автоматически'
                        ]);
                        
                        $results['details'][] = 'Обновление профиля: ' . ($updateResult ? 'Успешно' : 'Ошибка');
                        
                        // Удаляем тестовый профиль
                        $deleteResult = $adapter->deleteProfile($testProfileId);
                        $results['details'][] = 'Удаление профиля: ' . ($deleteResult ? 'Успешно' : 'Ошибка');
                    }
                }
                
                $results['success'] = $results['connection'] && $results['profiles'];
            }
        } catch (\Exception $e) {
            $results['details'][] = 'Ошибка: ' . $e->getMessage();
        }
        
        return $results;
    }
}
