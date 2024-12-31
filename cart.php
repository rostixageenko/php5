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
}
else {
    // Если пользователь не авторизован, можно перенаправить на страницу входа
    header('Location: login.php');
    exit();
}


// Оформление заказа
// Оформление заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $totalPrice = array_sum(array_column($parts, 'purchase_price'));
    
    if ($totalPrice !== 0) {
        $selectedDeliveryMethod = $_POST['deliveryMethod'];
        $customerId = $_SESSION['customerId'];
        $status = 'Ожидается подтверждение';

        // Получаем адрес, если выбран способ доставки "Доставка"
        if ($selectedDeliveryMethod === "Доставка") {
            $sql = "SELECT address FROM customers WHERE id = ?";
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $customerId);
            mysqli_stmt_execute($stmt);
            $partsResult = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($partsResult);

            if (!isset($row['address'])) {
                $message = "Добавьте адрес в личном кабинете, чтобы оформить доставку";
                $messageType = "error";
            } else {
                // Добавляем заказ в таблицу orders
                $orderData = [
                    'type_order' => $selectedDeliveryMethod,
                    'status' => $status,
                    'purchase_price' => $totalPrice,
                    'idcustomer' => $customerId,
                    'address' => $row['address']
                ];
                $orderTab = new TableFunction($db, 'orders');
                $orderTab->addRecord($orderData); // Добавляем заказ
                $orderID = $orderTab->getLastInsertedId(); // Получаем ID последнего заказа

                // Обновляем запчасти в таблице auto_parts
                $sql = "SELECT DISTINCT idautoparts FROM cart c 
                        JOIN cart_auto_parts cap ON c.id = cap.idcart
                        WHERE c.idcustomer = ?";
                $stmt = mysqli_prepare($db, $sql);
                mysqli_stmt_bind_param($stmt, 'i', $customerId);
                mysqli_stmt_execute($stmt);
                $partsResult = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($partsResult)) {
                    $data = [
                        'idorder' => $orderID,
                        'status' => 'Заказан' // Обновляем статус на "Заказан"
                    ];
                    $partsautoTable = new TableFunction($db, 'auto_parts');
                    $partsautoTable->updateRecord('auto_parts', 'id', $row['idautoparts'], $data);
                }

                // Удаляем запчасти из корзины
                $sql = 'DELETE FROM cart_auto_parts WHERE idcart = ?';
                $stmt = mysqli_prepare($db, $sql);
                mysqli_stmt_bind_param($stmt, 'i', $cartId);
                mysqli_stmt_execute($stmt);

                $message = "Заказ оформлен успешно, ожидайте";
                $messageType = "success";
                header('Location: cart.php');
                exit();
            }
        } else { // Способ доставки "Самовывоз"
            $orderData = [
                'type_order' => $selectedDeliveryMethod,
                'status' => $status,
                'purchase_price' => $totalPrice,
                'idcustomer' => $customerId
            ];
            $orderTab = new TableFunction($db, 'orders');
            $orderTab->addRecord($orderData); // Добавляем заказ
            $orderID = $orderTab->getLastInsertedId(); // Получаем ID последнего заказа

            // Получаем ID запчастей из корзины
            $sql = "SELECT DISTINCT idautoparts FROM cart c 
                    JOIN cart_auto_parts cap ON c.id = cap.idcart
                    WHERE c.idcustomer = ?";
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $customerId);
            mysqli_stmt_execute($stmt);
            $partsResult = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($partsResult)) {
                $data = [
                    'idorder' => $orderID,
                    'status' => 'Заказан' // Обновляем статус на "Заказан"
                ];
                $partsautoTable = new TableFunction($db, 'auto_parts');
                $partsautoTable->updateRecord('auto_parts', 'id', $row['idautoparts'], $data);
            }

            // Удаляем запчасти из корзины
            $sql = 'DELETE FROM cart_auto_parts WHERE idcart = ?';
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $cartId);
            mysqli_stmt_execute($stmt);

            $message = "Заказ оформлен успешно, ожидайте";
            $messageType = "success";
            header('Location: cart.php');
            exit();
        }
    } else {
        $message = "Ошибка: добавьте товар в заказ";
        $messageType = "error";
    }
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
    <script>
        function updateOrderSummary() {
            const cartItems = document.querySelectorAll('.part-card');
            const itemCount = cartItems.length;
            const totalPriceElement = document.getElementById('totalPrice');
            
            let totalPrice = 0;
            cartItems.forEach(item => {
                const price = parseFloat(item.querySelector('.part-price').innerText) || 0;
                totalPrice += price;
            });

            document.getElementById('itemCount').innerText = `${itemCount} товара(ов)`;
            totalPriceElement.innerText = totalPrice + ' б.р';
        }

        function removeFromCart(partId, customerId, button) {
            const formData = new FormData();
            formData.append('part_id', partId);
            formData.append('customer_id', customerId);
            formData.append('delete_from_cart', true);

            fetch('server.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Удаляем элемент из DOM
                    button.closest('.part-card').remove();
                    // Обновляем количество товаров и итоговую стоимость
                    updateOrderSummary();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Ошибка:', error));
        }
    </script>
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
                    <span class="item-count" id="itemCount"><?php echo count($parts); ?> товара(ов)</span>
                    <span class="arrow">&#9660;</span>
                </div>

                <div class="cart-content" id="cartContent">
                    <div class="cart-items">
                        <?php echo $part_cart; ?>
                    </div>
                </div>
            </div>
           
        </div>
        
        <div>
            <aside class="order-summary">
                <h2>Информация о заказе</h2> 
                <form method="POST" action="cart.php">
                <span>Способ доставки</span>
                <select class="deliverySelect" id="deliverySelect" name="deliveryMethod">
                    <option value="Самовывоз">Самовывоз</option>
                    <option value="Доставка">Доставка</option>
                </select>
                <div class="summary-item total">
                    <span>Итого</span>
                    <span class="total-price" id="totalPrice"><?php 
                        $prices = array_column($parts, 'purchase_price');
                        $totalPrice = array_sum($prices);
                        echo $totalPrice; ?> б.р</span>
                </div>
                <button type="submit" class="custom-button-users" id="checkout" name="checkout">Заказать</button>
            </form>
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
        const deliveryAddress = document.getElementById('deliveryAddress');
        const deliveryContainer = document.querySelector('.delivery-container');
        const deliveryAddressDisplay = document.getElementById('deliveryAddressDisplay');
        const deliverySelect = document.getElementById('deliverySelect');
        const addressInput = document.getElementById('addressInput');

        cartSummary.addEventListener('click', () => {
            cartContent.classList.toggle('active');
            if (cartContent.classList.contains('active')) {
                const contentHeight = cartContent.scrollHeight; 
                cartContainer.style.height = `${120 + contentHeight}px`; 
            } else {
                cartContainer.style.height = '120px'; 
            }
        });

    </script>
    <script src="frontjs.js"></script>
</body>
</html>