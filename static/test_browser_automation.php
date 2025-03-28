<?php
// Тестовый скрипт для проверки функциональности системы с антидетект браузерами

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\LogManager;
use App\Browser\BrowserProfileManager;
use App\Browser\Posting\TwitterPoster;
use App\Browser\Posting\LinkedInPoster;

// Инициализация логгера
$logger = LogManager::getInstance();
$logger->info('Starting browser automation test');

// Загрузка конфигурации
$config = Config::getInstance();

// Создание менеджера профилей браузеров
$profileManager = new BrowserProfileManager();

// Функция для тестирования постинга в Twitter
function testTwitterPosting($profileManager, $profileId, $content) {
    global $logger;
    
    $logger->info('Testing Twitter posting', ['profile_id' => $profileId]);
    
    try {
        // Запуск профиля браузера
        $profileData = $profileManager->startProfile($profileId);
        
        if (!$profileData) {
            $logger->error('Failed to start browser profile', ['profile_id' => $profileId]);
            return false;
        }
        
        $logger->info('Browser profile started successfully', ['profile_id' => $profileId]);
        
        // Получение Selenium WebDriver
        $driver = $profileManager->getSeleniumDriver($profileId, $profileData['connection_data']);
        
        if (!$driver) {
            $logger->error('Failed to get Selenium WebDriver', ['profile_id' => $profileId]);
            $profileManager->stopProfile($profileId);
            return false;
        }
        
        $logger->info('Selenium WebDriver created successfully');
        
        // Создание постера для Twitter
        $credentials = [
            'username' => 'your_twitter_username',
            'password' => 'your_twitter_password'
        ];
        
        $twitterPoster = new TwitterPoster($driver, $credentials);
        
        // Авторизация в Twitter
        if (!$twitterPoster->login()) {
            $logger->error('Failed to login to Twitter');
            $profileManager->stopProfile($profileId);
            return false;
        }
        
        $logger->info('Successfully logged in to Twitter');
        
        // Публикация контента
        $success = $twitterPoster->post($content);
        
        if ($success) {
            $logger->info('Content successfully posted to Twitter');
        } else {
            $logger->error('Failed to post content to Twitter');
        }
        
        // Выход из аккаунта
        $twitterPoster->logout();
        
        // Остановка профиля браузера
        $profileManager->stopProfile($profileId);
        
        return $success;
    } catch (Exception $e) {
        $logger->error('Exception during Twitter posting test', [
            'error' => $e->getMessage()
        ]);
        
        // Остановка профиля браузера в случае ошибки
        $profileManager->stopProfile($profileId);
        
        return false;
    }
}

// Функция для тестирования постинга в LinkedIn
function testLinkedInPosting($profileManager, $profileId, $content) {
    global $logger;
    
    $logger->info('Testing LinkedIn posting', ['profile_id' => $profileId]);
    
    try {
        // Запуск профиля браузера
        $profileData = $profileManager->startProfile($profileId);
        
        if (!$profileData) {
            $logger->error('Failed to start browser profile', ['profile_id' => $profileId]);
            return false;
        }
        
        $logger->info('Browser profile started successfully', ['profile_id' => $profileId]);
        
        // Получение Selenium WebDriver
        $driver = $profileManager->getSeleniumDriver($profileId, $profileData['connection_data']);
        
        if (!$driver) {
            $logger->error('Failed to get Selenium WebDriver', ['profile_id' => $profileId]);
            $profileManager->stopProfile($profileId);
            return false;
        }
        
        $logger->info('Selenium WebDriver created successfully');
        
        // Создание постера для LinkedIn
        $credentials = [
            'username' => 'your_linkedin_email',
            'password' => 'your_linkedin_password'
        ];
        
        $linkedInPoster = new LinkedInPoster($driver, $credentials);
        
        // Авторизация в LinkedIn
        if (!$linkedInPoster->login()) {
            $logger->error('Failed to login to LinkedIn');
            $profileManager->stopProfile($profileId);
            return false;
        }
        
        $logger->info('Successfully logged in to LinkedIn');
        
        // Публикация контента
        $success = $linkedInPoster->post($content);
        
        if ($success) {
            $logger->info('Content successfully posted to LinkedIn');
        } else {
            $logger->error('Failed to post content to LinkedIn');
        }
        
        // Выход из аккаунта
        $linkedInPoster->logout();
        
        // Остановка профиля браузера
        $profileManager->stopProfile($profileId);
        
        return $success;
    } catch (Exception $e) {
        $logger->error('Exception during LinkedIn posting test', [
            'error' => $e->getMessage()
        ]);
        
        // Остановка профиля браузера в случае ошибки
        $profileManager->stopProfile($profileId);
        
        return false;
    }
}

// Обработка формы
$message = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    $profileId = $_POST['profile_id'] ?? '';
    $platform = $_POST['platform'] ?? '';
    
    if (empty($content)) {
        $error = 'Пожалуйста, введите текст для публикации';
    } elseif (empty($profileId)) {
        $error = 'Пожалуйста, выберите профиль браузера';
    } elseif (empty($platform)) {
        $error = 'Пожалуйста, выберите платформу для публикации';
    } else {
        // Выполняем тестирование в зависимости от выбранной платформы
        if ($platform === 'twitter') {
            $success = testTwitterPosting($profileManager, $profileId, $content);
        } elseif ($platform === 'linkedin') {
            $success = testLinkedInPosting($profileManager, $profileId, $content);
        } else {
            $error = 'Неизвестная платформа: ' . $platform;
        }
        
        if ($success) {
            $message = 'Контент успешно опубликован в ' . ucfirst($platform);
        } else {
            $error = 'Ошибка при публикации контента. Проверьте логи для получения дополнительной информации.';
        }
    }
}

// Получение списка профилей браузеров
$profiles = $profileManager->getProfiles();

// HTML страницы
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест автоматизации с антидетект браузерами</title>
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
        <h1>Тест автоматизации с антидетект браузерами</h1>
        
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
                <label for="profile_id">Выберите профиль браузера:</label>
                <select name="profile_id" id="profile_id" required>
                    <option value="">-- Выберите профиль --</option>
                    <?php foreach ($profiles as $profile): ?>
                        <option value="<?php echo htmlspecialchars($profile['id']); ?>">
                            <?php echo htmlspecialchars($profile['name']); ?> 
                            (<?php echo htmlspecialchars($profile['browser_type']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="platform">Выберите платформу:</label>
                <select name="platform" id="platform" required>
                    <option value="">-- Выберите платформу --</option>
                    <option value="twitter">Twitter</option>
                    <option value="linkedin">LinkedIn</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="content">Текст для публикации:</label>
                <textarea name="content" id="content" required placeholder="Введите текст для публикации..."></textarea>
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
