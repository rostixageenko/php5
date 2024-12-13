<?php
class TableFunction {
    public $db;
    public $tableName;

    public function __construct($dbConnection, $tableName) {
        $this->db = $dbConnection;
        $this->tableName = $tableName;
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
                'message' => 'Запчасть успешно изменена.',
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
                //     $file="debug.txt";
                // $datafile=$params['id'] ;
                // file_put_contents($file, $datafile);
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
}
// Подключение к базе данных
include('server.php');

$selectedTable = isset($_GET['table']) ? $_GET['table'] : 'users';

$usersTable = new TableFunction($db, 'users');
$partsTable = new TableFunction($db, 'auto_parts');
$ordersTable = new TableFunction($db, 'orders');
$customersTable = new TableFunction($db, 'customers');
$staffsTable = new TableFunction($db, 'staff');
$suppliersTable = new TableFunction($db, 'suppliers');
$inventoryTable = new TableFunction($db, 'inventory');
$carsTable = new TableFunction($db, 'cars');
$cartTable= new TableFunction($db, 'cart');
$staffGarageTable= new TableFunction($db, 'staff_garage');
$historyOperationsWithAutopartTable=new TableFunction($db, 'history_operations_with_autoparts');
$historyOperationsWithCarsTable=new TableFunction($db, 'history_operations_with_car');

$logger = new ActionLogger();

// Получение количества строк для отображения
$rowCount = isset($_POST['row_count']) ? intval($_POST['row_count']) : 25; // По умолчанию 25 строк

// Получение данных с учетом ограничения
$users = $usersTable->fetchLimited($rowCount);
$parts = $partsTable->fetchLimited($rowCount);
$orders = $ordersTable->fetchLimited($rowCount);
$customers = $customersTable->fetchLimited($rowCount);
$staffs = $staffsTable->fetchLimited($rowCount);
$suppliers = $suppliersTable->fetchLimited($rowCount);
$inventory = $inventoryTable->fetchLimited($rowCount);
$cars = $carsTable->fetchLimited($rowCount);

$message = "";
$messageType = "success"; // По умолчанию тип сообщения

 // сортировка
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sort_field'], $_POST['sort_order'])&&isset($_POST['sort_table'])) {
    $sortField =  isset($_POST['sort_field']) ? trim($_POST['sort_field']) : '';
    $sort_order=  isset($_POST['sort_order']) ? trim($_POST['sort_order']) : '';
 // true для по возрастанию, false для по убыванию
    // Сортируем данные
    switch ($selectedTable) {
        case 'users':
            $users = $usersTable->universalSort($sortField, $sort_order);
            break;
        case 'auto_parts':
            $parts = $partsTable->universalSort($sortField, $sort_order); 
            break;
        case 'orders':
            $orders = $ordersTable->universalSort($sortField, $sort_order);
            break;
        case 'customers':
            $customers = $customersTable->universalSort($sortField, $sort_order);
            break;
        case 'staff':
            $staffs = $staffsTable->universalSort($sortField, $sort_order);
            break;
        case 'suppliers':
            $suppliers = $suppliersTable->universalSort($sortField, $sort_order);
            break;
        case 'cars':
            $cars = $carsTable->universalSort($sortField, $sort_order);
            break;
        default:
            // Обработка случая, когда выбранная таблица не поддерживается
            $message = "Ошибка: выбранная таблица не поддерживается.";
            $messageType = "error";
            break;
    }

} 

// Поиск пользователей
$searchConditions = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_users'])) {
   // $file = 'C:\Users\37529\OneDrive\Рабочий стол\log.txt';
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $type_role = isset($_POST['type_role']) ? trim($_POST['type_role']) : '';

    // Добавляем условия поиска только для заполненных полей
    if (!empty($id)) {
        $searchConditions[] = "id = " . intval($id);
        //file_put_contents($file, 1);
    }
    if (!empty($login)) {
        $searchConditions[] = "login LIKE '%" . mysqli_real_escape_string($db, $login) . "%'";
    }
    if (!empty($type_role) && in_array((int)$type_role, [0, 1, 2])) {
        $searchConditions[] = "type_role = " . intval($type_role);
    }
    //file_put_contents($file, 1);
    $users = $usersTable->fetch($searchConditions);
    // Проверка, есть ли результаты
    if (empty($users)) {
        $message = "Пользователи не найдены.";
        $messageType = "error"; // Ошибка
    } else {
        $message = ""; // Очистка сообщения, если нашли пользователей
    }
}

// Добавление нового пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedTable === 'users' &&isset($_POST['add_users'])) 
{
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $type_role = isset($_POST['type_role']) ? trim($_POST['type_role']) : '';
    $garage_id = isset($_POST['garage_id']) ? trim($_POST['garage_id']) : ''; // Получаем ID гаража

    if (!empty($login) && !empty($password)) 
    {
            // Проверка на существование логина
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE login = ?");
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $message = "Пользователь с таким логином уже существует.";
                $messageType = "error"; // Ошибка
            } else {
                // Проверка на существование ID гаража
                $stmt_garage = $db->prepare("SELECT COUNT(*) FROM garage WHERE id = ?");
                $stmt_garage->bind_param("i", $garage_id);
                $stmt_garage->execute();
                $stmt_garage->bind_result($garage_count);
                $stmt_garage->fetch();
                $stmt_garage->close();

                if ($garage_count === 0 && !empty($garage_id)){
                    $message = "ID гаража не существует.";
                    $messageType = "error"; // Ошибка
                } else {
                    $hashedPassword = md5($password);

                    // Вставка в таблицу users
                    $stmt = $db->prepare("INSERT INTO users (login, password, type_role) VALUES (?, ?, ?)");
                    $stmt->bind_param("ssi", $login, $hashedPassword, $type_role);
                    
                    try {
                        if ($stmt->execute()) {
                            $old_login = $_SESSION['login'];
                            $old_id_user = $_SESSION['user_id'];
                            $type_role1=$_SESSION['type_role'];

                            $Actstr = "Пользователь $old_login типа '$type_role1' добавил нового пользователя $login типа $type_role.";
                            $dbExecutor->insertAction($old_id_user, $Actstr);

                            // Вставка в таблицу staff или customers в зависимости от type_role
                            switch ($type_role) {
                                case 1:
                                    // Вставка в таблицу staff с idpost = 9
                                    $stmt_staff = $db->prepare("INSERT INTO staff (login, idpost) VALUES (?, 9)");
                                    $stmt_staff->bind_param("s", $login);
                                    $stmt_staff->execute();
                                    $idstaff = $stmt_staff->insert_id; // Получаем ID нового сотрудника
                                    $stmt_staff->close();

                                    // Вставка в staff_garage
                                    $stmt_garage = $db->prepare("INSERT INTO staff_garage (idstaff, idgarage) VALUES (?, 15)");
                                    $stmt_garage->bind_param("i", $idstaff);
                                    $stmt_garage->execute();
                                    $stmt_garage->close();
                                    break;

                                case 2:
                                    // Вставка в таблицу staff без idpost
                                    $stmt_staff = $db->prepare("INSERT INTO staff (login) VALUES (?)");
                                    $stmt_staff->bind_param("s", $login);
                                    $stmt_staff->execute();
                                    $idstaff = $stmt_staff->insert_id; // Получаем ID нового сотрудника
                                    $stmt_staff->close();

                                    // Вставка в staff_garage
                                    $stmt_garage = $db->prepare("INSERT INTO staff_garage (idstaff, idgarage) VALUES (?,? )");
                                    $stmt_garage->bind_param("ii", $idstaff,   $garage_id);
                                    $stmt_garage->execute();
                                    $stmt_garage->close();
                                    break;

                                case 0:
                                    // Вставка в таблицу customers
                                    $stmt_customers = $db->prepare("INSERT INTO customers (login) VALUES (?)");
                                    $stmt_customers->bind_param("s", $login);
                                    $stmt_customers->execute();
                                    
                                    // Получаем id нового customer
                                    $customerId = $stmt_customers->insert_id; // Получаем ID нового customer
                                    $stmt_customers->close();
        
                                    // Вставка в таблицу cart для нового customer
                                    $stmt_cart = $db->prepare("INSERT INTO cart (idcustomer) VALUES (?);");
                                    $stmt_cart->bind_param("i", $customerId);
                                    $stmt_cart->execute();
                                    $stmt_cart->close();
                                    break;
                            }

                            $message = "Пользователь добавлен успешно.";
                            $messageType = "success"; // Успешное сообщение
                            $users = $usersTable->fetch(); // Обновляем данные
                        }
                    } catch (mysqli_sql_exception $e) {
                        $message = "Ошибка добавления пользователя: " . $e->getMessage();
                        $messageType = "error"; // Ошибка
                    }

                    $stmt->close();
                }
            }
    } else {
        $message = "Пожалуйста, заполните все поля.";
        $messageType = "error"; // Ошибка
    }
}
// Изменение пароля пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedTable === 'users' && isset($_GET['action']) && $_GET['action'] === 'change_password') {
    $change_login = isset($_POST['change_login']) ? trim($_POST['change_login']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

    if (!empty($change_login) && !empty($new_password)) {
        $hashedNewPassword = md5($new_password);

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE login = ?");
        $stmt->bind_param("ss", $hashedNewPassword, $change_login);
        
        try {
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $login = $_SESSION['login'];
                $id_user = $_SESSION['user_id'];
                $type_role=$_SESSION['type_role'];

                $Actstr = "Пользователь $login типа '$type_role' изменил пароль пользователю $change_login.";
                $dbExecutor->insertAction($id_user, $Actstr);
                $message = "Пароль пользователя изменен успешно.";
                $messageType = "success"; // Успешное сообщение
            } else {
                $message = "Пользователь не найден или пароль не изменен.";
                $messageType = "error"; // Ошибка
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Ошибка изменения пароля: " . $e->getMessage();
            $messageType = "error"; // Ошибка
        }
        
        $stmt->close();
    } else {
        $message = "Пожалуйста, заполните все поля.";
        $messageType = "error"; // Ошибка
    }
}

