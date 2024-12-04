<?php
class TableFunction {
    public $db;
    protected $tableName;

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

    public function fetchLimited($limit, $conditions = []) {
        $query = "SELECT * FROM " . mysqli_real_escape_string($this->db, $this->tableName);
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " LIMIT " . intval($limit);
        return $this->executeQuery($query);
    }

    private function executeQuery($query) {
        $result = mysqli_query($this->db, $query);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public function deleteUser($id) {
        $stmt = $this->db->prepare("DELETE FROM " . $this->tableName . " WHERE id = ?");
        $stmt->bind_param("i", $id);
        try {
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                return 1;
            } else {
                return 0;
            }
        } catch (mysqli_sql_exception $e) {
            return "Ошибка удаления пользователя: " . $e->getMessage();
        } finally {
            $stmt->close();
        }
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
                echo "<tr>";
                foreach ($row as $key => $cell) {
                    // Проверяем, является ли ячейка строкой и содержит ли корректный JSON
                    if (is_string($cell) && $this->is_json($cell)) {
                        // Декодируем JSON
                        $jsonData = json_decode($cell, true);
                        echo "<td>";
    
                        // Проверяем, является ли декодированное значение массивом
                        if (is_array($jsonData)) {
                            $listItems = [];
                            foreach ($jsonData as $jsonKey => $jsonValue) {
                                // Формируем строку для списка
                                $listItems[] = htmlspecialchars($jsonKey) . ": " . htmlspecialchars($jsonValue);
                            }
                            // Выводим список
                            echo implode("; ", $listItems);
                        } else {
                            // Если это не массив, выводим сообщение
                            echo "Некорректные данные";
                        }
    
                        echo "</td>";
                    } elseif ($key === 'photo' && !empty($cell)) {
                        // Если это поле для изображения, выводим его
                        echo "<td>";
                        // Проверяем, что содержимое является BLOB-данными
                        if (is_string($cell)) {
                            // Выводим изображение
                            echo "<img src='data:image/jpg;base64," . base64_encode($cell) . "' alt='Изображение' style='max-width: 100px; height: auto;'>";
                        } else {
                            echo "Изображение недоступно";
                        }
                        echo "</td>";
                    } else {
                        // Обычная ячейка (число или строка)
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                }
                // Кнопка удаления
                echo "<td class='table-cell-delete'>";
                echo "<form method='POST' action='?table=users&action=delete' class='delete-form'>";
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
        // Проверяем, является ли строка не пустой и начинается с '{' или '['
        if (!is_string($string) || empty($string) || !($string[0] === '{' || $string[0] === '[')) {
            return false;
        }
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}

// Подключение к базе данных
include('server.php');

$selectedTable = isset($_GET['table']) ? $_GET['table'] : 'users';

$usersTable = new TableFunction($db, 'users');
$partsTable = new TableFunction($db, 'auto_parts');
$ordersTable = new TableFunction($db, 'orders');
$customersTable = new TableFunction($db, 'Customers');
$staffsTable = new TableFunction($db, 'Staff');
$suppliersTable = new TableFunction($db, 'suppliers');
$inventoryTable = new TableFunction($db, 'Inventory');
$carsTable = new TableFunction($db, 'Cars');

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
} else {
    // Если это не поиск, просто загружаем всех пользователей
    $users = $usersTable->fetch();
}

// Добавление нового пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedTable === 'users' && !isset($_POST['search_users'])) 
{
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $type_role = isset($_POST['type_role']) ? trim($_POST['type_role']) : '';
    $garage_id = isset($_POST['garage_id']) ? trim($_POST['garage_id']) : ''; // Получаем ID гаража
    // $file = 'C:\Users\37529\OneDrive\Рабочий стол\log.txt';
    // file_put_contents($file, $garage_id);
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
                    
                            $Actstr = "Пользователь $old_login типа '0' добавил нового пользователя $login типа $type_role.";
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
                                    $stmt_garage = $db->prepare("INSERT INTO staff_garage (idstaff, idgarage) VALUES (?, ?)");
                                    $stmt_garage->bind_param("ii", $idstaff);
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
                
                $Actstr = "Пользователь $login типа '0' изменил пароль пользователю $change_login.";
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
 //удаление пользователей
 if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Получаем login и type_role пользователя
    $stmt = $db->prepare("SELECT login, type_role FROM $selectedTable WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row) {
        $login_delete_user = $row['login'];
        $type_role = $row['type_role'];

        // Удаляем пользователя из таблицы users
        if ($usersTable->deleteUser($id) === 1) {
            $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            
            // Логируем действие
            $Actstr = "Пользователь $login типа '0' удалил пользователя $login_delete_user.";
            $dbExecutor->insertAction($id_user, $Actstr);

            // Удаляем из соответствующей таблицы
            if ($type_role == 0) {
                // Удаляем из таблицы cart и customers
                $stmt = $db->prepare("SELECT id FROM customers WHERE login = ?");
                $stmt->bind_param("s", $login_delete_user);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $id_delete_user= $row["id"];
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
                $id_delete_user= $row["id"];
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

            $message = "Пользователь успешно удален."; // Успешное сообщение
            $messageType = "success"; // Успешное сообщение
            $users = $usersTable->fetch(); // Обновляем данные
        } else {
            $message = "Ошибка: пользователь не найден или не удалось удалить.";
            $messageType = "error"; // Ошибка
        }
    } else {
        $message = "Ошибка: пользователь не найден.";
        $messageType = "error"; // Ошибка
    }

    $stmt->close();
}



?>

