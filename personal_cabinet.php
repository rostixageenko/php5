<?php
include('table_func.php'); // Подключаем файл с функциями и классами
include_once('server.php'); // Подключение к базе данных

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Получение данных пользователя
$login = $_SESSION['login'];
$query = "SELECT * FROM staff WHERE login='$login'";
$results = mysqli_query($db, $query);
$row = mysqli_fetch_assoc($results);
$id_user = $row['id'];

$customerTable = new TableFunction($db, 'staff');
$customer = $customerTable->fetch(["id = '$id_user'"]);

// Проверка, существует ли пользователь
if (empty($customer)) {
    die("Пользователь не найден.");
}
$customer = $customer[0]; // Извлекаем данные пользователя

$message = ""; // Сообщение об обновлении данных
$messageType = "success"; // По умолчанию тип сообщения

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
        // Подготовка и выполнение запроса обновления данных
        $stmt = $db->prepare("UPDATE staff SET first_name = ?, second_name = ?, email = ?, contact_phone = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $first_name, $second_name, $email, $phone, $id_user);

        try {
            if ($stmt->execute()) {
                $message = "Данные успешно обновлены!";
                $_SESSION['first_name'] = $first_name; // Обновление сессионных данных
                // После успешного обновления можно обновить данные из базы
                $customer = $customerTable->fetch(["id = '$id_user'"])[0];
                $login = $_SESSION['login'];
                $id_user = $_SESSION['user_id'];
                
                // Логируем действие
                $Actstr = "Пользователь $login типа '0' обновил данные о себе";
                $dbExecutor->insertAction($id_user, $Actstr);
            } else {
                $message = "Ошибка обновления данных.";
                $messageType = "error";
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Ошибка: " . $e->getMessage();
            $messageType = "error";
        }

        $stmt->close();
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
    <img src="image/logo5.png" alt="Логотип" class="logo"> 
    <p>
        <a href="admin_interface_main.php" class="button">Назад</a>    
        <a href="index.php?logout='1'" class="button">Выйти</a>
    </p>
</header>

<main>
    <div class="container">
        <h2>Личный кабинет</h2>

        <!-- Всплывающее сообщение -->
        <div id="popup-message" class="<?php echo htmlspecialchars($messageType); ?>" style="<?php echo !empty($message) ? 'display:block;' : 'display:none;'; ?>">
            <?php if (!empty($message)) echo htmlspecialchars($message); ?>
        </div>

        <form method="POST" action="">
            <div class="input-group">
                <label for="first_name">Имя:</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
            </div>
            <div class="input-group">
                <label for="second_name">Фамилия:</label>
                <input type="text" name="second_name" value="<?php echo htmlspecialchars($customer['second_name']); ?>" required>
            </div>
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
            </div>
            <div class="input-group">
                <label for="contact_phone">Телефон:</label>
                <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($customer['contact_phone']); ?>" required>
            </div>
            <button type="submit" class="btn">Сохранить изменения</button>
        </form>
    </div>
</main>

</body>
</html>