// Вывод сообщения
if (!empty($message)) {
    echo "<div class='$messageType'>$message</div>";
}

// Удаление пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete' && $_GET['table'] === 'users') {
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Получаем login и type_role пользователя
    $stmt = $db->prepare("SELECT id, login, type_role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row) {
        $login_delete_user = $row['login'];
        $type_role = $row['type_role'];

        // Начинаем транзакцию
        $db->begin_transaction();

        try {
            // Удаляем пользователя из таблицы users
            if ($usersTable->deleteUser($id) !== 1) {
                throw new Exception("Ошибка: пользователь не найден или не удалось удалить.");
            }

            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role1 = $_SESSION['type_role'];

            // Логируем действие
            $Actstr = "Пользователь $login типа '$type_role1' удалил пользователя $login_delete_user.";
            $dbExecutor->insertAction($id_user, $Actstr);

            // Удаляем из соответствующей таблицы
            if ($type_role == 0) {
                // Удаляем из таблицы cart и customers
                $stmt = $db->prepare("SELECT id FROM customers WHERE login = ?");
                $stmt->bind_param("s", $login_delete_user);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $id_delete_user = $row["id"];
                
                $stmt_cart = $db->prepare("DELETE FROM cart WHERE idcustomer = ?");
                $stmt_cart->bind_param("i", $id_delete_user);
                $stmt_cart->execute();
                $stmt_cart->close();

                $stmt_customers = $db->prepare("DELETE FROM customers WHERE login = ?");
                $stmt_customers->bind_param("s", $login_delete_user);
                $stmt_customers->execute();
                $stmt_customers->close();
            } elseif ($type_role == 1 || $type_role == 2) {
                // Удаляем из таблицы staff
                $stmt = $db->prepare("SELECT id FROM staff WHERE login = ?");
                $stmt->bind_param("s", $login_delete_user);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $id_delete_user = $row["id"];
                
                $stmt_staff = $db->prepare("DELETE FROM staff_garage WHERE idstaff = ?");
                $stmt_staff->bind_param("i", $id_delete_user);
                $stmt_staff->execute();
                $stmt_staff->close();

                $stmt_staff = $db->prepare("DELETE FROM history_operations_with_autoparts WHERE idstaff = ?");
                $stmt_staff->bind_param("i", $id_delete_user);
                $stmt_staff->execute();
                $stmt_staff->close();

                $stmt_staff = $db->prepare("DELETE FROM history_operations_with_car WHERE idstaff = ?");
                $stmt_staff->bind_param("i", $id_delete_user);
                $stmt_staff->execute();
                $stmt_staff->close();

                $stmt_staff = $db->prepare("DELETE FROM staff WHERE login = ?");
                $stmt_staff->bind_param("s", $login_delete_user);
                $stmt_staff->execute();
                $stmt_staff->close();
            }

            // Если все операции прошли успешно, зафиксируем транзакцию
            $db->commit();
            $message = "Пользователь успешно удален.";
            $messageType = "success";
        } catch (Exception $e) {
            // В случае ошибки откатываем транзакцию
            $db->rollback();
            $message = $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "Ошибка: пользователь не найден.";
        $messageType = "error";
    }
}

//добавление запчасти
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['table']) && $_GET['table'] === 'auto_parts'&&isset($_POST['add_part'])) {
    $hasError = false; // Флаг для отслеживания ошибок

    // Проверка на заполнение обязательных полей
    if (empty($_POST['part_name']) || empty($_POST['article']) || empty($_POST['condition']) || empty($_POST['price']) || empty($_POST['car_id']) || empty($_POST['garage_id'])) {
        $message = "Пожалуйста, заполните все обязательные поля.";
        $messageType = "error"; // Ошибка
        $hasError = true; // Устанавливаем флаг ошибки
    } else {
        // Получаем данные из формы
        $name_parts = $_POST['part_name'];
        $article = $_POST['article'];
        $condition = $_POST['condition'];
        $purchase_price = $_POST['price'];
        $description = $_POST['description'] ?? null; // Необязательное поле
        $idcar = $_POST['car_id'];
        $idgarage = $_POST['garage_id']; // Обязательное поле

        // Проверка уникальности артикула
        $stmt = $db->prepare("SELECT COUNT(*) FROM auto_parts WHERE article = ?");
        $stmt->bind_param("s", $article);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $message = "Ошибка: Артикул уже существует.";
            $messageType = "error"; // Ошибка
            $hasError = true; // Устанавливаем флаг ошибки
        }

        // Проверка существования idcar в таблице car
        if (!$hasError) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM cars WHERE id = ?");
            $stmt->bind_param("i", $idcar);
            $stmt->execute();
            $stmt->bind_result($carExists);
            $stmt->fetch();
            $stmt->close();

            if ($carExists === 0) {
                $message = "Ошибка: Указанный idcar не существует.";
                $messageType = "error"; // Ошибка
                $hasError = true; // Устанавливаем флаг ошибки
            }
        }

        // Проверка диапазона idgarage
        if (!$hasError) {
            if ($idgarage < 9 || $idgarage > 13) {
                $message = "Ошибка: Значение idgarage должно быть между 9 и 13.";
                $messageType = "error"; // Ошибка
                $hasError = true; // Устанавливаем флаг ошибки
            }
        }

        // // Проверка марки машины
        if (!$hasError) {
            // Получаем марку машины
            $stmt = $db->prepare("SELECT brand FROM cars WHERE id = ?");
            $stmt->bind_param("i", $idcar);
            $stmt->execute();
            $stmt->bind_result($carBrand);
            $stmt->fetch();
            $stmt->close();

            // Получаем марки машин в гараже
            $stmt = $db->prepare("SELECT idcar_brands FROM garage_car_brands WHERE idgarage = ?"); // Предполагаем, что есть таблица garage_brands
            $stmt->bind_param("i", $idgarage);
            $stmt->execute();
            $result = $stmt->get_result();

            $allowedBrands = [];
            while ($row = $result->fetch_assoc()) {
                $allowedBrands[] = $row['idcar_brands']; // Сохраняем все марки в массив
            }
            $stmt->close();

            // Проверяем, содержится ли марка машины в списке марок гаража
            if (!in_array($carBrand, $allowedBrands)) {
                $message = "Ошибка: Марка машины не соответствует марке машины гаража.";
                $messageType = "error"; // Ошибка
                $hasError = true; // Устанавливаем флаг ошибки
            }
        }

        // Проверка загрузки файла (необязательное поле)
        $fileData = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];

            // Логирование информации о файле
            file_put_contents('debug.txt', print_r($file, true));

            // Проверка размера файла
            if ($file['size'] > 10 * 1024 * 1024) {
                $message = "Размер файла не должен превышать 10 МБ.";
                $messageType = "error";
                $hasError = true; // Устанавливаем флаг ошибки
            }

            // Проверка типа файла
            if (!$hasError) {
                $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif'];
                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $message = "Допустимые типы файлов: .jpeg, .jpg, .png, .gif.";
                    $messageType = "error";
                    $hasError = true; // Устанавливаем флаг ошибки
                }
            }

            // Проверка корректности изображения
            if (!$hasError) {
                if (!getimagesize($file['tmp_name'])) {
                    $message = "Изображение повреждено. Замените его на не поврежденный вариант.";
                    $messageType = "error";
                    $hasError = true; // Устанавливаем флаг ошибки
                }
            }

            // Чтение содержимого файла
            if (!$hasError) {
                $fileData = file_get_contents($file['tmp_name']);
            }
        }
        if (!$hasError && $fileData !== null && strlen($fileData) > 55 * 1024) {
            $fileData = compressImage($fileData); // Сжимаем изображение
        }
        // Если ошибок нет, выполняем SQL-запрос для вставки данных
        if (!$hasError) {
            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role=$_SESSION['type_role'];

            // Логируем действие
            $Actstr = "Пользователь $login типа '$type_role' добавил запчасть, артикул: $article";
            $dbExecutor->insertAction($id_user, $Actstr);

            $stmt = $db->prepare("INSERT INTO auto_parts (name_parts, article, `condition`, purchase_price, description, idcar, idgarage, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssisss", $name_parts, $article, $condition, $purchase_price, $description, $idcar, $idgarage, $fileData);

            if ($stmt->execute()) {
                 $file="debug.txt";
                 $data="hello";
                 file_put_contents($file, $data );
                $message = "Запчасть успешно добавлена.";
                $messageType = "success"; // Успех
            } else {
                $message = "Ошибка: " . $stmt->error;
                $messageType = "error"; // Ошибка
            }

            $stmt->close();
        }
    }
}

