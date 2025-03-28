<?php
// Простая тестовая страница для проверки работы PHP

// Включаем отображение всех ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Записываем информацию в лог
file_put_contents(__DIR__ . '/../logs/test.log', date('Y-m-d H:i:s') . " - Test page accessed\n", FILE_APPEND);

// Проверяем наличие необходимых расширений PHP
$required_extensions = ['curl', 'mbstring', 'xml', 'mysqli', 'pdo_mysql', 'gd', 'zip'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

// Выводим информацию о PHP
echo "<h1>PHP Test Page</h1>";
echo "<h2>PHP Version: " . phpversion() . "</h2>";

// Выводим информацию о расширениях
echo "<h2>Extensions:</h2>";
echo "<ul>";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? "✅ Loaded" : "❌ Missing";
    echo "<li>$ext: $status</li>";
}
echo "</ul>";

// Выводим информацию о системе
echo "<h2>System Info:</h2>";
echo "<ul>";
echo "<li>OS: " . PHP_OS . "</li>";
echo "<li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>Server Name: " . $_SERVER['SERVER_NAME'] . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "</ul>";

// Проверяем доступ к директориям
echo "<h2>Directory Access:</h2>";
echo "<ul>";
$directories = ['../logs', '../config', '../src'];
foreach ($directories as $dir) {
    $full_path = realpath(__DIR__ . '/' . $dir);
    $is_readable = is_readable($full_path) ? "✅ Readable" : "❌ Not Readable";
    $is_writable = is_writable($full_path) ? "✅ Writable" : "❌ Not Writable";
    echo "<li>$dir ($full_path): $is_readable, $is_writable</li>";
}
echo "</ul>";

// Проверяем наличие файлов конфигурации
echo "<h2>Configuration Files:</h2>";
echo "<ul>";
$config_files = ['../config/config.php'];
foreach ($config_files as $file) {
    $full_path = realpath(__DIR__ . '/' . $file);
    $exists = file_exists($full_path) ? "✅ Exists" : "❌ Missing";
    echo "<li>$file ($full_path): $exists</li>";
}
echo "</ul>";

// Выводим переменные окружения
echo "<h2>Environment Variables:</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
