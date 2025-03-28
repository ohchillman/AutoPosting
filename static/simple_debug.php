<?php
// Включаем отображение всех ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Настраиваем логирование ошибок
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/app.log');

// Загружаем автозагрузчик Composer
require __DIR__ . '/../vendor/autoload.php';

// Простая функция для безопасного вывода
function safe_echo($text) {
    echo htmlspecialchars($text);
}

// Функция для получения последних строк лога
function get_last_log_lines($file, $lines = 50) {
    if (!file_exists($file)) {
        return ["Файл лога не найден: $file"];
    }
    
    $result = [];
    $fp = fopen($file, "r");
    if ($fp) {
        $pos = -2;
        $eof = "";
        $line = "";
        $count = 0;
        
        while ($count < $lines && ($pos >= -filesize($file) || $count == 0)) {
            $eof = fseek($fp, $pos, SEEK_END);
            if ($eof < 0) {
                break;
            }
            
            $char = fgetc($fp);
            if ($char === "\n") {
                if ($line !== "") {
                    $result[] = $line;
                    $line = "";
                    $count++;
                }
            } else {
                $line = $char . $line;
            }
            $pos--;
        }
        
        if ($line !== "") {
            $result[] = $line;
        }
        
        fclose($fp);
    }
    
    return array_reverse($result);
}

