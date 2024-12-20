<?php 
session_start(); 

if (!isset($_SESSION['login'])) {
    $_SESSION['msg'] = "Вы вошли впервые";
    header('location: login.php');
}
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['login']);
    header("location: login.php");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Home</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .success {
            color: white; /* Белый цвет текста */
            background: #4dbc4e; /* Непрозрачный зеленый фон */
            border: 1px solid #3c763d;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 8px; /* Скругленные края */
        }
        .error {
            color: white; /* Белый цвет текста */
            background: #a94442; /* Непрозрачный красный фон */
            border: 1px solid #3c763d;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 8px; /* Скругленные края */
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Начальная страница</h2>
    </div>

    <div class="login_register-form">
        <!-- уведомление -->
        <?php if (isset($_SESSION['success'])) : ?>
            <div class="success">
                <h3>
                    <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                    ?>
                </h3>
            </div>
        <?php endif ?>

        <!-- информация о вошедшем в систему пользователе -->
        <?php if (isset($_SESSION['login'])) : ?>
            <p>Добро пожаловать, <strong><?php echo $_SESSION['login']?></strong></p>

            <?php if ($_SESSION['type_role'] == 1) : ?>
                <p>Вы вошли как <strong>Администратор</strong></p>
            <?php elseif ($_SESSION['type_role'] == 2) : ?>
                <p>Вы вошли как <strong>Сотрудник</strong></p>
            <?php endif; ?>

            <div class="button-container">
                <p><a href="index.php?logout='1'" style="color: red;">Выйти</a></p>

                <?php if ($_SESSION['type_role'] == 0) : ?>
                    <p><a href="user_interface_main.php" class="btn">Перейти к выбору автозапчастей</a></p>
                <?php elseif ($_SESSION['type_role'] == 1) : ?>
                    <p><a href="admin_interface_main.php" class="btn">Перейти в админ панель</a></p>
                <?php elseif ($_SESSION['type_role'] == 2) : ?>
                    <p><a href="employee_interface_main.php" class="btn">Перейти в интерфейс сотрудника</a></p>
                <?php endif; ?>
        <?php endif ?>
    </div>   
</body>
</html>