<?php
class TableFunction {
    public $db;
    public $tableName;
    private $isClosed = false;

    public function __construct($dbConnection,$tableName= 'default_table') {
        $this->db = $dbConnection;
        $this->tableName = $tableName;
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }
    public function fetch($conditions = []) {
        $query = "SELECT * FROM " . mysqli_real_escape_string($this->db, $this->tableName);
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        return $this->executeQuery($query);
    }

    public function updateRecord($table, $searchField, $searchValue, $data) {
        // Проверка на наличие обязательных параметров
        if (empty($table) || empty($searchField) || empty($searchValue) || empty($data)) {
            return [
                'message' => 'Ошибка: Все параметры должны быть указаны.',
                'type' => 'error'
            ];
        }

        // Инициализация массивов для обновляемых полей и значений
        $updateFields = [];
        $updateValues = [];

        // Обработка данных для обновления
        foreach ($data as $field => $value) {
            if (!empty($value)) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $value;
            }
        }

        // Проверка наличия обновляемых полей
        if (count($updateFields) === 0) {
            return [
                'message' => 'Ошибка: Не указаны поля для обновления.',
                'type' => 'error'
            ];
        }

        // Формируем SQL-запрос для обновления
        $setClause = implode(", ", $updateFields);
        $sql = "UPDATE $table SET $setClause WHERE $searchField = ?";
        $updateValues[] = $searchValue; // Добавляем значение для условия WHERE

