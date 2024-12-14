<?php
// Подключаем файл с функцией
include 'user_interface_main.php';

// Предполагаем, что функция renderTable принимает массив товаров в корзине
$cartItems = []; // Здесь должен быть массив с товарами в корзине

// Если у вас уже есть логика для получения содержимого корзины, используйте её
// $cartItems = getCartItems(); // пример функции получения товаров из корзины

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
    <link rel="stylesheet" href="styles.css"> <!-- Подключите ваши стили -->
</head>
<body>
    <header>
        <a href="user_interface_main.php">
            <img src="image/logo5.png" alt="Логотип" class="logo">
        </a>
        <nav>
            <a href="cart.php" class="custom_button_second">
                <img src="image/cart_white.png" alt="Корзина" class="nav-icon">
            </a>
            <a href="personal_cabinet.php" class="custom_button_second">
                <img src="image/cabinet_white.png" alt="Личный кабинет" class="nav-icon">
            </a>    
            <a href="index.php?logout='1'" class="custom_button_second">Выйти</a>
        </nav>
    </header>

    <main>
        <h1>КОРЗИНА</h1>
        <?php
        // Проверка, есть ли товары в корзине
        if (empty($cartItems)) {
            echo "<p>Корзина пуста.</p>";
        } else {
            // Выводим таблицу с товарами в корзине
            renderTable($cartItems);
        }
        ?>
    </main>

    <footer>
        <p>&copy; 2024 Ваш магазин</p>
    </footer>
</body>
</html>