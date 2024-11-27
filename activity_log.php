<?php
// Database configuration
include 'sessionConf.php';
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
        /* Ваши стили */
    </style>
</head>
<body>

<header>
    <img src="image/logo5.png" alt="Логотип" class="logo"> 
    <div class="menu">
        <a href="activity_log.php" class="button">История операций</a>
    </div>
    <p><a href="index.php?logout='1'" class="button">Выйти</a></p>
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
            if (isset($_SESSION['search_results'])) {
                $result = $_SESSION['search_results'];
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['id']) . "</td>
                        <td>" . htmlspecialchars($row['login']) . "</td>
                        <td>" . htmlspecialchars($row['action_datetime']) . "</td>
                        <td>" . htmlspecialchars($row['action']) . "</td>
                    </tr>";
                }
                // Очистка результатов после вывода
                unset($_SESSION['search_results']);
            } else {
                echo "<tr><td colspan='4'>Нет данных для отображения.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</main>

</body>
</html>