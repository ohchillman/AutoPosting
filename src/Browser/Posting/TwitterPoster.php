<?php

namespace App\Browser\Posting;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriver;

/**
 * Класс для автоматизации постинга в Twitter через Selenium
 */
class TwitterPoster extends SocialMediaPoster
{
    /**
     * @var string URL страницы логина
     */
    protected $loginUrl = 'https://twitter.com/login';
    
    /**
     * @var string URL главной страницы
     */
    protected $homeUrl = 'https://twitter.com/home';
    
    /**
     * Авторизация в Twitter
     * 
     * @return bool Результат авторизации
     */
    public function login(): bool
    {
        if ($this->isLoggedIn) {
            $this->logger->info('Already logged in to Twitter');
            return true;
        }
        
        $this->logger->info('Logging in to Twitter');
        
        try {
            // Открываем страницу логина
            if (!$this->automation->navigateTo($this->loginUrl)) {
                $this->handleError('Failed to navigate to Twitter login page');
                return false;
            }
            
            // Ждем загрузки страницы
            $this->automation->waitForPageLoad();
            
            // Проверяем, возможно мы уже авторизованы
            if ($this->checkLoginStatus()) {
                $this->isLoggedIn = true;
                $this->logger->info('Already logged in to Twitter');
                return true;
            }
            
            // Вводим имя пользователя
            $usernameField = WebDriverBy::xpath('//input[@autocomplete="username"]');
            if (!$this->automation->waitForElement($usernameField)) {
                $this->handleError('Username field not found');
                return false;
            }
            
            $this->automation->sendKeys($usernameField, $this->credentials['username']);
            
            // Нажимаем кнопку "Далее"
            $nextButton = WebDriverBy::xpath('//div[@role="button"][contains(.,"Next")]');
            if (!$this->automation->click($nextButton)) {
                $this->handleError('Failed to click Next button');
                return false;
            }
            
            // Ждем появления поля для пароля
            $passwordField = WebDriverBy::xpath('//input[@name="password"]');
            if (!$this->automation->waitForElement($passwordField)) {
                $this->handleError('Password field not found');
                return false;
            }
            
            // Вводим пароль
            $this->automation->sendKeys($passwordField, $this->credentials['password']);
            
            // Нажимаем кнопку "Войти"
            $loginButton = WebDriverBy::xpath('//div[@role="button"][contains(.,"Log in")]');
            if (!$this->automation->click($loginButton)) {
                $this->handleError('Failed to click Login button');
                return false;
            }
            
            // Ждем загрузки главной страницы
            $this->automation->waitForPageLoad();
            
            // Проверяем успешность авторизации
            $this->isLoggedIn = $this->checkLoginStatus();
            
            if ($this->isLoggedIn) {
                $this->logger->info('Successfully logged in to Twitter');
            } else {
                $this->handleError('Failed to log in to Twitter');
            }
            
            return $this->isLoggedIn;
        } catch (\Exception $e) {
            $this->handleError('Exception when logging in to Twitter', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Публикация контента в Twitter
     * 
     * @param string $content Текст публикации
     * @param array $media Массив путей к медиа-файлам
     * @param array $options Дополнительные параметры
     * @return bool Результат публикации
     */
    public function post(string $content, array $media = [], array $options = []): bool
    {
        $this->logger->info('Posting to Twitter', [
            'content_length' => strlen($content),
            'media_count' => count($media)
        ]);
        
        try {
            // Проверяем авторизацию
            if (!$this->isLoggedIn && !$this->login()) {
                $this->handleError('Not logged in to Twitter');
                return false;
            }
            
            // Переходим на главную страницу
            if (!$this->automation->navigateTo($this->homeUrl)) {
                $this->handleError('Failed to navigate to Twitter home page');
                return false;
            }
            
            // Ждем загрузки страницы
            $this->automation->waitForPageLoad();
            
            // Находим поле для ввода твита
            $tweetField = WebDriverBy::xpath('//div[@role="textbox" and @data-testid="tweetTextarea_0"]');
            if (!$this->automation->waitForElement($tweetField)) {
                // Пробуем найти кнопку "Tweet" и нажать на нее
                $composeTweetButton = WebDriverBy::xpath('//a[@data-testid="SideNav_NewTweet_Button"]');
                if ($this->automation->isElementPresent($composeTweetButton)) {
                    $this->automation->click($composeTweetButton);
                    
                    // Теперь ждем появления поля для ввода твита
                    if (!$this->automation->waitForElement($tweetField)) {
                        $this->handleError('Tweet field not found');
                        return false;
                    }
                } else {
                    $this->handleError('Tweet field and Compose button not found');
                    return false;
                }
            }
            
            // Форматируем контент
            $formattedContent = $this->formatContent($content, $options);
            
            // Вводим текст твита
            $this->automation->sendKeys($tweetField, $formattedContent);
            
            // Загружаем медиа-файлы, если есть
            if (!empty($media)) {
                $mediaButton = WebDriverBy::xpath('//input[@data-testid="fileInput"]');
                if (!$this->automation->isElementPresent($mediaButton)) {
                    $this->handleError('Media upload button not found');
                    return false;
                }
                
                // Загружаем каждый файл
                foreach ($media as $mediaFile) {
                    if (file_exists($mediaFile)) {
                        $this->automation->sendKeys($mediaButton, $mediaFile);
                        
                        // Ждем загрузки файла
                        $this->automation->waitForElement(WebDriverBy::xpath('//div[@data-testid="attachments"]'));
                    } else {
                        $this->logger->warning('Media file not found', ['file' => $mediaFile]);
                    }
                }
            }
            
            // Нажимаем кнопку "Tweet"
            $tweetButton = WebDriverBy::xpath('//div[@data-testid="tweetButtonInline"]');
            if (!$this->automation->click($tweetButton)) {
                $this->handleError('Failed to click Tweet button');
                return false;
            }
            
            // Ждем исчезновения кнопки "Tweet" (признак успешной публикации)
            $this->automation->waitForPageLoad();
            
            // Проверяем успешность публикации
            $success = !$this->automation->isElementPresent($tweetButton);
            
            if ($success) {
                $this->logger->info('Successfully posted to Twitter');
            } else {
                $this->handleError('Failed to post to Twitter');
            }
            
            return $success;
        } catch (\Exception $e) {
            $this->handleError('Exception when posting to Twitter', [
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
        $this->logger->info('Checking Twitter login status');
        
        try {
            // Проверяем наличие элементов, которые видны только авторизованным пользователям
            $profileButton = WebDriverBy::xpath('//a[@data-testid="AppTabBar_Profile_Link"]');
            $isLoggedIn = $this->automation->isElementPresent($profileButton);
            
            $this->logger->info('Twitter login status checked', ['is_logged_in' => $isLoggedIn]);
            
            return $isLoggedIn;
        } catch (\Exception $e) {
            $this->logger->error('Exception when checking Twitter login status', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Выход из аккаунта Twitter
     * 
     * @return bool Результат операции
     */
    public function logout(): bool
    {
        $this->logger->info('Logging out from Twitter');
        
        try {
            // Проверяем авторизацию
            if (!$this->isLoggedIn && !$this->checkLoginStatus()) {
                $this->logger->info('Already logged out from Twitter');
                return true;
            }
            
            // Находим кнопку профиля
            $profileButton = WebDriverBy::xpath('//div[@data-testid="SideNav_AccountSwitcher_Button"]');
            if (!$this->automation->click($profileButton)) {
                $this->handleError('Failed to click profile button');
                return false;
            }
            
            // Ждем появления меню
            $this->automation->waitForElement(WebDriverBy::xpath('//div[@role="menu"]'));
            
            // Находим пункт "Выйти"
            $logoutButton = WebDriverBy::xpath('//a[@data-testid="logout"]');
            if (!$this->automation->click($logoutButton)) {
                $this->handleError('Failed to click logout button');
                return false;
            }
            
            // Подтверждаем выход
            $confirmButton = WebDriverBy::xpath('//div[@data-testid="confirmationSheetConfirm"]');
            if (!$this->automation->click($confirmButton)) {
                $this->handleError('Failed to confirm logout');
                return false;
            }
            
            // Ждем загрузки страницы
            $this->automation->waitForPageLoad();
            
            // Проверяем успешность выхода
            $this->isLoggedIn = $this->checkLoginStatus();
            
            if (!$this->isLoggedIn) {
                $this->logger->info('Successfully logged out from Twitter');
                return true;
            } else {
                $this->handleError('Failed to log out from Twitter');
                return false;
            }
        } catch (\Exception $e) {
            $this->handleError('Exception when logging out from Twitter', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение информации об аккаунте Twitter
     * 
     * @return array Информация об аккаунте
     */
    public function getAccountInfo(): array
    {
        $this->logger->info('Getting Twitter account info');
        
        try {
            // Проверяем авторизацию
            if (!$this->isLoggedIn && !$this->login()) {
                $this->handleError('Not logged in to Twitter');
                return [];
            }
            
            // Переходим на страницу профиля
            $profileButton = WebDriverBy::xpath('//a[@data-testid="AppTabBar_Profile_Link"]');
            if (!$this->automation->click($profileButton)) {
                $this->handleError('Failed to click profile button');
                return [];
            }
            
            // Ждем загрузки страницы профиля
            $this->automation->waitForPageLoad();
            
            // Получаем имя пользователя
            $nameElement = WebDriverBy::xpath('//div[@data-testid="UserName"]//span');
            $name = $this->automation->getText($nameElement) ?? '';
            
            // Получаем никнейм
            $usernameElement = WebDriverBy::xpath('//div[@data-testid="UserName"]//span[contains(text(), "@")]');
            $username = $this->automation->getText($usernameElement) ?? '';
            $username = str_replace('@', '', $username);
            
            // Получаем количество подписчиков
            $followersElement = WebDriverBy::xpath('//a[contains(@href, "/followers")]//span');
            $followers = $this->automation->getText($followersElement) ?? '0';
            
            // Получаем количество подписок
            $followingElement = WebDriverBy::xpath('//a[contains(@href, "/following")]//span');
            $following = $this->automation->getText($followingElement) ?? '0';
            
            $accountInfo = [
                'name' => $name,
                'username' => $username,
                'followers' => $followers,
                'following' => $following
            ];
            
            $this->logger->info('Twitter account info retrieved', $accountInfo);
            
            return $accountInfo;
        } catch (\Exception $e) {
            $this->handleError('Exception when getting Twitter account info', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Форматирование контента для Twitter
     * 
     * @param string $content Исходный контент
     * @param array $options Дополнительные параметры
     * @return string Отформатированный контент
     */
    protected function formatContent(string $content, array $options = []): string
    {
        // Ограничение длины твита
        $maxLength = 280;
        
        if (strlen($content) > $maxLength) {
            $content = substr($content, 0, $maxLength - 3) . '...';
        }
        
        return $content;
    }
}
