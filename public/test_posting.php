<?php
// Тестовый скрипт для публикации в социальных сетях
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\LogManager;
use App\Posting\TwitterPoster;
use App\Posting\LinkedInPoster;

// Инициализация логгера
$logger = LogManager::getInstance();
$logger->info('Starting social media posting test');

// Загрузка конфигурации
$config = Config::getInstance();

// Обработка формы
$message = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    $accountId = $_POST['account_id'] ?? '';
    
    if (empty($content)) {
        $error = 'Пожалуйста, введите текст для публикации';
    } elseif (empty($accountId)) {
        $error = 'Пожалуйста, выберите аккаунт для публикации';
    } else {
        // Определяем тип аккаунта
        $accountType = explode('_', $accountId)[0] ?? '';
        
        try {
            $poster = null;
            
            // Создаем соответствующий постер
            switch ($accountType) {
                case 'twitter':
                    $poster = new TwitterPoster($accountId);
                    break;
                case 'linkedin':
                    $poster = new LinkedInPoster($accountId);
                    break;
                default:
                    $error = 'Неизвестный тип аккаунта: ' . $accountType;
                    break;
            }
            
            if ($poster) {
                // Проверяем статус аккаунта
                if ($poster->checkAccountStatus()) {
                    // Публикуем контент
                    $logger->info('Attempting to post content', [
                        'account_id' => $accountId,
                        'content_length' => strlen($content)
                    ]);
                    
                    // В тестовом режиме не выполняем реальную публикацию
                    $testMode = isset($_POST['test_mode']) && $_POST['test_mode'] === 'on';
                    
                    if ($testMode) {
                        $logger->info('Test mode enabled, simulating successful post');
                        $success = true;
                        $message = 'Тестовый режим: Симуляция успешной публикации в ' . $accountId;
                    } else {
                        // Реальная публикация
                        $success = $poster->post($content);
                        
                        if ($success) {
                            $message = 'Контент успешно опубликован в ' . $accountId;
                            $logger->info('Content successfully posted', [
                                'account_id' => $accountId
                            ]);
                        } else {
                            $error = 'Ошибка при публикации контента. Проверьте логи для получения дополнительной информации.';
                            $logger->error('Failed to post content', [
                                'account_id' => $accountId
                            ]);
                        }
                    }
                } else {
                    $error = 'Аккаунт ' . $accountId . ' неактивен. Проверьте настройки.';
                    $logger->error('Account is inactive', [
                        'account_id' => $accountId
                    ]);
                }
            }
        } catch (Exception $e) {
            $error = 'Произошла ошибка: ' . $e->getMessage();
            $logger->error('Exception during posting', [
                'error' => $e->getMessage(),
                'account_id' => $accountId
            ]);
        }
    }
}

// Получение списка доступных аккаунтов
$accounts = [
    'twitter_account1' => 'Twitter Account 1',
    'twitter_account2' => 'Twitter Account 2',
    'linkedin_account1' => 'LinkedIn Account'
];

// HTML страницы
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест публикации в социальных сетях</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 150px;
            resize: vertical;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #2980b9;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .checkbox-group {
            margin-top: 10px;
        }
        .checkbox-group label {
            display: inline;
            font-weight: normal;
            margin-left: 5px;
        }
        .log-container {
            background-color: #f8f9fa;
            border: 1px solid #eee;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            max-height: 300px;
            overflow-y: auto;
        }
        .log-entry {
            margin-bottom: 5px;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Тест публикации в социальных сетях</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="account_id">Выберите аккаунт:</label>
                <select name="account_id" id="account_id" required>
                    <option value="">-- Выберите аккаунт --</option>
                    <?php foreach ($accounts as $id => $name): ?>
                        <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="content">Текст для публикации:</label>
                <textarea name="content" id="content" required placeholder="Введите текст для публикации..."></textarea>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" name="test_mode" id="test_mode" checked>
                <label for="test_mode">Тестовый режим (без реальной публикации)</label>
            </div>
            
            <div class="form-group">
                <button type="submit">Опубликовать</button>
            </div>
        </form>
        
        <div class="log-container">
            <h3>Последние записи лога:</h3>
            <?php
            $logFile = __DIR__ . '/../logs/app.log';
            if (file_exists($logFile)) {
                $logs = file($logFile);
                $logs = array_slice($logs, -10); // Последние 10 записей
                foreach ($logs as $log) {
                    echo '<div class="log-entry">' . htmlspecialchars($log) . '</div>';
                }
            } else {
                echo '<div class="log-entry">Файл лога не найден</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
