<?php

namespace App\Browser\Posting;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriver;

/**
 * Класс для автоматизации постинга в LinkedIn через Selenium
 */
class LinkedInPoster extends SocialMediaPoster
{
    /**
     * @var string URL страницы логина
     */
    protected $loginUrl = 'https://www.linkedin.com/login';
    
    /**
     * @var string URL главной страницы
     */
    protected $homeUrl = 'https://www.linkedin.com/feed/';
    
    /**
     * Авторизация в LinkedIn
     * 
     * @return bool Результат авторизации
     */
    public function login(): bool
    {
        if ($this->isLoggedIn) {
            $this->logger->info('Already logged in to LinkedIn');
            return true;
        }
        
        $this->logger->info('Logging in to LinkedIn');
        
        try {
            // Открываем страницу логина
            if (!$this->automation->navigateTo($this->loginUrl)) {
                $this->handleError('Failed to navigate to LinkedIn login page');
                return false;
            }
            
            // Ждем загрузки страницы
            $this->automation->waitForPageLoad();
            
            // Проверяем, возможно мы уже авторизованы
            if ($this->checkLoginStatus()) {
                $this->isLoggedIn = true;
                $this->logger->info('Already logged in to LinkedIn');
                return true;
            }
            
            // Вводим email
            $emailField = WebDriverBy::id('username');
            if (!$this->automation->waitForElement($emailField)) {
                $this->handleError('Email field not found');
                return false;
            }
            
            $this->automation->sendKeys($emailField, $this->credentials['username']);
            
            // Вводим пароль
            $passwordField = WebDriverBy::id('password');
            if (!$this->automation->waitForElement($passwordField)) {
                $this->handleError('Password field not found');
                return false;
            }
            
            $this->automation->sendKeys($passwordField, $this->credentials['password']);
            
            // Нажимаем кнопку "Войти"
            $loginButton = WebDriverBy::xpath('//button[@type="submit"]');
            if (!$this->automation->click($loginButton)) {
                $this->handleError('Failed to click Login button');
                return false;
            }
            
            // Ждем загрузки главной страницы
            $this->automation->waitForPageLoad();
            
            // Проверяем успешность авторизации
            $this->isLoggedIn = $this->checkLoginStatus();
            
            if ($this->isLoggedIn) {
                $this->logger->info('Successfully logged in to LinkedIn');
            } else {
                $this->handleError('Failed to log in to LinkedIn');
            }
            
            return $this->isLoggedIn;
        } catch (\Exception $e) {
            $this->handleError('Exception when logging in to LinkedIn', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Публикация контента в LinkedIn
     * 
     * @param string $content Текст публикации
     * @param array $media Массив путей к медиа-файлам
     * @param array $options Дополнительные параметры
     * @return bool Результат публикации
     */
    public function post(string $content, array $media = [], array $options = []): bool
    {
        $this->logger->info('Posting to LinkedIn', [
            'content_length' => strlen($content),
            'media_count' => count($media)
        ]);
        
        try {
            // Проверяем авторизацию
            if (!$this->isLoggedIn && !$this->login()) {
                $this->handleError('Not logged in to LinkedIn');
                return false;
            }
            
            // Переходим на главную страницу
            if (!$this->automation->navigateTo($this->homeUrl)) {
                $this->handleError('Failed to navigate to LinkedIn home page');
                return false;
            }
            
            // Ждем загрузки страницы
            $this->automation->waitForPageLoad();
            
            // Находим кнопку "Создать пост"
            $createPostButton = WebDriverBy::xpath('//button[contains(@class, "share-box-feed-entry__trigger")]');
            if (!$this->automation->click($createPostButton)) {
                $this->handleError('Failed to click Create Post button');
                return false;
            }
            
            // Ждем появления окна создания поста
            $postTextArea = WebDriverBy::xpath('//div[contains(@class, "ql-editor")]');
            if (!$this->automation->waitForElement($postTextArea)) {
                $this->handleError('Post text area not found');
                return false;
            }
            
            // Форматируем контент
            $formattedContent = $this->formatContent($content, $options);
            
            // Вводим текст поста
            $this->automation->sendKeys($postTextArea, $formattedContent);
            
            // Загружаем медиа-файлы, если есть
            if (!empty($media)) {
                $mediaButton = WebDriverBy::xpath('//button[contains(@aria-label, "Add a photo")]');
                if (!$this->automation->click($mediaButton)) {
                    $this->handleError('Failed to click media button');
                    return false;
                }
                
                // Ждем появления поля для загрузки файла
                $fileInput = WebDriverBy::xpath('//input[@type="file"]');
                if (!$this->automation->waitForElement($fileInput)) {
                    $this->handleError('File input not found');
                    return false;
                }
                
                // Загружаем каждый файл
                foreach ($media as $mediaFile) {
                    if (file_exists($mediaFile)) {
                        $this->automation->sendKeys($fileInput, $mediaFile);
                        
                        // Ждем загрузки файла
                        $this->automation->waitForElement(WebDriverBy::xpath('//div[contains(@class, "share-box-file-attachment")]'));
                    } else {
                        $this->logger->warning('Media file not found', ['file' => $mediaFile]);
                    }
                }
            }
            
            // Нажимаем кнопку "Опубликовать"
            $postButton = WebDriverBy::xpath('//button[contains(@class, "share-actions__primary-action")]');
            if (!$this->automation->click($postButton)) {
                $this->handleError('Failed to click Post button');
                return false;
            }
            
            // Ждем исчезновения окна создания поста (признак успешной публикации)
            $this->automation->waitForPageLoad();
            
            // Проверяем успешность публикации
            $success = !$this->automation->isElementPresent($postTextArea);
            
            if ($success) {
                $this->logger->info('Successfully posted to LinkedIn');
            } else {
                $this->handleError('Failed to post to LinkedIn');
            }
            
            return $success;
        } catch (\Exception $e) {
            $this->handleError('Exception when posting to LinkedIn', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Проверка статуса авторизации
     * 
     * @return bool Статус авторизации
     */
    public function checkLoginStatus(): bool
    {
        $this->logger->info('Checking LinkedIn login status');
        
        try {
            // Проверяем наличие элементов, которые видны только авторизованным пользователям
            $profileButton = WebDriverBy::xpath('//div[contains(@class, "global-nav__me-photo")]');
            $isLoggedIn = $this->automation->isElementPresent($profileButton);
            
            $this->logger->info('LinkedIn login status checked', ['is_logged_in' => $isLoggedIn]);
            
            return $isLoggedIn;
        } catch (\Exception $e) {
            $this->logger->error('Exception when checking LinkedIn login status', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Выход из аккаунта LinkedIn
     * 
     * @return bool Результат операции
     */
    public function logout(): bool
    {
        $this->logger->info('Logging out from LinkedIn');
        
        try {
            // Проверяем авторизацию
            if (!$this->isLoggedIn && !$this->checkLoginStatus()) {
                $this->logger->info('Already logged out from LinkedIn');
                return true;
            }
            
            // Находим кнопку профиля
            $profileButton = WebDriverBy::xpath('//div[contains(@class, "global-nav__me")]');
            if (!$this->automation->click($profileButton)) {
                $this->handleError('Failed to click profile button');
                return false;
            }
            
            // Ждем появления меню
            $this->automation->waitForElement(WebDriverBy::xpath('//div[contains(@class, "global-nav__me-content")]'));
            
            // Находим пункт "Выйти"
            $logoutButton = WebDriverBy::xpath('//a[contains(@href, "logout")]');
            if (!$this->automation->click($logoutButton)) {
                $this->handleError('Failed to click logout button');
                return false;
            }
            
            // Ждем загрузки страницы
            $this->automation->waitForPageLoad();
            
            // Проверяем успешность выхода
            $this->isLoggedIn = $this->checkLoginStatus();
            
            if (!$this->isLoggedIn) {
                $this->logger->info('Successfully logged out from LinkedIn');
                return true;
            } else {
                $this->handleError('Failed to log out from LinkedIn');
                return false;
            }
        } catch (\Exception $e) {
            $this->handleError('Exception when logging out from LinkedIn', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение информации об аккаунте LinkedIn
     * 
     * @return array Информация об аккаунте
     */
    public function getAccountInfo(): array
    {
        $this->logger->info('Getting LinkedIn account info');
        
        try {
            // Проверяем авторизацию
            if (!$this->isLoggedIn && !$this->login()) {
                $this->handleError('Not logged in to LinkedIn');
                return [];
            }
            
            // Переходим на страницу профиля
            $profileButton = WebDriverBy::xpath('//div[contains(@class, "global-nav__me")]');
            if (!$this->automation->click($profileButton)) {
                $this->handleError('Failed to click profile button');
                return [];
            }
            
            // Находим ссылку на профиль
            $profileLink = WebDriverBy::xpath('//a[contains(@href, "/in/")]');
            if (!$this->automation->click($profileLink)) {
                $this->handleError('Failed to click profile link');
                return [];
            }
            
            // Ждем загрузки страницы профиля
            $this->automation->waitForPageLoad();
            
            // Получаем имя пользователя
            $nameElement = WebDriverBy::xpath('//h1[contains(@class, "text-heading-xlarge")]');
            $name = $this->automation->getText($nameElement) ?? '';
            
            // Получаем заголовок профиля
            $titleElement = WebDriverBy::xpath('//div[contains(@class, "text-body-medium")]');
            $title = $this->automation->getText($titleElement) ?? '';
            
            // Получаем количество связей
            $connectionsElement = WebDriverBy::xpath('//span[contains(@class, "t-bold")][contains(text(), "connection")]');
            $connections = $this->automation->getText($connectionsElement) ?? '0';
            
            $accountInfo = [
                'name' => $name,
                'title' => $title,
                'connections' => $connections
            ];
            
            $this->logger->info('LinkedIn account info retrieved', $accountInfo);
            
            return $accountInfo;
        } catch (\Exception $e) {
            $this->handleError('Exception when getting LinkedIn account info', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Форматирование контента для LinkedIn
     * 
     * @param string $content Исходный контент
     * @param array $options Дополнительные параметры
     * @return string Отформатированный контент
     */
    protected function formatContent(string $content, array $options = []): string
    {
        // LinkedIn не имеет жестких ограничений на длину поста,
        // но рекомендуется не превышать 1300 символов
        $maxLength = 1300;
        
        if (strlen($content) > $maxLength) {
            $content = substr($content, 0, $maxLength - 3) . '...';
        }
        
        return $content;
    }
}
