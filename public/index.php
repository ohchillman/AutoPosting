<?php
// Главная страница системы автоматизации с антидетект браузерами

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\LogManager;
use App\Browser\BrowserProfileManager;

// Инициализация логгера
$logger = LogManager::getInstance();
$logger->info('Accessing main page');

// Загрузка конфигурации
$config = Config::getInstance();

// Создание менеджера профилей браузеров
$profileManager = new BrowserProfileManager();

// Получение списка профилей браузеров
$profiles = $profileManager->getProfiles();

// Обработка формы синхронизации профилей
$syncMessage = '';
$syncError = '';

if (isset($_POST['sync_profiles'])) {
    $logger->info('Syncing browser profiles');
    
    $success = $profileManager->syncProfiles();
    
    if ($success) {
        $syncMessage = 'Профили успешно синхронизированы';
        $profiles = $profileManager->getProfiles(); // Обновляем список профилей
    } else {
        $syncError = 'Ошибка при синхронизации профилей';
    }
}

// HTML страницы
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система автоматизации с антидетект браузерами</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .btn-success {
            background-color: #2ecc71;
        }
        .btn-success:hover {
            background-color: #27ae60;
        }
        .btn-danger {
            background-color: #e74c3c;
        }
        .btn-danger:hover {
            background-color: #c0392b;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        .status-inactive {
            color: #7f8c8d;
        }
        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .feature-card {
            flex: 1;
            min-width: 300px;
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            border-left: 4px solid #3498db;
        }
        .feature-card h3 {
            margin-top: 0;
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Система автоматизации с антидетект браузерами</h1>
        
        <?php if ($syncMessage): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($syncMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($syncError): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($syncError); ?>
            </div>
        <?php endif; ?>
        
        <div class="features">
            <div class="feature-card">
                <h3>Управление профилями</h3>
                <p>Централизованное управление профилями AdsPower и Dolphin Anty через единый интерфейс.</p>
            </div>
            <div class="feature-card">
                <h3>Автоматизация постинга</h3>
                <p>Автоматическая публикация контента в социальных сетях с использованием Selenium.</p>
            </div>
            <div class="feature-card">
                <h3>Мультиаккаунтинг</h3>
                <p>Работа с множеством аккаунтов социальных сетей через антидетект браузеры.</p>
            </div>
        </div>
        
        <div class="card">
            <h2>Профили браузеров</h2>
            
            <form method="post" action="">
                <button type="submit" name="sync_profiles" class="btn btn-success">Синхронизировать профили</button>
                <a href="test_browser_automation.php" class="btn">Тестировать автоматизацию</a>
            </form>
            
            <br>
            
            <?php if (empty($profiles)): ?>
                <p>Профили не найдены. Нажмите кнопку "Синхронизировать профили" для получения профилей из антидетект браузеров.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Тип браузера</th>
                            <th>ID профиля</th>
                            <th>Статус</th>
                            <th>Последнее использование</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profiles as $profile): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($profile['id']); ?></td>
                                <td><?php echo htmlspecialchars($profile['name']); ?></td>
                                <td><?php echo htmlspecialchars($profile['browser_type']); ?></td>
                                <td><?php echo htmlspecialchars($profile['profile_id']); ?></td>
                                <td>
                                    <?php if ($profile['is_active']): ?>
                                        <span class="status-active">Активен</span>
                                    <?php else: ?>
                                        <span class="status-inactive">Неактивен</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $profile['last_used'] ? htmlspecialchars($profile['last_used']) : 'Никогда'; ?></td>
                                <td>
                                    <a href="test_browser_automation.php?profile_id=<?php echo htmlspecialchars($profile['id']); ?>" class="btn">Тестировать</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Документация</h2>
            <p>Для получения подробной информации об установке и использовании системы, пожалуйста, обратитесь к файлу README.md в корне проекта.</p>
            <p>Основные разделы документации:</p>
            <ul>
                <li>Требования к системе</li>
                <li>Установка и настройка</li>
                <li>Настройка антидетект браузеров</li>
                <li>Использование системы</li>
                <li>Устранение неполадок</li>
            </ul>
        </div>
    </div>
</body>
</html>
