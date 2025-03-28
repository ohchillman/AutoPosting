<?php

namespace App\Core;

use App\Core\LogManager;

/**
 * Класс для асинхронной обработки задач
 */
class AsyncTaskManager
{
    private static $instance = null;
    private $logger;
    private $tasksDir;
    private $resultsDir;
    
    /**
     * Приватный конструктор для реализации паттерна Singleton
     */
    private function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->tasksDir = __DIR__ . '/../../data/tasks';
        $this->resultsDir = __DIR__ . '/../../data/results';
        
        // Создаем директории для задач и результатов, если они не существуют
        if (!file_exists($this->tasksDir)) {
            mkdir($this->tasksDir, 0755, true);
        }
        
        if (!file_exists($this->resultsDir)) {
            mkdir($this->resultsDir, 0755, true);
        }
    }
    
    /**
     * Получение экземпляра менеджера асинхронных задач
     * 
     * @return AsyncTaskManager
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Добавление задачи в очередь
     * 
     * @param string $type Тип задачи
     * @param array $data Данные задачи
     * @return string|false ID задачи или false в случае ошибки
     */
    public function enqueueTask($type, $data)
    {
        try {
            // Генерируем уникальный ID задачи
            $taskId = uniqid('task_', true);
            
            // Создаем задачу
            $task = [
                'id' => $taskId,
                'type' => $type,
                'data' => $data,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Сохраняем задачу в файл
            $taskFile = $this->tasksDir . '/' . $taskId . '.json';
            $result = file_put_contents($taskFile, json_encode($task));
            
            if ($result === false) {
                $this->logger->error('Failed to save task file', ['task_id' => $taskId]);
                return false;
            }
            
            $this->logger->info('Task enqueued', ['task_id' => $taskId, 'type' => $type]);
            
            // Запускаем обработчик задач в фоновом режиме
            $this->startTaskProcessor();
            
            return $taskId;
            
        } catch (\Exception $e) {
            $this->logger->error('Error enqueueing task', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Запуск обработчика задач в фоновом режиме
     */
    private function startTaskProcessor()
    {
        // Путь к скрипту обработчика задач
        $processorScript = __DIR__ . '/../../scripts/process_tasks.php';
        
        // Проверяем, существует ли скрипт
        if (!file_exists($processorScript)) {
            // Создаем скрипт обработчика задач
            $this->createTaskProcessorScript($processorScript);
        }
        
        // Запускаем обработчик задач в фоновом режиме
        $command = 'php ' . escapeshellarg($processorScript) . ' > /dev/null 2>&1 &';
        exec($command);
        
        $this->logger->debug('Task processor started');
    }
    
    /**
     * Создание скрипта обработчика задач
     * 
     * @param string $scriptPath Путь к скрипту
     */
    private function createTaskProcessorScript($scriptPath)
    {
        $scriptContent = '<?php
// Скрипт для обработки асинхронных задач

// Подключаем автозагрузчик
require_once __DIR__ . "/../vendor/autoload.php";

use App\Core\AsyncTaskManager;
use App\Core\LogManager;

// Получаем экземпляры менеджеров
$taskManager = AsyncTaskManager::getInstance();
$logger = LogManager::getInstance();

$logger->info("Task processor started");

// Обрабатываем задачи
$taskManager->processTasks();

$logger->info("Task processor finished");
';
        
        file_put_contents($scriptPath, $scriptContent);
        chmod($scriptPath, 0755);
        
        // Создаем директорию для скриптов, если она не существует
        $scriptsDir = dirname($scriptPath);
        if (!file_exists($scriptsDir)) {
            mkdir($scriptsDir, 0755, true);
        }
    }
    
    /**
     * Обработка задач из очереди
     * 
     * @param int $limit Максимальное количество задач для обработки
     * @return int Количество обработанных задач
     */
    public function processTasks($limit = 10)
    {
        try {
            // Получаем список файлов задач
            $taskFiles = glob($this->tasksDir . '/*.json');
            
            // Сортируем файлы по времени создания
            usort($taskFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Ограничиваем количество задач для обработки
            $taskFiles = array_slice($taskFiles, 0, $limit);
            
            $processedCount = 0;
            
            foreach ($taskFiles as $taskFile) {
                // Загружаем задачу из файла
                $taskData = file_get_contents($taskFile);
                $task = json_decode($taskData, true);
                
                if ($task === null) {
                    $this->logger->error('Failed to decode task data', ['file' => $taskFile]);
                    continue;
                }
                
                // Обновляем статус задачи
                $task['status'] = 'processing';
                $task['updated_at'] = date('Y-m-d H:i:s');
                file_put_contents($taskFile, json_encode($task));
                
                // Обрабатываем задачу в зависимости от типа
                $result = $this->processTask($task);
                
                // Обновляем статус задачи
                $task['status'] = 'completed';
                $task['result'] = $result;
                $task['updated_at'] = date('Y-m-d H:i:s');
                file_put_contents($taskFile, json_encode($task));
                
                // Сохраняем результат в отдельный файл
                $resultFile = $this->resultsDir . '/' . $task['id'] . '.json';
                file_put_contents($resultFile, json_encode([
                    'task_id' => $task['id'],
                    'type' => $task['type'],
                    'result' => $result,
                    'completed_at' => date('Y-m-d H:i:s')
                ]));
                
                // Удаляем файл задачи
                unlink($taskFile);
                
                $processedCount++;
                
                $this->logger->info('Task processed', ['task_id' => $task['id'], 'type' => $task['type']]);
            }
            
            return $processedCount;
            
        } catch (\Exception $e) {
            $this->logger->error('Error processing tasks', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Обработка конкретной задачи
     * 
     * @param array $task Данные задачи
     * @return mixed Результат обработки задачи
     */
    private function processTask($task)
    {
        try {
            switch ($task['type']) {
                case 'parse_news':
                    return $this->processParseNewsTask($task['data']);
                
                case 'rewrite_content':
                    return $this->processRewriteContentTask($task['data']);
                
                case 'post_to_social':
                    return $this->processPostToSocialTask($task['data']);
                
                case 'analyze_statistics':
                    return $this->processAnalyzeStatisticsTask($task['data']);
                
                default:
                    $this->logger->warning('Unknown task type', ['type' => $task['type']]);
                    return ['error' => 'Unknown task type'];
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing task', [
                'task_id' => $task['id'],
                'type' => $task['type'],
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Обработка задачи парсинга новостей
     * 
     * @param array $data Данные задачи
     * @return array Результат обработки задачи
     */
    private function processParseNewsTask($data)
    {
        $this->logger->info('Processing parse news task', ['sources' => $data['sources'] ?? 'all']);
        
        // Здесь должен быть код для парсинга новостей
        // В данном примере просто имитируем работу
        sleep(2);
        
        return [
            'status' => 'success',
            'news_count' => rand(5, 20),
            'sources_processed' => $data['sources'] ?? ['source1', 'source2', 'source3']
        ];
    }
    
    /**
     * Обработка задачи рерайта контента
     * 
     * @param array $data Данные задачи
     * @return array Результат обработки задачи
     */
    private function processRewriteContentTask($data)
    {
        $this->logger->info('Processing rewrite content task', ['news_id' => $data['news_id'] ?? 'unknown']);
        
        // Здесь должен быть код для рерайта контента
        // В данном примере просто имитируем работу
        sleep(3);
        
        return [
            'status' => 'success',
            'original_length' => rand(500, 2000),
            'rewritten_length' => rand(600, 2500)
        ];
    }
    
    /**
     * Обработка задачи публикации в социальных сетях
     * 
     * @param array $data Данные задачи
     * @return array Результат обработки задачи
     */
    private function processPostToSocialTask($data)
    {
        $this->logger->info('Processing post to social task', [
            'content_id' => $data['content_id'] ?? 'unknown',
            'networks' => $data['networks'] ?? 'all'
        ]);
        
        // Здесь должен быть код для публикации в социальных сетях
        // В данном примере просто имитируем работу
        sleep(2);
        
        return [
            'status' => 'success',
            'posted_to' => $data['networks'] ?? ['twitter', 'linkedin', 'youtube', 'threads'],
            'post_urls' => [
                'twitter' => 'https://twitter.com/example/status/123456789',
                'linkedin' => 'https://linkedin.com/post/123456789',
                'youtube' => 'https://youtube.com/post/123456789',
                'threads' => 'https://threads.net/post/123456789'
            ]
        ];
    }
    
    /**
     * Обработка задачи анализа статистики
     * 
     * @param array $data Данные задачи
     * @return array Результат обработки задачи
     */
    private function processAnalyzeStatisticsTask($data)
    {
        $this->logger->info('Processing analyze statistics task', [
            'period' => $data['period'] ?? 'day',
            'networks' => $data['networks'] ?? 'all'
        ]);
        
        // Здесь должен быть код для анализа статистики
        // В данном примере просто имитируем работу
        sleep(4);
        
        return [
            'status' => 'success',
            'period' => $data['period'] ?? 'day',
            'total_posts' => rand(10, 100),
            'engagement' => rand(100, 1000),
            'clicks' => rand(50, 500),
            'conversions' => rand(5, 50)
        ];
    }
    
    /**
     * Получение статуса задачи
     * 
     * @param string $taskId ID задачи
     * @return array|false Статус задачи или false в случае ошибки
     */
    public function getTaskStatus($taskId)
    {
        // Проверяем, существует ли файл задачи
        $taskFile = $this->tasksDir . '/' . $taskId . '.json';
        
        if (file_exists($taskFile)) {
            // Задача еще в очереди или обрабатывается
            $taskData = file_get_contents($taskFile);
            $task = json_decode($taskData, true);
            
            if ($task === null) {
                $this->logger->error('Failed to decode task data', ['file' => $taskFile]);
                return false;
            }
            
            return [
                'id' => $task['id'],
                'type' => $task['type'],
                'status' => $task['status'],
                'created_at' => $task['created_at'],
                'updated_at' => $task['updated_at']
            ];
        }
        
        // Проверяем, существует ли файл результата
        $resultFile = $this->resultsDir . '/' . $taskId . '.json';
        
        if (file_exists($resultFile)) {
            // Задача выполнена
            $resultData = file_get_contents($resultFile);
            $result = json_decode($resultData, true);
            
            if ($result === null) {
                $this->logger->error('Failed to decode result data', ['file' => $resultFile]);
                return false;
            }
            
            return [
                'id' => $result['task_id'],
                'type' => $result['type'],
                'status' => 'completed',
                'result' => $result['result'],
                'completed_at' => $result['completed_at']
            ];
        }
        
        // Задача не найдена
        return false;
    }
    
    /**
     * Получение списка активных задач
     * 
     * @return array Список активных задач
     */
    public function getActiveTasks()
    {
        $tasks = [];
        
        // Получаем список файлов задач
        $taskFiles = glob($this->tasksDir . '/*.json');
        
        foreach ($taskFiles as $taskFile) {
            $taskData = file_get_contents($taskFile);
            $task = json_decode($taskData, true);
            
            if ($task === null) {
                continue;
            }
            
            $tasks[] = [
                'id' => $task['id'],
                'type' => $task['type'],
                'status' => $task['status'],
                'created_at' => $task['created_at'],
                'updated_at' => $task['updated_at']
            ];
        }
        
        return $tasks;
    }
    
    /**
     * Получение списка завершенных задач
     * 
     * @param int $limit Максимальное количество задач
     * @return array Список завершенных задач
     */
    public function getCompletedTasks($limit = 10)
    {
        $tasks = [];
        
        // Получаем список файлов результатов
        $resultFiles = glob($this->resultsDir . '/*.json');
        
        // Сортируем файлы по времени создания (от новых к старым)
        usort($resultFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Ограничиваем количество результатов
        $resultFiles = array_slice($resultFiles, 0, $limit);
        
        foreach ($resultFiles as $resultFile) {
            $resultData = file_get_contents($resultFile);
            $result = json_decode($resultData, true);
            
            if ($result === null) {
                continue;
            }
            
            $tasks[] = [
                'id' => $result['task_id'],
                'type' => $result['type'],
                'status' => 'completed',
                'result' => $result['result'],
                'completed_at' => $result['completed_at']
            ];
        }
        
        return $tasks;
    }
}
