<?php

namespace App\Core\Templates;

/**
 * Класс для управления шаблонами страниц
 */
class Layout
{
    /**
     * Рендеринг заголовка страницы
     * 
     * @param string $title Заголовок страницы
     * @return string HTML-контент
     */
    public static function renderHeader(string $title): string
    {
        return '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - Система автоматизации</title>
    <link rel="stylesheet" href="/vendor/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">Система автоматизации</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/settings">Настройки</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>';
    }
    
    /**
     * Рендеринг подвала страницы
     * 
     * @return string HTML-контент
     */
    public static function renderFooter(): string
    {
        return '
    <script src="/vendor/bootstrap.bundle.min.js"></script>
</body>
</html>';
    }
}
