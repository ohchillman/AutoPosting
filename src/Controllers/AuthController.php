<?php

namespace App\Controllers;

use App\Core\AuthManager;
use App\Core\LogManager;

/**
 * Контроллер для управления аутентификацией пользователей
 */
class AuthController
{
    private $logger;
    private $authManager;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = LogManager::getInstance();
        $this->authManager = AuthManager::getInstance();
    }
    
    /**
     * Отображение страницы входа
     * 
     * @return string HTML-код страницы
     */
    public function loginPage()
    {
        // Если пользователь уже авторизован, перенаправляем на дашборд
        if ($this->authManager->isAuthenticated()) {
            header('Location: /dashboard');
            exit;
        }
        
        // Получаем сообщение об ошибке, если есть
        $error = $_GET['error'] ?? '';
        $errorMessage = '';
        
        if ($error === 'invalid_credentials') {
            $errorMessage = 'Неверное имя пользователя или пароль';
        } elseif ($error === 'not_authenticated') {
            $errorMessage = 'Для доступа к этой странице необходимо войти в систему';
        }
        
        // Формируем HTML-код страницы
        $html = $this->renderHeader('Вход в систему');
        
        $html .= '
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Вход в систему</h4>
                        </div>
                        <div class="card-body">
                            ' . ($errorMessage ? '<div class="alert alert-danger">' . $errorMessage . '</div>' : '') . '
                            <form action="/auth/login" method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Имя пользователя</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Пароль</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Войти</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        $html .= $this->renderFooter();
        
        return $html;
    }
    
    /**
     * Обработка входа пользователя
     * 
     * @param array $params Параметры запроса
     * @return void
     */
    public function login($params)
    {
        // Проверяем наличие необходимых параметров
        if (!isset($params['username']) || !isset($params['password'])) {
            header('Location: /auth/login?error=invalid_credentials');
            exit;
        }
        
        // Аутентифицируем пользователя
        $user = $this->authManager->authenticate($params['username'], $params['password']);
        
        if ($user) {
            // Если аутентификация успешна, перенаправляем на дашборд
            header('Location: /dashboard');
            exit;
        } else {
            // Если аутентификация не удалась, возвращаемся на страницу входа с ошибкой
            header('Location: /auth/login?error=invalid_credentials');
            exit;
        }
    }
    
    /**
     * Обработка выхода пользователя
     * 
     * @return void
     */
    public function logout()
    {
        // Выходим из системы
        $this->authManager->logout();
        
        // Перенаправляем на страницу входа
        header('Location: /auth/login');
        exit;
    }
    
    /**
     * Отображение страницы регистрации
     * 
     * @return string HTML-код страницы
     */
    public function registerPage()
    {
        // Если пользователь уже авторизован, перенаправляем на дашборд
        if ($this->authManager->isAuthenticated()) {
            header('Location: /dashboard');
            exit;
        }
        
        // Получаем сообщение об ошибке, если есть
        $error = $_GET['error'] ?? '';
        $errorMessage = '';
        
        if ($error === 'user_exists') {
            $errorMessage = 'Пользователь с таким именем или email уже существует';
        } elseif ($error === 'passwords_dont_match') {
            $errorMessage = 'Пароли не совпадают';
        }
        
        // Формируем HTML-код страницы
        $html = $this->renderHeader('Регистрация');
        
        $html .= '
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Регистрация нового пользователя</h4>
                        </div>
                        <div class="card-body">
                            ' . ($errorMessage ? '<div class="alert alert-danger">' . $errorMessage . '</div>' : '') . '
                            <form action="/auth/register" method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Имя пользователя</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Пароль</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                            </form>
                        </div>
                        <div class="card-footer text-center">
                            <p class="mb-0">Уже есть аккаунт? <a href="/auth/login">Войти</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        $html .= $this->renderFooter();
        
        return $html;
    }
    
    /**
     * Обработка регистрации пользователя
     * 
     * @param array $params Параметры запроса
     * @return void
     */
    public function register($params)
    {
        // Проверяем наличие необходимых параметров
        if (!isset($params['username']) || !isset($params['email']) || 
            !isset($params['password']) || !isset($params['password_confirm'])) {
            header('Location: /auth/register?error=invalid_data');
            exit;
        }
        
        // Проверяем совпадение паролей
        if ($params['password'] !== $params['password_confirm']) {
            header('Location: /auth/register?error=passwords_dont_match');
            exit;
        }
        
        // Регистрируем пользователя
        $userId = $this->authManager->registerUser([
            'username' => $params['username'],
            'email' => $params['email'],
            'password' => $params['password'],
            'role' => 'user' // По умолчанию роль "user"
        ]);
        
        if ($userId) {
            // Если регистрация успешна, перенаправляем на страницу входа
            header('Location: /auth/login');
            exit;
        } else {
            // Если регистрация не удалась, возвращаемся на страницу регистрации с ошибкой
            header('Location: /auth/register?error=user_exists');
            exit;
        }
    }
    
    /**
     * Отображение страницы профиля пользователя
     * 
     * @return string HTML-код страницы
     */
    public function profilePage()
    {
        // Если пользователь не авторизован, перенаправляем на страницу входа
        if (!$this->authManager->isAuthenticated()) {
            header('Location: /auth/login?error=not_authenticated');
            exit;
        }
        
        // Получаем данные текущего пользователя
        $user = $this->authManager->getCurrentUser();
        
        // Получаем сообщение об успехе или ошибке, если есть
        $success = $_GET['success'] ?? '';
        $error = $_GET['error'] ?? '';
        $successMessage = '';
        $errorMessage = '';
        
        if ($success === 'password_changed') {
            $successMessage = 'Пароль успешно изменен';
        }
        
        if ($error === 'passwords_dont_match') {
            $errorMessage = 'Пароли не совпадают';
        } elseif ($error === 'current_password_incorrect') {
            $errorMessage = 'Текущий пароль указан неверно';
        }
        
        // Формируем HTML-код страницы
        $html = $this->renderHeader('Профиль пользователя');
        
        $html .= '
        <div class="container mt-4">
            <h1>Профиль пользователя</h1>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Информация о пользователе</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" value="' . htmlspecialchars($user['username']) . '" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="' . htmlspecialchars($user['email']) . '" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Роль</label>
                                <input type="text" class="form-control" value="' . htmlspecialchars($user['role']) . '" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Последний вход</label>
                                <input type="text" class="form-control" value="' . ($user['last_login'] ?? 'Никогда') . '" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Изменение пароля</h5>
                        </div>
                        <div class="card-body">
                            ' . ($successMessage ? '<div class="alert alert-success">' . $successMessage . '</div>' : '') . '
                            ' . ($errorMessage ? '<div class="alert alert-danger">' . $errorMessage . '</div>' : '') . '
                            <form action="/auth/change-password" method="post">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Текущий пароль</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Новый пароль</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password_confirm" class="form-label">Подтверждение нового пароля</label>
                                    <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Изменить пароль</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        $html .= $this->renderFooter();
        
        return $html;
    }
    
    /**
     * Обработка изменения пароля пользователя
     * 
     * @param array $params Параметры запроса
     * @return void
     */
    public function changePassword($params)
    {
        // Если пользователь не авторизован, перенаправляем на страницу входа
        if (!$this->authManager->isAuthenticated()) {
            header('Location: /auth/login?error=not_authenticated');
            exit;
        }
        
        // Проверяем наличие необходимых параметров
        if (!isset($params['current_password']) || !isset($params['new_password']) || 
            !isset($params['new_password_confirm'])) {
            header('Location: /auth/profile?error=invalid_data');
            exit;
        }
        
        // Проверяем совпадение новых паролей
        if ($params['new_password'] !== $params['new_password_confirm']) {
            header('Location: /auth/profile?error=passwords_dont_match');
            exit;
        }
        
        // Получаем данные текущего пользователя
        $user = $this->authManager->getCurrentUser();
        
        // Проверяем текущий пароль
        if (!$this->authManager->authenticate($user['username'], $params['current_password'])) {
            header('Location: /auth/profile?error=current_password_incorrect');
            exit;
        }
        
        // Изменяем пароль
        $result = $this->authManager->changePassword($user['id'], $params['new_password']);
        
        if ($result) {
            // Если изменение пароля успешно, перенаправляем на страницу профиля с сообщением об успехе
            header('Location: /auth/profile?success=password_changed');
            exit;
        } else {
            // Если изменение пароля не удалось, возвращаемся на страницу профиля с ошибкой
            header('Location: /auth/profile?error=password_change_failed');
            exit;
        }
    }
    
    /**
     * Формирование заголовка HTML-страницы
     * 
     * @param string $title Заголовок страницы
     * @return string HTML-код заголовка
     */
    private function renderHeader($title)
    {
        return '<!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $title . ' - Система автоматизации</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
                <div class="container">
                    <a class="navbar-brand" href="/">Система автоматизации</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            ' . ($this->authManager->isAuthenticated() ? '
                            <li class="nav-item">
                                <a class="nav-link" href="/dashboard">Дашборд</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/auth/profile">Профиль</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/auth/logout">Выход</a>
                            </li>
                            ' : '
                            <li class="nav-item">
                                <a class="nav-link" href="/auth/login">Вход</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/auth/register">Регистрация</a>
                            </li>
                            ') . '
                        </ul>
                    </div>
                </div>
            </nav>';
    }
    
    /**
     * Формирование подвала HTML-страницы
     * 
     * @return string HTML-код подвала
     */
    private function renderFooter()
    {
        return '
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>';
    }
}