// Начало HTML
echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Упрощенная отладка системы</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            margin: -15px -15px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 14px;
            border: 1px solid #ddd;
        }
        .log-entry {
            margin-bottom: 5px;
            padding: 5px;
            border-radius: 3px;
        }
        .log-info {
            background-color: #d1ecf1;
        }
        .log-error {
            background-color: #f8d7da;
        }
        .log-warning {
            background-color: #fff3cd;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        form {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Упрощенная отладка системы автоматизации</h1>
        
        <div class="card">
            <div class="card-header">Системная информация</div>
            <div class="card-body">
                <p><strong>PHP версия:</strong> <?php echo PHP_VERSION; ?></p>
                <p><strong>Операционная система:</strong> <?php echo PHP_OS; ?></p>
                <p><strong>Время сервера:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                <p><strong>Директория проекта:</strong> <?php echo realpath(__DIR__ . '/../'); ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Последние записи журнала</div>
            <div class="card-body">
                <?php
                $logFile = __DIR__ . '/../logs/app.log';
                $logs = get_last_log_lines($logFile, 30);
                
                if (!empty($logs)) {
                    echo '<pre>';
                    foreach ($logs as $log) {
                        $logClass = 'log-info';
                        if (strpos($log, 'ERROR') !== false) {
                            $logClass = 'log-error';
                        } elseif (strpos($log, 'WARNING') !== false) {
                            $logClass = 'log-warning';
                        }
                        
                        echo '<div class="log-entry ' . $logClass . '">' . htmlspecialchars($log) . '</div>';
                    }
                    echo '</pre>';
                } else {
                    echo '<div class="alert alert-warning">Журнал пуст или недоступен</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Тест парсера новостей</div>
            <div class="card-body">
                <form action="simple_debug.php?action=test_parser" method="post">
                    <button type="submit" class="btn">Запустить тест парсера</button>
                </form>
                
                <?php
                if (isset($_GET['action']) && $_GET['action'] === 'test_parser') {
                    try {
                        // Создаем экземпляр парсера для тестирования
                        $parserManager = new \App\Parsers\NewsParserManager();
                        $news = $parserManager->getAllNews();
                        
                        echo '<div class="alert alert-success">
                            <strong>Результат:</strong> Получено ' . count($news) . ' новостей
                        </div>';
                        
                        if (count($news) > 0) {
                            echo '<div>
                                <strong>Пример новости:</strong>
                                <pre>' . htmlspecialchars(print_r($news[0], true)) . '</pre>
                            </div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">
                            <strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '
                        </div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Тест рерайта контента</div>
            <div class="card-body">
                <form action="simple_debug.php?action=test_rewrite" method="post">
                    <div>
                        <label for="test_content">Тестовый контент:</label>
                        <textarea id="test_content" name="test_content" rows="3">Это тестовый контент для проверки системы рерайта.</textarea>
                    </div>
                    <div>
                        <label for="rewrite_account">Аккаунт для рерайта:</label>
                        <select id="rewrite_account" name="rewrite_account">
                            <option value="twitter_account1">Twitter Account 1 (Профессиональный)</option>
                            <option value="twitter_account2">Twitter Account 2 (Разговорный)</option>
                            <option value="linkedin_account1">LinkedIn Account (Экспертный)</option>
                            <option value="youtube_account1">YouTube Account (Образовательный)</option>
                            <option value="threads_account1">Threads Account (Трендовый)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Запустить тест рерайта</button>
                </form>
                
                <?php
                if (isset($_GET['action']) && $_GET['action'] === 'test_rewrite') {
                    try {
                        $testContent = $_POST['test_content'] ?? 'Это тестовый контент для проверки системы рерайта.';
                        $rewriteAccount = $_POST['rewrite_account'] ?? 'twitter_account1';
                        
                        // Создаем экземпляр менеджера рерайта для тестирования
                        $rewriteManager = new \App\Rewrite\ContentRewriteManager();
                        $rewrittenContent = $rewriteManager->rewriteForAccount($testContent, $rewriteAccount);
                        
                        if (!empty($rewrittenContent)) {
                            echo '<div class="alert alert-success">
                                <strong>Результат:</strong> Контент успешно переписан для аккаунта ' . htmlspecialchars($rewriteAccount) . '
                            </div>
                            <div>
                                <strong>Исходный текст:</strong>
                                <pre>' . htmlspecialchars($testContent) . '</pre>
                                <strong>Переписанный текст:</strong>
                                <pre>' . htmlspecialchars($rewrittenContent) . '</pre>
                            </div>';
                        } else {
                            echo '<div class="alert alert-warning">
                                <strong>Результат:</strong> Не удалось переписать контент для аккаунта ' . htmlspecialchars($rewriteAccount) . '
                            </div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">
                            <strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '
                        </div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Тест публикации</div>
            <div class="card-body">
                <form action="simple_debug.php?action=test_posting" method="post">
                    <div>
                        <label for="test_post_content">Тестовый пост:</label>
                        <textarea id="test_post_content" name="test_post_content" rows="3">Это тестовый пост для проверки системы публикации.</textarea>
                    </div>
                    <div>
                        <label for="test_account">Аккаунт:</label>
                        <select id="test_account" name="test_account">
                            <option value="twitter_account1">Twitter Account 1</option>
                            <option value="twitter_account2">Twitter Account 2</option>
                            <option value="linkedin_account1">LinkedIn Account</option>
                            <option value="youtube_account1">YouTube Account</option>
                            <option value="threads_account1">Threads Account</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Запустить тест публикации</button>
                </form>
                
                <?php
                if (isset($_GET['action']) && $_GET['action'] === 'test_posting') {
                    try {
                        $testContent = $_POST['test_post_content'] ?? 'Это тестовый пост для проверки системы публикации.';
                        $testAccount = $_POST['test_account'] ?? 'twitter_account1';
                        
                        // Создаем экземпляр менеджера публикации для тестирования
                        $postingManager = new \App\Posting\SocialMediaPostingManager();
                        $result = $postingManager->testPostToAccount($testAccount, $testContent);
                        
                        if ($result['success']) {
                            echo '<div class="alert alert-success">
                                <strong>Результат:</strong> ' . htmlspecialchars($result['message']) . '
                            </div>';
                        } else {
                            echo '<div class="alert alert-warning">
                                <strong>Результат:</strong> ' . htmlspecialchars($result['message']) . '
                            </div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">
                            <strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '
                        </div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Статус аккаунтов</div>
            <div class="card-body">
                <form action="simple_debug.php?action=check_accounts" method="post">
                    <button type="submit" class="btn">Проверить статус аккаунтов</button>
                </form>
                
                <?php
                if (isset($_GET['action']) && $_GET['action'] === 'check_accounts') {
                    try {
                        // Создаем экземпляр менеджера публикации
                        $postingManager = new \App\Posting\SocialMediaPostingManager();
                        $statuses = $postingManager->checkAllAccountsStatus();
                        
                        echo '<div class="alert alert-success">
                            <strong>Результат:</strong> Проверено ' . count($statuses) . ' аккаунтов
                        </div>';
                        
                        echo '<ul>';
                        foreach ($statuses as $accountId => $isActive) {
                            $statusText = $isActive ? 'Активен' : 'Неактивен';
                            $statusClass = $isActive ? 'log-info' : 'log-warning';
                            echo '<li class="' . $statusClass . '">' . htmlspecialchars($accountId) . ': ' . $statusText . '</li>';
                        }
                        echo '</ul>';
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">
                            <strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '
                        </div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Запуск полной автоматизации</div>
            <div class="card-body">
                <form action="simple_debug.php?action=run_automation" method="post">
                    <div>
                        <label for="keywords">Ключевые слова (через запятую):</label>
                        <input type="text" id="keywords" name="keywords" value="технологии, инновации, бизнес">
                    </div>
                    <div>
                        <label for="max_news">Максимальное количество новостей:</label>
                        <input type="text" id="max_news" name="max_news" value="5">
                    </div>
                    <button type="submit" class="btn">Запустить автоматизацию</button>
                </form>
                
                <?php
                if (isset($_GET['action']) && $_GET['action'] === 'run_automation') {
                    try {
                        $keywords = $_POST['keywords'] ?? 'технологии, инновации, бизнес';
                        $maxNews = intval($_POST['max_news'] ?? 5);
                        
                        // Запускаем процесс автоматизации
                        $processor = new \App\NewsProcessor();
                        $result = $processor->processNews($keywords, $maxNews);
                        
                        echo '<div class="alert alert-success">
                            <strong>Результат автоматизации:</strong>
                        </div>';
                        
                        echo '<ul>';
                        echo '<li>Обработано новостей: ' . $result['processed'] . '</li>';
                        echo '<li>Переписано контента: ' . $result['rewritten'] . '</li>';
                        echo '<li>Опубликовано постов: ' . $result['posted'] . '</li>';
                        echo '<li>Процент успешных публикаций: ' . number_format($result['success_rate'], 2) . '%</li>';
                        echo '</ul>';
                        
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">
                            <strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '
                        </div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