//изменение данных о запчастях
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['table']) && $_GET['table'] === 'auto_parts' && isset($_GET['action']) && $_GET['action'] === 'update_part')
{
    // Получаем значения из формы
    $search_field = $_POST['search_field'];
    $search_value = $_POST['search_value'];

    // Инициализируем массив данных для обновления
    $data = [];

    // Добавляем только те поля, которые заданы
    if (!empty($_POST['new_part_name'])) {
        $data['name_parts'] = $_POST['new_part_name'];
    }
    if (!empty($_POST['new_article'])) {
        $data['article'] = $_POST['new_article'];
    }
    if (!empty($_POST['new_condition'])) {
        $data['condition'] = $_POST['new_condition'];
    }
    if (!empty($_POST['new_price'])) {
        $data['purchase_price'] = $_POST['new_price'];
    }
    if (!empty($_POST['new_description'])) {
        $data['description'] = $_POST['new_description'];
    }
    if (!empty($_POST['new_car_id'])) {
        $data['idcar'] = $_POST['new_car_id'];
    }
    if (!empty($_POST['new_garage_id'])) {
        $data['idgarage'] = $_POST['new_garage_id'];
    }

    $hasError = false;
    if (!empty($_POST['new_article'])){
        $stmt = $db->prepare("SELECT COUNT(*) FROM auto_parts WHERE article = ?");
        $stmt->bind_param("s", $data['article'] );
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $message = "Ошибка: Артикул уже существует.";
            $messageType = "error"; // Ошибка
            $hasError = true; // Устанавливаем флаг ошибки
        }
    }

    // Проверка существования idcar в таблице car
    if (!$hasError&&!empty($_POST['new_car_id'])) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM cars WHERE id = ?");
        $stmt->bind_param("i", $data['idcar']);
        $stmt->execute();
        $stmt->bind_result($carExists);
        $stmt->fetch();
        $stmt->close();

        if ($carExists === 0) {
            $message = "Ошибка: Указанный idcar не существует.";
            $messageType = "error"; // Ошибка
            $hasError = true; // Устанавливаем флаг ошибки
        }
    }

    // Проверка диапазона idgarage
    if (!$hasError&&!empty($_POST['new_garage_id'])) {
        if ($data['idgarage']  < 9 || $data['idgarage']  > 13) {
            $message = "Ошибка: Значение idgarage должно быть между 9 и 13.";
            $messageType = "error"; // Ошибка
            $hasError = true; // Устанавливаем флаг ошибки
        }
    }

    // Проверка на наличие ошибок перед выполнением обновления
    if (!$hasError) 
    {
        // Формируем запрос обновления
        $setClause = [];
        foreach ($data as $key => $value) {
            // Оборачиваем 'condition' в обратные кавычки
            $columnName = ($key === 'condition') ? '`condition`' : $key;
            $setClause[] = "$columnName = ?";
        }

        // Проверка наличия полей для обновления
        if (empty($setClause)) {
            $message = 'Нет данных для обновления.';
            $messageType = 'error';
        } else
        {
            $setClause = implode(", ", $setClause);
            $sql = "UPDATE auto_parts SET $setClause WHERE $search_field = ?";

            // Подготовка и выполнение запроса
            $stmt = $db->prepare($sql); // Убедитесь, что $db - это ваш объект подключения
            
            // Подготовка значений для привязки
            $updateValues = array_values($data);
            $updateValues[] = $search_value; // Добавляем значение для условия WHERE

            // Подготовка типов для bind_param
            $types = str_repeat('s', count($updateValues)); // предполагаем, что все значения будут строками

            // Привязываем параметры
            $stmt->bind_param($types, ...$updateValues);

            if ($stmt->execute()) {
                $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role= $_SESSION['type_role'];

            // Логируем действие
            $Actstr = "Пользователь $login типа '$type_role' изменил информацию о запчасти $search_field=$search_value";
            $dbExecutor->insertAction($id_user, $Actstr);

                $message = 'Запчасть успешно изменена.';
                $messageType = 'success';
            } else {
                $message = 'Ошибка: ' . $stmt->error;
                $messageType = 'error';
            }
        }
    
    }
}


//изображение изменение
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['table']) && $_GET['table'] === 'auto_parts' && isset($_POST['update_image'])) {
    $hasError = false; // Флаг для отслеживания ошибок

    // Проверка на заполнение обязательных полей
    if (empty($_POST['image_part_id'])) {
        $message = "Пожалуйста, укажите ID запчасти.";
        $messageType = "error"; // Ошибка
        $hasError = true; // Устанавливаем флаг ошибки
    } else {
        $partId = $_POST['image_part_id'];

        // Проверка существования запчасти по ID
        $stmt = $db->prepare("SELECT COUNT(*) FROM auto_parts WHERE id = ?");
        $stmt->bind_param("i", $partId);
        $stmt->execute();
        $stmt->bind_result($partExists);
        $stmt->fetch();
        $stmt->close();

        if ($partExists === 0) {
            $message = "Ошибка: Указанный ID запчасти не существует.";
            $messageType = "error"; // Ошибка
            $hasError = true; // Устанавливаем флаг ошибки
        }
    }

    // Проверка загрузки файла (необязательное поле)
    $fileData = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];

        // Логирование информации о файле
        file_put_contents('debug.txt', print_r($file, true));

        // Проверка размера файла
        if ($file['size'] > 2 * 1024 * 1024) {
            $message = "Размер файла не должен превышать 10 МБ.";
            $messageType = "error";
            $hasError = true; // Устанавливаем флаг ошибки
        }

        // Проверка типа файла
        if (!$hasError) {
            $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                $message = "Допустимые типы файлов: .jpeg, .jpg, .png, .gif.";
                $messageType = "error";
                $hasError = true; // Устанавливаем флаг ошибки
            }
        }

        // Проверка корректности изображения
        if (!$hasError) {
            if (!getimagesize($file['tmp_name'])) {
                $message = "Изображение повреждено. Замените его на не поврежденный вариант.";
                $messageType = "error";
                $hasError = true; // Устанавливаем флаг ошибки
            }
        }

        // Чтение содержимого файла
        if (!$hasError) {
            $fileData = file_get_contents($file['tmp_name']);
        }
    }

    // Если ошибок нет, выполняем сжатие изображения, если оно больше 55 КБ
    if (!$hasError && $fileData !== null && strlen($fileData) > 55 * 1024) {
        $fileData = compressImage($fileData); // Сжимаем изображение
    }

    // Если ошибок нет, выполняем SQL-запрос для обновления изображения
    if (!$hasError) {
        $stmt = $db->prepare("UPDATE auto_parts SET photo = ? WHERE id = ?");
        $stmt->bind_param("si", $fileData, $partId);

        if ($stmt->execute()) {
            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role = $_SESSION['type_role'];
            // Логируем действие
            $Actstr = "Пользователь $login типа '$type_role' изменил изображение для запчасти id=$partId";
            $dbExecutor->insertAction($id_user, $Actstr);

            $message = "Изображение успешно обновлено.";
            $messageType = "success"; // Успех
        } else {
            $message = "Ошибка: " . $stmt->error;
            $messageType = "error"; // Ошибка
        }

        $stmt->close();
    }
}

