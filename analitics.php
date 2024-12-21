<?php
include('server.php');

$query = "
    SELECT *
    FROM (
        SELECT c.brand AS car_brand, COUNT(*) AS count_parts
        FROM auto_parts ap
        JOIN cars c ON c.id = ap.idcar
        JOIN orders o ON o.id = ap.idorder
        WHERE ap.status IN ('Продана', 'продана', 'продано', 'Продано')
          AND o.datetime >= NOW() - INTERVAL 3 MONTH
        GROUP BY c.brand
    ) AS t1
    JOIN (
        SELECT c.brand AS car_brand, SUM(ap.purchase_price) AS total_price
        FROM auto_parts ap
        JOIN orders o ON o.id = ap.idorder
        JOIN cars c ON c.id = ap.idcar
        WHERE ap.status IN ('Продана', 'продана', 'продано', 'Продано')
          AND o.datetime >= NOW() - INTERVAL 3 MONTH
        GROUP BY c.brand
    ) AS t2
    USING (car_brand);
";

$result = mysqli_query($db, $query);
$data = mysqli_fetch_all($result, MYSQLI_ASSOC);

$totalProfitQuery = "
    SELECT SUM(ap.purchase_price) AS total_profit
    FROM auto_parts ap
    JOIN orders o ON o.id = ap.idorder
    WHERE ap.status IN ('Продана', 'продана', 'продано', 'Продано')
      AND o.datetime >= NOW() - INTERVAL 3 MONTH;
";

$totalProfitResult = mysqli_query($db, $totalProfitQuery);
$totalProfitData = mysqli_fetch_assoc($totalProfitResult);
$totalProfit = $totalProfitData['total_profit'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'Марка автомобиля');
    $sheet->setCellValue('C1', 'Количество проданных запчастей');
    $sheet->setCellValue('E1', 'Прибыль');
    
    $sheet->mergeCells('A1:B1'); 
    $sheet->mergeCells('C1:D1'); 

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
    
    $row = 2;
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $row, $item['car_brand']);
        $sheet->setCellValue('C' . $row, $item['count_parts']);
        $sheet->setCellValue('E' . $row, $item['total_price']);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->mergeCells('C' . $row . ':D' . $row);
        
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        
        $row++;
    }

    $sheet->setCellValue('G2', 'Общая сумма прибыли:');
    $sheet->setCellValue('I2', number_format($totalProfit, 2, ',', ' '));
    $sheet->mergeCells('G2:H2');

    $sheet->getStyle('G2:I2')->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
        'font' => [
            'bold' => true,
        ],
    ]);

    $salesChartImage = 'sales_chart.png';
    $profitChartImage = 'profit_chart.png';

    file_put_contents($salesChartImage, base64_decode($_POST['salesChart']));
    file_put_contents($profitChartImage, base64_decode($_POST['profitChart']));

    $drawing1 = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $drawing1->setName('Sales Chart');
    $drawing1->setDescription('Sales Chart');
    $drawing1->setPath($salesChartImage);
    $drawing1->setCoordinates('A' . ($row + 2));
    $drawing1->setHeight(200);
    $drawing1->setWorksheet($sheet);

    $drawing2 = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $drawing2->setName('Profit Chart');
    $drawing2->setDescription('Profit Chart');
    $drawing2->setPath($profitChartImage);
    $drawing2->setCoordinates('E' . ($row + 2));
    $drawing2->setHeight(200);
    $drawing2->setWorksheet($sheet);

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
    <title>Аналитика</title>
    <link rel="stylesheet" href="style.css">
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
        .menu {
            display: flex;
            align-items: center;
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
            background-color:rgb(40, 185, 47);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<header>
    <a href="admin_interface_main.php">
        <img src="image/logo5.png" alt="Логотип" class="logo">
    </a>
    <p>
        <a href="admin_interface_main.php" class="button">Назад</a>    
        <a href="index.php?logout='1'" class="button">Выйти</a>
    </p>
</header>

<main>
    <section class="analytics">
        <h1>Аналитика запчастей</h1>
        <div class="summary">
            <h2>Количество проданных запчастей и прибыль по маркам автомобилей за последние 3 месяца</h2>
            <table>
                <thead>
                    <tr>
                        <th>Марка автомобиля</th>
                        <th>Количество проданных запчастей</th>
                        <th>Прибыль</th>
                    </tr>
                </thead>
                <tbody id="parts-summary">
                    <?php
                    foreach ($data as $row) {
                        echo "<tr>
                                <td>{$row['car_brand']}</td>
                                <td>{$row['count_parts']}</td>
                                <td>{$row['total_price']}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="charts">
            <h2>Диаграммы продаж и прибыли по маркам автомобилей</h2>
            <canvas id="salesChart" width="350" height="200"></canvas>
            <canvas id="profitChart" width="350" height="200"></canvas>
        </div>
        <h3>Общая сумма прибыли: <span id="total-profit"><?php echo number_format($totalProfit, 2, ',', ' '); ?></span> руб.</h3>
        <button id="exportReport" class="custom-button" onclick="exportReport()">Выгрузить отчет в Excel</button>
    </section>
</main>

<script>
    const carBrands = <?php echo json_encode(array_column($data, 'car_brand')); ?>;
    const salesData = <?php echo json_encode(array_column($data, 'count_parts')); ?>;
    const profitData = <?php echo json_encode(array_column($data, 'total_price')); ?>;

    const ctxSales = document.getElementById('salesChart').getContext('2d');
    const ctxProfit = document.getElementById('profitChart').getContext('2d');

    const salesChart = new Chart(ctxSales, {
        type: 'bar',
        data: {
            labels: carBrands,
            datasets: [{
                label: 'Количество проданных запчастей',
                data: salesData,
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

    const profitChart = new Chart(ctxProfit, {
        type: 'bar',
        data: {
            labels: carBrands,
            datasets: [{
                label: 'Прибыль',
                data: profitData,
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
        const salesChartImage = ctxSales.canvas.toDataURL('image/png');
        const profitChartImage = ctxProfit.canvas.toDataURL('image/png');

        const formData = new FormData();
        formData.append('salesChart', salesChartImage.split(',')[1]);
        formData.append('profitChart', profitChartImage.split(',')[1]);

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
        }).catch(error => console.error('There was a problem with the fetch operation:', error));
    }
</script>
</body>
</html>