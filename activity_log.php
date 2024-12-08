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
if (isset($_GET['export']) && $_GET['export'] === 'excel') 
{
    include 'vendor/autoload.php'; // Подключаем автозагрузчик Composer
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Заголовки таблицы
    $sheet->setCellValue('A1', 'ИД события');
    $sheet->setCellValue('B1', 'Действующее лицо');
    $sheet->setCellValue('D1', 'Дата и время действия');
    $sheet->setCellValue('G1', 'Действие');

    // Объединяем ячейки
    $sheet->mergeCells('B1:C1'); // Объединяем ячейки B1 и C1
    $sheet->mergeCells('D1:F1'); // Объединяем ячейки D1, E1 и F1
    $sheet->mergeCells('G1:M1'); // Объединяем ячейки G1 и M1

    // Установка границ для заголовков
    $styleArrayHeader = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                'color' => ['argb' => 'FF000000'], // Черный цвет
            ],
        ],
        'font' => [
            'bold' => true, // Жирный шрифт для заголовков
        ],
    ];

    // Применение стиля к заголовкам
    $sheet->getStyle('A1:M1')->applyFromArray($styleArrayHeader);

    // Установка автоматической ширины для столбцов
    $sheet->getColumnDimension('A')->setAutoSize(true);


    // Заполнение данными из всей таблицы sys_activity_log
    $query = "SELECT * FROM sys_activity_log"; // Запрос для получения всех данных
    $result = $conn->query($query);
    
    // Заполнение данными
    $row = 2; // Начинаем со второй строки
    if ($result->num_rows > 0) {
        while ($data = $result->fetch_assoc()) {
            // Заполнение данными
            $sheet->setCellValue('A' . $row, $data['id']);
            $sheet->setCellValue('B' . $row, $data['actor_id']);
            $sheet->setCellValue('D' . $row, $data['action_datetime']);
            $sheet->setCellValue('G' . $row, $data['action']);
            
            $sheet->mergeCells('B' . $row . ':C' . $row); // Объединение для actor_id
            $sheet->mergeCells('D' . $row . ':F' . $row); // Объединение для action_datetime
            $sheet->mergeCells('G' . $row . ':M' . $row); // Объединение для action
            // Применение границ к данным
            $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'], // Черный цвет
                    ],
                ],
            ]);
    
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

// Проверка на скачивание JSON
if (isset($_GET['export']) && $_GET['export'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="activity_log.json"');
    header('Cache-Control: no-cache');

    $query = "SELECT * FROM sys_activity_log"; // Запрос для получения всех данных
    $result = $conn->query($query);

    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row; // Добавление каждой строки в массив
        }
    }

    // Кодирование в JSON с поддержкой UTF-8 и форматированием
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); // Конвертация массива в JSON и вывод
    exit();
}

// Проверка на скачивание XML
if (isset($_GET['export']) && $_GET['export'] === 'xml') {
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="activity_log.xml"');
    header('Cache-Control: no-cache');

    // Создание нового XML документа
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><activity_log/>');

    $query = "SELECT * FROM sys_activity_log"; // Запрос для получения всех данных
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $entry = $xml->addChild('entry');
            foreach ($row as $key => $value) {
                $entry->addChild($key, htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8')); // Экранируем специальные символы
            }
        }
    }

    // Форматирование XML с использованием DOMDocument
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());

    echo $dom->saveXML(); // Выводим отформатированный XML документ
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
        tr:nth-child(even) {
        background-color: #f9f9f9; /* Чередующиеся цвета строк */
        }
        .filter-container {
            display: flex; /* Используем flexbox для расположения в ряд */
            gap: 10px; /* Отступы между элементами */
            align-items: center; /* Выравниваем элементы по центру по вертикали */
        }

        .filter-container input[type="text"],
        .filter-container input[type="date"],
        .filter-container button {
            padding: 10px; /* Отступ внутри полей */
            height: 40px; /* Одинаковая высота */
            font-size: 14px; /* Размер шрифта */
            border: 1px solid #ccc; /* Рамка полей */
            border-radius: 4px; /* Закругление углов */
            margin: 0; /* Убираем отступы */
            box-sizing: border-box; /* Учитываем границы и отступы в общей ширине и высоте */
        }

        .filter-container button {
            background-color: #8e8e8e; /* Цвет фона кнопки */
            color: white; /* Цвет текста кнопки */
            cursor: pointer; /* Указатель, меняется на "руку" при наведении */
        }

        .filter-container button:hover {
            background-color: #4a4a4a; /* Цвет фона кнопки при наведении */
        }
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
    <!-- Кнопка для скачивания Excel и выгрузки в json -->
    <a href="activity_log.php?export=excel" class="btn">Скачать в Excel</a>
    <a href="activity_log.php?export=json" class="btn">Выгрузить в JSON</a>
    <a href="activity_log.php?export=xml" class="btn">Выгрузить в XML</a>
</main>

</body>
</html>