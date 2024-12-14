<?php

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
                    <span class="item-count">2 товара</span>
                    <span class="arrow">&#9660;</span>
                </div>

                <div class="cart-content" id="cartContent">
                    <div class="cart-items">
                        <?php
                        $cartItems = [
                            [
                                'name' => 'Масло для массажа тела',
                                'volume' => '400мл, VOS',
                                'quantity' => 1,
                                'price' => '15,31',
                                'image' => 'path_to_image_1.jpg'
                            ],
                            [
                                'name' => 'Арахисовая паста 1кг',
                                'volume' => 'DuoDrops',
                                'quantity' => 1,
                                'price' => '16,35',
                                'image' => 'path_to_image_2.jpg'
                            ]
                        ];

                        foreach ($cartItems as $item) {
                            echo "
                            <div class='cart-item'>
                                <img src='{$item['image']}' alt='{$item['name']}' class='item-image'>
                                <div class='item-details'>
                                    <h2 class='item-title'>{$item['name']}</h2>
                                    <p class='item-volume'>{$item['volume']}</p>
                                    <div class='item-quantity'>
                                        <span>— {$item['quantity']} —</span>
                                    </div>
                                </div>
                                <div class='item-price'>
                                    <span>{$item['price']} ₽</span>
                                </div>
                            </div>
                            ";
                        }
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
        
        <div >
            <aside class="order-summary">
                <h2>Информация о заказе</h2>
                <div class="summary-item">
                    <span>Товары, 2 шт.</span>
                    <span>41,07 ₽</span>
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
        const cartSummary = document.getElementById('cartSummary');
        const cartContent = document.getElementById('cartContent');
        const cartContainer = document.getElementById('cartContainer');
        const deliverySelect = document.getElementById('deliverySelect');
        const addressInput = document.getElementById('addressInput');
        const deliveryContainer = document.querySelector('.delivery-container');

        cartSummary.addEventListener('click', () => {
            cartContent.classList.toggle('active');

            if (cartContent.classList.contains('active')) {
                const contentHeight = cartContent.scrollHeight; // Получаем высоту содержимого
                cartContainer.style.height = `${120 + contentHeight}px`; // Увеличиваем высоту контейнера
            } else {
                cartContainer.style.height = '120px'; // Сбрасываем высоту
            }
        });


        deliverySelect.addEventListener('change', () => {
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
    </script>
</body>
</html>