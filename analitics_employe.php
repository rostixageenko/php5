<?php
include('server.php');

// Запрос для получения данных по задачам с автомобилями
$queryCars = "
    SELECT CONCAT(s.first_name, ' ', s.second_name) AS staff_name, 
           COUNT(h.id) AS count_tasks_cars
    FROM staff s
    LEFT JOIN history_operations_with_car h ON s.id = h.idstaff
    WHERE h.id IS NOT NULL
    GROUP BY s.id;
";

$resultCars = mysqli_query($db, $queryCars);
$dataCars = mysqli_fetch_all($resultCars, MYSQLI_ASSOC);

// Запрос для получения данных по задачам с запчастями
$queryParts = "
    SELECT CONCAT(s.first_name, ' ', s.second_name) AS staff_name, 
           COUNT(h.id) AS count_tasks_parts
    FROM staff s
    LEFT JOIN history_operations_with_autoparts h ON s.id = h.idstaff
    WHERE h.id IS NOT NULL
    GROUP BY s.id;
";

$resultParts = mysqli_query($db, $queryParts);
$dataParts = mysqli_fetch_all($resultParts, MYSQLI_ASSOC);

// Объединяем данные для диаграмм
$staffNamesCars = array_column($dataCars, 'staff_name');
$countTasksCars = array_column($dataCars, 'count_tasks_cars');

$staffNamesParts = array_column($dataParts, 'staff_name');
$countTasksParts = array_column($dataParts, 'count_tasks_parts');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'ФИО работника');
    $sheet->setCellValue('C1', 'Количество выполненных задач (машины)');
    $sheet->setCellValue('E1', 'Количество выполненных задач (запчасти)');

    $sheet->mergeCells('A1:B1');
    $sheet->mergeCells('C1:D1');
    $sheet->mergeCells('E1:F1');

    $styleArrayHeader = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
        'font' => [
            'bold' => true,
        ],
    ];

    $sheet->getStyle('A1:E1')->applyFromArray($styleArrayHeader);
    $sheet->getColumnDimension('A')->setAutoSize(true);

    // Заполняем данные по автомобилям
    $row = 2;
    foreach ($dataCars as $item) {
        $sheet->setCellValue('A' . $row, $item['staff_name']);
        $sheet->setCellValue('C' . $row, $item['count_tasks_cars']);
        $sheet->setCellValue('E' . $row, 0); // Запчасти по умолчанию 0
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->mergeCells('C' . $row . ':D' . $row);
        $sheet->mergeCells('E' . $row . ':F' . $row);

        $row++;
    }

    // Заполняем данные по запчастям
    foreach ($dataParts as $item) {
        // Проверяем, есть ли уже работник в таблице по автомобилям
        if (!in_array($item['staff_name'], $staffNamesCars)) {
            $sheet->setCellValue('A' . $row, $item['staff_name']);
            $sheet->setCellValue('C' . $row, 0); // Машины по умолчанию 0
            $sheet->setCellValue('E' . $row, $item['count_tasks_parts']);
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->mergeCells('C' . $row . ':D' . $row);
            $sheet->mergeCells('E' . $row . ':F' . $row);

            $row++;
        }
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="report.xlsx"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика по работникам</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        main {
            width: auto;
            max-width: 100%;
            padding: 20px;
            background: rgba(203, 202, 202, 0.89);
        }

        header {
            background: #333;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            height: 50px;
        }

        .menu .button {
            background: #555;
            color: #fff;
            padding: 10px 15px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .menu .button:hover {
            background: #777;
        }

        h1, h2, h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .analytics {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        table th {
            background-color: #4CAF50;
            color: white;
        }

        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table tr:hover {
            background-color: #ddd;
        }

        .custom-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .custom-button:hover {
            background-color: rgb(40, 185, 47);
        }

        .charts {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .charts canvas {
            flex: 1;
            margin: 0 10px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .charts {
                flex-direction: column;
            }

            .charts canvas {
                margin: 10px 0;
            }

            .menu {
                flex-direction: column;
            }

            .menu .button {
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="admin_interface_main.php">
            <img src="image/logo5.png" alt="Логотип" class="logo">
        </a>
        <p>
            <a href="analitics.php" class="button">Аналитика запчастей по маркам</a>
            <a href="analitics_part.php" class="button">Аналитика запчастей по видам</a>
            <a href="admin_interface_main.php" class="button">Назад</a>
            <a href="index.php?logout='1'" class="button">Выйти</a>
        </p>
    </header>
    <main>
        <section class="analytics">
            <h1>Аналитика по работникам</h1>
            <h2>Задачи по автомобилям</h2>
            <table>
                <thead>
                    <tr>
                        <th>ФИО работника</th>
                        <th>Количество задач (машины)</th>
                    </tr>
                </thead>
                <tbody id="staff-summary-cars">
                    <?php
                    foreach ($dataCars as $row) {
                        echo "<tr>
                            <td>{$row['staff_name']}</td>
                            <td>{$row['count_tasks_cars']}</td>
                          </tr>";
                    }
                    ?>
                </tbody>
            </table>

            <h2>Задачи по запчастям</h2>
            <table>
                <thead>
                    <tr>
                        <th>ФИО работника</th>
                        <th>Количество задач (запчасти)</th>
                    </tr>
                </thead>
                <tbody id="staff-summary-parts">
                    <?php
                    foreach ($dataParts as $row) {
                        echo "<tr>
                            <td>{$row['staff_name']}</td>
                            <td>{$row['count_tasks_parts']}</td>
                          </tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="charts">
                <h2>Диаграммы по работникам</h2>
                <canvas id="tasksCarsChart" width="350" height="200"></canvas>
                <canvas id="tasksPartsChart" width="350" height="200"></canvas>
            </div>
            <button id="exportReport" class="custom-button" onclick="exportReport()">Выгрузить отчет в Excel</button>
        </section>
    </main>
    <script>
        const staffNamesCars = <?php echo json_encode($staffNamesCars); ?>;
        const countTasksCars = <?php echo json_encode($countTasksCars); ?>;

        const staffNamesParts = <?php echo json_encode($staffNamesParts); ?>;
        const countTasksParts = <?php echo json_encode($countTasksParts); ?>;

        const ctxTasksCars = document.getElementById('tasksCarsChart').getContext('2d');
        const ctxTasksParts = document.getElementById('tasksPartsChart').getContext('2d');

        const tasksCarsChart = new Chart(ctxTasksCars, {
            type: 'bar',
            data: {
                labels: staffNamesCars,
                datasets: [{
                    label: 'Количество задач (машины)',
                    data: countTasksCars,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const tasksPartsChart = new Chart(ctxTasksParts, {
            type: 'bar',
            data: {
                labels: staffNamesParts,
                datasets: [{
                    label: 'Количество задач (запчасти)',
                    data: countTasksParts,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function exportReport() {
            const formData = new FormData();
            fetch('', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Network response was not ok.');
            }).then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'report.xlsx';
                document.body.appendChild(a);
                a.click();
                a.remove();
            }).catch(error => console.error('Ошибка:', error));
        }
    </script>
</body>
</html>