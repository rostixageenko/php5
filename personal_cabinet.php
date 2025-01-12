<?php

include_once('server.php'); // Подключение к базе данных

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$login = $_SESSION['login'];
$type_role = $_SESSION['type_role'];
$message = ""; 
$messageType = "success";



$customerTable = new TableFunction($db, 'staff');
$customer = $customerTable->fetch(["login = '$login'"]);


// Проверка, существует ли пользователь
if (empty($customer)) {
    die("Пользователь не найден.");
}
$customer = $customer[0]; // Извлекаем данные пользователя

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка формы изменения данных
    $first_name = trim($_POST['first_name']);
    $second_name = trim($_POST['second_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['contact_phone']);

    // Валидация данных
    if (empty($first_name) || empty($second_name) || empty($email) || empty($phone)) {
        $message = "Пожалуйста, заполните все поля.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Некорректный формат email.";
        $messageType = "error";
    } else {
        // Проверка существования email
        $emailCheckStmt = $db->prepare("SELECT COUNT(*) FROM staff WHERE email = ? AND login != ?");
        $emailCheckStmt->bind_param("ss", $email, $login);
        $emailCheckStmt->execute();
        $emailCheckStmt->bind_result($emailCount);
        $emailCheckStmt->fetch();
        $emailCheckStmt->close();

        if ($emailCount > 0) {
            $message = "Этот email уже используется другим пользователем.";
            $messageType = "error";
        } else {
            // Подготовка данных для обновления
            $data = [
                'first_name' => $first_name,
                'second_name' => $second_name,
                'email' => $email,
                'contact_phone' => $phone,
            ];

            // Используем функцию updateRecord для обновления данных
            $result = $customerTable->updateRecord('staff', 'login', $login, $data);

            // Обработка результата обновления
            $message = $result['message'];
            $messageType = $result['type'];

            // Логируем действие, если обновление прошло успешно
            if ($messageType === 'success') {
                $_SESSION['first_name'] = $first_name;
                $customer = $customerTable->fetch(["login = '$login'"])[0];
                $id_user = $_SESSION['user_id'];

                $Actstr = "Пользователь $login типа '$type_role' обновил данные о себе";
                $dbExecutor->insertAction($id_user, $Actstr);
            }
        }
    }
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
        /* Стили для сообщений и формы */
        .success, .error {
            color: white;
            padding: 10px;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            display: none;
            border-radius: 8px;
        }
        .success {
            background: rgba(76, 175, 80, 0.8);
            border: 1px solid #3c763d;
        }
        .error {
            background: rgba(192, 57, 43, 0.8);
            border: 1px solid #a94442;
        }
        .input-group input {
            height: 30px;
            width: 1000px;
            padding: 5px 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid gray;
        }
    </style>
    <script>
        function hidePopup() {
            var popup = document.getElementById('popup-message');
            if (popup) {
                popup.style.display = 'none';
            }
        }

        // Если есть сообщение, скрываем его через 5 секунд
        window.onload = function() {
            setTimeout(hidePopup, 5000);
        };
    </script>
</head>
<body>
<header>
<a href="<?php 
        echo 'admin_interface_main.php'; 
?>">
    <img src="image/logo_new.png" alt="Логотип" class="logo">
</a>
    <p>
        <a class="button" href="admin_interface_main.php"> Назад</a>  
          
        <a href="index.php?logout='1'" class="button">Выйти</a>
    </p>
</header>

<main class="custom-main">
    <div class="container-cabinet">
        <h2>Личный кабинет</h2>

        <!-- Всплывающее сообщение -->
        <div id="popup-message" class="<?php echo htmlspecialchars($messageType); ?>" style="<?php echo !empty($message) ? 'display:block;' : 'display:none;'; ?>">
            <?php if (!empty($message)) echo htmlspecialchars($message); ?>
        </div>

        <form method="POST" action="">
            <div class="input-group">
                <label for="first_name">Имя:</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" >
            </div>
            <div class="input-group">
                <label for="second_name">Фамилия:</label>
                <input type="text" name="second_name" value="<?php echo htmlspecialchars($customer['second_name']); ?>" >
            </div>
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" >
            </div>
            <div class="input-group">
                <label for="contact_phone">Телефон:</label>
                <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($customer['contact_phone']); ?>" >
            </div>
            <button type="submit" class="btn">Сохранить изменения</button>
        </form>
    </di>
</main>

</body>
</html>