<?php
include_once("db_executer.php");
include('table_func.php');
include('parts.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Запускаем сессию только если она не активна
}


$login = "";
$password = "";
$errors = array();

// Подключаемся к БД
$db = mysqli_connect('localhost', 'root', '', 'auto_disassembly_station');
    $dbExecutor = new ActionLogger();
// Проверка соединения
if (!$db) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

// РЕГИСТРАЦИЯ ЮЗЕРА
if (isset($_POST['reg_user'])) {
    $login = mysqli_real_escape_string($db, $_POST['login']);
    $password_1 = mysqli_real_escape_string($db, $_POST['password_1']);
    $password_2 = mysqli_real_escape_string($db, $_POST['password_2']);

    if (empty($login)) { array_push($errors, "Введите логин"); }
    if (empty($password_1)) { array_push($errors, "Введите пароль"); }
    if ($password_1 != $password_2) {
        array_push($errors, "Пароли не совпадают");
    }

    // Извлекаем информацию о юзере
    $user_check_query = "SELECT * FROM users WHERE login='$login' LIMIT 1";
    $result = mysqli_query($db, $user_check_query);
    $user = mysqli_fetch_assoc($result);

    if ($user) { // Если юзер существует
        if ($user['login'] === $login) {
            array_push($errors, "Такой логин уже существует");
        }
    }

// Регистрируем юзера если нет ошибок
if (count($errors) == 0) {
    $password = md5($password_1); // Хешируем пароль юзера

    // Исправленный запрос для вставки данных в таблицу users
    $query = "INSERT INTO users (login, password, type_role) 
              VALUES ( '$login', '$password', '0')";
    
    mysqli_query($db, $query); // Выполняем запрос
    
    // Запрос для вставки данных в таблицу customers
    $query1 = "INSERT INTO customers (id, login) 
                SELECT id, login FROM users 
                WHERE login = '$login'"; // Убедитесь, что мы вставляем только что созданного пользователя
    
    $query2 = "SELECT id FROM users 
                WHERE login = '$login'";

    mysqli_query($db, $query1); // Выполняем запрос
    $row = mysqli_fetch_assoc(mysqli_query($db, $query2));
    $id_user= $row["id"];
    $Actstr = "Пользователь $login типа '0' зарегистрировался в системе.";
    $dbExecutor->insertAction($id_user, $Actstr);

    $_SESSION['user_id'] =$id_user;
    $_SESSION['login'] = $login;
    $_SESSION['success'] = "Вы успешно вошли в систему";
    $_SESSION['type_role'] = 0; 

    header('location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['part_id'])) {
        $partId = intval($_POST['part_id']);
        
        // Логика добавления в корзину
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Проверка, не добавлен ли товар уже
        if (!in_array($partId, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $partId;
            header("Location: some_page.php?success=1"); // Перенаправление с сообщением об успехе
            exit();
        } else {
            header("Location: some_page.php?error=exists"); // Перенаправление с ошибкой
            exit();
        }
    }
}
}

// ВХОД ЮЗЕРА
if (isset($_POST['login_user'])) {
    $login = mysqli_real_escape_string($db, $_POST['login']);
    $password = mysqli_real_escape_string($db, $_POST['password']);
  
    if (empty($login)) {
        array_push($errors, "Введите логин");
    }
    if (empty($password)) {
        array_push($errors, "Введите пароль");
    }
  
    if (count($errors) == 0) {
        $password = md5($password);
        $query = "SELECT * FROM users WHERE login='$login' AND password='$password'";
        $results = mysqli_query($db, $query);
        
        if (mysqli_num_rows($results) == 1) {
            $row = mysqli_fetch_assoc($results);
            $user_type=$row['type_role'];
            $id_user=$row['id'];
            
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['login'] = $login;
            $_SESSION['success'] = "Вы успешно вошли!!!";
            $_SESSION['type_role'] = $row['type_role']; 
            $Actstr = "Пользователь $login типа '$user_type' зашел в систему.";
            $dbExecutor->insertAction($id_user, $Actstr);
            if($_SESSION['type_role']===0){
                header('Location: user_interface_main.php');
            }else{
                header('Location: index.php');
            }
            exit(); 
        } else {
            array_push($errors, "Неверный логин или пароль");
        }
    }
}


// ПОИСК В ЖУРНАЛЕ ДЕЙСТВИЙ
if (isset($_POST['search'])) {
    $event_id = isset($_POST['event_id']) ? trim($_POST['event_id']) : '';
    $actor_name = isset($_POST['actor_name']) ? trim($_POST['actor_name']) : '';
    $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    // Начало SQL-запроса
    $query = "
    SELECT 
        sys_activity_log.id,
        users.login,
        sys_activity_log.action_datetime,
        sys_activity_log.action
    FROM 
        sys_activity_log
    JOIN 
        users ON sys_activity_log.actor_id = users.id
    WHERE 1=1";

    $params = [];

    // Добавление условий к запросу
    if (!empty($event_id) && filter_var($event_id, FILTER_VALIDATE_INT) !== false) {
        $query .= " AND sys_activity_log.id = ?";
        $params[] = $event_id;
    }
    
    if (!empty($actor_name)) {
        $query .= " AND users.login LIKE ?";
        $params[] = "%$actor_name%";
    }
    
    if (!empty($start_date)) {
        $query .= " AND action_datetime >= ?";
        $params[] = $start_date;
    }
    
    if (!empty($end_date)) {
        $query .= " AND action_datetime <= ?";
        $params[] = $end_date . ' 23:59:59';
    }
    
    if (!empty($action)) {
        $query .= " AND sys_activity_log.action LIKE ?";
        $params[] = "%$action%";
    }

    $stmt = $db->prepare($query);
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Сохраняем результаты в сессию
    $_SESSION['search_results'] = $result;

    // Перенаправляем на страницу журнала действий
    header('Location: activity_log.php');
    exit(); 
}

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
//$parts = $partsTable->fetchLimited($rowCount);
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
    if (isset($_POST['description'])) {
        $descriptionString = $_POST['description'];
        $descriptionArray = explode(',', $descriptionString);
        
        $color = isset($descriptionArray[0]) ? trim($conditionArray[0]) : null;
        $status = isset($conditionArray[1]) ? trim($conditionArray[1]) : null;
        $features = array_slice($conditionArray, 2); 

        $descriptionData = [
            'описание' => array_map('trim', $features)
        ];
    
        // Преобразуем массив в JSON с поддержкой русских символов
        $descriptionJson = json_encode($descriptionData, JSON_UNESCAPED_UNICODE);
    } else {
        $descriptionJson = null;
    }

    // Проверка на заполнение обязательных полей
    if (empty($_POST['part_name']) || empty($_POST['article']) || empty($_POST['condition']) || empty($_POST['price']) || empty($_POST['car_id']) || empty($_POST['garage_id'])) {
        $message = "Пожалуйста, заполните все обязательные поля.";
        $messageType = "error"; // Ошибка
        $hasError = true; // Устанавливаем флаг ошибки
    } else {
        // Получаем данные из формы
        $name_parts = $_POST['part_name'];
        $article = $_POST['article'];
        $condition = $_POST['conditionJson'];
        $purchase_price = $_POST['price'];
        $description = $descriptionJson ?? null; // Необязательное поле
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
    if (isset($_POST['description'])) {
        $descriptionString = $_POST['description'];
        $descriptionArray = explode(',', $descriptionString);
        
        $color = isset($descriptionArray[0]) ? trim($conditionArray[0]) : null;
        $status = isset($conditionArray[1]) ? trim($conditionArray[1]) : null;
        $features = array_slice($conditionArray, 2); 

        $descriptionData = [
            'описание' => array_map('trim', $features)
        ];
    
        // Преобразуем массив в JSON с поддержкой русских символов
        $descriptionJson = json_encode($descriptionData, JSON_UNESCAPED_UNICODE);
    } else {
        $descriptionJson = null;
    }
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
        $data['description'] = $descriptionJson;
    }
    if (!empty($_POST['new_car_id'])) {
        $data['idcar'] = $_POST['new_car_id'];
    }
    if (!empty($_POST['new_garage_id'])) {
        $data['idgarage'] = $_POST['new_garage_id'];
    }
    if (!empty($_POST['new_status'])) {
        $data['status'] = $_POST['new_status'];
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
    $status=isset($_POST['search_status']) ? trim($_POST['search_status']) :'';
    
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
    if (!empty($status)) { 
        $searchConditions[] = "status LIKE '%" . mysqli_real_escape_string($db, $partName) . "%'";
    };

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
        'idcustomer' => $_POST['id_customer'],
        'address' => $_POST['address']
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
        $sql = "UPDATE orders SET " . implode(", ", $updateFields) . ", datetime = NOW() WHERE id = ?";
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
    $tranzact = new TableFunction($db);

    // Начало транзакции
    $tranzact->beginTransaction();

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
            $tranzact->commit();

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
            $tranzact->rollBack();
            $message = "Ошибка: не удалось удалить покупателя.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        // Откатываем транзакцию в случае исключения
        $tranzact->rollBack();
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
    $tranzact = new TableFunction($db);

    // Начало транзакции
 
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
        $tranzact->beginTransaction();
        $garageDeleted = $staffGarageTable->deleteUser($id, 'idstaff');
        $userDeleted = $usersTable->deleteUser($user_id);
        $historyAutopartsDeleted = $historyOperationsWithAutopartTable->deleteUser($id, 'idstaff');
        $historyCarDeleted = $historyOperationsWithCarsTable->deleteUser($id, 'idstaff');
        $staffDeleted = $staffsTable->deleteUser($id);

        // $file = "debug.txt";
        // $datafile = [$user_id, ' ', $id, ' ', $garageDeleted, ' ', $userDeleted, ' ', $staffDeleted];
        // file_put_contents($file, $datafile);

        // Проверяем, были ли успешно удалены все записи
        if ($garageDeleted && $userDeleted && $staffDeleted) {
            // Подтверждаем транзакцию
            $tranzact->commit();

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
            $tranzact->rollBack();
            $message = "Ошибка: не удалось удалить сотрудника.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        // Откатываем транзакцию в случае исключения
        $tranzact->rollBack();
        $message =  $e->getMessage();
        $messageType = "error"; // Ошибка
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
    $tranzact = new TableFunction($db);
    // Начало транзакции
    $tranzact->beginTransaction();

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
            $tranzact->commit();

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
            $tranzact->rollBack();
            $message = "Ошибка: не удалось удалить поставщика.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        // Откатываем транзакцию в случае исключения
        $tranzact->rollBack();
        $message = "Ошибка: " . $e->getMessage();
        $messageType = "error";
    }
}
}






