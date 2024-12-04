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
        .success, .error {
            color: white; /* Белый цвет текста */
            padding: 10px;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            display: none; /* Скрыто по умолчанию */
            border-radius: 8px; /* Скругленные края */
        }
        .success {
            background: rgba(76, 175, 80, 0.8); /* Прозрачный фон */
            border: 1px solid #3c763d;
        }
        .error {
            background: rgba(192, 57, 43, 0.8); /* Прозрачный фон */
            border: 1px solid #a94442;
        }
        .input-group {
            margin-bottom: 15px; /* Отступ между полями */
        }
        /* Стили для выпадающего списка */
        select {
            height: 43px; /* Высота выпадающего списка */
            width: 100%; /* Ширина 95% */
            padding: 5px 10px; /* Внутренние отступы */
            font-size: 16px; /* Размер шрифта */
            border-radius: 5px; /* Скругленные углы */
            border: 1px solid gray; /* Серый цвет рамки */
            background-color: white; /* Белый фон */
            color: gray; /* Цвет текста по умолчанию */
            cursor: pointer; /* Указатель при наведении */
            appearance: none; /* Убираем стандартный стиль браузера */
        }
        /* Серый цвет для первого элемента */
        select option[value=""] {
            color: gray; /* Серый цвет текста для первого элемента */
        }
        /* Стили для опций внутри выпадающего списка */
        select option {
            padding: 10px; /* Внутренние отступы для опций */
            background-color: white; /* Фон опций */
            color: black; /* Цвет текста */
        }
        /* Эффект при наведении на опции (для некоторых браузеров) */
        select:hover {
            border-color: #888; /* Цвет рамки при наведении */
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
        <a href="activity_log.php" class="button">История операций</a>
        <a href="personal_cabinet.php" class="button">Личный кабинет</a> <!-- Кнопка личного кабинета -->
        <a href="sql_queries.php" class="button">SQL Запросы</a> <!-- Новая кнопка -->
    </div>
    <p><a href="index.php?logout='1'" class="button">Выйти</a></p>
</header>
<main>
    <div class="container">
        <div class="form-container">
            <h2>Добавить пользователя</h2>
            <form method="POST" action="?table=users">
                <div class="input-group">
                    <input type="text" name="login" placeholder="Логин" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Пароль" required>
                </div>
                <div class="input-group">
                    <select name="type_role" required>
                        <option value="" disabled selected style="color: gray;">Выберите тип роли</option>
                        <option value="0">Покупатель</option>
                        <option value="1">Администратор</option>
                        <option value="2">Сотрудник</option>
                    </select>
                </div>
                <button type="submit" class="btn">Добавить</button>
            </form>

            <h2>Вывод данных</h2>
            <form method="POST" action="?table=<?php echo $selectedTable; ?>">
                <div class="input-group">
                    <input type="number" name="row_count" placeholder="Количество строк" required min="1">
                </div>
                <button type="submit" class="btn">Вывести</button>
            </form>

            <h2>Изменить пароль пользователя</h2>
            <form method="POST" action="?table=users&action=change_password">
                <div class="input-group">
                    <input type="text" name="change_login" placeholder="Логин пользователя" required>
                </div>
                <div class="input-group">
                    <input type="password" name="new_password" placeholder="Новый пароль" required>
                </div>
                <button type="submit" class="btn">Изменить</button>
            </form>

            <h2>Поиск пользователей</h2>
            <form method="POST" action="?table=users">
                <div class="input-group">
                    <input type="text" name="id" placeholder="ID пользователя">
                </div>
                <div class="input-group">
                    <input type="text" name="login" placeholder="Логин">
                </div>
                <div class="input-group">
                    <select name="type_role">
                        <option value="" disabled selected style="color: gray;">Выберите тип роли (необязательно)</option>
                        <option value="0">Покупатель</option>
                        <option value="1">Администратор</option>
                        <option value="2">Сотрудник</option>
                    </select>
                </div>
                <button type="submit" name="search_users" class="btn">Поиск</button>
            </form>
        </div>

        <div class="tables-container">
            <div class="table-scroll">
                <?php
                // Вывод данных с учетом ограничения
                $rowCount = isset($_POST['row_count']) ? intval($_POST['row_count']) : 25; // По умолчанию 25 строк

                switch ($selectedTable) {
                    case 'users':
                        if (!isset($_POST['search_users'])) {
                            $users = $usersTable->fetchLimited($rowCount);
                        }
                        $usersTable->renderTable($users, 'Пользователи');
                        break;
                    case 'auto_parts':
                        $parts = $partsTable->fetchLimited($rowCount);
                        $partsTable->renderTable($parts, 'Запчасти');
                        break;
                    case 'orders':
                        $orders = $ordersTable->fetchLimited($rowCount);
                        $ordersTable->renderTable($orders, 'Заказы');
                        break;
                    case 'customers':
                        $customers = $customersTable->fetchLimited($rowCount);
                        $customersTable->renderTable($customers, 'Покупатели');
                        break;
                    case 'staff':
                        $staffs = $staffsTable->fetchLimited($rowCount);
                        $staffsTable->renderTable($staffs, 'Сотрудники');
                        break;
                    case 'suppliers':
                        $suppliers = $suppliersTable->fetchLimited($rowCount);
                        $suppliersTable->renderTable($suppliers, 'Поставщики');
                        break;
                    case 'inventory':
                        $inventory = $inventoryTable->fetchLimited($rowCount);
                        $inventoryTable->renderTable($inventory, 'Инвентарь');
                        break;
                    case 'cars':
                        $cars = $carsTable->fetchLimited($rowCount);
                        $carsTable->renderTable($cars, 'Автомобили');
                        break;
                    default:
                        echo "<p>Выберите таблицу из базы данных.</p>";
                }
                ?>
            </div>
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