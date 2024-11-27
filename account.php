<?php
include('server.php'); // Подключаем файл для работы с базой данных

// Проверка, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправляем на страницу входа, если не авторизован
    exit();
}

// Получение данных пользователя из базы данных
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Если пользователь не найден, перенаправляем на страницу входа
if (!$user) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
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
        <button class="button">История операций</button>
    </div>
    <a href="account.php" class="button">Личный кабинет</a>
</header>

<main>
    <div class="container">
        <h1>Добро пожаловать, <?php echo htmlspecialchars($user['login']); ?>!</h1>
        <?php 
    $role = htmlspecialchars($user['type_role']); 
?>

<p><strong>Роль:</strong> 
<?php 
    if ($role === '1') {
        echo 'администратор';
    } elseif ($role === '2') {
        echo 'сотрудник';
    } elseif ($role === '0') {
        echo 'покупатель';
    } else {
        echo 'Неизвестная роль'; 
    }
?></p>

<?php if ($role !== '1'): ?>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
<?php endif; ?>
        
        <div class="button-container">
            <a href="edit_account.php" class="button">Редактировать профиль</a>
            <p><a href="index.php?logout='1'" class="button">Выйти</a></p>
            </form>
        </div>
    </div>
</main>

</body>
</html>