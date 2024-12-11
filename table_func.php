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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sort_field'], $_POST['sort_order'])&&isset($_POST['sort_table'])) {
    $sortField =  isset($_POST['sort_field']) ? trim($_POST['sort_field']) : '';
    $sort_order=  isset($_POST['sort_order']) ? trim($_POST['sort_order']) : '';
 // true для по возрастанию, false для по убыванию
    // Сортируем данные
    switch ($selectedTable) {
        case 'users':
            $users = $usersTable->universalSort( $sortField, $sort_order);
            break;
        case 'auto_parts':
            $parts = $partsTable->universalSort( $sortField, $sort_order); 
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

//удаление пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete'&& $_GET['table'] === 'users') {

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
            $type_role1=$_SESSION['type_role'];

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

            $message = "Пользователь успешно удален.";
            $messageType = "success";
        } else {
            $message = "Ошибка: пользователь не найден или не удалось удалить.";
            $messageType = "error";
        }
    } else {
        $message ="Ошибка: пользователь не найден.";
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
        if (!$hasError && $fileData !== null && strlen($fileData) > 55 * 1024) {
            $fileData = compressImage($fileData); // Сжимаем изображение
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
    $quality = 75; 
    $compressedImageData = null;

    // Сжимаем изображение, пока его размер не станет меньше 50 КБ
    do {
        ob_start(); // Начинаем буферизацию вывода
        imagejpeg($image, null, $quality); // Сохраняем изображение в буфер с заданным качеством
        $compressedImageData = ob_get_contents(); // Получаем содержимое буфера
        ob_end_clean(); // Очищаем буфер

        // Уменьшаем качество
        $quality -= 5;
    } while (strlen($compressedImageData) > 75 * 1024 && $quality > 0); // Проверяем размер и качество

    imagedestroy($image); // Освобождаем память
    return $compressedImageData; // Возвращаем сжатое изображение
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

//удаление пользователя
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

?>