<?php include('server.php'); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="header">
        <h2>Регистрация</h2>
    </div>

    <form method="post" action="register.php"  class="login_register-form"> <!-- Теперь класс login_register-form -->
        <?php include('errors.php'); ?>
        <div class="input-group">
            <label>Логин</label>
            <input type="text" name="login" required>
        </div>

        <div class="input-group">
            <label>Пароль</label>
            <input type="password" name="password_1" required>
        </div>

        <div class="input-group">
            <label>Введите пароль ещё раз</label>
            <input type="password" name="password_2" required>
        </div>

        <div class="input-group">
            <button type="submit" class="btn" name="reg_user">Зарегистрироваться</button>
        </div>

        <p>
            Уже зарегистрированы? <a href="login.php">Войти</a>
        </p>   
    </form>
</body>
</html>