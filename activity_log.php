<?php
// Database configuration
include 'sessionConf.php';
include 'activity_log_manager.php';
$servername = "localhost";
$username = "root"; // Ваше имя пользователя MySQL
$password = ""; // Ваш пароль MySQL
$dbname = "auto_disassembly_station"; // Имя вашей базы данных

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
    <p>
    <a href="admin_interface_main.php" class="button">Назад</a>    
    <a href="index.php?logout='1'" class="button">Выйти</a>
</p>
</header>

<main>
    <h1 class="title">История операций</h1>
    <form method="POST" action="server.php">
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
</main>

</body>
</html>