<?php

include_once('server.php'); // Подключаем файл с настройками базы данных

$message = ""; // Сообщение об ошибке или успехе

$results = []; // Массив для хранения результатов запроса

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql_query'])) {

$sql_query = trim($_POST['sql_query']);

// Проверка на пустой запрос

if (empty($sql_query)) {

$message = "Пожалуйста, введите SQL-запрос.";

$message_type = "error";

} else {

// Выполнение SQL-запроса

try {

if ($query_result = mysqli_query($db, $sql_query)) {

// Если запрос успешен, получаем результаты

if (mysqli_num_rows($query_result) > 0) {

while ($row = mysqli_fetch_assoc($query_result)) {

$results[] = $row;

}

$login = $_SESSION['login'];

$id_user = $_SESSION['user_id'];


// Логируем действие

$Actstr = "Пользователь $login типа '0' выполнил запрос $sql_query";

$dbExecutor->insertAction($id_user, $Actstr);

} else {

$message = "Запрос выполнен, но нет результатов.";

}

} else {

throw new Exception("Ошибка выполнения запроса: " . mysqli_error($db));

}

} catch (Exception $e) {

// Логируем ошибку в файл

error_log($e->getMessage(), 3, 'error_log.txt');

$message = "Произошла ошибка при выполнении запроса.";

}

}

}

?>

<!DOCTYPE html>

<html lang="ru">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SQL Запросы</title>

<link rel="stylesheet" href="style.css">

<style>

.container {

max-width: 600px;

margin: auto;

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

textarea {

width: 100%;

height: 100px;

margin-bottom: 10px;

padding: 5px 10px;

font-size: 16px;

border-radius: 5px;

border: 1px solid gray;

}

.btn {

padding: 10px;

font-size: 15px;

color: white;

background: #8e8e8e; /* Серый фон для кнопок */

border: none;

border-radius: 5px;

cursor: pointer; /* Указатель при наведении */

margin-top: 10px; /* Отступ сверху для кнопки */

width: 100%; /* Кнопка занимает всю ширину */

}


Copy
    tr:nth-child(even) {
        background-color: #f9f9f9; /* Чередующиеся цвета строк */
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

<div class="container">

<h2>Выполнение SQL Запросов</h2>

php-template

Copy
    <?php if (!empty($message)): ?>
        <div class="<?php echo strpos($message, 'Ошибка') !== false ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <textarea name="sql_query" placeholder="Введите ваш SQL-запрос здесь..."></textarea>
        <button type="submit" class="btn">Выполнить запрос</button> <!-- Кнопка под полем -->
    </form>
</div>
<?php if (!empty($results)): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <?php foreach ($results[0] as $key => $value): ?>
                            <th><?php echo htmlspecialchars($key); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

</body>

</html>