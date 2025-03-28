<?php

namespace App\Controllers;

use App\Core\LogManager;
use App\Core\Config;
use App\Core\SettingsManager;

/**
 * Контроллер для управления настройками системы через веб-интерфейс
 */
class SettingsController
{
    private $logger;
    private $config;
    private $settingsManager;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->settingsManager = new SettingsManager();
    }
    
    /**
     * Отображение страницы настроек
     * 
     * @return string HTML-контент
     */
    public function index(): string
    {
        // Получаем все текущие настройки
        $settings = $this->settingsManager->getAllSettings();
        
        // Формируем HTML-контент
        $html = $this->renderHeader('Настройки системы');
        
        $html .= '
        <div class="container mt-4">
            <h1>Настройки системы</h1>
            
            <div class="alert alert-info">
                <p>Здесь вы можете настроить все параметры системы, включая API-ключи для социальных сетей и других сервисов.</p>
                <p>После изменения настроек нажмите кнопку "Сохранить" в соответствующем разделе.</p>
            </div>
            
            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">Общие</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="database-tab" data-bs-toggle="tab" data-bs-target="#database" type="button" role="tab">База данных</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="parser-tab" data-bs-toggle="tab" data-bs-target="#parser" type="button" role="tab">Парсер новостей</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="make-tab" data-bs-toggle="tab" data-bs-target="#make" type="button" role="tab">Make.com</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="twitter-tab" data-bs-toggle="tab" data-bs-target="#twitter" type="button" role="tab">Twitter</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="linkedin-tab" data-bs-toggle="tab" data-bs-target="#linkedin" type="button" role="tab">LinkedIn</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="youtube-tab" data-bs-toggle="tab" data-bs-target="#youtube" type="button" role="tab">YouTube</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="dolphin-tab" data-bs-toggle="tab" data-bs-target="#dolphin" type="button" role="tab">Dolphin Anty</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="proxy-tab" data-bs-toggle="tab" data-bs-target="#proxy" type="button" role="tab">Прокси</button>
                </li>
            </ul>
            
            <div class="tab-content p-4 border border-top-0 rounded-bottom" id="settingsTabsContent">
                <!-- Общие настройки -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <h3>Общие настройки</h3>
                    <form action="/settings/save" method="post">
                        <input type="hidden" name="section" value="app">
                        
                        <div class="mb-3">
                            <label for="app_env" class="form-label">Окружение</label>
                            <select class="form-select" id="app_env" name="app_env">
                                <option value="development" ' . ($settings['app']['env'] === 'development' ? 'selected' : '') . '>Разработка</option>
                                <option value="production" ' . ($settings['app']['env'] === 'production' ? 'selected' : '') . '>Продакшн</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="app_debug" class="form-label">Режим отладки</label>
                            <select class="form-select" id="app_debug" name="app_debug">
                                <option value="1" ' . ($settings['app']['debug'] ? 'selected' : '') . '>Включен</option>
                                <option value="0" ' . (!$settings['app']['debug'] ? 'selected' : '') . '>Выключен</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="app_url" class="form-label">URL приложения</label>
                            <input type="text" class="form-control" id="app_url" name="app_url" value="' . htmlspecialchars($settings['app']['url']) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
                
                <!-- Настройки базы данных -->
                <div class="tab-pane fade" id="database" role="tabpanel">
                    <h3>Настройки базы данных</h3>
                    <form action="/settings/save" method="post">
                        <input type="hidden" name="section" value="database">
                        
                        <div class="mb-3">
                            <label for="db_connection" class="form-label">Тип соединения</label>
                            <select class="form-select" id="db_connection" name="db_connection">
                                <option value="mysql" ' . ($settings['database']['connection'] === 'mysql' ? 'selected' : '') . '>MySQL</option>
                                <option value="pgsql" ' . ($settings['database']['connection'] === 'pgsql' ? 'selected' : '') . '>PostgreSQL</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_host" class="form-label">Хост</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="' . htmlspecialchars($settings['database']['host']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_port" class="form-label">Порт</label>
                            <input type="text" class="form-control" id="db_port" name="db_port" value="' . htmlspecialchars($settings['database']['port']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_database" class="form-label">Имя базы данных</label>
                            <input type="text" class="form-control" id="db_database" name="db_database" value="' . htmlspecialchars($settings['database']['database']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_username" class="form-label">Имя пользователя</label>
                            <input type="text" class="form-control" id="db_username" name="db_username" value="' . htmlspecialchars($settings['database']['username']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="db_password" name="db_password" value="' . htmlspecialchars($settings['database']['password']) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
                
                <!-- Настройки парсера новостей -->
                <div class="tab-pane fade" id="parser" role="tabpanel">
                    <h3>Настройки парсера новостей</h3>
                    <form action="/settings/save" method="post">
                        <input type="hidden" name="section" value="parser">
                        
                        <div class="mb-3">
                            <label for="parser_source_1" class="form-label">Источник новостей 1</label>
                            <input type="text" class="form-control" id="parser_source_1" name="parser_source_1" value="' . htmlspecialchars($settings['parser']['sources'][0]) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="parser_source_2" class="form-label">Источник новостей 2</label>
                            <input type="text" class="form-control" id="parser_source_2" name="parser_source_2" value="' . htmlspecialchars($settings['parser']['sources'][1]) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="parser_source_3" class="form-label">Источник новостей 3</label>
                            <input type="text" class="form-control" id="parser_source_3" name="parser_source_3" value="' . htmlspecialchars($settings['parser']['sources'][2]) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="parser_source_4" class="form-label">Источник новостей 4</label>
                            <input type="text" class="form-control" id="parser_source_4" name="parser_source_4" value="' . htmlspecialchars($settings['parser']['sources'][3]) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
                
                <!-- Настройки Make.com -->
                <div class="tab-pane fade" id="make" role="tabpanel">
                    <h3>Настройки Make.com</h3>
                    <form action="/settings/save" method="post">
                        <input type="hidden" name="section" value="make">
                        
                        <div class="mb-3">
                            <label for="make_api_key" class="form-label">API ключ</label>
                            <input type="text" class="form-control" id="make_api_key" name="make_api_key" value="' . htmlspecialchars($settings['make']['api_key']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="make_webhook_url" class="form-label">Webhook URL</label>
                            <input type="text" class="form-control" id="make_webhook_url" name="make_webhook_url" value="' . htmlspecialchars($settings['make']['webhook_url']) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
                
                <!-- Настройки Twitter -->
                <div class="tab-pane fade" id="twitter" role="tabpanel">
                    <h3>Настройки Twitter</h3>
                    <form action="/settings/save" method="post">
                        <input type="hidden" name="section" value="twitter">
                        
                        <div class="mb-3">
                            <label for="twitter_api_key" class="form-label">API ключ</label>
                            <input type="text" class="form-control" id="twitter_api_key" name="twitter_api_key" value="' . htmlspecialchars($settings['social']['twitter']['api_key']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="twitter_api_secret" class="form-label">API секрет</label>
                            <input type="text" class="form-control" id="twitter_api_secret" name="twitter_api_secret" value="' . htmlspecialchars($settings['social']['twitter']['api_secret']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="twitter_access_token" class="form-label">Access Token</label>
                            <input type="text" class="form-control" id="twitter_access_token" name="twitter_access_token" value="' . htmlspecialchars($settings['social']['twitter']['access_token']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="twitter_access_secret" class="form-label">Access Secret</label>
                            <input type="text" class="form-control" id="twitter_access_secret" name="twitter_access_secret" value="' . htmlspecialchars($settings['social']['twitter']['access_secret']) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
                
                <!-- Настройки LinkedIn -->
                <div class="tab-pane fade" id="linkedin" role="tabpanel">
                    <h3>Настройки LinkedIn</h3>
                    <form action="/settings/save" method="post">
                        <input type="hidden" name="section" value="linkedin">
                        
                        <div class="mb-3">
                            <label for="linkedin_client_id" class="form-label">Client ID</label>
                            <input type="text" class="form-control" id="linkedin_client_id" name="linkedin_client_id" value="' . htmlspecialchars($settings['social']['linkedin']['client_id']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="linkedin_client_secret" class="form-label">Client Secret</label>
                            <input type="text" class="form-control" id="linkedin_client_secret" name="linkedin_client_secret" value="' . htmlspecialchars($settings['social']['linkedin']['client_secret']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="linkedin_access_token" class="form-label">Access Token</label>
                            <input type="text" class="form-control" id="linkedin_access_token" name="linkedin_access_token" value="' . htmlspecialchars($settings['social']['linkedin']['access_token']) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
                
                <!-- Настройки YouTube -->
                <div class="tab-pane fade" id="youtube" role="tabpanel">
                    <h3>Настройки YouTube</h3>
                    <form action="/settings/save" method="post">
                        <input type="hidden" name="section" value="youtube">
                        
                        <div class="mb-3">
                            <label for="youtube_api_key" class="form-label">API ключ</label>
                            <input type="text" class="form-control" id="youtube_api_key" name="youtube_api_key" value="' . htmlspecialchars($settings['social']['youtube']['api_key']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="youtube_client_id" class="form-label">Client ID</label>
                            <input type="text" class="form-control" id="youtube_client_id" name="youtube_client_id" value="' . htmlspecialchars($settings['social']['youtube']['client_id']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="youtube_client_secret" class="form-label">Client Secret</label>
                            <input type="text" class="form-control" id="youtube_client_secret" name="youtube_client_secret" value="' . htmlspecialchars($settings['social']['youtube']['client_secret']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="youtube_refresh_token" class="form-label">Refresh Token</label>
                            <input type="text" class="form-control" id="youtube_refresh_token" name="youtube_refresh_token" value="' . htmlspecialchars($settings['social']['youtube']['refresh_token']) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
                
                <!-- Настройки Dolphin Anty -->
                <div class="tab-pane fade" id="dolphin" role="tabpanel">
                    <h3>Настройки Dolphin Anty</h3>
                    <form action="/settings/save" method="post">
                        <input type="hidden" name="section" value="dolphin">
                        
                        <div class="mb-3">
                            <label for="dolphin_api_key" class="form-label">API ключ</label>
                            <input type="text" class="form-control" id="dolphin_api_key" name="dolphin_api_key" value="' . htmlspecialchars($settings['dolphin']['api_key']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="dolphin_api_url" class="form-label">API URL</label>
                            <input type="text" class="form-control" id="dolphin_api_url" name="dolphin_api_url" value="' . htmlspecialchars($settings['dolphin']['api_url']) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
                
                <!-- Настройки прокси -->
                <div class="tab-pane fade" id="proxy" role="tabpanel">
                    <h3>Настройки прокси</h3>
                    <form action="/settings/save" method="post">
                        <input type="hidden" name="section" value="proxy">
                        
                        <div class="mb-3">
                            <label for="proxy_server" class="form-label">Сервер</label>
                            <input type="text" class="form-control" id="proxy_server" name="proxy_server" value="' . htmlspecialchars($settings['proxy']['server']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="proxy_port" class="form-label">Порт</label>
                            <input type="text" class="form-control" id="proxy_port" name="proxy_port" value="' . htmlspecialchars($settings['proxy']['port']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="proxy_username" class="form-label">Имя пользователя</label>
                            <input type="text" class="form-control" id="proxy_username" name="proxy_username" value="' . htmlspecialchars($settings['proxy']['username']) . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="proxy_password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="proxy_password" name="proxy_password" value="' . htmlspecialchars($settings['proxy']['password']) . '">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="/" class="btn btn-secondary">Вернуться на главную</a>
                <a href="/settings/test" class="btn btn-warning">Проверить настройки</a>
            </div>
        </div>';
        
        $html .= $this->renderFooter();
        
        return $html;
    }
    
    /**
     * Сохранение настроек
     * 
     * @param array $params Параметры запроса
     * @return string HTML-контент с результатами
     */
    public function save(array $params): string
    {
        $section = $params['section'] ?? '';
        $result = false;
        $message = '';
        
        try {
            switch ($section) {
                case 'app':
                    $settings = [
                        'env' => $params['app_env'] ?? 'development',
                        'debug' => filter_var($params['app_debug'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'url' => $params['app_url'] ?? 'http://localhost:8000'
                    ];
                    $result = $this->settingsManager->saveSettings('app', $settings);
                    break;
                    
                case 'database':
                    $settings = [
                        'connection' => $params['db_connection'] ?? 'mysql',
                        'host' => $params['db_host'] ?? '127.0.0.1',
                        'port' => $params['db_port'] ?? '3306',
                        'database' => $params['db_database'] ?? 'social_media_automation',
                        'username' => $params['db_username'] ?? 'root',
                        'password' => $params['db_password'] ?? ''
                    ];
                    $result = $this->settingsManager->saveSettings('database', $settings);
                    break;
                    
                case 'parser':
                    $settings = [
                        'sources' => [
                            $params['parser_source_1'] ?? 'https://example.com/news',
                            $params['parser_source_2'] ?? 'https://example2.com/news',
                            $params['parser_source_3'] ?? 'https://example3.com/news',
                            $params['parser_source_4'] ?? 'https://example4.com/news'
                        ]
                    ];
                    $result = $this->settingsManager->saveSettings('parser', $settings);
                    break;
                    
                case 'make':
                    $settings = [
                        'api_key' => $params['make_api_key'] ?? '',
                        'webhook_url' => $params['make_webhook_url'] ?? ''
                    ];
                    $result = $this->settingsManager->saveSettings('make', $settings);
                    break;
                    
                case 'twitter':
                    $settings = [
                        'api_key' => $params['twitter_api_key'] ?? '',
                        'api_secret' => $params['twitter_api_secret'] ?? '',
                        'access_token' => $params['twitter_access_token'] ?? '',
                        'access_secret' => $params['twitter_access_secret'] ?? ''
                    ];
                    $result = $this->settingsManager->saveSettings('social.twitter', $settings);
                    break;
                    
                case 'linkedin':
                    $settings = [
                        'client_id' => $params['linkedin_client_id'] ?? '',
                        'client_secret' => $params['linkedin_client_secret'] ?? '',
                        'access_token' => $params['linkedin_access_token'] ?? ''
                    ];
                    $result = $this->settingsManager->saveSettings('social.linkedin', $settings);
                    break;
                    
                case 'youtube':
                    $settings = [
                        'api_key' => $params['youtube_api_key'] ?? '',
                        'client_id' => $params['youtube_client_id'] ?? '',
                        'client_secret' => $params['youtube_client_secret'] ?? '',
                        'refresh_token' => $params['youtube_refresh_token'] ?? ''
                    ];
                    $result = $this->settingsManager->saveSettings('social.youtube', $settings);
                    break;
                    
                case 'dolphin':
                    $settings = [
                        'api_key' => $params['dolphin_api_key'] ?? '',
                        'api_url' => $params['dolphin_api_url'] ?? 'https://api.dolphin-anty.com/v1'
                    ];
                    $result = $this->settingsManager->saveSettings('dolphin', $settings);
                    break;
                    
                case 'proxy':
                    $settings = [
                        'server' => $params['proxy_server'] ?? '',
                        'port' => $params['proxy_port'] ?? '',
                        'username' => $params['proxy_username'] ?? '',
                        'password' => $params['proxy_password'] ?? ''
                    ];
                    $result = $this->settingsManager->saveSettings('proxy', $settings);
                    break;
                    
                default:
                    $message = 'Неизвестный раздел настроек';
                    break;
            }
            
            if ($result) {
                $message = 'Настройки успешно сохранены';
                $this->logger->info('Settings saved', ['section' => $section]);
            } else {
                $message = 'Ошибка при сохранении настроек';
                $this->logger->error('Error saving settings', ['section' => $section]);
            }
        } catch (\Exception $e) {
            $message = 'Ошибка: ' . $e->getMessage();
            $this->logger->error('Exception saving settings', [
                'section' => $section,
                'error' => $e->getMessage()
            ]);
        }
        
        // Формируем HTML-контент с результатами
        $html = $this->renderHeader('Результат сохранения настроек');
        
        $html .= '
        <div class="container mt-4">
            <h1>Результат сохранения настроек</h1>
            
            <div class="alert ' . ($result ? 'alert-success' : 'alert-danger') . '">
                ' . htmlspecialchars($message) . '
            </div>
            
            <div class="mt-4">
                <a href="/settings" class="btn btn-primary">Вернуться к настройкам</a>
                <a href="/" class="btn btn-secondary">На главную</a>
            </div>
        </div>';
        
        $html .= $this->renderFooter();
        
        return $html;
    }
    
    /**
     * Тестирование настроек
     * 
     * @return string HTML-контент с результатами
     */
    public function test(): string
    {
        $results = [];
        
        try {
            // Тестирование соединения с базой данных
            $dbResult = $this->settingsManager->testDatabaseConnection();
            $results['database'] = [
                'success' => $dbResult,
                'message' => $dbResult ? 'Соединение с базой данных успешно' : 'Ошибка соединения с базой данных'
            ];
            
            // Тестирование парсера новостей
            $parserResult = $this->settingsManager->testParser();
            $results['parser'] = [
                'success' => $parserResult['success'],
                'message' => $parserResult['message'],
                'details' => $parserResult['details'] ?? []
            ];
            
            // Тестирование Make.com
            $makeResult = $this->settingsManager->testMakeConnection();
            $results['make'] = [
                'success' => $makeResult,
                'message' => $makeResult ? 'Соединение с Make.com успешно' : 'Ошибка соединения с Make.com'
            ];
            
            // Тестирование социальных сетей
            $socialResults = $this->settingsManager->testSocialNetworks();
            $results['social'] = $socialResults;
            
            // Тестирование Dolphin Anty
            $dolphinResult = $this->settingsManager->testDolphinConnection();
            $results['dolphin'] = [
                'success' => $dolphinResult,
                'message' => $dolphinResult ? 'Соединение с Dolphin Anty успешно' : 'Ошибка соединения с Dolphin Anty'
            ];
            
            // Тестирование прокси
            $proxyResult = $this->settingsManager->testProxyConnection();
            $results['proxy'] = [
                'success' => $proxyResult,
                'message' => $proxyResult ? 'Соединение через прокси успешно' : 'Ошибка соединения через прокси'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Error testing settings', ['error' => $e->getMessage()]);
        }
        
        // Формируем HTML-контент с результатами
        $html = $this->renderHeader('Результаты тестирования настроек');
        
        $html .= '
        <div class="container mt-4">
            <h1>Результаты тестирования настроек</h1>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>База данных</h5>
                </div>
                <div class="card-body">
                    <div class="alert ' . ($results['database']['success'] ? 'alert-success' : 'alert-danger') . '">
                        ' . htmlspecialchars($results['database']['message']) . '
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Парсер новостей</h5>
                </div>
                <div class="card-body">
                    <div class="alert ' . ($results['parser']['success'] ? 'alert-success' : 'alert-danger') . '">
                        ' . htmlspecialchars($results['parser']['message']) . '
                    </div>';
                    
        if (!empty($results['parser']['details'])) {
            $html .= '
                    <h6>Детали:</h6>
                    <ul>';
            foreach ($results['parser']['details'] as $source => $status) {
                $html .= '
                        <li>' . htmlspecialchars($source) . ': <span class="badge ' . ($status ? 'bg-success' : 'bg-danger') . '">' . ($status ? 'Доступен' : 'Недоступен') . '</span></li>';
            }
            $html .= '
                    </ul>';
        }
                    
        $html .= '
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Make.com</h5>
                </div>
                <div class="card-body">
                    <div class="alert ' . ($results['make']['success'] ? 'alert-success' : 'alert-danger') . '">
                        ' . htmlspecialchars($results['make']['message']) . '
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Социальные сети</h5>
                </div>
                <div class="card-body">
                    <h6>Twitter</h6>
                    <div class="alert ' . ($results['social']['twitter']['success'] ? 'alert-success' : 'alert-danger') . '">
                        ' . htmlspecialchars($results['social']['twitter']['message']) . '
                    </div>
                    
                    <h6>LinkedIn</h6>
                    <div class="alert ' . ($results['social']['linkedin']['success'] ? 'alert-success' : 'alert-danger') . '">
                        ' . htmlspecialchars($results['social']['linkedin']['message']) . '
                    </div>
                    
                    <h6>YouTube</h6>
                    <div class="alert ' . ($results['social']['youtube']['success'] ? 'alert-success' : 'alert-danger') . '">
                        ' . htmlspecialchars($results['social']['youtube']['message']) . '
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Dolphin Anty</h5>
                </div>
                <div class="card-body">
                    <div class="alert ' . ($results['dolphin']['success'] ? 'alert-success' : 'alert-danger') . '">
                        ' . htmlspecialchars($results['dolphin']['message']) . '
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Прокси</h5>
                </div>
                <div class="card-body">
                    <div class="alert ' . ($results['proxy']['success'] ? 'alert-success' : 'alert-danger') . '">
                        ' . htmlspecialchars($results['proxy']['message']) . '
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="/settings" class="btn btn-primary">Вернуться к настройкам</a>
                <a href="/" class="btn btn-secondary">На главную</a>
            </div>
        </div>';
        
        $html .= $this->renderFooter();
        
        return $html;
    }
    
    /**
     * Рендеринг заголовка страницы
     * 
     * @param string $title Заголовок страницы
     * @return string HTML-контент
     */
    private function renderHeader(string $title): string
    {
        return \App\Core\Templates\Layout::renderHeader($title);
    }
    
    /**
     * Рендеринг подвала страницы
     * 
     * @return string HTML-контент
     */
    private function renderFooter(): string
    {
        return \App\Core\Templates\Layout::renderFooter();
    }
}
