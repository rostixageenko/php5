<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once('server.php'); // подключаем файл с настройками БД

// Проверка, есть ли пользователь в сессии
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$customerId = $_SESSION['customerId'];

// Получаем заказы пользователя
$sql = 'SELECT o.id, o.type_order, o.status, o.purchase_price, o.datetime 
        FROM orders o 
        WHERE o.idcustomer = ? 
        ORDER BY o.datetime DESC';
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'i', $customerId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

// Закрываем подготовленный запрос
mysqli_stmt_close($stmt);

$autoPartsManager = new AutoPartsManager();
$_SESSION['orders'] = 1;

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header-user">
        <a href="user_interface_main.php">
            <img src="image/logo_new.png" alt="Логотип" class="logo">
        </a>
        <nav>
            <a href="user_interface_main.php" class="custom_button_second">Назад</a>
            <a href="index.php?logout='1'" class="custom_button_second">Выйти</a>
        </nav>
    </header>

    <main class="custom-main">

        <div class="orders-container">
            <h1>Мои заказы</h1>
            <?php if (empty($orders)): ?>
                <p>У вас пока нет заказов.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-details">
                        <h3>Тип заказа: <?php echo htmlspecialchars($order['type_order']); ?></h3>
                        <p style="color: blue;">Статус: <?php echo htmlspecialchars($order['status']); ?></p>
                        <p>Время заказа: <?php echo htmlspecialchars($order['datetime']); ?></p>
                        <p>Итоговая стоимость: <?php echo htmlspecialchars($order['purchase_price']); ?> р.</p>
                        
                        <div class="parts-list">
                            <?php
                            // Получаем запчасти для текущего заказа
                            $orderId = $order['id'];
                            $partsSql = "SELECT ap.id, name_parts, article, ap.`condition`, ap.purchase_price, description, 
                                                idcar, ap.idgarage, photo, idorder, status, brand, model, 
                                                year_production, VIN_number, mileage, date_receipt, 
                                                engine_volume, fuel_type, transmission_type, body_type 
                                          FROM auto_parts ap 
                                          join cars c on ap.idcar=c.id 
                                          WHERE ap.idorder = ?";
                            $partsStmt = mysqli_prepare($db, $partsSql);
                            mysqli_stmt_bind_param($partsStmt, 'i', $orderId);
                            mysqli_stmt_execute($partsStmt);
                            $partsResult = mysqli_stmt_get_result($partsStmt);

                            $parts = [];
                            while ($part = mysqli_fetch_assoc($partsResult)) {
                                $parts[] = $part; // Сохраняем запчасти в массив
                            }

                            mysqli_stmt_close($partsStmt);

                            // Отображаем запчасти с использованием метода renderTable
                            echo $autoPartsManager->renderTable($parts, $customerId);
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?> 
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Radiator</p>
    </footer>
</body>
</html>