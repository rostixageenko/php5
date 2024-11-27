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
    <style>
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none; /* Скрыть по умолчанию */
            position: absolute; /* Позиционирование */
            background-color: white; /* Белый фон */
            min-width: 160px; /* Минимальная ширина */
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2); /* Тень */
            z-index: 1; /* На переднем плане */
        }

        .dropdown:hover .dropdown-content {
            display: block; /* Показать при наведении */
        }

        .dropdown-content a {
            color: black; /* Цвет текста */
            padding: 12px 16px; /* Внутренние отступы */
            text-decoration: none; /* Убрать подчеркивание */
            display: block; /* Блочный элемент */
        }

        .dropdown-content a:hover {
            background-color: #ddd; /* Фон при наведении */
        }

        .account-button {
            background-color: #3498db; /* Цвет кнопки */
            color: white; /* Цвет текста */
            padding: 10px 15px; /* Отступы */
            border: none; /* Убрать рамку */
            border-radius: 5px; /* Закругленные углы */
            cursor: pointer; /* Указатель при наведении */
        }

        .account-button:hover {
            background-color: #2980b9; /* Цвет кнопки при наведении */
        }
    </style>
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