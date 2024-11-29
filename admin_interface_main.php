<?php  
include('server.php');
include('table_func.php');

// Создаем экземпляры SelectTable для различных таблиц
$usersTable = new TableFunction($db, 'users');
$partsTable = new TableFunction($db, 'auto_parts');
$ordersTable = new TableFunction($db, 'orders');
$customersTable = new TableFunction($db, 'Customers');
$staffsTable = new TableFunction($db, 'Staff');
$suppliersTable = new TableFunction($db, 'suppliers');
$inventoryTable = new TableFunction($db, 'Inventory');
$carsTable = new TableFunction($db, 'Cars');

// Получение данных из таблиц
$users = $usersTable->fetch();
$parts = $partsTable->fetch();
$orders = $ordersTable->fetch();
$customers = $customersTable->fetch();
$staffs = $staffsTable->fetch();
$suppliers = $suppliersTable->fetch();
$inventory = $inventoryTable->fetch();
$cars = $carsTable->fetch();

// Определение текущей таблицы
$selectedTable = isset($_GET['table']) ? $_GET['table'] : 'users'; // По умолчанию - пользователи
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Interface</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <img src="image/logo5.png" alt="Логотип" class="logo"> 
    <div class="menu">
        <div class="dropdown">
            <button class="button">База данных</button>
            <div class="dropdown-content">
                <a href="?table=users">Пользователи</a>
                <a href="?table=auto_parts">Запчасти</a>
                <a href="?table=orders">Заказы</a>
                <a href="?table=customers">Покупатели</a>
                <a href="?table=staff">Сотрудники</a>
                <a href="?table=suppliers">Поставщики</a>
                <a href="?table=inventory">Инвентарь</a>
                <a href="?table=cars">Автомобили</a>
            </div>
        </div>
        <button class="button">Аналитика</button>
        <a href="activity_log.php" class="button">История операций</a>
    </div>
    <p><a href="index.php?logout='1'" class="button">Выйти</a></p>
</header>

<main>
    <div class="tables-container">
        <?php
            // В зависимости от выбранной таблицы, отображаем ее
            switch ($selectedTable) {
                case 'users':
                    $usersTable->renderTable($users, 'Пользователи');
                    break;
                case 'auto_parts':
                    $partsTable->renderTable($parts, 'Запчасти');
                    break;
                case 'orders':
                    $ordersTable->renderTable($orders, 'Заказы');
                    break;
                case 'customers':
                    $customersTable->renderTable($customers, 'Покупатели');
                    break;
                case 'staff':
                    $staffsTable->renderTable($staffs, 'Сотрудники');
                    break;
                case 'suppliers':
                    $suppliersTable->renderTable($suppliers, 'Поставщики');
                    break;
                case 'inventory':
                    $inventoryTable->renderTable($inventory, 'Инвентарь');
                    break;
                case 'cars':
                    $carsTable->renderTable($cars, 'Автомобили');
                    break;
                default:
                    echo "<p>Выберите таблицу из базы данных.</p>";
            }
        ?>
    </div>
</main>

</body>
</html>