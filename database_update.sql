-- Создание таблицы для профилей браузеров
CREATE TABLE IF NOT EXISTS browser_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    browser_type ENUM('adspower', 'dolphin') NOT NULL,
    profile_id VARCHAR(100) NOT NULL,
    social_account_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    last_used DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE SET NULL
);

-- Создание таблицы для задач постинга
CREATE TABLE IF NOT EXISTS posting_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    browser_profile_id INT NOT NULL,
    content TEXT NOT NULL,
    media_urls TEXT,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    scheduled_at DATETIME,
    started_at DATETIME,
    completed_at DATETIME,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (browser_profile_id) REFERENCES browser_profiles(id) ON DELETE CASCADE
);
