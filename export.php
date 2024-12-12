<?php
// Настройки подключения к базе данных
$host = 'localhost'; // Хост
$user = 'root'; // Имя пользователя
$password = ''; // Пароль
$dbname = 'auto_disassembly_station'; // Имя базы данных

// Соединение с базой данных
$conn = new mysqli($host, $user, $password, $dbname);


// Проверка соединения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}  

// Установка кодировки соединения на utf8mb4
$conn->set_charset("utf8mb4");

// Проверка, была ли нажата кнопка "Выгрузить"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $tableName = $_POST['table'];

    if (empty($tableName)) {
        die("Ошибка: Не указано имя таблицы.");
    }

    // Выполнение запроса для получения данных
    $query = "SELECT * FROM `$tableName`"; // Получаем все столбцы
    $result = $conn->query($query);

    if (!$result) {
        die("Ошибка запроса: " . $conn->error);
    }

    if ($result->num_rows === 0) {
        $message = 'Нет данных в таблице.';
        $messageType = "error";

    }

    $data = [];

    while ($row = $result->fetch_assoc()) {
     // Проверка наличия данных в поле photo
        if($tableName==="auto_parts"){
            if (isset($row['photo'])) {
                // Преобразование BLOB в Base64
                $row['photo'] = base64_encode($row['photo']);
                // Добавление метаданных о типе изображения, если необходимо
                $row['photo'] = 'data:image/jpeg;base64,' . $row['photo']; // Замените 'image/jpeg' на нужный тип
            } else {
                $row['photo'] = null; // Если данных нет
            }
        }
        $data[] = $row;
    }

    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Ошибка кодирования в JSON: ' . json_last_error_msg());
    }else{
        $login = $_SESSION['login'];
        $id_user = $_SESSION['user_id'];
        $type_role = $_SESSION['type_role'];
            $actStr = "Пользователь $login типа '$type_role'  загрузил таблицу $table.";
            $dbExecutor->insertAction($id_user, $actStr); 
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $tableName . '_export.json"');
    echo $jsonData;
}

$conn->close();

function cleanUtf8($data) {
    return preg_replace('/[^\x{0000}-\x{FFFF}]/u', '', $data);
}
?>