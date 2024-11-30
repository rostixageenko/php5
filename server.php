<?php
include ("db_executer.php");
session_start();

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

    mysqli_query($db, $query1); // Выполняем запрос

    $_SESSION['login'] = $login;
    $_SESSION['success'] = "Вы успешно вошли в систему";
    $_SESSION['type_role'] = 0; 

    header('location: index.php');
    exit();
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
            header('Location: index.php');
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


?>
