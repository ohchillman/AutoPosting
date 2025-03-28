<?php

namespace App\Controllers;

use App\Core\LogManager;
use App\Core\Config;
use App\Core\AutomationWorkflow;

/**
 * Контроллер для управления автоматизацией через веб-интерфейс
 */
class AutomationController
{
    private $logger;
    private $config;
    private $workflow;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->config = Config::getInstance();
        $this->workflow = new AutomationWorkflow();
    }
    
    /**
     * Отображение главной страницы
     * 
     * @return string HTML-контент
     */
    public function index(): string
    {
        // Получение статуса системы
        $status = $this->workflow->checkSystemStatus();
        
        // Формирование HTML-контента
        $html = \App\Core\Templates\Layout::renderHeader('Система автоматизации управления контентом');
        
        $html .= '<div class="container mt-4">
        <h1>Система автоматизации управления контентом</h1>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Статус системы</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Парсеры новостей
                                <span class="badge ' . ($status['parsers'] ? 'bg-success' : 'bg-danger') . ' rounded-pill">
                                    ' . ($status['parsers'] ? 'Активны' : 'Неактивны') . '
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Система рерайта
                                <span class="badge ' . ($status['rewrite'] ? 'bg-success' : 'bg-danger') . ' rounded-pill">
                                    ' . ($status['rewrite'] ? 'Активна' : 'Неактивна') . '
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Система постинга
                                <span class="badge ' . ($status['posting'] ? 'bg-success' : 'bg-danger') . ' rounded-pill">
                                    ' . ($status['posting'] ? 'Активна' : 'Неактивна') . '
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Статус аккаунтов</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">';
        
        foreach ($status['accounts'] as $accountId => $isActive) {
            $html .= '
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ' . htmlspecialchars($accountId) . '
                                <span class="badge ' . ($isActive ? 'bg-success' : 'bg-danger') . ' rounded-pill">
                                    ' . ($isActive ? 'Активен' : 'Неактивен') . '
                                </span>
                            </li>';
        }
        
        $html .= '
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Запуск автоматизации</h5>
                    </div>
                    <div class="card-body">
                        <form action="/automation/run" method="post">
                            <div class="mb-3">
                                <label for="keywords" class="form-label">Ключевые слова (через запятую)</label>
                                <input type="text" class="form-control" id="keywords" name="keywords" placeholder="технологии, искусственный интеллект, AI">
                            </div>
                            
                            <div class="mb-3">
                                <label for="max_news" class="form-label">Максимальное количество новостей</label>
                                <input type="number" class="form-control" id="max_news" name="max_news" value="5" min="1" max="20">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Запустить</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Последние запуски</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            История запусков будет отображаться здесь
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/vendor/bootstrap.bundle.min.js"></script>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Запуск процесса автоматизации
     * 
     * @param array $params Параметры запроса
     * @return string HTML-контент с результатами
     */
    public function run(array $params): string
    {
        $keywords = [];
        if (!empty($params['keywords'])) {
            $keywords = array_map('trim', explode(',', $params['keywords']));
        }
        
        $maxNews = isset($params['max_news']) ? (int)$params['max_news'] : 5;
        
        $options = [
            'max_news' => $maxNews
        ];
        
        // Запуск рабочего процесса
        $results = $this->workflow->run($keywords, $options);
        
        // Формирование HTML-контента с результатами
        $html = \App\Core\Templates\Layout::renderHeader('Результаты автоматизации');
        
        $html .= '<div class="container mt-4">
        <h1>Результаты автоматизации</h1>
        
        <div class="alert alert-info mt-4">
            <h4>Сводка</h4>
            <ul>
                <li>Обработано новостей: ' . $results['parsed_news'] . '</li>
                <li>Переписано контента: ' . $results['rewritten_content'] . '</li>
                <li>Опубликовано постов: ' . $results['posted_content'] . '</li>
                <li>Процент успешных публикаций: ' . number_format($results['success_rate'], 2) . '%</li>
            </ul>
        </div>
        
        <h2 class="mt-4">Детали обработки</h2>';
        
        if (empty($results['details'])) {
            $html .= '<div class="alert alert-warning">Нет данных для отображения</div>';
        } else {
            foreach ($results['details'] as $detail) {
                $html .= '
        <div class="card mt-3">
            <div class="card-header">
                <h5>' . htmlspecialchars($detail['title']) . '</h5>
                <small class="text-muted">' . htmlspecialchars($detail['source']) . ' - ' . htmlspecialchars($detail['date']) . '</small>
            </div>
            <div class="card-body">
                <h6>Рерайт контента:</h6>
                <ul>';
                
                if (empty($detail['rewrite_results'])) {
                    $html .= '<li>Нет данных</li>';
                } else {
                    foreach ($detail['rewrite_results'] as $account) {
                        $html .= '<li>' . htmlspecialchars($account) . '</li>';
                    }
                }
                
                $html .= '
                </ul>
                
                <h6>Результаты публикации:</h6>
                <ul>';
                
                if (empty($detail['posting_results'])) {
                    $html .= '<li>Нет данных</li>';
                } else {
                    foreach ($detail['posting_results'] as $account => $success) {
                        $html .= '<li>' . htmlspecialchars($account) . ': <span class="badge ' . ($success ? 'bg-success' : 'bg-danger') . '">' . ($success ? 'Успешно' : 'Ошибка') . '</span></li>';
                    }
                }
                
                $html .= '
                </ul>
            </div>
        </div>';
            }
        }
        
        $html .= '
        <div class="mt-4 mb-4">
            <a href="/" class="btn btn-primary">Вернуться на главную</a>
        </div>
    </div>';
        
        $html .= \App\Core\Templates\Layout::renderFooter();
        
        return $html;
    }
}