// Функция для сжатия изображения до 50 КБ
function compressImage($imageData) {
    $image = imagecreatefromstring($imageData);
    if (!$image) {
        return $imageData; // Если не удалось создать изображение, возвращаем оригинал
    }

    // Начальное значение качества
    $quality = 90; // Начальное качество, чтобы начать с высокого качества
    $compressedImageData = null;

    // Сжимаем изображение, пока его размер не станет меньше 50 КБ
    do {
        ob_start(); // Начинаем буферизацию вывода
        imagejpeg($image, null, $quality); // Сохраняем изображение в буфер с заданным качеством
        $compressedImageData = ob_get_contents(); // Получаем содержимое буфера
        ob_end_clean(); // Очищаем буфер

        // Уменьшаем качество
        $quality -= 10; // Уменьшаем на 10, чтобы быстрее достигнуть нужного размера
    } while (strlen($compressedImageData) > 50 * 1024 && $quality > 0); // Проверяем размер и качество

    // Если все еще больше 50 КБ, изменяем размер изображения
    if (strlen($compressedImageData) > 50 * 1024) {
        $compressedImageData = resizeAndCompressImage($compressedImageData, 800, 800); // Уменьшаем размер, если необходимо
    }

    imagedestroy($image); // Освобождаем память
    return $compressedImageData; // Возвращаем сжатое изображение
}

function resizeAndCompressImage($imageData, $maxWidth, $maxHeight) {
    $image = imagecreatefromstring($imageData);
    if (!$image) {
        return $imageData; // Возвращаем оригинал, если создание не удалось
    }

    // Получаем оригинальные размеры
    list($width, $height) = getimagesizefromstring($imageData);
    $ratio = $width / $height;

    // Рассчитываем новые размеры
    if ($width > $height) {
        $newWidth = $maxWidth;
        $newHeight = $maxWidth / $ratio;
    } else {
        $newHeight = $maxHeight;
        $newWidth = $maxHeight * $ratio;
    }

    // Создаем новое изображение с новыми размерами
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Сжимаем изображение и сохраняем в буфер
    ob_start();
    imagejpeg($resizedImage, null, 75); // Используем фиксированное качество
    $compressedImageData = ob_get_contents();
    ob_end_clean();

    // Освобождаем память
    imagedestroy($image);
    imagedestroy($resizedImage);

    return $compressedImageData; // Возвращаем сжатое и измененное изображение
}

//поиск автозапчастей

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_parts'])) {
    $partId = isset($_POST['search_part_id']) ? trim($_POST['search_part_id']) : '';
    $article = isset($_POST['search_article']) ? trim($_POST['search_article']) : '';
    $partName = isset($_POST['search_part_name']) ? trim($_POST['search_part_name']) : '';
    $carId = isset($_POST['search_car_id']) ? trim($_POST['search_car_id']) : '';
    $garageId = isset($_POST['search_garage_id']) ? trim($_POST['search_garage_id']) : '';
    
    $searchConditions = [];
    // Добавляем условия поиска только для заполненных полей
    if (!empty($partId)) {
        $searchConditions[] = "id = " . intval($partId);
    }
    if (!empty($article)) {
        $searchConditions[] = "article LIKE '%" . mysqli_real_escape_string($db, $article) . "%'";
    }
    if (!empty($partName)) {
        $searchConditions[] = "name_parts LIKE '%" . mysqli_real_escape_string($db, $partName) . "%'";
    }
    if (!empty($carId)) {
        $searchConditions[] = "idcar = " . intval($carId);
    }
    if (!empty($garageId)) {
        $searchConditions[] = "idgarage = " . intval($garageId);
    }

    // Выполняем поиск запчастей с учетом условий
    $parts = $partsTable->fetch($searchConditions);

    // Проверка, есть ли результаты
    if (empty($parts)) {
        $message = "Запчасти не найдены.";
        $messageType = "error"; // Ошибка
    } else {
        
        $message = ""; // Очистка сообщения, если нашли запчасти
    }
}

//удаление автозапчасти
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete'&& $_GET['table'] === 'auto_parts') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        // Удаляем запись
        
       $db->query("ALTER TABLE cart_auto_parts DROP FOREIGN KEY cart_auto_parts_ibfk_2;");
       $db->query("ALTER TABLE history_operations_with_autoparts DROP FOREIGN KEY history_operations_with_autoparts_ibfk_2;");
    
        if ($partsTable->deleteUser($id) === 1) {
            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role=$_SESSION['type_role'];
            // Логируем действие
            $Actstr = "Пользователь $login типа '$type_role' удалил автозапчасть id=$id.";
            $dbExecutor->insertAction($id_user, $Actstr);

                $stmt_cart = $db->prepare("DELETE FROM history_operations_with_autoparts WHERE idautoparts = ?");
                $stmt_cart->bind_param("i", $id);
                $stmt_cart->execute();
                $stmt_cart->close();
                $stmt_customers = $db->prepare("DELETE FROM cart_auto_parts WHERE idautoparts = ?");
                $stmt_customers->bind_param("i", $id);
                $stmt_customers->execute();
                $stmt_customers->close();
            
            $message = "Запчасть успешно удален.";
            $messageType = "success";
        } else {
            $message = "Ошибка: запчасть не найдена или не удалось удалить.";
            $messageType = "error";
        }

    $db->query("ALTER TABLE history_operations_with_autoparts ADD CONSTRAINT `history_operations_with_autoparts_ibfk_2` FOREIGN KEY (`idautoparts`) REFERENCES `auto_parts` (`id`);");
    $db->query("ALTER TABLE  cart_auto_parts ADD CONSTRAINT `cart_auto_parts_ibfk_2` FOREIGN KEY (`idautoparts`) REFERENCES `auto_parts` (`id`);");
}




// добавление заказа

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_order'])) {
    $suc = true; // Флаг успешного выполнения
    // Собираем данные из формы
    $data = [
        'type_order' => $_POST['type_order'],
        'status' => $_POST['status'],
        'purchase_price' => $_POST['purchase_price'],
        'idcustomer' => $_POST['id_customer']
    ];

    // Вызов функции добавления заказа
    $result = $ordersTable->addRecord($data);

    // Если заказ успешно добавлен
    if ($result) {
        // Получаем ID созданного заказа
        $orderId = $ordersTable->getLastInsertedId(); // Предполагаем, что эта функция существует

        // Проверяем, есть ли запчасти
        if (!empty($_POST['parts'])) {
            foreach ($_POST['parts'] as $partId) {
                // Проверяем, связана ли запчасть с другим заказом
                $partCheck = $ordersTable->checkPartOrder($partId); // Предполагаем, что этот метод существует

                if ($partCheck && $partCheck['idorder'] !== null) {
                    $message = 'Ошибка: Запчасть с ID ' . $partId . ' уже заказана.';
                    $messageType = 'error';
                    $suc = false;
                    break; // Прекращаем цикл при ошибке
                }

                // Обновляем ID заказа в таблице auto_parts
                $updateData = [
                    'idorder' => $orderId
                ];

                // Вызов функции для обновления автозапчасти
                $updateResult = $ordersTable->updateRecord('auto_parts', 'id', $partId, $updateData);

                // Проверка результата обновления
                if ($updateResult['type'] !== 'success') {
                    $message = 'Ошибка при обновлении запчасти: ' . $updateResult['message'];
                    $messageType = 'error';
                    $suc = false;
                    break; // Прекращаем цикл при ошибке
                }
            }
        }

        // Если все прошло успешно
        if ($suc) {
            $login = $_SESSION['login'];
        $id_user = $_SESSION['user_id'];
        $type_role = $_SESSION['type_role'];
            $actStr = "Пользователь $login типа '$type_role'  добавил заказ id=$$orderId.";
            $dbExecutor->insertAction($id_user, $actStr);    
            $message = 'Заказ добавлен успешно';
            $messageType = 'success'; // Успешное сообщение
        }
    } else {
        $message = 'Ошибка: Не удалось добавить заказ';
        $messageType = 'error'; // Ошибка
    }
}


