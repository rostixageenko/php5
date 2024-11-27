<?php

include 'db.php'; 
include 'sessionConf.php';
// require 'vendor/autoload.php'; // Убедитесь, что вы установили PhpSpreadsheet через Composer

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$search_query = '';
$order_by = 'action_datetime';
$order_dir = 'DESC';

$event_id = '';
$actor_name = '';
$start_date = '';
$end_date = '';
$action = '';

try {
    if (isset($_SESSION['error_message']) && $_SESSION['error_message'] == "Неверный логин или пароль, попробуйте еще раз.") {
        $_SESSION['error_message'] = '';
    }
    if ($_SESSION['server_conn_error'] === true) {
        throw new Exception("Ошибка соединения с сервером");
    }
    if (!isset($conn)) {
        throw new Exception("Ошибка соединения с сервером");
    }

    if (isset($_GET['order_by']) && isset($_GET['order_dir'])) {
        $order_by = $_GET['order_by'];
        $order_dir = $_GET['order_dir'] === 'ASC' ? 'DESC' : 'ASC';
    }

    if ($search_query == '') {
        $query = "
        SELECT 
        users.login,
        sys_activity_log.id,
        sys_activity_log.action_datetime,
        sys_activity_log.action
        FROM 
            sys_activity_log
        JOIN 
            users ON sys_activity_log.actor_id = users.id
        WHERE 1=1";
        
        if (!empty($search_query)) {
            $query .= " AND name LIKE '%$search_query%' ";
        }

        $query .= "
            ORDER BY $order_by $order_dir
        ";

        $result = $conn->query($query);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $event_id = trim($_POST['event_id']);
        $actor_name = trim($_POST['actor_name']);
        $start_date = trim($_POST['start_date']);
        $end_date = trim($_POST['end_date']);
        $action = trim($_POST['action']);
        
        $query = "
        SELECT 
            users.login,
            sys_activity_log.id,
            sys_activity_log.action_datetime,
            sys_activity_log.action
        FROM 
            sys_activity_log
        JOIN 
            users ON sys_activity_log.actor_id = users.id
        WHERE 1=1";
        
        $params = [];
        
        // Проверка id
        if (!empty($event_id) && filter_var($event_id, FILTER_VALIDATE_INT) !== false) {
            $query .= " AND sys_activity_log.id = ?";
            $params[] = $event_id;
        }
        
        // Проверка actor_name
        if (!empty($actor_name)) {
            $query .= " AND users.login LIKE ?";
            $params[] = "%$actor_name%";
        }
        
        // Проверка на даты
        if (!empty($start_date) || !empty($end_date)) {
            if (!empty($start_date) && !empty($end_date)) {
                // Если обе даты заполнены, проверяем порядок
                if ($end_date < $start_date) {
                    $_SESSION['error_message'] = "Конечная дата не может быть раньше стартовой даты.";
                } else {
                    $query .= " AND action_datetime BETWEEN ? AND ?";
                    $params[] = $start_date;
                    $params[] = $end_date . ' 23:59:59'; // Учитываем весь день
                }
            } elseif (!empty($start_date)) {
                // Если только стартовая дата заполнена
                $query .= " AND action_datetime >= ?";
                $params[] = $start_date;
            } elseif (!empty($end_date)) {
                // Если только конечная дата заполнена
                $query .= " AND action_datetime <= ?";
                $params[] = $end_date;
            }
        }
        
        // Проверка action
        if (!empty($action)) {
            $query .= " AND sys_activity_log.action LIKE ?";
            $params[] = "%$action%";
        }
        $query .= "
            ORDER BY $order_by $order_dir
        ";
        
        // Подготовка и выполнение запроса
        $stmt = $conn->prepare($query);
        if ($params) {
            $types = str_repeat('s', count($params)); // Все параметры как строки
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
    }

} catch (mysqli_sql_exception $e) {
    ?>
    <div class="error-message">
        ✖ <?php echo htmlspecialchars($_SESSION['sql_error_message']) . ' ' . htmlspecialchars($e->getMessage()); ?>
    </div>
    <?php
    exit();

} catch (Exception $e) {
    ?>
    <div class="error-message">
        ✖ <?php echo htmlspecialchars($_SESSION['server_error_message']) . ' ' . htmlspecialchars($e->getMessage()); ?>
    </div>
    <?php
    exit();
}
?>