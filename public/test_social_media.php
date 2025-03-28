<?php
// Тестовый скрипт для проверки подключения к социальным сетям
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\LogManager;
use App\Posting\TwitterPoster;
use App\Posting\LinkedInPoster;

// Инициализация логгера
$logger = LogManager::getInstance();
$logger->info('Starting social media connection test');

// Загрузка конфигурации
$config = Config::getInstance();

// Тестирование Twitter
echo "<h2>Testing Twitter Connection</h2>";
$twitterPoster = new TwitterPoster('twitter_account1');
$twitterStatus = $twitterPoster->checkAccountStatus();
echo "Twitter Account 1 Status: " . ($twitterStatus ? "Active" : "Inactive") . "<br>";

$twitter2Poster = new TwitterPoster('twitter_account2');
$twitter2Status = $twitter2Poster->checkAccountStatus();
echo "Twitter Account 2 Status: " . ($twitter2Status ? "Active" : "Inactive") . "<br>";

// Тестирование LinkedIn
echo "<h2>Testing LinkedIn Connection</h2>";
$linkedinPoster = new LinkedInPoster('linkedin_account1');
$linkedinStatus = $linkedinPoster->checkAccountStatus();
echo "LinkedIn Account Status: " . ($linkedinStatus ? "Active" : "Inactive") . "<br>";

// Вывод информации о ключах (без показа самих ключей)
echo "<h2>API Keys Information</h2>";
echo "Twitter API Key: " . (empty($config->get('social.twitter.api_key')) ? "Not set" : "Set (starts with " . substr($config->get('social.twitter.api_key'), 0, 4) . "...)") . "<br>";
echo "Twitter API Secret: " . (empty($config->get('social.twitter.api_secret')) ? "Not set" : "Set (starts with " . substr($config->get('social.twitter.api_secret'), 0, 4) . "...)") . "<br>";
echo "Twitter Access Token: " . (empty($config->get('social.twitter.access_token')) ? "Not set" : "Set (starts with " . substr($config->get('social.twitter.access_token'), 0, 4) . "...)") . "<br>";
echo "Twitter Access Secret: " . (empty($config->get('social.twitter.access_secret')) ? "Not set" : "Set (starts with " . substr($config->get('social.twitter.access_secret'), 0, 4) . "...)") . "<br>";

echo "LinkedIn Client ID: " . (empty($config->get('social.linkedin.client_id')) ? "Not set" : "Set (starts with " . substr($config->get('social.linkedin.client_id'), 0, 4) . "...)") . "<br>";
echo "LinkedIn Client Secret: " . (empty($config->get('social.linkedin.client_secret')) ? "Not set" : "Set (starts with " . substr($config->get('social.linkedin.client_secret'), 0, 4) . "...)") . "<br>";
echo "LinkedIn Access Token: " . (empty($config->get('social.linkedin.access_token')) ? "Not set" : "Set (starts with " . substr($config->get('social.linkedin.access_token'), 0, 4) . "...)") . "<br>";

// Вывод последних записей лога
echo "<h2>Recent Log Entries</h2>";
$logFile = __DIR__ . '/../logs/app.log';
if (file_exists($logFile)) {
    $logs = file($logFile);
    $logs = array_slice($logs, -20); // Последние 20 записей
    echo "<pre>";
    foreach ($logs as $log) {
        echo htmlspecialchars($log);
    }
    echo "</pre>";
} else {
    echo "Log file not found";
}

$logger->info('Social media connection test completed');
