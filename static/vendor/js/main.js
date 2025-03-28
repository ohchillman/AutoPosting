// Основные скрипты для веб-интерфейса
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех компонентов
    initializeComponents();
    
    // Обработчики событий для форм
    setupFormHandlers();
    
    // Обработчики для кнопок тестирования
    setupTestButtons();
});

/**
 * Инициализация всех компонентов интерфейса
 */
function initializeComponents() {
    // Инициализация выпадающих меню
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdownMenu = this.nextElementSibling;
            dropdownMenu.classList.toggle('show');
        });
    });
    
    // Закрытие выпадающих меню при клике вне них
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle')) {
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
    
    // Инициализация вкладок
    const tabLinks = document.querySelectorAll('.nav-tabs .nav-link');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Удаление активного класса со всех вкладок
            tabLinks.forEach(l => l.classList.remove('active'));
            
            // Добавление активного класса текущей вкладке
            this.classList.add('active');
            
            // Скрытие всех панелей содержимого
            const tabContents = document.querySelectorAll('.tab-content .tab-pane');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Отображение соответствующей панели содержимого
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });
}

/**
 * Настройка обработчиков форм
 */
function setupFormHandlers() {
    // Обработчик формы настроек
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Сбор данных формы
            const formData = new FormData(this);
            
            // Отправка данных на сервер
            fetch('/settings/save', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Настройки успешно сохранены');
                } else {
                    showAlert('danger', 'Ошибка при сохранении настроек: ' + data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Произошла ошибка: ' + error.message);
            });
        });
    }
    
    // Обработчик формы запуска автоматизации
    const automationForm = document.getElementById('automationForm');
    if (automationForm) {
        automationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Сбор данных формы
            const formData = new FormData(this);
            
            // Отправка данных на сервер
            fetch('/automation/run', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Автоматизация запущена успешно');
                    // Запуск проверки статуса
                    checkAutomationStatus();
                } else {
                    showAlert('danger', 'Ошибка при запуске автоматизации: ' + data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Произошла ошибка: ' + error.message);
            });
        });
    }
}

/**
 * Настройка кнопок тестирования
 */
function setupTestButtons() {
    // Кнопка тестирования настроек
    const testSettingsBtn = document.getElementById('testSettingsBtn');
    if (testSettingsBtn) {
        testSettingsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Отображение индикатора загрузки
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Тестирование...';
            this.disabled = true;
            
            // Отправка запроса на тестирование
            fetch('/settings/test')
            .then(response => response.json())
            .then(data => {
                // Восстановление кнопки
                this.innerHTML = 'Проверить настройки';
                this.disabled = false;
                
                if (data.success) {
                    showAlert('success', 'Все настройки работают корректно');
                } else {
                    showAlert('danger', 'Ошибка в настройках: ' + data.message);
                }
            })
            .catch(error => {
                // Восстановление кнопки
                this.innerHTML = 'Проверить настройки';
                this.disabled = false;
                
                showAlert('danger', 'Произошла ошибка: ' + error.message);
            });
        });
    }
}

/**
 * Проверка статуса автоматизации
 */
function checkAutomationStatus() {
    const statusContainer = document.getElementById('automationStatus');
    if (!statusContainer) return;
    
    // Установка интервала проверки
    const statusInterval = setInterval(function() {
        fetch('/automation/status')
        .then(response => response.json())
        .then(data => {
            // Обновление статуса
            statusContainer.innerHTML = `
                <div class="alert alert-info">
                    <strong>Статус:</strong> ${data.status}<br>
                    <strong>Прогресс:</strong> ${data.progress}%
                </div>
            `;
            
            // Если процесс завершен, останавливаем проверку
            if (data.status === 'completed' || data.status === 'failed') {
                clearInterval(statusInterval);
                
                if (data.status === 'completed') {
                    showAlert('success', 'Автоматизация успешно завершена');
                } else {
                    showAlert('danger', 'Автоматизация завершилась с ошибкой: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Ошибка при проверке статуса:', error);
        });
    }, 5000); // Проверка каждые 5 секунд
}

/**
 * Отображение оповещения
 * @param {string} type - Тип оповещения (success, danger, warning, info)
 * @param {string} message - Сообщение для отображения
 */
function showAlert(type, message) {
    const alertsContainer = document.getElementById('alerts');
    if (!alertsContainer) return;
    
    // Создание элемента оповещения
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    // Добавление оповещения в контейнер
    alertsContainer.appendChild(alert);
    
    // Настройка автоматического скрытия
    setTimeout(function() {
        alert.classList.remove('show');
        setTimeout(function() {
            alertsContainer.removeChild(alert);
        }, 150);
    }, 5000);
    
    // Обработчик кнопки закрытия
    const closeBtn = alert.querySelector('.close');
    closeBtn.addEventListener('click', function() {
        alert.classList.remove('show');
        setTimeout(function() {
            alertsContainer.removeChild(alert);
        }, 150);
    });
}
