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

// Установка кодировки соединения
$conn->set_charset("utf8"); // Установка кодировки UTF-8

// Проверка, была ли нажата кнопка "Выгрузить"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $tableName = $_POST['table']; // Получаем имя таблицы из формы

    // Проверка на наличие имени таблицы
    if (empty($tableName)) {
        die("Ошибка: Не указано имя таблицы.");
    }

    // Выполнение запроса для получения данных
    $query = "SELECT * FROM `$tableName`"; // Обрамление имени таблицы в обратные кавычки для безопасности
    $result = $conn->query($query);

    if ($result) {
        $data = [];

        // Извлечение данных из результата
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // Кодирование данных в JSON
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // Используем JSON_UNESCAPED_UNICODE

        // Проверка на ошибки кодирования в JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            die('Ошибка кодирования в JSON: ' . json_last_error_msg());
        }

        // Установка заголовков для загрузки JSON-ответа
        header('Content-Type: application/json; charset=utf-8'); // Установка кодировки в заголовках
        header('Content-Disposition: attachment; filename="' . $tableName . '_export.json"');
        echo $jsonData; // Возвращаем JSON-данные
    } else {
        // Обработка ошибок запроса
        echo "Ошибка получения данных: " . $conn->error;
    }
}

// Закрытие соединения
$conn->close();
?>