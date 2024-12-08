<?php
// Подключение к базе данных
$host = '127.0.0.1';
$db = 'auto_disassembly_station';
$user = 'root'; // Замените на ваше имя пользователя
$pass = ''; // Замените на ваш пароль
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
// Указываем имя файла
$filename = 'debug.txt';

// Строка, которую нужно записать
$data = "Это пример строки для записи в файл.";

// Записываем строку в файл
file_put_contents($filename, $data); // FILE_APPEND добавляет строку в конец файла


// Проверка, была ли отправлена форма для экспорта
if (isset($_POST['export'])) {
                            
    // Получаем имя файла из формы или используем значение по умолчанию
    $filename = !empty($_POST['filename']) ? $_POST['filename'] : 'database_export.json';

    // Список таблиц для экспорта
    $tables = [
        'auto_parts', 
        'cars', 
        'cart', 
        'cart_auto_parts', 
        'car_brands', 
        'customers', 
        'departments', 
        'garage', 
        'garage_car_brands', 
        'history_operations_with_autoparts', 
        'history_operations_with_car', 
        'inventory', 
        'orders', 
        'posts', 
        'staff', 
        'staff_garage', 
        'suppliers', 
        'sys_activity_log', 
        'users'
    ];

    $data = [];

    // Экспорт данных из каждой таблицы
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT * FROM $table");
        $data[$table] = $stmt->fetchAll();
    }

    // Устанавливаем заголовки для скачивания
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Выводим данные в формате JSON
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}


    // Логика для загрузки базы данных
    if (isset($_POST['upload'])) {
        // Проверка, был ли загружен файл
        if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['json_file']['tmp_name'];

            // Чтение содержимого файла
            $jsonData = file_get_contents($fileTmpPath);
            $data = json_decode($jsonData, true);

            // Проверка на ошибки декодирования JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "Ошибка: некорректный JSON файл.";
                exit;
            }

            // Логика обработки данных (например, сохранение в базу данных)
            // Ваш код для обработки данных здесь
            // Например, можно сохранить в файл или в базу данных

            echo "Данные успешно загружены!";
            exit;
        } else {
            echo "Ошибка загрузки файла.";
        }
    }
?>