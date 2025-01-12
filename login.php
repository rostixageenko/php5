<?php include('server.php'); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .error {
            color: white; /* Белый цвет текста */
            background: #fc3030; /* Непрозрачный красный фон */
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 8px; /* Скругленные края */
        }

        .input-group {
            margin-bottom: 15px; /* Отступы между полями ввода */
        }

        .input-group input {
            height: 30px;
            width: 100%; /* Задаем 100% ширины контейнера */
            max-width: 430px;/* Ограничение максимальной ширины */
            padding: 5px 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid gray;
        }

        .btn {
            background-color: #4CAF50; /* Зеленый фон кнопки */
            color: white; /* Белый текст */
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background-color: #45a049; /* Темнее при наведении */
        }

        .header {
            text-align: center; /* Центрирование заголовка */
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Вход</h2>
    </div>

    <form method="post" action="login.php" class="login_register-form">
        <?php include('errors.php'); ?>
        <div class="input-group">
            <label for="login">Логин</label>
            <input type="text" name="login" id="login" required>
        </div>
        <div class="input-group">
            <label for="password">Пароль</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="input-group">
            <button type="submit" class="btn" name="login_user">Войти</button>
        </div>
        <p>
            Ещё не зарегистрированы? <a href="register.php">Зарегистрироваться</a>
        </p>
    </form>
</body>
</html>