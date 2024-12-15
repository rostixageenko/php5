<?php

// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// include_once('server.php'); // подключаем файл с настройками БД и функционалом
// include_once('parts.php'); // подключаем файл с классом для работы с запчастями

// // Проверка, есть ли пользователь в сессии
// if (isset($_SESSION['user_id'])) {
//     $customerId = $_SESSION['customerId'];

//     // Шаг 1: Получаем ID корзины по customerId
//     $sql = 'SELECT id FROM cart WHERE idcustomer = ?';
//     $stmt = mysqli_prepare($db, $sql);
//     mysqli_stmt_bind_param($stmt, 'i', $customerId);
//     mysqli_stmt_execute($stmt);
//     $result = mysqli_stmt_get_result($stmt);
    
//     if ($row = mysqli_fetch_assoc($result)) {
//         $cartId = $row['id'];

//         // Шаг 2: Получаем ID запчастей из таблицы cart_auto_parts
//         $sql = 'SELECT idautoparts FROM cart_auto_parts WHERE idcart = ?';
//         $stmt = mysqli_prepare($db, $sql);
//         mysqli_stmt_bind_param($stmt, 'i', $cartId);
//         mysqli_stmt_execute($stmt);
//         $result = mysqli_stmt_get_result($stmt);

//         $partIds = [];
//         while ($row = mysqli_fetch_assoc($result)) {
//             $partIds[] = $row['idautoparts'];
//         }

//         // Шаг 3: Получаем полную информацию о запчастях
//         if (!empty($partIds)) {
//             $partIdsString = implode(',', $partIds); // Преобразуем массив в строку для SQL-запроса
//             $sql = "SELECT * FROM auto_parts ap join cars c on ap.idcar=c.id  WHERE ap.id IN ($partIdsString)";
//             $partsResult = mysqli_query($db, $sql);

//             $parts = [];
//             while ($row = mysqli_fetch_assoc($partsResult)) {
//                 $parts[] = $row; // Сохраняем запчасти в массив
//             }
//         } else {
//             $parts = []; // Если нет запчастей, создаем пустой массив
//         }

//         // Шаг 4: Отображаем запчасти с помощью renderTable
//         $autoPartsManager = new AutoPartsManager();
//         $part_cart = $autoPartsManager->renderTable($parts,$customerId);
//     } else {
//         $part_cart = []; // Если корзина не найдена
//     }

//     mysqli_stmt_close($stmt);
// } else {
//     // Если пользователь не авторизован, можно перенаправить на страницу входа
//     header('Location: login.php');
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header-user">
        <a href="user_interface_main.php">
            <img src="image/logo_new.png" alt="Логотип" class="logo">
        </a>
        <nav>
            <a href="user_interface_main.php" class="custom_button_second"> Назад</a>
            <a href="index.php?logout='1'" class="custom_button_second">Выйти</a>
        </nav>
    </header>

    <main class="custom-main-cart">
        <div class="cart-order-address">
            <div class="cart-container" id="cartContainer">
                <h3 class="cart-title">Корзина</h3>
                <div class="cart-summary" id="cartSummary">
                    <span class="item-count"><?php echo count($parts); ?> товара</span>
                    <span class="arrow">&#9660;</span>
                </div>

                <div class="cart-content" id="cartContent">
                    <div class="cart-items">
                        <?php
                        // Отображаем запчасти
                        echo $part_cart;
                        ?>
                    </div>
                </div>
            </div>
            <!-- Новое поле для выбора способа доставки -->
            <div class="delivery-container">
                <h3 class="cart-title">Способ доставки</h3>
                <select class="deliverySelect" id="deliverySelect">
                    <option value="pickup">Самовывоз</option>
                    <option value="delivery">Доставка</option>
                </select>
                <div class="address-input" id="addressInput">
                    <input type="text" placeholder="Введите адрес доставки" />
                </div>
            </div>
        </div>
        
        <div>
            <aside class="order-summary">
                <h2>Информация о заказе</h2>
                <div class="summary-item">
                    <span>Товары, <?php echo count($parts); ?> шт.</span>
                    <span><!-- Здесь можно посчитать общую сумму товаров --></span>
                </div>
                <div class="summary-item total">
                    <span>Итого</span>
                    <span class="total-price">31,66 ₽</span>
                </div>
                <button class="custom-button-users" id="checkout">Заказать</button>
            </aside>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Radiator</p>
    </footer>

    <script>
        // JavaScript код для функциональности корзины
    </script>
</body>
</html>