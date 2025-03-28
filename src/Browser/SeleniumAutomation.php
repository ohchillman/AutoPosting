<?php

namespace App\Browser;

use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\TimeoutException;
use App\Core\LogManager;

/**
 * Базовый класс для автоматизации действий в браузере через Selenium
 */
class SeleniumAutomation
{
    /**
     * @var WebDriver Экземпляр WebDriver
     */
    protected $driver;
    
    /**
     * @var LogManager Логгер
     */
    protected $logger;
    
    /**
     * @var int Таймаут ожидания элементов (в секундах)
     */
    protected $timeout = 30;
    
    /**
     * Конструктор
     * 
     * @param WebDriver $driver Экземпляр WebDriver
     */
    public function __construct(WebDriver $driver)
    {
        $this->driver = $driver;
        $this->logger = LogManager::getInstance();
    }
    
    /**
     * Установка таймаута ожидания элементов
     * 
     * @param int $timeout Таймаут в секундах
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }
    
    /**
     * Открытие URL
     * 
     * @param string $url URL для открытия
     * @return bool Результат операции
     */
    public function navigateTo(string $url): bool
    {
        $this->logger->info('Navigating to URL', ['url' => $url]);
        
        try {
            $this->driver->get($url);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Exception when navigating to URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Ожидание загрузки элемента
     * 
     * @param WebDriverBy $by Локатор элемента
     * @return bool Результат операции
     */
    public function waitForElement(WebDriverBy $by): bool
    {
        $this->logger->info('Waiting for element', ['locator' => $by->getValue()]);
        
        try {
            $this->driver->wait($this->timeout)->until(
                WebDriverExpectedCondition::presenceOfElementLocated($by)
            );
            return true;
        } catch (TimeoutException $e) {
            $this->logger->error('Timeout waiting for element', [
                'locator' => $by->getValue(),
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Exception when waiting for element', [
                'locator' => $by->getValue(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Ожидание кликабельности элемента
     * 
     * @param WebDriverBy $by Локатор элемента
     * @return bool Результат операции
     */
    public function waitForElementClickable(WebDriverBy $by): bool
    {
        $this->logger->info('Waiting for element to be clickable', ['locator' => $by->getValue()]);
        
        try {
            $this->driver->wait($this->timeout)->until(
                WebDriverExpectedCondition::elementToBeClickable($by)
            );
            return true;
        } catch (TimeoutException $e) {
            $this->logger->error('Timeout waiting for element to be clickable', [
                'locator' => $by->getValue(),
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Exception when waiting for element to be clickable', [
                'locator' => $by->getValue(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Клик по элементу
     * 
     * @param WebDriverBy $by Локатор элемента
     * @return bool Результат операции
     */
    public function click(WebDriverBy $by): bool
    {
        $this->logger->info('Clicking on element', ['locator' => $by->getValue()]);
        
        try {
            if (!$this->waitForElementClickable($by)) {
                return false;
            }
            
            $this->driver->findElement($by)->click();
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Exception when clicking on element', [
                'locator' => $by->getValue(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Ввод текста в элемент
     * 
     * @param WebDriverBy $by Локатор элемента
     * @param string $text Текст для ввода
     * @return bool Результат операции
     */
    public function sendKeys(WebDriverBy $by, string $text): bool
    {
        $this->logger->info('Sending keys to element', [
            'locator' => $by->getValue(),
            'text_length' => strlen($text)
        ]);
        
        try {
            if (!$this->waitForElement($by)) {
                return false;
            }
            
            $element = $this->driver->findElement($by);
            $element->clear();
            $element->sendKeys($text);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Exception when sending keys to element', [
                'locator' => $by->getValue(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Проверка наличия элемента
     * 
     * @param WebDriverBy $by Локатор элемента
     * @return bool Результат проверки
     */
    public function isElementPresent(WebDriverBy $by): bool
    {
        $this->logger->info('Checking if element is present', ['locator' => $by->getValue()]);
        
        try {
            $elements = $this->driver->findElements($by);
            return count($elements) > 0;
        } catch (\Exception $e) {
            $this->logger->error('Exception when checking if element is present', [
                'locator' => $by->getValue(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Получение текста элемента
     * 
     * @param WebDriverBy $by Локатор элемента
     * @return string|null Текст элемента или null в случае ошибки
     */
    public function getText(WebDriverBy $by): ?string
    {
        $this->logger->info('Getting text from element', ['locator' => $by->getValue()]);
        
        try {
            if (!$this->waitForElement($by)) {
                return null;
            }
            
            return $this->driver->findElement($by)->getText();
        } catch (\Exception $e) {
            $this->logger->error('Exception when getting text from element', [
                'locator' => $by->getValue(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Выполнение JavaScript
     * 
     * @param string $script JavaScript код
     * @param array $args Аргументы скрипта
     * @return mixed Результат выполнения скрипта
     */
    public function executeScript(string $script, array $args = []): mixed
    {
        $this->logger->info('Executing JavaScript', ['script_length' => strlen($script)]);
        
        try {
            return $this->driver->executeScript($script, $args);
        } catch (\Exception $e) {
            $this->logger->error('Exception when executing JavaScript', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Ожидание загрузки страницы
     * 
     * @param int $timeout Таймаут в секундах
     * @return bool Результат операции
     */
    public function waitForPageLoad(int $timeout = null): bool
    {
        $timeout = $timeout ?? $this->timeout;
        $this->logger->info('Waiting for page to load', ['timeout' => $timeout]);
        
        try {
            $this->driver->wait($timeout)->until(
                function () {
                    $readyState = $this->executeScript('return document.readyState');
                    return $readyState === 'complete';
                }
            );
            return true;
        } catch (TimeoutException $e) {
            $this->logger->error('Timeout waiting for page to load', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Exception when waiting for page to load', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Сделать скриншот
     * 
     * @param string $filename Имя файла для сохранения скриншота
     * @return bool Результат операции
     */
    public function takeScreenshot(string $filename): bool
    {
        $this->logger->info('Taking screenshot', ['filename' => $filename]);
        
        try {
            $this->driver->takeScreenshot($filename);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Exception when taking screenshot', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Закрытие браузера
     * 
     * @return bool Результат операции
     */
    public function quit(): bool
    {
        $this->logger->info('Quitting browser');
        
        try {
            $this->driver->quit();
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Exception when quitting browser', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