//АВТОМОБИЛИ

// Поиск автомобилей
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_car'])) {
    $params = [];
    $searchableFields = [];
    $suc=true;
    // Проверка и добавление параметров
    if (!empty($_POST['search_id'])) {
        $params['id'] = $_POST['search_id'];
        $searchableFields[] = 'id';
    }

    if (!empty($_POST['search_brand'])) {
        $params['brand'] = $_POST['search_brand'];
        $searchableFields[] = 'brand';
    }

    if (!empty($_POST['search_model'])) {
        $params['model'] = $_POST['search_model'];
        $searchableFields[] = 'model';
    }
    if (!empty($_POST['search_year_start']) && ($_POST['search_year_start'] < 1900 || $_POST['search_year_start'] > date('Y'))) {
        $message = "Ошибка: Год начала должен быть между 1900 и " . date('Y') . ".";
        $messageType = "error";
        $suc=false;
    }
    
    if (!empty($_POST['search_year_end']) && ($_POST['search_year_end'] < 1900 || $_POST['search_year_end'] > date('Y'))) {
        $message = "Ошибка: Год окончания должен быть между 1900 и " . date('Y') . ".";
        $messageType = "error";
        $suc=false;
    }
    if (!empty($_POST['search_year_start'])&&$suc) {
        
        $params['year_production_start'] = $_POST['search_year_start'];
    
        // $file="debug.txt";
        // $datafile=  $params['year_production_start'];
        // file_put_contents($file, $datafile);
    }

    if (!empty($_POST['search_year_end'])&&$suc) {
        $params['year_production_end'] = $_POST['search_year_end'];
    }

    if (!empty($_POST['search_VIN'])&&$suc) {
        $params['VIN_number'] = $_POST['search_VIN'];
        $searchableFields[] = 'VIN_number';
    }

    // Поля для проверки диапазона годов
    if($suc){
        $yearFields = ['year_production']; // Укажите поле года, по которому будет выполняться поиск

    // Выполнение поиска с формированными параметрами
    $searchResult = $carsTable->universalSearch($params, $searchableFields, $yearFields);

    if (!$searchResult['success']) {
        $message = $searchResult['message'];
        $messageType = 'error';
    } else {
        $cars = $searchResult['data'];
        if (empty($cars)) {
            $message = "Автомобили не найдены.";
            $messageType = "error"; // Ошибка
        } 
    }
}
}