//поиск заказов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_order'])) {
    $params = [];
    $searchableFields = [];
    
    // Проверка и добавление параметров
    if (!empty($_POST['search_id'])) {
        // $file="debug.txt";
        // $datafile="zaebis6";
        // file_put_contents($file, $datafile);
        $params['id'] = $_POST['search_id'];
        $searchableFields[] = 'id';
    }

    if (!empty($_POST['search_type_order'])) {
        $params['type_order'] = $_POST['search_type_order'];
        $searchableFields[] = 'type_order';
    }

    if (!empty($_POST['search_status'])) {
        $params['status'] = $_POST['search_status'];
        $searchableFields[] = 'status';
    }

    if (!empty($_POST['search_start_interval'])) {
        $params['datetime_start'] = $_POST['search_start_interval'];
        $searchableFields[] = 'datetime';
    }

    if (!empty($_POST['search_end_interval'])) {
        $params['datetime_end'] = $_POST['search_end_interval'];
        $searchableFields[] = 'datetime';
    }

    if (!empty($_POST['search_purchase_price'])) {
        $params['purchase_price'] = $_POST['search_purchase_price'];
        $searchableFields[] = 'purchase_price';
    }

    if (!empty($_POST['search_id_customer'])) {
        $params['id_customer'] = $_POST['search_id_customer'];
        $searchableFields[] = 'idcustomer';
    }

    // Поля для проверки диапазона дат
    $dateFields = ['datetime']; // Укажите поле даты, по которому будет выполняться поиск

    // Выполнение поиска с формированными параметрами
    $searchResult = $ordersTable->universalSearch($params, $searchableFields, $dateFields);

    if (!$searchResult['success']) {
        $message = $searchResult['message'];
        $messageType = 'error';
    } else {
        $orders = $searchResult['data'];
        if (empty($orders)) {
            $message = "Заказы не найдены.";
            $messageType = "error"; // Ошибка
         } 
    }
}

// Удаление заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete' && $_GET['table'] === 'orders') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0; // Получаем ID заказа для удаления    

    // Обновляем записи в таблице auto_parts, устанавливая idorder в NULL
    $updatePartsStmt = $db->prepare("UPDATE auto_parts SET idorder = NULL WHERE idorder = ?");
    $updatePartsStmt->bind_param("i", $id);
    $updatePartsStmt->execute();
    $updatePartsStmt->close();

    // Удаляем заказ
    if ($ordersTable->deleteUser($id) === 1) { 
        $login = $_SESSION['login'];
        $id_user = $_SESSION['user_id'];
        $type_role = $_SESSION['type_role'];

        // Логируем действие
        $actStr = "Пользователь $login типа '$type_role' удалил заказ id=$id.";
        $dbExecutor->insertAction($id_user, $actStr);

        // Сообщение об успешном удалении
        $message = "Заказ успешно удален.";
        $messageType = "success";
    } else {
        // Сообщение об ошибке
        $message = "Ошибка: заказ не найден или не удалось удалить.";
        $messageType = "error";
    }
}

//изменение заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit' && $_GET['table'] === 'orders') {
    // Извлекаем данные из формы
    $id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $typeOrder = isset($_POST['edit_type_order']) ? $_POST['edit_type_order'] : null;
    $status = isset($_POST['edit_status']) ? $_POST['edit_status'] : null;
    $purchasePrice = isset($_POST['edit_purchase_price']) ? floatval($_POST['edit_purchase_price']) : null;

    // Подготовка SQL-запроса для обновления данных
    $updateFields = [];
    $params = [];

    // Добавляем поля для обновления, если они не пустые
    if ($typeOrder !== null) {
        $updateFields[] = "type_order = ?";
        $params[] = $typeOrder;
    }
    if ($status !== null) {
        $updateFields[] = "status = ?";
        $params[] = $status;
    }
    if ($purchasePrice !== null) {
        $updateFields[] = "purchase_price = ?";
        $params[] = $purchasePrice;
    }

    // Если нет полей для обновления, выводим сообщение об ошибке
    if (empty($updateFields)) {
        $message = "Ошибка: Нет данных для изменения.";
        $messageType = "error";
    } else {
        // Формируем SQL-запрос
        $sql = "UPDATE orders SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $params[] = $id; // Добавляем ID заказа в параметры

        // Подготовка и выполнение запроса
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Ошибка подготовки SQL-запроса: ' . $db->error);
        }

        // Привязываем параметры
        $types = str_repeat("s", count($params) - 1) . "i"; // Типы параметров (s - строка, i - целое число)
        $stmt->bind_param($types, ...$params);

        // Выполняем запрос
        if ($stmt->execute()) {
            $login = $_SESSION['login'];
        $id_user = $_SESSION['user_id'];
        $type_role = $_SESSION['type_role'];

        // Логируем действие
        $actStr = "Пользователь $login типа '$type_role' изменил заказ id=$id.";
        $dbExecutor->insertAction($id_user, $actStr);
            $message = "Данные заказа успешно изменены.";
            $messageType = "success";
        } else {
            $message = "Ошибка: Не удалось изменить данные заказа.";
            $messageType = "error";
        }

        $stmt->close(); // Закрываем подготовленный запрос
    }
}













// Поиск покупателей
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_customer'])) {
    $params = [];
    $searchableFields = [];

    // Проверка и добавление параметров
    if (!empty($_POST['search_id'])) {
        $params['id'] = $_POST['search_id'];
        $searchableFields[] = 'id';
 
    }

    if (!empty($_POST['search_login'])) {
        $params['login'] = $_POST['search_login'];
        $searchableFields[] = 'login';
    }

    if (!empty($_POST['search_first_name'])) {
        $params['first_name'] = $_POST['search_first_name'];
        $searchableFields[] = 'first_name';
    }

    if (!empty($_POST['search_second_name'])) {
        $params['name'] = $_POST['search_second_name'];
        $searchableFields[] = 'second_name';
    }

    if (!empty($_POST['search_email'])) {
        $params['email'] = $_POST['search_email'];
        $searchableFields[] = 'email';
    }

    if (!empty($_POST['search_contact_phone'])) {
        $params['contact_phone'] = $_POST['search_contact_phone'];
        $searchableFields[] = 'contact_phone';
    }

    // Выполнение поиска с формированными параметрами
    $searchResult = $customersTable->universalSearch($params, $searchableFields);

    if (!$searchResult['success']) {
        $message = $searchResult['message'];
        $messageType = 'error';
    } else {
        $customers = $searchResult['data'];
        if (empty($customers)) {
            $message = "Покупатели не найдены.";
            $messageType = "error"; // Ошибка
        } 
    }
}

// Добавление покупателя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $suc = true; // Флаг успешного выполнения
    $type_role = 0;

    // Собираем данные из формы
    $login = $_POST['login'];
    $data1 = [
        'login' => $login,
        'first_name' => $_POST['first_name'],
        'second_name' => $_POST['second_name'],
        'email' => $_POST['email'],
        'contact_phone' => $_POST['contact_phone'],
        'address' => $_POST['address']
    ];
    
    $data2 = [
        'login' => $login,
        'password' => md5($_POST['password']),
        'type_role' => $type_role
    ];

    // Проверка уникальности логина
    $existingUser = $customersTable->getUserByLogin($login); 
    if ($existingUser) {
        $message = 'Ошибка: Логин уже занят. Пожалуйста, выберите другой.';
        $messageType = 'error'; // Ошибка
        $suc = false; // Устанавливаем флаг на false
    }

    // Проверка уникальности email
    $existingEmail = $customersTable->getUserByEmail($data1['email']); 
    if ($existingEmail) {
        $message = 'Ошибка: Email уже занят. Пожалуйста, выберите другой.';
        $messageType = 'error'; // Ошибка
        $suc = false; // Устанавливаем флаг на false
    }

    // Если логин и email уникальны, добавляем пользователя
    if ($suc) {
        // Вызов функции добавления покупателя
        $result1 = $customersTable->addRecord($data1);
        
        // Если покупатель успешно добавлен
        if ($result1 ) {
            // Получаем ID созданного покупателя
            $customerId = $customersTable->getLastInsertedId(); 
            $data3=['idcustomer'=>$customerId];
            
            $result2 =$cartTable->addRecord($data3);
            // $file="debug.txt";
            // $datafile=$result3['type'];
            // file_put_contents($file, $datafile);
                
            $result3 = $usersTable->addRecord($data2);
            // Записываем действие в журнал
            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $actStr = "Пользователь $login добавил нового покупателя с ID=$customerId.";
            $dbExecutor->insertAction($id_user, $actStr);
            if($result2['type']==='success'&&$result3['type']==='success'){
            $message = 'Покупатель добавлен успешно';
            $messageType = 'success'; // Успешное сообщение
            }else{
                $message = 'Ошибка: Не удалось добавить покупателя в users или cart';
            $messageType = 'error';
            }
        } else {
            $message = 'Ошибка: Не удалось добавить покупателя';
            $messageType = 'error'; 
        }
    }
}

