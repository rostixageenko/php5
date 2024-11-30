<?php
// Database configuration
include 'sessionConf.php';
include 'activity_log_manager.php';
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "auto_disassembly_station";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Создание соединения
    $conn = new mysqli($servername, $username, $password, $dbname);
    $_SESSION['server_conn_error'] = false;
} catch (mysqli_sql_exception $e) {
    ?>
    <div class="error-message">
        ✖ <?php echo htmlspecialchars($_SESSION['sql_error_message']) . ' ' . htmlspecialchars($e->getMessage());
        $_SESSION['server_conn_error'] = true;
        ?>
    </div>
    <?php
    error_log($_SESSION['sql_error_message']);
}

// Проверка на скачивание Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    include 'vendor/autoload.php'; // Подключаем автозагрузчик Composer
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Заголовки таблицы
    $sheet->setCellValue('A1', 'ИД события');
    $sheet->setCellValue('B1', 'Действующее лицо');
    $sheet->setCellValue('C1', 'Дата и время действия');
    $sheet->setCellValue('D1', 'Действие');

    // Заполнение данными
    $row = 2; // Начинаем со второй строки
    if ($result->num_rows > 0) {
        while ($data = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $data['id']);
            $sheet->setCellValue('B' . $row, $data['login']);
            $sheet->setCellValue('C' . $row, $data['action_datetime']);
            $sheet->setCellValue('D' . $row, $data['action']);
            $row++;
        }
    }

    // Установка заголовков для скачивания файла
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="activity_log.xlsx"');
    header('Cache-Control: max-age=0');

    // Запись в выходной поток
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Начало HTML
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История операций</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Ваши стили */
    </style>
</head>
<body>

<header>
    <img src="image/logo5.png" alt="Логотип" class="logo"> 
    <p>
        <a href="admin_interface_main.php" class="button">Назад</a>    
        <a href="index.php?logout='1'" class="button">Выйти</a>
    </p>
</header>

<main>
    <h1 class="title">История операций</h1>
    <form method="POST" action="activity_log.php">
        <div class="filter-container">
            <input type="text" name="event_id" placeholder="ИД события" />
            <input type="text" name="actor_name" placeholder="Действующее лицо" />
            <input type="date" name="start_date" />
            <input type="date" name="end_date" />
            <input type="text" name="action" placeholder="Действие" />
            <button type="submit" name="search" class="btn">Поиск</button>
        </div>
    </form>

    <?php
    // Обработка и вывод ошибок
    if (isset($_SESSION['error_message']) && $_SESSION['error_message'] !== '') {
        echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        $_SESSION['error_message'] = ''; // Очистить сообщение об ошибке
    }
    ?>

    <table>
        <thead>
            <tr>
                <th>ИД события</th>
                <th>Действующее лицо</th>
                <th>Дата и время действия</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Проверяем, есть ли результаты поиска в сессии
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            ?>
                <tr>
                    <td style="cursor:pointer"><?php echo htmlspecialchars($row['id']); ?></td>
                    <td style="cursor:pointer"><?php echo htmlspecialchars($row['login']); ?></td>
                    <td style="cursor:pointer"><?php echo htmlspecialchars($row['action_datetime']); ?></td>
                    <td style="cursor:pointer"><?php echo htmlspecialchars($row['action']); ?></td>
                </tr>
            <?php        
                }
            } else {
                echo "<tr><td colspan='4'>Нет данных для отображения.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Кнопка для скачивания Excel -->
    <a href="activity_log.php?export=excel" class="btn">Скачать в Excel</a>
</main>

</body>
</html>