// Добавление автомобиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_car'])) {
    $suc = true; // Флаг успешного выполнения
    
    if (isset($_POST['condition'])) {
        $conditionString = $_POST['condition'];
        $conditionArray = explode(',', $conditionString);
        
        $color = isset($conditionArray[0]) ? trim($conditionArray[0]) : null;
        $status = isset($conditionArray[1]) ? trim($conditionArray[1]) : null;
        $features = array_slice($conditionArray, 2); 

        $conditionData = [
            'цвет' => $color,
            'статус' => $status,
            'особенности' => array_map('trim', $features) 
        ];
    
        // Преобразуем массив в JSON с поддержкой русских символов
        $conditionJson = json_encode($conditionData, JSON_UNESCAPED_UNICODE);
    } else {
        $conditionJson = null;
    }
    
    // Собираем данные из формы
    $data = [
        'brand' => $_POST['brand'],
        'model' => $_POST['model'],
        'year_production' => $_POST['year_production'],
        'VIN_number' => $_POST['VIN_number'],
        'purchase_price' => $_POST['purchase_price'],
        '`condition`' =>$conditionJson, 
        'idgarage' => $_POST['idgarage'],
        'idsupplier' => $_POST['idsupplier'],
        'mileage' => $_POST['mileage'] ?? null,
        'engine_volume' => $_POST['engine_volume'] ?? null,
        'fuel_type' => $_POST['fuel_type'] ?? null,
        'transmission_type' => $_POST['transmission_type'] ?? null,
        'body_type' => $_POST['body_type'] ?? null,
    ];

    // Проверка уникальности VIN номера
    if (!empty($data['VIN_number'])) {
        $existingVIN = $carsTable->getCarByVIN($data['VIN_number']);
        if ($existingVIN) {
            $message = 'Ошибка: VIN номер уже занят. Пожалуйста, выберите другой.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    // Проверка существования поставщика
    $existingSupplier = $suppliersTable->getSupplierById($data['idsupplier']);
    if (!$existingSupplier) {
        $message = 'Ошибка: Поставщик с указанным ID не найден.';
        $messageType = 'error'; // Ошибка
        $suc = false; // Устанавливаем флаг на false
    }

    // Проверка idgarage
    if (!isset($data['idgarage']) || $data['idgarage'] < 1 || $data['idgarage'] > 4) {
        $message = 'Ошибка: ID гаража должен быть от 1 до 4.';
        $messageType = 'error'; // Ошибка
        $suc = false; // Устанавливаем флаг на false
    }

    if ($suc) {
        $stmt = $db->prepare("
            SELECT 1 FROM garage g 
            JOIN garage_car_brands gcb ON g.id = gcb.idgarage
            JOIN car_brands cb ON gcb.idcar_brands = cb.id
            WHERE name_brand = ? AND idgarage = ?;
        ");
        $stmt->bind_param("si", $data['brand'], $data['idgarage']);
        $stmt->execute();
        $result_check = $stmt->get_result();

        if ($result_check->num_rows === 0) {
            $message = 'Ошибка: Комбинация марки и гаража некорректна. Пожалуйста, проверьте введенные данные.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    // Если все проверки пройдены, начинаем транзакцию
    if ($suc) {
        $db->begin_transaction(); // Начало транзакции

        try {
            // Вызов функции добавления автомобиля
            $result = $carsTable->addRecord($data);

            // Проверяем, успешно ли добавлен автомобиль
            if ($result) {
                $carId = $carsTable->getLastInsertedId();

                // Записываем действие в журнал (если необходимо)
                $actStr = "Пользователь {$_SESSION['login']} добавил новый автомобиль с ID=$carId.";
                $dbExecutor->insertAction($_SESSION['user_id'], $actStr);

                $db->commit(); // Фиксация транзакции
                $message = 'Автомобиль добавлен успешно.';
                $messageType = 'success';
            } else {
                throw new Exception('Ошибка: Не удалось добавить автомобиль.');
            }
        } catch (Exception $e) {
            $db->rollback(); // Откат транзакции
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Изменение данных автомобиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit' && $_GET['table'] === 'cars') {
    // Извлекаем данные из формы
    $id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $brand = isset($_POST['edit_brand']) ? $_POST['edit_brand'] : null;
    $model = isset($_POST['edit_model']) ? $_POST['edit_model'] : null;
    $yearProduction = isset($_POST['edit_year_production']) ? intval($_POST['edit_year_production']) : null;
    $vinNumber = isset($_POST['edit_VIN_number']) ? $_POST['edit_VIN_number'] : null;
    $purchasePrice = isset($_POST['edit_purchase_price']) ? floatval($_POST['edit_purchase_price']) : null;
    $idGarage = isset($_POST['edit_idgarage']) ? intval($_POST['edit_idgarage']) : null;
    $idSupplier = isset($_POST['edit_idsupplier']) ? intval($_POST['edit_idsupplier']) : null;
    $mileage = isset($_POST['edit_mileage']) ? intval($_POST['edit_mileage']) : null;
    $engineVolume = isset($_POST['edit_engine_volume']) ? floatval($_POST['edit_engine_volume']) : null;
    $fuelType = isset($_POST['fuel_type']) ? $_POST['fuel_type'] : null;
    $transmissionType = isset($_POST['transmission_type']) ? $_POST['transmission_type'] : null;
    $bodyType = isset($_POST['body_type']) ? $_POST['body_type'] : null;


    if (isset($_POST['condition'])) {
        $conditionString = $_POST['condition'];
        $conditionArray = explode(',', $conditionString);
        
        $color = isset($conditionArray[0]) ? trim($conditionArray[0]) : null;
        $status = isset($conditionArray[1]) ? trim($conditionArray[1]) : null;
        $features = array_slice($conditionArray, 2); 

        $conditionData = [
            'цвет' => $color,
            'статус' => $status,
            'особенности' => array_map('trim', $features) 
        ];
    
        // Преобразуем массив в JSON с поддержкой русских символов
        $conditionJson = json_encode($conditionData, JSON_UNESCAPED_UNICODE);
    } else {
        $conditionJson = null;
    }

    $suc = true; // Флаг успешного выполнения

    // Проверка на существующий VIN номер
    if ($vinNumber) {
        $existingVIN = $carsTable->getCarByVIN($vinNumber);
        if ($existingVIN && $existingVIN['id'] != $id) { // Проверяем, что VIN занят другим автомобилем
            $message = 'Ошибка: VIN номер уже занят. Пожалуйста, выберите другой.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    // Получаем старые данные автомобиля
    if ($suc) {
        $stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $car = $result->fetch_assoc();

        if (!$car) {
            $message = 'Ошибка: Автомобиль не найден.';
            $messageType = 'error';
            $suc = false;
        }
    }

    // Проверка существования поставщика
    if($idSupplier){
       $existingSupplier = $suppliersTable->getSupplierById($idSupplier);
    if (!$existingSupplier) {
        $message = 'Ошибка: Поставщик с указанным ID не найден.';
        $messageType = 'error'; // Ошибка
        $suc = false; // Устанавливаем флаг на false
    } 
    }
    

    // Проверка idgarage
    if($idGarage){
         if (!isset($idGarage) || $idGarage< 1 || $idGarage > 4) {
        $message = 'Ошибка: ID гаража должен быть от 1 до 4.';
        $messageType = 'error'; // Ошибка
        $suc = false; // Устанавливаем флаг на false
        }
    }
   

    if ($suc&&$idGarage&&$brand) {
        $stmt = $db->prepare("
            SELECT 1 FROM garage g 
            JOIN garage_car_brands gcb ON g.id = gcb.idgarage
            JOIN car_brands cb ON gcb.idcar_brands = cb.id
            WHERE name_brand = ? AND idgarage = ?;
        ");
        $stmt->bind_param("si", $brand, $idGarage);
        $stmt->execute();
        $result_check = $stmt->get_result();

        if ($result_check->num_rows === 0) {
            $message = 'Ошибка: Комбинация марки и гаража некорректна. Пожалуйста, проверьте введенные данные.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    if ($suc&&$idGarage&&!$brand) {
        $stmt = $db->prepare("
            SELECT 1 FROM garage g 
            JOIN garage_car_brands gcb ON g.id = gcb.idgarage
            JOIN car_brands cb ON gcb.idcar_brands = cb.id
            WHERE name_brand = ? AND idgarage = ?;
        ");
        $stmt->bind_param("si", $car['brand'], $idGarage);
        $stmt->execute();
        $result_check = $stmt->get_result();

        if ($result_check->num_rows === 0) {
            $message = 'Ошибка: Комбинация марки и гаража некорректна. Пожалуйста, проверьте введенные данные.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    if ($suc&&!$idGarage&&$brand) {
        $stmt = $db->prepare("
            SELECT 1 FROM garage g 
            JOIN garage_car_brands gcb ON g.id = gcb.idgarage
            JOIN car_brands cb ON gcb.idcar_brands = cb.id
            WHERE name_brand = ? AND idgarage = ?;
        ");
        $stmt->bind_param("si", $brand, $car['idgarage']);
        $stmt->execute();
        $result_check = $stmt->get_result();

        if ($result_check->num_rows === 0) {
            $message = 'Ошибка: Комбинация марки и гаража некорректна. Пожалуйста, проверьте введенные данные.';
            $messageType = 'error'; // Ошибка
            $suc = false; // Устанавливаем флаг на false
        }
    }

    if ($suc) {
        // Подготовка данных для обновления
        $data = [];
        if ($brand !== null) $data['brand'] = $brand;
        if ($model !== null) $data['model'] = $model;
        if ($yearProduction !== null) $data['year_production'] = $yearProduction;
        if ($vinNumber !== null) $data['VIN_number'] = $vinNumber;
        if ($purchasePrice !== null) $data['purchase_price'] = $purchasePrice;
        if ($_POST['condition'] !== null) $data['condition']=$conditionJson;
        if ($idGarage !== null) $data['idgarage'] = $idGarage;
        if ($idSupplier !== null) $data['idsupplier'] = $idSupplier;
        if ($mileage !== null) $data['mileage'] = $mileage;
        if ($engineVolume !== null) $data['engine_volume'] = $engineVolume;
        if ($fuelType !== null) $data['fuel_type'] = $fuelType;
        if ($transmissionType !== null) $data['transmission_type'] = $transmissionType;
        if ($bodyType !== null) $data['body_type'] = $bodyType;

        // Начинаем транзакцию
        $db->begin_transaction();

        try {
            // Инициализируем переменные результата
            $result = ['type' => 'error']; // Значение по умолчанию
            
            // Обновление данных автомобиля, если есть что обновлять
            if (!empty($data)) {
                $result = $carsTable->updateRecord('cars', 'id', $id, $data);
            }

            // Проверяем результат выполнения
            if ($result['type'] === 'success') {
                $db->commit(); // Фиксация транзакции
                $login = $_SESSION['login'];
                $type_role = $_SESSION['type_role'];

                // Логируем действие
                $actStr = "Пользователь $login типа '$type_role' изменил данные автомобиля id=$id.";
                $dbExecutor->insertAction($_SESSION['user_id'], $actStr);

                $message = 'Данные автомобиля успешно изменены.';
                $messageType = 'success';
            } else {
                throw new Exception('Ошибка при обновлении данных автомобиля.');
            }
        } catch (Exception $e) {
            $db->rollback(); // Откат транзакции
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Удаление автомобиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete' && $_GET['table'] === 'cars') {
    // Извлекаем ID автомобиля из формы
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    $suc = true; // Флаг успешного выполнения

    // Получаем данные автомобиля по ID
    if ($suc) {
        $stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $car = $result->fetch_assoc();

        if (!$car) {
            $message = 'Ошибка: Автомобиль не найден.';
            $messageType = 'error';
            $suc = false;
        }
    }

    if ($suc) {
        // Начинаем транзакцию
        $db->begin_transaction();

        try {
            $db->query("ALTER TABLE cart_auto_parts DROP FOREIGN KEY cart_auto_parts_ibfk_2;");
            $db->query("ALTER TABLE history_operations_with_autoparts DROP FOREIGN KEY history_operations_with_autoparts_ibfk_2;");

            $stmt_cart = $db->prepare("DELETE FROM history_operations_with_autoparts WHERE idautoparts = ?");
            $stmt_cart->bind_param("i", $id);
            $stmt_cart->execute();
            $stmt_cart->close();
            $stmt_customers = $db->prepare("DELETE FROM cart_auto_parts WHERE idautoparts = ?");
            $stmt_customers->bind_param("i", $id);
            $stmt_customers->execute();
            $stmt_customers->close();

            $db->query("ALTER TABLE history_operations_with_autoparts ADD CONSTRAINT `history_operations_with_autoparts_ibfk_2` FOREIGN KEY (`idautoparts`) REFERENCES `auto_parts` (`id`);");
            $db->query("ALTER TABLE  cart_auto_parts ADD CONSTRAINT `cart_auto_parts_ibfk_2` FOREIGN KEY (`idautoparts`) REFERENCES `auto_parts` (`id`);");
            // Удаление автомобиля
            $result=$carsTable->deleteUser($id);

            if ($result) {
                // Фиксация транзакции
                $db->commit();
                $login = $_SESSION['login'];
                $type_role = $_SESSION['type_role'];

                // Логируем действие
                $actStr = "Пользователь $login типа '$type_role' удалил автомобиль id=$id.";
                $dbExecutor->insertAction($_SESSION['user_id'], $actStr);

                $message = 'Автомобиль успешно удалён.';
                $messageType = 'success';
            } else {
                throw new Exception('Ошибка при удалении автомобиля. Возможно, он не существует.');
            }
        } catch (Exception $e) {
            $db->rollback(); // Откат транзакции
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Создаем экземпляр класса AutoPartsManager
$autoPartsManager = new AutoPartsManager();

// Получение запчастей
// $message = '';
// $messageType = 'success';

// Проверка метода запроса и наличие кнопки поиска
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_parts'])) {

    // Получаем данные из формы
    $carBrand = isset($_POST['search_car_brand']) ? trim($_POST['search_car_brand']) : '';
    $carModel = isset($_POST['search_car_model']) ? trim($_POST['search_car_model']) : '';
    $sparePart = isset($_POST['spare_parts']) ? trim($_POST['spare_parts']) : '';
    $releaseYearStart = isset($_POST['release_year_start']) ? trim($_POST['release_year_start']) : '';
    $releaseYearEnd = isset($_POST['release_year_end']) ? trim($_POST['release_year_end']) : '';
    $article = isset($_POST['search_article']) ? trim($_POST['search_article']) : '';
    $body = isset($_POST['body']) ? trim($_POST['body']) : '';
    $itemNumber = isset($_POST['item_number']) ? trim($_POST['item_number']) : '';

    // Условия для поиска
    $searchConditions = [];
    
    // Формируем условия для поиска
    if (!empty($carBrand)) {
        $searchConditions[] = "brand = '" . mysqli_real_escape_string($db, $carBrand) . "'";
    }
    if (!empty($carModel)) {
        $searchConditions[] = "model = '" . mysqli_real_escape_string($db, $carModel) . "'";
    }
    if (!empty($sparePart)) {
        $searchConditions[] = "name_parts = '" . mysqli_real_escape_string($db, $sparePart) . "'";
    }
    if (isset($releaseYearStart) && !empty($releaseYearStart)) {
        $searchConditions[] = "year_production >= " . intval($releaseYearStart);
    }
    if (isset($releaseYearEnd) && !empty($releaseYearEnd)) {
        $searchConditions[] = "year_production <= " . intval($releaseYearEnd);
    }
    if (!empty($body)) {
        $searchConditions[] = "body_type = '" . mysqli_real_escape_string($db, $body) . "'";
    }
    if (!empty($itemNumber)) {
        $searchConditions[] = "article LIKE '%" . mysqli_real_escape_string($db, $itemNumber) . "%'";
    }

    // Выполняем поиск запчастей с учетом условий
    $part = $autoPartsManager->fetchParts($searchConditions);

    // Проверка наличия результатов
    if (empty($part)) {
        $message = "Запчасти не найдены.";
        $messageType = "error";
    } else {
        $message = ""; // Очистка сообщения, если нашли запчасти
    }
} else {
    // Получаем все запчасти, если поиск не выполнен
    $part = $autoPartsManager->getAllParts();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'&& isset($_POST['add_to_cart'])) {
    if (isset($_POST['part_id']) && isset($_POST['customer_id'])) {
        $partId = intval($_POST['part_id']);
        $customerId = intval($_POST['customer_id']);
        
        // Получаем ID корзины для данного покупателя
        $query = "SELECT id FROM cart WHERE idcustomer = $customerId";
        $result = $db->query($query);
        
        if (!$result) {
            die("Ошибка запроса: " . $db->error);
        }

        if ($result->num_rows > 0) {
            $cart = $result->fetch_assoc();
            $cartId = $cart['id'];

            // Добавляем запись в таблицу cart_auto_part
            $insertQuery = "INSERT INTO cart_auto_parts (idcart, idautoparts) VALUES ($cartId, $partId)";
            if ($db->query($insertQuery) === TRUE) {
                $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role = $_SESSION['type_role'];
            $actStr = "Покупатель $login добавил запчасть в корзину ID=$partId.";
            $logger->insertAction($id_user, $actStr);
            } else {
                $message= "Ошибка добавления запчасти в корзину: " . $db->error;
                $messageType = "error";
            }
        } else {
            $message=  "Корзина не найдена для данного покупателя.";
            $messageType = "error";
        }
    } else {
        $message=  "Не указаны необходимые данные.";
        $messageType = "error";
    }
} 


 //сортировка запчастей

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sort_options'])) {
    $sortOption = isset($_POST['sort_options']) ? $_POST['sort_options'] : 'date'; // Значение по умолчанию
    // Сортировка запчастей
    $part = $autoPartsManager->sortParts($part, $_POST['sort_options']);
}

 //удаление из корзины
if ($_SERVER['REQUEST_METHOD'] === 'POST'&& isset($_POST['delete_from_cart'])) {
    if (isset($_POST['part_id']) && isset($_POST['customer_id'])) {
        $partId = intval($_POST['part_id']);
        $customerId = intval($_POST['customer_id']);
        $query = "SELECT id FROM cart WHERE idcustomer = $customerId";
        $result = $db->query($query);
        
        if (!$result) {
            die("Ошибка запроса: " . $db->error);
        }

        if ($result->num_rows > 0) {
            $cart = $result->fetch_assoc();
            $cartId = $cart['id'];

            // Добавляем запись в таблицу cart_auto_part
            $deleteQuery = "DELETE FROM cart_auto_parts WHERE idcart=$cartId AND idautoparts= $partId";
            if ($db->query($deleteQuery) === TRUE) {
                $login = $_SESSION['login'];
            $id_user = $_SESSION['user_id'];
            $type_role = $_SESSION['type_role'];
            $actStr = "Покупатель $login удалил запчасть из корзины ID=$partId.  $cartId     $customerId  ";
            $logger->insertAction($id_user, $actStr);
            } else {
                $message= "Ошибка добавления запчасти в корзину: " . $db->error;
                $messageType = "error";
            }
        } else {
            $message=  "Корзина не найдена для данного покупателя.";
            $messageType = "error";
        }
    } else {
        $message=  "Не указаны необходимые данные.";
        $messageType = "error";
    }
} 

?>
