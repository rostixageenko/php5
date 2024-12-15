<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once('server.php'); // подключаем файл с настройками БД и функционалом
include_once('parts.php'); // подключаем файл с классом для работы с запчастями

// Проверка, есть ли пользователь в сессии
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['customerId'];

    // Шаг 1: Получаем ID корзины по customerId
    $sql = 'SELECT id FROM cart WHERE idcustomer = ?';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $cartId = $row['id'];

        // Шаг 2: Получаем ID запчастей из таблицы cart_auto_parts
        $sql = 'SELECT idautoparts FROM cart_auto_parts WHERE idcart = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $cartId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $partIds = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $partIds[] = $row['idautoparts'];
        }

        // Шаг 3: Получаем полную информацию о запчастях
        if (!empty($partIds)) {
            $partIdsString = implode(',', $partIds); // Преобразуем массив в строку для SQL-запроса
            $sql = "SELECT ap.id, name_parts, article, ap.`condition`, ap.purchase_price, description, idcar, ap.idgarage, photo, idorder, status, brand, model, year_production, VIN_number, mileage, date_receipt, engine_volume, fuel_type, transmission_type, body_type FROM auto_parts ap JOIN cars c ON ap.idcar=c.id WHERE ap.id IN ($partIdsString)";
            $partsResult = mysqli_query($db, $sql);

            $parts = [];
            while ($row = mysqli_fetch_assoc($partsResult)) {
                $parts[] = $row; // Сохраняем запчасти в массив
            }
        } else {
            $parts = []; // Если нет запчастей, создаем пустой массив
        }

        // Шаг 4: Отображаем запчасти с помощью renderTable
        $autoPartsManager = new AutoPartsManager();
        $_SESSION['cart'] = 1;
        $part_cart = $autoPartsManager->renderTable($parts, $customerId);
    } else {
        $part_cart = []; // Если корзина не найдена
    }

    mysqli_stmt_close($stmt);
} else {
    // Если пользователь не авторизован, можно перенаправить на страницу входа
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
    <link rel="stylesheet" href="style.css">
    <style>
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
    </style>
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
                        <?php echo $part_cart; ?>
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
                <div class="address-input" id="addressInput" style="display: none;">
                    <input type="text" id="deliveryAddress" placeholder="Введите адрес доставки" required />
                </div>
            </div>
        </div>
        
        <div>
            <aside class="order-summary">
                <h2>Информация о заказе</h2>
                <div class="summary-item">
                    <span>Товары:</span>
                    <span><?php echo count($parts); ?> шт.</span>
                </div>
                <div class="summary-item">
                    <span>Способ доставки:</span>
                    <span id="deliveryMethodDisplay">Самовывоз</span>
                </div>
                <div class="summary-item total">
                    <span>Итого</span>
                    <span class="total-price"><?php 
                        $prices = array_column($parts, 'purchase_price');
                        $totalPrice = array_sum($prices);
                        echo $totalPrice; ?> б.р</span>
                </div>
                <button type="submit" class="custom-button-users" id="checkout" name="checkout">Заказать</button>
            </aside>
        </div>
    </main>

    <div id="popup-message" class="<?php echo $messageType; ?>" style="<?php echo !empty($message) ? 'display:block;' : ''; ?>">
        <?php if (!empty($message)) echo $message; ?>
    </div>

    <footer>
        <p>&copy; 2024 Radiator</p>
    </footer>

    <script>
        const cartSummary = document.getElementById('cartSummary');
        const cartContent = document.getElementById('cartContent');
        const cartContainer = document.getElementById('cartContainer');
        const deliverySelect = document.getElementById('deliverySelect');
        const addressInput = document.getElementById('addressInput');
        const deliveryAddress = document.getElementById('deliveryAddress');
        const deliveryContainer = document.querySelector('.delivery-container');
        const deliveryAddressDisplay = document.getElementById('deliveryAddressDisplay');

        // Глобальные переменные для хранения способа доставки и адреса
        let selectedDeliveryMethod = 'pickup'; // Значение по умолчанию
        let deliveryAddressValue = ''; // Переменная для адреса доставки

        cartSummary.addEventListener('click', () => {
            cartContent.classList.toggle('active');

            if (cartContent.classList.contains('active')) {
                const contentHeight = cartContent.scrollHeight; 
                cartContainer.style.height = `${120 + contentHeight}px`; 
            } else {
                cartContainer.style.height = '120px'; 
            }
        });

        deliverySelect.addEventListener('change', () => {
            selectedDeliveryMethod = deliverySelect.value; // Обновляем глобальную переменную
            document.getElementById('deliveryMethodDisplay').textContent = selectedDeliveryMethod === 'delivery' ? 'Доставка' : 'Самовывоз';
            if (deliverySelect.value === 'delivery') {
                addressInput.style.display = 'block';
                adjustDeliveryContainerHeight(); 
            } else {
                addressInput.style.display = 'none';
                resetDeliveryContainerHeight();
            }
        });

        // Функция для увеличения высоты контейнера
        function adjustDeliveryContainerHeight() {
            const contentHeight = deliveryContainer.scrollHeight; 
            deliveryContainer.style.height = `${contentHeight}px`; 
        }

        // Функция для сброса высоты контейнера
        function resetDeliveryContainerHeight() {
            deliveryContainer.style.height = '140px'; 
        }
        deliverySelect.addEventListener('change', () => {
            if (deliverySelect.value === 'delivery') {
                addressInput.style.display = 'block';
            } else {
                addressInput.style.display = 'none';
            }
        });    

        deliveryAddress.addEventListener('input', () => {
            deliveryAddressValue = deliveryAddress.value; // Обновляем адрес доставки
            deliveryAddressDisplay.textContent = deliveryAddressValue; // Отображаем введённый адрес
        });

        document.getElementById('checkout').addEventListener('click', () => {
            if (selectedDeliveryMethod === 'delivery' && !deliveryAddressValue) {
                alert('Пожалуйста, введите адрес доставки.'); // Сообщение об ошибке
                return;
            }
            // Отправка формы или выполнение дальнейших действий
            alert('Заказ оформлен!'); // Пример уведомления
        });
    </script>
    <script src="frontjs.js"></script>
</body>
</html>