        // Подготовка и выполнение запроса
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($updateValues) - 1) . 's', ...$updateValues); // Подготовка параметров

        if ($stmt->execute()) {
            return [
                'message' => ' успешно изменена.',
                'type' => 'success'
            ];
        } else {
            return [
                'message' => 'Ошибка: ' . $stmt->error,
                'type' => 'error'
            ];
        }
    }

    public function fetchLimited($limit, $conditions = []) {
        $query = "SELECT * FROM " . mysqli_real_escape_string($this->db, $this->tableName);
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " LIMIT " . intval($limit);
        return $this->executeQuery($query);
    }

    private function executeQuery($query) {
        // Выполняем запрос
        if ($result = mysqli_query($this->db, $query)) {
            // Возвращаем ассоциативный массив результатов
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            die('Ошибка выполнения запроса: ' . mysqli_error($this->db));
        }
    }

    public function deleteUser($value, $field = 'id') {
        // Создаем SQL-запрос с динамическим полем
        $stmt = $this->db->prepare("DELETE FROM " . $this->tableName . " WHERE " . $field . " = ?");
        
        // Определяем тип параметра
        $paramType = is_int($value) ? "i" : "s"; // "i" для integer, "s" для string
        $stmt->bind_param($paramType, $value);
    
        try {
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                return 1; // Удаление прошло успешно
            } else {
                return 0; // Удаление не произошло (пользователь не найден)
            }
        } catch (mysqli_sql_exception $e) {
            return "Ошибка удаления пользователя: " . $e->getMessage();
        } finally {
            $stmt->close();
        }
    }
    public function checkPartOrder($partId) {
        $sql = "SELECT idorder FROM auto_parts WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $partId); // Предполагается, что id - это целое число
        $stmt->execute();
        $result = $stmt->get_result();
    
        return $result->fetch_assoc(); // Возвращает ассоциативный массив с idorder
    }
    public function renderTable($data, $title) {
        echo "<h2>" . htmlspecialchars($title) . "</h2>";
        
        if (count($data) > 0) {
            echo "<table>";
            echo "<tr>";
    
            // Выводим заголовки столбцов
            foreach (array_keys($data[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "<th>Действия</th>"; // Заголовок для действий
            echo "</tr>";
    
            foreach ($data as $row) {
                echo "<tr data-id='" . htmlspecialchars($row['id']) . "'>";
                foreach ($row as $key => $cell) {
                    if ($key === 'photo' && !empty($cell)) {
                        echo "<td>";
                        if (is_string($cell)) {
                            // Проверка размера изображения
                            if (strlen($cell) > 55 * 1024) { // Если размер больше 55 КБ
                                echo "<div style='color: red;'>Ошибка: размер изображения превышает 55 КБ.</div>";
                            } else {
                                // Получение типа файла
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $mimeType = finfo_buffer($finfo, $cell);
                                finfo_close($finfo);
    
                                // Контейнер для изображения
                                echo "<div style='width: 300px; height: 200px; overflow: hidden; border: 1px solid #ccc; padding: 10px;'>";
                                echo "<img src='data:{$mimeType};base64," . base64_encode($cell) . "' alt='Изображение' style='width: 100%; height: 100%; object-fit: contain;'>";
                                echo "</div>";
                            }
                        } else {
                            echo "Изображение недоступно";
                        }
                        echo "</td>";
                    } elseif ($key === 'password') {
                        echo "<td>Пароль недоступен для просмотра</td>";
                    } elseif ($this->is_json($cell)) {
                        // Если это JSON, выводим его без кавычек
                        $jsonData = json_decode($cell, true); // Декодируем JSON в массив
                        echo "<td><pre style='white-space: pre-wrap;'>" . htmlspecialchars($this->formatJsonWithoutQuotes($jsonData)) . "</pre></td>";
                    } else {
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                }
                // Кнопка удаления
                echo "<td class='table-cell-delete'>";
                echo "<form method='POST' action='?table=$this->tableName&action=delete' class='delete-form'>";
                echo "<input type='hidden' name='id' value='" . htmlspecialchars($row['id']) . "'>";
                echo "<button type='submit' class='delete-btn'>Удалить</button>";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Нет данных для отображения.</p>";
        }
    }
    
    // Публичная функция для проверки, является ли строка JSON
    public function is_json($string) {
        return is_string($string) && !empty($string) && ($string[0] === '{' || $string[0] === '[') && json_last_error() === JSON_ERROR_NONE;
    }
    
    // Функция для форматирования JSON без кавычек
    private function formatJsonWithoutQuotes($jsonData) {
        // Преобразуем массив в строку без кавычек и индексов
        $result = '';
    
        if (is_array($jsonData)) {
            foreach ($jsonData as $key => $value) {
                // Добавляем название группы (ключ)
                $result .= "$key:\n";
                
                if (is_array($value)) {
                    // Если значение - массив, добавляем его элементы
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            // Рекурсивный вызов для вложенных массивов
                            $result .= $this->formatJsonWithoutQuotes($item);
                        } else {
                            // Добавляем только значение
                            $result .= "  - $item\n"; // Используем отступ для лучшего форматирования
                        }
                    }
                } else {
                    // Если значение не массив, просто добавляем его
                    $result .= "  - $value\n"; // Используем отступ для лучшего форматирования
                }
            }
        }
    
        return trim($result); // Удаляем лишние пробелы в конце
    }
    public function universalSort(string $sortField, string $order): array {
        $query = "SELECT * FROM `$this->tableName` ORDER BY `$sortField` $order";       
        // Выполняем запрос
        return $this->executeQuery($query);
    }

    public function addRecord($data) {
        // Проверка на наличие данных
        if (empty($data) || !is_array($data)) {
            return [
                'message' => 'Ошибка: Неверные данные для добавления.',
                'type' => 'error'
            ];
        }

        // Формирование SQL-запроса
        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), '?'));
        $sql = "INSERT INTO " . mysqli_real_escape_string($this->db, $this->tableName) . " ($fields) VALUES ($placeholders)";

        // Подготовка параметров
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('s', count($data)); // Предполагаем, что все данные - строки
        $stmt->bind_param($types, ...array_values($data));

        // Выполнение запроса
        if ($stmt->execute()) {
            return [
                'message' => 'Запись успешно добавлена.',
                'type' => 'success'
            ];
        } else {
            return [
                'message' => 'Ошибка: ' . $stmt->error,
                'type' => 'error'
            ];
        }
    }

    public function universalSearch($params, $searchableFields, $dateFields = []) 
    {
        $conditions = [];
        $values = [];
        $types = ''; // Строка для хранения типов данных
        
        // Обработка всех полей для поиска
        foreach ($searchableFields as $field) {
            if (isset($params[$field])) {
                
                //Определение типа данных на основе названия поля
                if (stripos($field, 'id') !== false) {
                    //если в названии поля есть "id", используем целочисленный тип
               
                    $conditions[] = "$field = ?";
                    $values[] = (int)$params[$field]; // Приводим к целому числу
                    $types .= 'i'; 
                    // Тип целого числа
                } else {
                    // Для остальных полей используем строковый тип
                    $conditions[] = "$field = ?";
                    $values[] = (string)$params[$field]; // Приводим к строке
                    $types .= 's'; // Тип строки
                }
            }
        }

        // Проверка диапазона дат
        foreach ($dateFields as $field) {

            $startDate = $params[$field . '_start'] ?? null;
            $endDate = $params[$field . '_end'] ?? null;

            // $file="debug.txt";
            // $datafile=  $params['year_production_start'];
            // file_put_contents($file, $datafile);

            if (!empty($startDate) && !empty($endDate)) {
                // Проверка, что конечная дата не раньше начальной
                if ($startDate > $endDate) {
                    return [
                        'success' => false,
                        'message' => 'Ошибка: конечная дата не может быть раньше начальной.'
                    ]; // Если ошибка, возвращаем сообщение
                }
                $conditions[] = "$field >= ?";
                $values[] = $startDate;
                $types .= 's'; // Дата считается строкой для MySQL

                $conditions[] = "$field <= ?";
                $values[] = $endDate;
                $types .= 's'; // Дата считается строкой для MySQL
            } elseif (!empty($startDate)) {
                $conditions[] = "$field >= ?";
                $values[] = $startDate;
                $types .= 's'; // Дата считается строкой для MySQL
            } elseif (!empty($endDate)) {
                $conditions[] = "$field <= ?";
                $values[] = $endDate;
                $types .= 's'; // Дата считается строкой для MySQL
            }
        }

        // Формирование SQL-запроса
        $sql = "SELECT * FROM " . mysqli_real_escape_string($this->db, $this->tableName);
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Подготовка и выполнение запроса
        $stmt = $this->db->prepare($sql);

        // Проверка наличия значений для привязки
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values); // Подготовка параметров
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            return [
                'success' => true,
                'data' => $result->fetch_all(MYSQLI_ASSOC) // Получаем массив всех результатов
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $stmt->error
            ];
        }
    }
    public function getLastInsertedId() {
        // Получаем ID последней вставленной записи
        return $this->db->insert_id;
    }

    public function getUserByLogin($login) {
        $sql = "SELECT * FROM users WHERE login = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $login); // Привязываем параметр
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // Возвращаем ассоциативный массив
    }

    public function getCustomerByLogin($login) {
        $sql = "SELECT * FROM customers WHERE login = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $login); // Привязываем параметр
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // Возвращаем ассоциативный массив
    }

    // Метод для получения пользователя по email
    public function getUserByEmail($email) {
        // Подготовка SQL-запроса
        $sql = "SELECT * FROM customers WHERE email = ?";
        
        // Подготовка и выполнение запроса
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Ошибка подготовки SQL-запроса: ' . $this->db->error);
        }

        // Привязываем параметр
        $stmt->bind_param('s', $email);
        $stmt->execute();
        
        // Получаем результат
        $result = $stmt->get_result();
        
        // Проверяем, найден ли пользователь
        if ($result->num_rows > 0) {
            return $result->fetch_assoc(); // Возвращаем данные пользователя как ассоциативный массив
        } else {
            return null; // Если пользователь не найден, возвращаем null
        }
    }

    public function getSupplierByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getCarByVIN($vin) {
        // Подготовка SQL-запроса для проверки существования VIN
        $stmt = $this->db->prepare("SELECT * FROM cars WHERE VIN_number = ?");
        $stmt->bind_param('s', $vin); // Привязываем параметр
    
        // Выполнение запроса
        $stmt->execute();
        $result = $stmt->get_result();
    
        // Проверяем, был ли найден автомобиль
        if ($result->num_rows > 0) {
            return $result->fetch_assoc(); // Возвращаем ассоциативный массив с данными автомобиля
        } else {
            return null; // Если автомобиль не найден, возвращаем null
        }
    }

    public function getSupplierById($id) {
        // Подготовка SQL-запроса для проверки существования поставщика
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->bind_param('i', $id); // Привязываем параметр
    
        // Выполнение запроса
        $stmt->execute();
        $result = $stmt->get_result();
    
        // Проверяем, был ли найден поставщик
        if ($result->num_rows > 0) {
            return $result->fetch_assoc(); // Возвращаем ассоциативный массив с данными поставщика
        } else {
            return null; // Если поставщик не найден, возвращаем null
        }
    }
    public function beginTransaction() {
        $this->db->begin_transaction();
    }

    public function commit() {
        $this->db->commit();
    }

    public function rollBack() {
        $this->db->rollback();
    }

    public function getConnection() {
        return $this->db;
    }

}
