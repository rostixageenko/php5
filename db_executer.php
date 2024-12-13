<?php
class ActionLogger {
    private $conn;

    public function __construct() {
        $servername = "localhost";
        $username = "root"; // Ваше имя пользователя MySQL
        $password = ""; // Ваш пароль MySQL
        $dbname = "auto_disassembly_station"; // Имя вашей базы данных
        
        // Создаем соединение с базой данных
        $this->conn = new mysqli($servername, $username, $password, $dbname);

        // Проверяем соединение
        if ($this->conn->connect_error) {
            throw new Exception("Ошибка соединения: " . $this->conn->connect_error);
        }
    }

    public function insertAction($actorId, $action) {
        try {
            // Подготовка SQL запроса
            $sql = "INSERT INTO sys_activity_log (actor_id, action_datetime, action) VALUES (?, DEFAULT, ?)";
            $stmt = $this->conn->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Ошибка подготовки запроса: " . $this->conn->error);
            }

            // Связываем параметры
            $stmt->bind_param("ss", $actorId, $action); // "is" означает: integer и string
            
            // Выполняем запрос
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            throw new Exception("Ошибка SQL: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Ошибка: " . $e->getMessage());
        }
    }
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }

    public function commit() {
        $this->conn->commit();
    }

    public function rollBack() {
        $this->conn->rollback();
    }

    public function getConnection() {
        return $this->conn;
    }

    public function __destruct() {
        $this->conn->close();
    }
}
?>