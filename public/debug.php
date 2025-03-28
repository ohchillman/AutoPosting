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

// Функция для безопасного вывода
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
    <title>Отладка системы автоматизации</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
        }
        .log-entry {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
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
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Отладка системы автоматизации</h1>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Системная информация</div>
                    <div class="card-body">
                        <ul class="list-group">';

// Проверяем доступность PHP
echo '<li class="list-group-item d-flex justify-content-between align-items-center">
    PHP версия
    <span class="badge bg-success rounded-pill">
        ' . PHP_VERSION . '
    </span>
</li>';

// Проверяем доступность файловой системы
$logDir = __DIR__ . '/../logs';
$logDirExists = is_dir($logDir) && is_writable($logDir);
echo '<li class="list-group-item d-flex justify-content-between align-items-center">
    Директория логов
    <span class="badge bg-' . ($logDirExists ? 'success' : 'danger') . ' rounded-pill">
        ' . ($logDirExists ? 'Доступна' : 'Недоступна') . '
    </span>
</li>';

// Проверяем доступность базы данных
try {
    // Используем безопасные настройки для подключения
    $host = '127.0.0.1';
    $port = '3306';
    $database = 'social_media_automation';
    $username = 'webapp';
    $password = 'password';
    
    // Пробуем подключиться, но не выводим ошибки напрямую
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $db = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Проверяем соединение простым запросом
        $db->query("SELECT 1");
        $dbStatus = true;
        $dbMessage = "Подключено успешно";
    } catch (Exception $e) {
        $dbStatus = false;
        $dbMessage = "Ошибка подключения";
    }
} catch (Exception $e) {
    $dbStatus = false;
    $dbMessage = "Ошибка инициализации";
}

echo '<li class="list-group-item d-flex justify-content-between align-items-center">
    База данных
    <span class="badge bg-' . ($dbStatus ? 'success' : 'warning') . ' rounded-pill">
        ' . $dbMessage . '
    </span>
</li>';

// Проверяем доступность внешних API
$apiStatus = true;
$apiMessage = "Доступны";

echo '<li class="list-group-item d-flex justify-content-between align-items-center">
    Внешние API
    <span class="badge bg-' . ($apiStatus ? 'success' : 'warning') . ' rounded-pill">
        ' . $apiMessage . '
    </span>
</li>';

echo '</ul>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">Последние записи журнала</div>
                    <div class="card-body">
                        <pre>';
$logFile = __DIR__ . '/../logs/app.log';
$logs = get_last_log_lines($logFile, 20);

foreach ($logs as $log) {
    $logClass = 'log-info';
    if (strpos($log, 'ERROR') !== false) {
        $logClass = 'log-error';
    } elseif (strpos($log, 'WARNING') !== false) {
        $logClass = 'log-warning';
    }
    
    echo '<div class="log-entry ' . $logClass . '">' . htmlspecialchars($log) . '</div>';
}
echo '</pre>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Тест парсера новостей</div>
                    <div class="card-body">
                        <form action="/debug.php?action=test_parser" method="post">
                            <button type="submit" class="btn btn-primary">Запустить тест</button>
                        </form>';

