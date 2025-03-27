#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
import logging
import requests
from bs4 import BeautifulSoup
from datetime import datetime
import json

# Настройка логирования
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('../logs/parser.log'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger('news_parser')

class NewsParser:
    """
    Класс для парсинга новостей из различных источников
    """
    
    def __init__(self, source_url, keywords=None, max_age_days=1):
        """
        Инициализация парсера
        
        Args:
            source_url (str): URL источника новостей
            keywords (list): Список ключевых слов для фильтрации
            max_age_days (int): Максимальный возраст новости в днях
        """
        self.source_url = source_url
        self.keywords = keywords or []
        self.max_age_days = max_age_days
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        
    def fetch_content(self, url):
        """
        Получение HTML-контента страницы
        
        Args:
            url (str): URL страницы
            
        Returns:
            str: HTML-контент или None в случае ошибки
        """
        try:
            response = requests.get(url, headers=self.headers, timeout=30)
            response.raise_for_status()
            return response.text
        except requests.exceptions.RequestException as e:
            logger.error(f"Error fetching content: {e}")
            return None
    
    def is_relevant_by_date(self, publish_date):
        """
        Проверка актуальности новости по дате
        
        Args:
            publish_date (datetime): Дата публикации новости
            
        Returns:
            bool: True если новость актуальна, иначе False
        """
        now = datetime.now()
        diff = now - publish_date
        return diff.days <= self.max_age_days
    
    def is_relevant_by_keywords(self, title, content):
        """
        Проверка релевантности новости по ключевым словам
        
        Args:
            title (str): Заголовок новости
            content (str): Содержимое новости
            
        Returns:
            bool: True если новость релевантна, иначе False
        """
        if not self.keywords:
            return True
            
        text = (title + ' ' + content).lower()
        for keyword in self.keywords:
            if keyword.lower() in text:
                return True
        return False
    
    def parse(self):
        """
        Абстрактный метод для парсинга новостей
        
        Returns:
            list: Список новостей
        """
        raise NotImplementedError("Subclasses must implement parse() method")
    
    def save_results(self, news, output_file):
        """
        Сохранение результатов парсинга в JSON-файл
        
        Args:
            news (list): Список новостей
            output_file (str): Путь к файлу для сохранения
        """
        try:
            with open(output_file, 'w', encoding='utf-8') as f:
                json.dump(news, f, ensure_ascii=False, indent=4)
            logger.info(f"Results saved to {output_file}")
        except Exception as e:
            logger.error(f"Error saving results: {e}")