// Изменение покупателя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit' && $_GET['table'] === 'customers') {
    // Извлекаем данные из формы
    $id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $login = isset($_POST['edit_login']) ? $_POST['edit_login'] : null;
    $firstName = isset($_POST['edit_first_name']) ? $_POST['edit_first_name'] : null;
    $secondName = isset($_POST['edit_second_name']) ? $_POST['edit_second_name'] : null;
    $email = isset($_POST['edit_email']) ? $_POST['edit_email'] : null;
    $contactPhone = isset($_POST['edit_contact_phone']) ? $_POST['edit_contact_phone'] : null;
    $address = isset($_POST['edit_address']) ? $_POST['edit_address'] : null;

    $suc = true; // Флаг успешного выполнения
    $id_users = null;

    // Проверка на существующий логин
    $existingUser = $customersTable->getUserByLogin($login); // Предполагаем, что этот метод существует
    if ($existingUser) {
        $message = 'Ошибка: Логин уже занят. Пожалуйста, выберите другой.';
        $messageType = 'error'; // Ошибка
        $suc = false; // Устанавливаем флаг на false
    }

    // Проверка на существующий email
    if(isset($email)){
    $existingEmail = $customersTable->getUserByEmail($email); 
        if ($existingEmail &&$suc) {
            $message = 'Ошибка: Email уже занят. Пожалуйста, выберите другой.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }
    
    if ($login !== null && $suc) {
        // Запрос для получения ID пользователя по логину
        $sql = "SELECT login FROM customers WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id); // Привязываем параметр
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Извлекаем ассоциативный массив
        $customer = $result->fetch_assoc(); 
        
        // Проверяем, что пользователь найден
        if ($customer) {
            $old_login = $customer['login']; // Получаем старый логин
            $user_id = $usersTable->getUserByLogin($old_login); // Предполагаем, что этот метод существует
            
            if ($user_id) { // Проверяем, что пользователь найден
                $id_users = $user_id['id']; // Получаем ID пользователя
            }
        }
    }

    if ($suc) {
        // Подготовка данных для обновления
        $data1 = [];
        if ($login !== null) $data1['login'] = $login;
        if ($firstName !== null) $data1['first_name'] = $firstName;
        if ($secondName !== null) $data1['second_name'] = $secondName;
        if ($email !== null) $data1['email'] = $email;
        if ($contactPhone !== null) $data1['contact_phone'] = $contactPhone;
        if ($address !== null) $data1['address'] = $address;


        // Вызываем функцию для обновления данных
        $result1 = $customersTable->updateRecord('customers', 'id', $id, $data1);
        if ($id_users !== null) {
            $data2=[];
            $data2['login'] = $login;
            $result2 = $usersTable->updateRecord('users', 'id', $id_users, $data2);
        }

        // Проверяем результат выполнения
        if ($result1['type'] === 'success') {
            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role = $_SESSION['type_role'];

            // Логируем действие
            $actStr = "Пользователь $login типа '$type_role' изменил данные покупателя id=$id.";
            $dbExecutor->insertAction($id_user, $actStr);
        }

        // Устанавливаем сообщение для пользователя
        $message = $result1['message'];
        $messageType = $result1['type'];
    }
}


// Удаление покупателя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete' && $_GET['table'] === 'customers') {

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0; // Получаем ID покупателя для удаления    

    // Создаем экземпляр класса ActionLogger
    $logger = new ActionLogger();

    // Начало транзакции
    $logger->beginTransaction();

    try {

        $sql = "SELECT login FROM customers WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id); // Привязываем параметр
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc(); 
        $login_in_customers = $customer['login'];
        $us = $usersTable->getUserByLogin($login_in_customers);
        $user_id=$us['id'];

        $cartDeleted = $cartTable->deleteUser($id, 'idcustomer');

        $userDeleted = $usersTable->deleteUser($user_id);
        
        $customerDeleted = $customersTable->deleteUser($id);
            $file="debug.txt";
            $datafile=[$user_id,' ',$id,' ',$cartDeleted,' ',$userDeleted,' ',$customerDeleted];
            file_put_contents($file, $datafile);
        // Проверяем, были ли успешно удалены все записи
        if ($cartDeleted && $userDeleted && $customerDeleted) {
            // Подтверждаем транзакцию
            $logger->commit();

            // Логируем действие
            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role = $_SESSION['type_role'];
            $actStr = "Пользователь $login типа '$type_role' удалил покупателя id=$id.";
            $logger->insertAction($id_user, $actStr);

            // Сообщение об успешном удалении
            $message = "Покупатель успешно удален.";
            $messageType = "success";
        } else {
            // Откатываем транзакцию в случае ошибки
            $logger->rollBack();
            $message = "Ошибка: не удалось удалить покупателя.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        // Откатываем транзакцию в случае исключения
        $logger->rollBack();
        $message = "Ошибка: " . $e->getMessage();
        $messageType = "error";
    }
}





// Поиск сотрудников
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_staff'])) {
    $params = [];
    $searchableFields = [];

    // Проверка и добавление параметров
    if (!empty($_POST['search_id'])) {
        
        $params['id'] = $_POST['search_id'];
        $searchableFields[] = 'id';
  
    }

    if (!empty($_POST['search_login'])) {
        $params['login'] = $_POST['search_login'];
        $searchableFields[] = 'login';
    }

    if (!empty($_POST['search_first_name'])) {
        $params['first_name'] = $_POST['search_first_name'];
        $searchableFields[] = 'first_name';
    }

    if (!empty($_POST['search_second_name'])) {
        $params['second_name'] = $_POST['search_second_name'];
        $searchableFields[] = 'second_name';
    }

    if (!empty($_POST['search_email'])) {
        $params['email'] = $_POST['search_email'];
        $searchableFields[] = 'email';
    }

    if (!empty($_POST['search_contact_phone'])) {
        $params['contact_phone'] = $_POST['search_contact_phone'];
        $searchableFields[] = 'contact_phone';
    }

    // Выполнение поиска с формированными параметрами
    $searchResult = $staffsTable->universalSearch($params, $searchableFields);

    if (!$searchResult['success']) {
        $message = $searchResult['message'];
        $messageType = 'error';
    } else {
        $staffs = $searchResult['data'];
        if (empty($staffs)) {
            $message = "Сотрудники не найдены.";
            $messageType = "error"; // Ошибка
        } 
    }
}

