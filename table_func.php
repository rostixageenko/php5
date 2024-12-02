<?php
class TableFunction {
    protected $db;
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
        echo "<h2>$title</h2>";
        if (count($data) > 0) {
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($data[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "<th>Действия</th>"; // Новый заголовок для действий
            echo "</tr>";
            
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $cell) {
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
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
    $file = 'C:\Users\37529\OneDrive\Рабочий стол\log.txt';
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
    $users_search = $usersTable->fetch($searchConditions);
    
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedTable === 'users' && !isset($_POST['search_users'])) {
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $type_role = isset($_POST['type_role']) ? trim($_POST['type_role']) : '';

    if (!empty($login) && !empty($password)) {
        if (!is_numeric($type_role) || !in_array((int)$type_role, [0, 1, 2])) {
            $message = "Тип роли должен быть 0, 1 или 2.";
            $messageType = "error"; // Ошибка
        } else {
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
                $hashedPassword = md5($password);

                $stmt = $db->prepare("INSERT INTO users (login, password, type_role) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $login, $hashedPassword, $type_role);
                
                try {
                    if ($stmt->execute()) {
                        $old_login = $_SESSION['login'];
                        $old_id_user = $_SESSION['user_id'];
                
                        $Actstr = "Пользователь $old_login типа '0' добавил нового пользователя $login.";
                        $dbExecutor->insertAction($old_id_user, $Actstr);

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
 //удаление пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $row = mysqli_fetch_assoc(mysqli_query($db, "SELECT login FROM users WHERE id='$id'"));
    $login_delete_user= $row['login'];
    if ($id > 0 && $usersTable->deleteUser($id)===1) {
                $login = $_SESSION['login'];
                $id_user = $_SESSION['user_id'];
    
                $Actstr = "Пользователь $login типа '0' удалил пользователя $login_delete_user.";
                $dbExecutor->insertAction($id_user, $Actstr);
                
        $message ="Пользователь успешно удален." ; // Вызов метода удаления
        $messageType = "success"; // Успешное сообщение
        $users = $usersTable->fetch(); // Обновляем данные
    } else {
        $message ="Ошибка: пользователь не найден или не удалось удалить.";
        $messageType = "error"; // Ошибка
    }
}

?>

