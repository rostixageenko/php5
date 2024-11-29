<?php
include('table_func.php'); // Подключаем файл с функциями и классами
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Interface</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            display: flex;
        }
        .form-container {
            margin-right: 20px;
            width: 300px; /* Ширина контейнера формы */
        }
        .tables-container {
            flex-grow: 1; /* Занимает оставшееся пространство */
        }
        .success {
            color: white; /* Белый цвет текста */
            background: rgba(76, 175, 80, 0.8); /* Прозрачный фон */
            border: 1px solid #3c763d;
            margin-bottom: 20px;
            padding: 10px;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            display: none; /* Скрыто по умолчанию */
            border-radius: 8px; /* Скругленные края */
        }
        .error {
            color: white; /* Белый цвет текста */
            background: rgba(192, 57, 43, 0.8); /* Прозрачный фон */
            border: 1px solid #a94442;
            margin-bottom: 20px;
            padding: 10px;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            display: none; /* Скрыто по умолчанию */
            border-radius: 8px; /* Скругленные края */
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
    <div class="container">
        <div class="form-container">
            <h2>Добавить пользователя</h2>
            <form method="POST" action="?table=users">
                <input type="text" name="login" placeholder="Логин" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <input type="text" name="type_role" placeholder="Тип роли" required>
                <button type="submit" class="btn">Добавить</button>
            </form>
        </div>
        
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
    </div>
</main>

<!-- Всплывающее сообщение -->
<div id="popup-message" class="<?php echo $messageType; ?>" style="<?php echo !empty($message) ? 'display:block;' : ''; ?>">
    <?php if (!empty($message)) echo $message; ?>
</div>

<!-- Подключаем JavaScript -->
<script src="frontjs.js"></script>

</body>
</html>