// Добавление сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $suc = true; // Флаг успешного выполнения

    // Собираем данные из формы
    $login = $_POST['login'];
    $idgarage = $_POST['idgarage'];
    $data = [
        'first_name' => $_POST['first_name'],
        'second_name' => $_POST['second_name'],
        'login' => $login,
        'contact_phone' => $_POST['contact_phone'],
        'salary' => $_POST['salary'],
        'idpost' => $_POST['idpost'] // ID должности
    ];
    
    // Проверка и добавление email, если он установлен
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $data['email'] = $_POST['email'];
    }

    // Проверка уникальности логина
    $existingUser = $staffsTable->getUserByLogin($login);
    if ($existingUser) {
        $message = 'Ошибка: Логин уже занят. Пожалуйста, выберите другой.';
        $messageType = 'error'; // Ошибка
        $suc = false; // Устанавливаем флаг на false
    }

    // Проверка уникальности email
    if (!empty($data['email']) && $suc) {
        $existingEmail = $staffsTable->getUserByEmail($data['email']);
        if ($existingEmail) {
            $message = 'Ошибка: Email уже занят. Пожалуйста, выберите другой.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    // Проверка существования должности
    if ($suc) {
        $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->bind_param("i", $data['idpost']);
        $stmt->execute();
        $result_post = $stmt->get_result();
        if ($result_post->num_rows === 0) {
            $message = 'Ошибка: Должность не найдена. Пожалуйста, выберите корректную должность.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    // Проверка диапазона idgarage
    if ($suc) {
        if ($idgarage > 16 || $idgarage === 14) {
            $message = "Ошибка: Значение idgarage должно быть от 1 до 13, или от 15 до 16.";
            $messageType = "error"; // Ошибка
            $suc = false; // Устанавливаем флаг ошибки
        }
    }

    // Проверка соответствия idpost и idgarage
    if ($suc) {
        $stmt = $db->prepare("
            SELECT 1 
            FROM posts p 
            JOIN staff s ON s.idpost = p.id 
            JOIN staff_garage sg ON sg.idstaff = s.id 
            WHERE p.id = ? AND sg.idgarage = ?
        ");
        $stmt->bind_param("ii", $data['idpost'], $idgarage);
        $stmt->execute();
        $result_check = $stmt->get_result();

        if ($result_check->num_rows === 0) {
            $message = 'Ошибка: Комбинация должности и гаража некорректна. Пожалуйста, проверьте введенные данные.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    // Если все проверки пройдены, начинаем транзакцию
    if ($suc) {
        $db->begin_transaction(); // Начало транзакции

        try {
            // Вызов функции добавления сотрудника
            $result = $staffsTable->addRecord($data);

            // Проверяем, успешно ли добавлен сотрудник
            if ($result) {
                $staffId = $staffsTable->getLastInsertedId();
                $data1 = [ 
                    'idstaff' => $staffId,
                    'idgarage' => $idgarage // Предполагается, что idgarage также передается в форме
                ];
                $result1 = $staffGarageTable->addRecord($data1);
                $data2 = [
                    'login' => $login,
                    'password' => md5($_POST['password']),
                    'type_role' => 2
                ];
                
                $result2 = $usersTable->addRecord($data2);
                
                // Записываем действие в журнал
                $actStr = "Пользователь {$_SESSION['login']} добавил нового сотрудника с ID=$staffId.";
                $dbExecutor->insertAction($_SESSION['user_id'], $actStr);

                if ($result1['type'] === 'success' && $result2['type'] === 'success') {
                    $db->commit(); // Фиксация транзакции
                    $message = 'Сотрудник добавлен успешно.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Ошибка: Не удалось добавить сотрудника в users или staff_garage');
                }
            } else {
                throw new Exception('Ошибка: Не удалось добавить сотрудника');
            }
        } catch (Exception $e) {
            $db->rollback(); // Откат транзакции
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Изменение данных сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit' && $_GET['table'] === 'staff') {
    // Извлекаем данные из формы
    $id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $login = isset($_POST['edit_login']) ? $_POST['edit_login'] : null;
    $firstName = isset($_POST['edit_first_name']) ? $_POST['edit_first_name'] : null;
    $secondName = isset($_POST['edit_second_name']) ? $_POST['edit_second_name'] : null;
    $email = isset($_POST['edit_email']) ? $_POST['edit_email'] : null;
    $contactPhone = isset($_POST['edit_contact_phone']) ? $_POST['edit_contact_phone'] : null;
    $salary = isset($_POST['salary']) ? $_POST['salary'] : null;
    $idpost = isset($_POST['edit_idpost']) ? intval($_POST['edit_idpost']) : null;
    $idgarage = isset($_POST['edit_idgarage']) ? intval($_POST['edit_idgarage']) : null;

    $suc = true; // Флаг успешного выполнения
    $id_user = null;

    // Проверка на существующий логин
    $existingUser = $staffsTable->getUserByLogin($login);
    if ($existingUser && $existingUser['id'] != $id) { // Проверяем, что логин занят другим сотрудником
        $message = 'Ошибка: Логин уже занят. Пожалуйста, выберите другой.';
        $messageType = 'error'; // Ошибка
        $suc = false; // Устанавливаем флаг на false
    }

    // Проверка на существующий email
    if ($email) {
        $existingEmail = $staffsTable->getUserByEmail($email);
        if ($existingEmail && $existingEmail['id'] != $id) { // Проверяем, что email занят другим сотрудником
            $message = 'Ошибка: Email уже занят. Пожалуйста, выберите другой.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    // Получаем старые данные сотрудника
    if ($suc) {
        $stmt = $db->prepare("SELECT login FROM staff WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();

        if ($staff) {
            $old_login = $staff['login'];
            $user_id = $usersTable->getUserByLogin($old_login); // Получаем ID пользователя
            if ($user_id) {
                $id_user = $user_id['id']; // Получаем ID пользователя
            }
        } else {
            $message = 'Ошибка: Сотрудник не найден.';
            $messageType = 'error';
            $suc = false;
        }
    }

    if ($suc) {
        // Подготовка данных для обновления
        $data1 = [];
        if ($login !== null) $data1['login'] = $login;
        if ($firstName !== null) $data1['first_name'] = $firstName;
        if ($secondName !== null) $data1['second_name'] = $secondName;
        if ($email !== null) $data1['email'] = $email;
        if ($contactPhone !== null) $data1['contact_phone'] = $contactPhone;
        if ($salary !== null) $data1['salary'] = $salary;
        if ($idpost !== null) $data1['idpost'] = $idpost;

        // Начинаем транзакцию
        $db->begin_transaction();

        try {
            // Инициализируем переменные результата
            $result1 = ['type' => 'error']; // Значение по умолчанию
            
            // Обновление данных сотрудника, если есть что обновлять
            if (!empty($data1)) {
                $result1 = $staffsTable->updateRecord('staff', 'id', $id, $data1);
            }
            
            // Обновление данных пользователя, если необходимо
            if ($id_user !== null) {
                $data2 = [];
                if ($login !== null) $data2['login'] = $login;
                $result2 = $usersTable->updateRecord('users', 'id', $id_user, $data2);
            }

            // Обновление данных о гараже, если указано
            $result3 = ['type' => 'error']; // Значение по умолчанию
            if ($idgarage !== null) {
                $data3 = ['idgarage' => $idgarage];
                $result3 = $staffGarageTable->updateRecord('staff_garage', 'idstaff', $id, $data3);
            }

            // Проверяем результат выполнения
            if ($result1['type'] === 'success' || (!empty($data1) && $result3['type'] === 'success')) {
                $db->commit(); // Фиксация транзакции
                $login = $_SESSION['login'];
                $type_role = $_SESSION['type_role'];

                // Логируем действие
                $actStr = "Пользователь $login типа '$type_role' изменил данные сотрудника id=$id.";
                $dbExecutor->insertAction($_SESSION['user_id'], $actStr);

                $message = 'Данные сотрудника успешно изменены.';
                $messageType = 'success';
            } else {
                throw new Exception('Ошибка при обновлении данных сотрудника.');
            }
        } catch (Exception $e) {
            $db->rollback(); // Откат транзакции
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Удаление сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete' && $_GET['table'] === 'staff') {

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0; // Получаем ID сотрудника для удаления    

    // Создаем экземпляр класса ActionLogger
    $logger = new ActionLogger();

    // Начало транзакции
    $logger->beginTransaction();

    try {
        // Получаем логин сотрудника по ID
        $sql = "SELECT login FROM staff WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id); // Привязываем параметр
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc(); 

        if (!$staff) {
            throw new Exception('Ошибка: Сотрудник не найден.');
        }

        $login_in_staff = $staff['login'];
        $us = $usersTable->getUserByLogin($login_in_staff);
        $user_id = $us['id'];

        // Удаляем связанные записи
        $garageDeleted = $staffGarageTable->deleteUser($id, 'idstaff');
        $userDeleted = $usersTable->deleteUser($user_id);
        $historyAutopartsDeleted = $historyOperationsWithAutopartTable->deleteUser($id, 'idstaff');
        $historyCarDeleted = $historyOperationsWithCarsTable->deleteUser($id, 'idstaff');
        $staffDeleted = $staffsTable->deleteUser($id);

        // $file = "debug.txt";
        // $datafile = [$user_id, ' ', $id, ' ', $garageDeleted, ' ', $userDeleted, ' ', $staffDeleted];
        // file_put_contents($file, $datafile);

        // Проверяем, были ли успешно удалены все записи
        if ($garageDeleted && $userDeleted && $staffDeleted&&$historyAutopartsDeleted&&$historyCarDeleted) {
            // Подтверждаем транзакцию
            $logger->commit();

            // Логируем действие
            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role = $_SESSION['type_role'];
            $actStr = "Пользователь $login типа '$type_role' удалил сотрудника id=$id.";
            $logger->insertAction($id_user, $actStr);

            // Сообщение об успешном удалении
            $message = "Сотрудник успешно удален.";
            $messageType = "success";
        } else {
            // Откатываем транзакцию в случае ошибки
            $logger->rollBack();
            $message = "Ошибка: не удалось удалить сотрудника.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        // Откатываем транзакцию в случае исключения
        $logger->rollBack();
        $message = "Ошибка: " . $e->getMessage();
        $messageType = "error";
    }
}







//ПОСТАВЩИКИ

// Поиск поставщика
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_supplier'])) {
    $params = [];
    $searchableFields = [];

    // Проверка и добавление параметров
    if (!empty($_POST['search_id'])) {
        $params['id'] = intval($_POST['search_id']);
        $searchableFields[] = 'id';
    }

    if (!empty($_POST['search_name_organization'])) {
        $params['name_organization'] = $_POST['search_name_organization'];
        $searchableFields[] = 'name_organization';
    }

    if (!empty($_POST['search_email'])) {
        $params['email'] = $_POST['search_email'];
        $searchableFields[] = 'email';
    }

    if (!empty($_POST['search_contact_phone'])) {
        $params['contact_phone'] = $_POST['search_contact_phone'];
        $searchableFields[] = 'contact_phone';
    }

    if (!empty($_POST['search_contact_person'])) {
        $params['contact_person'] = $_POST['search_contact_person'];
        $searchableFields[] = 'contact_person';
    }

    // Выполнение поиска с формированными параметрами
    $searchResult = $suppliersTable->universalSearch($params, $searchableFields);

    if (!$searchResult['success']) {
        $message = $searchResult['message'];
        $messageType = 'error';
    } else {
        $suppliers = $searchResult['data'];
        if (empty($suppliers)) {
            $message = "Поставщики не найдены.";
            $messageType = "error"; // Ошибка
        } 
    }
}

// Добавление поставщика
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    $suc = true; // Флаг успешного выполнения

    // Собираем данные из формы
    $data = [
        'name_organization' => $_POST['name_organization'],
        'email' => $_POST['email'],
        'contact_phone' => $_POST['contact_phone'],
        'contact_person' => $_POST['contact_person'],
        'address' => $_POST['address'],
    ];

    // Проверка уникальности email
    if (!empty($data['email'])) {
        $existingEmail = $suppliersTable->getSupplierByEmail($data['email']);
        if ($existingEmail) {
            $message = 'Ошибка: Email уже занят. Пожалуйста, выберите другой.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    // Если все проверки пройдены, начинаем транзакцию
    if ($suc) {
        $db->begin_transaction(); // Начало транзакции

        try {
            // Вызов функции добавления поставщика
            $result = $suppliersTable->addRecord($data);

            // Проверяем, успешно ли добавлен поставщик
            if ($result) {
                $supplierId = $suppliersTable->getLastInsertedId();

                // Записываем действие в журнал (если необходимо)
                $actStr = "Пользователь {$_SESSION['login']} добавил нового поставщика с ID=$supplierId.";
                $dbExecutor->insertAction($_SESSION['user_id'], $actStr);

                $db->commit(); // Фиксация транзакции
                $message = 'Поставщик добавлен успешно.';
                $messageType = 'success';
            } else {
                throw new Exception('Ошибка: Не удалось добавить поставщика.');
            }
        } catch (Exception $e) {
            $db->rollback(); // Откат транзакции
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Изменение данных поставщика
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit' && $_GET['table'] === 'suppliers') {
    // Извлекаем данные из формы
    $id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $nameOrganization = isset($_POST['edit_name_organization']) ? $_POST['edit_name_organization'] : null;
    $email = isset($_POST['edit_email']) ? $_POST['edit_email'] : null;
    $contactPhone = isset($_POST['edit_contact_phone']) ? $_POST['edit_contact_phone'] : null;
    $contactPerson = isset($_POST['edit_contact_person']) ? $_POST['edit_contact_person'] : null;
    $address = isset($_POST['edit_address']) ? $_POST['edit_address'] : null;

    $suc = true; // Флаг успешного выполнения

    // Проверка на существующий email
    if ($email) {
        $existingEmail = $suppliersTable->getSupplierByEmail($email);
        if ($existingEmail && $existingEmail['id'] != $id) { // Проверяем, что email занят другим поставщиком
            $message = 'Ошибка: Email уже занят. Пожалуйста, выберите другой.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    // Получаем старые данные поставщика
    if ($suc) {
        $stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $supplier = $result->fetch_assoc();

        if (!$supplier) {
            $message = 'Ошибка: Поставщик не найден.';
            $messageType = 'error';
            $suc = false;
        }
    }

    if ($suc) {
        // Подготовка данных для обновления
        $data = [];
        if ($nameOrganization !== null) $data['name_organization'] = $nameOrganization;
        if ($email !== null) $data['email'] = $email;
        if ($contactPhone !== null) $data['contact_phone'] = $contactPhone;
        if ($contactPerson !== null) $data['contact_person'] = $contactPerson;
        if ($address !== null) $data['address'] = $address;

        // Начинаем транзакцию
        $db->begin_transaction();

        try {
            // Инициализируем переменные результата
            $result = ['type' => 'error']; // Значение по умолчанию
            
            // Обновление данных поставщика, если есть что обновлять
            if (!empty($data)) {
                $result = $suppliersTable->updateRecord('suppliers', 'id', $id, $data);
            }

            // Проверяем результат выполнения
            if ($result['type'] === 'success') {
                $db->commit(); // Фиксация транзакции
                $login = $_SESSION['login'];
                $type_role = $_SESSION['type_role'];

                // Логируем действие
                $actStr = "Пользователь $login типа '$type_role' изменил данные поставщика id=$id.";
                $dbExecutor->insertAction($_SESSION['user_id'], $actStr);

                $message = 'Данные поставщика успешно изменены.';
                $messageType = 'success';
            } else {
                throw new Exception('Ошибка при обновлении данных поставщика.');
            }
        } catch (Exception $e) {
            $db->rollback(); // Откат транзакции
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Удаление поставщика
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete' && $_GET['table'] === 'suppliers') {

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0; 
    if($id===61){
        $message="Нельзя удалить этого поставщика, так как когда то он уже был удалён(((";
        $message_type="error";
    }else{   
    $logger = new ActionLogger();
    // Начало транзакции
    $logger->beginTransaction();

    try {
        $sql = "SELECT name_organization FROM suppliers WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id); 
        $stmt->execute();
        $result = $stmt->get_result();
        $supplier = $result->fetch_assoc(); 

        if (!$supplier) {
            throw new Exception('Ошибка: Поставщик не найден.');
        }
        $name_organization = $supplier['name_organization'];
        
        
        $supplierDeleted = $suppliersTable->deleteUser($id);

        $data=[
            'idsupplier'=>61
        ];
        $resultcar = $carsTable->updateRecord('cars', 'idsupplier', $id, $data);
        // Проверяем, были ли успешно удалены все записи
        if ($supplierDeleted &&$resultcar) {
            // Подтверждаем транзакцию
            $logger->commit();

            // Логируем действие
            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role = $_SESSION['type_role'];
            $actStr = "Пользователь $login типа '$type_role' удалил поставщика '$name_organization' (ID=$id).";
            $logger->insertAction($id_user, $actStr);

            // Сообщение об успешном удалении
            $message = "Поставщик успешно удален.";
            $messageType = "success";
        } else {
            // Откатываем транзакцию в случае ошибки
            $logger->rollBack();
            $message = "Ошибка: не удалось удалить поставщика.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        // Откатываем транзакцию в случае исключения
        $logger->rollBack();
        $message = "Ошибка: " . $e->getMessage();
        $messageType = "error";
    }
}
}
?>