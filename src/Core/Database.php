<?php
namespace App\Core;

/**
 * Класс для работы с базой данных
 */
class Database
{
    /**
     * @var Database Экземпляр класса (singleton)
     */
    private static $instance = null;
    
    /**
     * @var \PDO Соединение с базой данных
     */
    private $connection = null;
    
    /**
     * @var Config Конфигурация
     */
    private $config;
    
    /**
     * @var LogManager Логгер
     */
    private $logger;
    
    /**
     * @var bool Флаг успешного соединения с базой данных
     */
    private $isConnected = false;
    
    /**
     * Приватный конструктор для реализации паттерна Singleton
     */
    private function __construct()
    {
        $this->config = Config::getInstance();
        $this->logger = LogManager::getInstance();
        $this->isConnected = $this->connect();
        
        if (!$this->isConnected) {
            $this->logger->error('Failed to establish database connection in constructor');
        }
    }
    
    /**
     * Получение экземпляра класса (singleton)
     * 
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Проверка соединения с базой данных
     * 
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->isConnected && $this->connection !== null;
    }
    
    /**
     * Повторная попытка соединения с базой данных
     * 
     * @return bool Результат операции
     */
    public function reconnect(): bool
    {
        $this->logger->info('Attempting to reconnect to database');
        $this->isConnected = $this->connect();
        return $this->isConnected;
    }
    
    /**
     * Установка соединения с базой данных
     * 
     * @return bool Результат операции
     */
    private function connect(): bool
    {
        try {
            $host = $this->config->get('database.host', 'localhost');
            $dbname = $this->config->get('database.dbname', 'automation_system');
            $username = $this->config->get('database.username', 'root');
            $password = $this->config->get('database.password', '');
            $charset = $this->config->get('database.charset', 'utf8mb4');
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new \PDO($dsn, $username, $password, $options);
            
            $this->logger->info('Database connection established', [
                'host' => $host,
                'database' => $dbname
            ]);
            
            return true;
        } catch (\PDOException $e) {
            $this->logger->error('Database connection failed', [
                'error' => $e->getMessage(),
                'host' => $host ?? 'unknown',
                'database' => $dbname ?? 'unknown'
            ]);
            $this->connection = null;
            return false;
        }
    }
    
    /**
     * Подготовка SQL-запроса
     * 
     * @param string $sql SQL-запрос
     * @return \PDOStatement|false
     */
    public function prepare(string $sql)
    {
        if (!$this->isConnected()) {
            $this->logger->error('Cannot prepare statement: No database connection');
            if (!$this->reconnect()) {
                return false;
            }
        }
        
        try {
            return $this->connection->prepare($sql);
        } catch (\PDOException $e) {
            $this->logger->error('Database prepare statement failed', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Выполнение SQL-запроса
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @return \PDOStatement|false
     */
    public function query(string $sql, array $params = [])
    {
        if (!$this->isConnected()) {
            $this->logger->error('Cannot execute query: No database connection');
            if (!$this->reconnect()) {
                return false;
            }
        }
        
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt;
        } catch (\PDOException $e) {
            $this->logger->error('Database query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Получение одной записи
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @return array|null Результат запроса или null в случае ошибки
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        
        if ($stmt === false) {
            return null;
        }
        
        $result = $stmt->fetch();
        
        return $result !== false ? $result : null;
    }
    
    /**
     * Получение всех записей
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @return array Результат запроса
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        
        if ($stmt === false) {
            return [];
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Получение значения одного поля
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @param mixed $default Значение по умолчанию
     * @return mixed Результат запроса или значение по умолчанию в случае ошибки
     */
    public function fetchColumn(string $sql, array $params = [], $default = null)
    {
        $stmt = $this->query($sql, $params);
        
        if ($stmt === false) {
            return $default;
        }
        
        $result = $stmt->fetchColumn();
        
        return $result !== false ? $result : $default;
    }
    
    /**
     * Вставка записи в таблицу
     * 
     * @param string $table Имя таблицы
     * @param array $data Данные для вставки
     * @return int|false ID вставленной записи или false в случае ошибки
     */
    public function insert(string $table, array $data)
    {
        if (!$this->isConnected()) {
            $this->logger->error('Cannot insert data: No database connection');
            if (!$this->reconnect()) {
                return false;
            }
        }
        
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ':' . $field;
        }, $fields);
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        $stmt = $this->query($sql, $data);
        
        if ($stmt === false) {
            return false;
        }
        
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * Обновление записей в таблице
     * 
     * @param string $table Имя таблицы
     * @param array $data Данные для обновления
     * @param string $where Условие WHERE
     * @param array $whereParams Параметры условия WHERE
     * @return int|false Количество обновленных записей или false в случае ошибки
     */
    public function update(string $table, array $data, string $where, array $whereParams = [])
    {
        if (!$this->isConnected()) {
            $this->logger->error('Cannot update data: No database connection');
            if (!$this->reconnect()) {
                return false;
            }
        }
        
        $fields = array_keys($data);
        $set = array_map(function($field) {
            return $field . ' = :' . $field;
        }, $fields);
        
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $set),
            $where
        );
        
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        
        if ($stmt === false) {
            return false;
        }
        
        return $stmt->rowCount();
    }
    
    /**
     * Удаление записей из таблицы
     * 
     * @param string $table Имя таблицы
     * @param string $where Условие WHERE
     * @param array $params Параметры условия WHERE
     * @return int|false Количество удаленных записей или false в случае ошибки
     */
    public function delete(string $table, string $where, array $params = [])
    {
        if (!$this->isConnected()) {
            $this->logger->error('Cannot delete data: No database connection');
            if (!$this->reconnect()) {
                return false;
            }
        }
        
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        
        $stmt = $this->query($sql, $params);
        
        if ($stmt === false) {
            return false;
        }
        
        return $stmt->rowCount();
    }
    
    /**
     * Начало транзакции
     * 
     * @return bool Результат операции
     */
    public function beginTransaction(): bool
    {
        if (!$this->isConnected()) {
            $this->logger->error('Cannot begin transaction: No database connection');
            if (!$this->reconnect()) {
                return false;
            }
        }
        
        try {
            return $this->connection->beginTransaction();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to begin transaction', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Фиксация транзакции
     * 
     * @return bool Результат операции
     */
    public function commit(): bool
    {
        if (!$this->isConnected()) {
            $this->logger->error('Cannot commit transaction: No database connection');
            return false;
        }
        
        try {
            return $this->connection->commit();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to commit transaction', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Откат транзакции
     * 
     * @return bool Результат операции
     */
    public function rollBack(): bool
    {
        if (!$this->isConnected()) {
            $this->logger->error('Cannot rollback transaction: No database connection');
            return false;
        }
        
        try {
            return $this->connection->rollBack();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to rollback transaction', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Проверка, находимся ли мы в транзакции
     * 
     * @return bool
     */
    public function inTransaction(): bool
    {
        if (!$this->isConnected()) {
            return false;
        }
        
        return $this->connection->inTransaction();
    }
    
    /**
     * Получение последнего ID вставленной записи
     * 
     * @return string
     */
    public function lastInsertId(): string
    {
        if (!$this->isConnected()) {
            $this->logger->error('Cannot get last insert ID: No database connection');
            return '0';
        }
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * Экранирование идентификатора (имени таблицы или поля)
     * 
     * @param string $identifier Идентификатор
     * @return string Экранированный идентификатор
     */
    public function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