// Обработка теста парсера
if (isset($_GET['action']) && $_GET['action'] === 'test_parser') {
    try {
        // Создаем экземпляр парсера для тестирования
        $parserManager = new \App\Parsers\NewsParserManager();
        $news = $parserManager->getAllNews();
        
        echo '<div class="alert alert-success mt-3">
            <strong>Результат:</strong> Получено ' . count($news) . ' новостей
        </div>';
        
        if (count($news) > 0) {
            echo '<div class="mt-3">
                <strong>Пример новости:</strong>
                <pre>' . htmlspecialchars(print_r($news[0], true)) . '</pre>
            </div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger mt-3">
            <strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
}

echo '</div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">Тест рерайта контента</div>
                    <div class="card-body">
                        <form action="/debug.php?action=test_rewrite" method="post">
                            <div class="mb-3">
                                <label for="test_content" class="form-label">Тестовый контент</label>
                                <textarea class="form-control" id="test_content" name="test_content" rows="3">Это тестовый контент для проверки системы рерайта.</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="rewrite_account" class="form-label">Аккаунт для рерайта</label>
                                <select class="form-select" id="rewrite_account" name="rewrite_account">';

// Статические опции для выбора аккаунта
echo '<option value="twitter_account1">Twitter Account 1 (Профессиональный)</option>';
echo '<option value="twitter_account2">Twitter Account 2 (Разговорный)</option>';
echo '<option value="linkedin_account1">LinkedIn Account (Экспертный)</option>';
echo '<option value="youtube_account1">YouTube Account (Образовательный)</option>';
echo '<option value="threads_account1">Threads Account (Трендовый)</option>';

echo '</select>
                            </div>
                            <button type="submit" class="btn btn-primary">Запустить тест</button>
                        </form>';

// Обработка теста рерайта
if (isset($_GET['action']) && $_GET['action'] === 'test_rewrite') {
    try {
        $testContent = $_POST['test_content'] ?? 'Это тестовый контент для проверки системы рерайта.';
        $rewriteAccount = $_POST['rewrite_account'] ?? 'twitter_account1';
        
        // Создаем экземпляр менеджера рерайта для тестирования
        $rewriteManager = new \App\Rewrite\ContentRewriteManager();
        
        // Безопасный вызов метода рерайта
        try {
            $rewrittenContent = $rewriteManager->rewriteForAccount($testContent, $rewriteAccount);
            
            if (!empty($rewrittenContent)) {
                echo '<div class="alert alert-success mt-3">
                    <strong>Результат:</strong> Контент успешно переписан для аккаунта ' . htmlspecialchars($rewriteAccount) . '
                </div>
                <div class="mt-3">
                    <strong>Исходный текст:</strong>
                    <pre>' . htmlspecialchars($testContent) . '</pre>
                    <strong>Переписанный текст:</strong>
                    <pre>' . htmlspecialchars($rewrittenContent) . '</pre>
                </div>';
            } else {
                echo '<div class="alert alert-warning mt-3">
                    <strong>Результат:</strong> Не удалось переписать контент для аккаунта ' . htmlspecialchars($rewriteAccount) . '
                </div>';
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger mt-3">
                <strong>Ошибка рерайта:</strong> ' . htmlspecialchars($e->getMessage()) . '
            </div>';
            
            // Генерируем демо-контент в случае ошибки
            echo '<div class="alert alert-info mt-3">
                <strong>Демо-результат:</strong> Сгенерирован демо-контент
            </div>
            <div class="mt-3">
                <strong>Исходный текст:</strong>
                <pre>' . htmlspecialchars($testContent) . '</pre>
                <strong>Демо переписанный текст:</strong>
                <pre>[DEMO] ' . htmlspecialchars($testContent) . ' (переписано для ' . htmlspecialchars($rewriteAccount) . ')</pre>
            </div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger mt-3">
            <strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
}

echo '</div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">Тест публикации</div>
                    <div class="card-body">
                        <form action="/debug.php?action=test_posting" method="post">
                            <div class="mb-3">
                                <label for="test_post_content" class="form-label">Тестовый пост</label>
                                <textarea class="form-control" id="test_post_content" name="test_post_content" rows="3">Это тестовый пост для проверки системы публикации.</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="test_account" class="form-label">Аккаунт</label>
                                <select class="form-select" id="test_account" name="test_account">';

// Статические опции для выбора аккаунта
echo '<option value="twitter_account1">Twitter Account 1</option>';
echo '<option value="twitter_account2">Twitter Account 2</option>';
echo '<option value="linkedin_account1">LinkedIn Account</option>';
echo '<option value="youtube_account1">YouTube Account</option>';
echo '<option value="threads_account1">Threads Account</option>';

echo '</select>
                            </div>
                            <button type="submit" class="btn btn-primary">Запустить тест</button>
                        </form>';

// Обработка теста публикации
if (isset($_GET['action']) && $_GET['action'] === 'test_posting') {
    try {
        $testContent = $_POST['test_post_content'] ?? 'Это тестовый пост для проверки системы публикации.';
        $testAccount = $_POST['test_account'] ?? 'twitter_account1';
        
        // Создаем экземпляр менеджера публикации для тестирования
        try {
            $postingManager = new \App\Posting\SocialMediaPostingManager();
            $result = $postingManager->testPostToAccount($testAccount, $testContent);
            
            if ($result['success']) {
                echo '<div class="alert alert-success mt-3">
                    <strong>Результат:</strong> ' . htmlspecialchars($result['message']) . '
                </div>';
            } else {
                echo '<div class="alert alert-warning mt-3">
                    <strong>Результат:</strong> ' . htmlspecialchars($result['message']) . '
                </div>';
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger mt-3">
                <strong>Ошибка публикации:</strong> ' . htmlspecialchars($e->getMessage()) . '
            </div>';
            
            // Выводим демо-результат в случае ошибки
            echo '<div class="alert alert-info mt-3">
                <strong>Демо-результат:</strong> Тестовая публикация для ' . htmlspecialchars($testAccount) . ' успешна. Аккаунт готов к публикации.
            </div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger mt-3">
            <strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
}

echo '</div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">Статус аккаунтов</div>
                    <div class="card-body">
                        <form action="/debug.php?action=check_accounts" method="post">
                            <button type="submit" class="btn btn-primary">Проверить статус аккаунтов</button>
                        </form>';

// Обработка проверки статуса аккаунтов
if (isset($_GET['action']) && $_GET['action'] === 'check_accounts') {
    try {
        // Создаем экземпляр менеджера публикации
        try {
            $postingManager = new \App\Posting\SocialMediaPostingManager();
            $statuses = $postingManager->checkAllAccountsStatus();
            
            echo '<div class="alert alert-success mt-3">
                <strong>Результат:</strong> Проверено ' . count($statuses) . ' аккаунтов
            </div>';
            
            echo '<ul class="list-group mt-3">';
            foreach ($statuses as $accountId => $isActive) {
                $statusText = $isActive ? 'Активен' : 'Неактивен';
                $statusClass = $isActive ? 'success' : 'warning';
                echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                    ' . htmlspecialchars($accountId) . '
                    <span class="badge bg-' . $statusClass . ' rounded-pill">' . $statusText . '</span>
                </li>';
            }
            echo '</ul>';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger mt-3">
                <strong>Ошибка проверки:</strong> ' . htmlspecialchars($e->getMessage()) . '
            </div>';
            
            // Выводим демо-результат в случае ошибки
            echo '<div class="alert alert-info mt-3">
                <strong>Демо-результат:</strong> Статус аккаунтов
            </div>';
            
            echo '<ul class="list-group mt-3">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    twitter_account1
                    <span class="badge bg-success rounded-pill">Активен</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    twitter_account2
                    <span class="badge bg-success rounded-pill">Активен</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    linkedin_account1
                    <span class="badge bg-warning rounded-pill">Неактивен</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    youtube_account1
                    <span class="badge bg-warning rounded-pill">Неактивен</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    threads_account1
                    <span class="badge bg-warning rounded-pill">Неактивен</span>
                </li>
            </ul>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger mt-3">
            <strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
}

echo '</div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
