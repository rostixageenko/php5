<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('table_func.php');

// Получение запчастей
$numResults = 0;
$parts = []; // Для хранения запчастей
$message = '';
$messageType = 'info'; // Тип сообщения, например 'success' или 'error'

// Логика для получения запчастей
$query = "
    SELECT ap.*, c.brand, c.model, c.year_production
    FROM auto_parts ap
    JOIN cars c ON ap.idcar = c.id
";
$result = $db->query($query);
if (!$result) {
    $message = "Ошибка запроса: " . $db->error;
    $messageType = 'error';
} else {
    if ($result->num_rows > 0) {
        while ($part = $result->fetch_assoc()) {
            $image = !empty($part['photo']) ? 'data:image/jpeg;base64,' . base64_encode($part['photo']) : 'default_image.jpg';
            $part['image'] = $image;
            $parts[] = $part;
        }
        $numResults = count($parts);
    } else {
        $message = "Запчасти не найдены.";
        $messageType = 'info';
    }
}

$db->close(); // Закрываем соединение с базой данных

// Функция для отображения запчастей
function renderTable($parts) {
    $output = '';
    foreach ($parts as $part) {
        $output .= '<div class="part-card">';
        $output .= '<div class="part-image-container">';
        $output .= '<img src="' . htmlspecialchars($part['image']) . '" alt="' . htmlspecialchars($part['name_parts']) . '" class="part-image">';
        $output .= '</div>'; // .part-image-container
        $output .= '<div class="part-details">';
        $output .= '<h3>' . htmlspecialchars($part['name_parts']) . '</h3>';
        $output .= '<p><strong>Марка:</strong> ' . htmlspecialchars($part['brand']) . '</p>';
        $output .= '<p><strong>Модель:</strong> ' . htmlspecialchars($part['model']) . '</p>';
        $output .= '<p class="part-price">' . htmlspecialchars($part['purchase_price']) . ' р.</p>';
        $output .= '<button class="add-to-cart-btn" onclick="addToCart(this, ' . $part['id'] . ')">Добавить в корзину</button>';
        $output .= '</div>'; // .part-details
        $output .= '</div>'; // .part-card
    }
    return $output;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Interface</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function addToCart(button, partId) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "add_to_cart.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                    button.textContent = "Перейти в корзину";
                    button.onclick = function() {
                        window.location.href = "cart.php"; // Замените на URL вашей корзины
                    };
                }
            };
            xhr.send("part_id=" + partId);
        }

        const carModels = {
            Toyota: ["Camry", "RAV4", "Highlander", "Corolla"],
            Honda: ["Accord", "CR-V", "Pilot", "Civic"],
            Ford: ["Focus", "Escape", "Explorer", "Mustang"],
            Chevrolet: ["Malibu", "Equinox", "Tahoe", "Camaro"],
            Nissan: ["Altima", "Rogue", "Murano", "370Z"],
            Volkswagen: ["Passat", "Tiguan", "Jetta", "Golf"],
            Hyundai: ["Sonata", "Tucson", "Elantra", "Santa Fe"],
            Kia: ["Optima", "Sportage", "Forte", "Seltos"],
            BMW: ["3 Series", "X5", "X3", "X1"],
            Audi: ["A4", "Q5", "A6", "Q3"],
            Subaru: ["Legacy", "Forester", "Outback"],
            Mazda: ["6", "CX-5", "CX-9"],
            Fiat: ["500", "Panda"],
            Volvo: ["S60", "XC60"]
        };

        document.getElementById('car_brand').addEventListener('change', function() {
            const brand = this.value;
            const modelSelect = document.getElementById('car_model');
            modelSelect.innerHTML = '<option value="">Модель</option>';
            if (brand) {
                carModels[brand].forEach(function(model) {
                    const option = document.createElement('option');
                    option.value = model;
                    option.textContent = model;
                    modelSelect.appendChild(option);
                });
            }
        });
    </script>
</head>
<body>
<header class="header-user">
    <a href="user_interface_main.php">
        <img src="image/logo5.png" alt="Логотип" class="logo">
    </a>
    <nav>
        <?php if (!isset($_SESSION['login'])): ?>
            <a href="login.php" class="custom_button_second">Войти</a>
        <?php else: ?>
            <a href="personal_cabinet.php" class="custom_button_second">
                <img src="image/cabinet_white.png" alt="Личный кабинет" class="nav-icon">
            </a>
            <a href="cart.php" class="custom_button_second">
                <img src="image/cart_white.png" alt="Корзина" class="nav-icon">
            </a> 
            <a href="orders.php" class="custom_button_second">Мои заказы</a>
            <a href="index.php?logout='1'" class="custom_button_second">Выйти</a>
        <?php endif; ?>
    </nav>
</header>

<main class="custom-main">
    <div class="container">
        <div>
            <div class="header-form">
                <h2 class="header-form-title">Поиск</h2>
            </div>
            <div class="form-container-users">
                <h2 class="results-header"><?php echo "Найдено запчастей: {$numResults}"; ?></h2>
                <form method="POST" action="?table=auto_parts&action=search">
                    <div class="input-group-users">
                        <label for="car_brand">Марка</label>
                        <select name="search_car_brand" id="car_brand" required>
                            <option value="">Марка</option>
                            <option value="Toyota">Toyota</option>
                            <option value="Honda">Honda</option>
                            <option value="Ford">Ford</option>
                            <option value="Chevrolet">Chevrolet</option>
                            <option value="Nissan">Nissan</option>
                            <option value="Volkswagen">Volkswagen</option>
                            <option value="Hyundai">Hyundai</option>
                            <option value="Kia">Kia</option>
                            <option value="BMW">BMW</option>
                            <option value="Audi">Audi</option>
                            <option value="Subaru">Subaru</option>
                            <option value="Mazda">Mazda</option>
                            <option value="Fiat">Fiat</option>
                            <option value="Volvo">Volvo</option>
                        </select>
                    </div>
                    <div class="input-group-users">
                        <label for="car_model">Модель</label>
                        <select name="search_car_model" id="car_model" required>
                            <option value="">Модель</option>
                        </select>
                    </div>
                    <div class="input-group-users">
                        <label for="spare_parts">Выберите запчасть</label>
                        <select name="spare_parts" id="spare_parts" required>
                            <option value="">Выберите запчасть</option>
                            <option value="transmission">Коробка передач</option>
                            <option value="fuel_pump">Топливный насос</option>
                            <option value="generator">Генератор</option>
                            <option value="starter">Стартер</option>
                            <option value="battery">Аккумулятор</option>
                            <option value="radiator">Радиатор</option>
                            <option value="shock_absorber">Амортизатор</option>
                            <option value="spring">Пружина подвески</option>
                            <option value="suspension_arm">Рычаг подвески</option>
                            <option value="generator_belt">Ремень генератора</option>
                            <option value="muffler">Глушитель</option>
                            <option value="ecu">Блок управления (ECU)</option>
                            <option value="oil_pressure_sensor">Датчик давления масла</option>
                            <option value="steering_wheel">Рулевое колесо</option>
                            <option value="egr_valve">Клапан EGR</option>
                        </select>
                    </div>
                    <div class="input-group-users">
                        <label for="construction_number">Артикул</label>
                        <input type="text" name="search_article" id="construction_number" placeholder="Конструкционный номер">
                    </div>
                    <div class="input-group-users" style="display: flex; align-items: center;">
                        <input type="checkbox" name="new_arrivals" id="new_arrivals" style="width: 15px; height: 15px; margin-right: 3px;">
                        <label for="new_arrivals" style="font-size: 0.8em;">Новые поступления</label>
                    </div>

                    <div class="toggle-button" id="toggle-additional-params" style="font-size: 0.8em;">
                        Дополнительные параметры
                    </div>

                    <div id="additional-params" style="display: none;">
                        <div class="input-group-users">
                            <label for="release_year_start">Год выпуска (начало)</label>
                            <input type="number" name="release_year_start" id="release_year_start" placeholder="Начальный год" min="1900" max="2100">
                        </div>
                        <div class="input-group-users">
                            <label for="release_year_end">Год выпуска (конец)</label>
                            <input type="number" name="release_year_end" id="release_year_end" placeholder="Конечный год" min="1900" max="2100">
                        </div>
                        <div class="input-group-users">
                            <label for="body">Кузов</label>
                            <input list="bodies" name="body" id="body">
                            <datalist id="bodies">
                                <option value="Седан">
                                <option value="Хэтчбек">
                                <option value="Универсал">
                                <option value="Кроссовер">
                            </datalist>
                        </div>
                        <div class="input-group-users">
                            <label for="item_number">Артикул товара</label>
                            <input type="text" name="item_number" id="item_number">
                        </div>
                        <div class="input-group-users">
                            <input type="checkbox" name="only_with_photo" id="only_with_photo" style="width: 15px; height: 15px; margin-right: 3px;">
                            <label for="only_with_photo" style="font-size: 0.8em;">Только с фото</label>
                        </div>
                    </div>

                    <button type="submit" class="custom-btn_sec" name="search_parts">Поиск запчастей</button>

                    <div class="reset-button" id="reset-params" style="font-size: 0.9em;">
                        <span class="reset-text">Сбросить</span><span class="cross-icon"> ✖</span>
                    </div>
                </form>
            </div>
        </div>

        <div class="catalog-container">
            <h2>Каталог запчастей</h2>
            <div class="sort_container">
                <div class="custom_input-group">
                    <label for="sort_options">Сортировать:</label>
                    <select name="sort_options" id="sort_options" class="custom-select">
                        <option value="date">По дате поступления</option>
                        <option value="discount">Максимальная скидка</option>
                        <option value="price_asc">По возрастанию цены</option>
                        <option value="price_desc">По убыванию цены</option>
                    </select>
                </div>
            </div>
            <div class="parts-list" id="parts-list">
                <?php echo renderTable($parts); // Вызов функции отображения запчастей ?>
            </div>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2024 Radiator</p>
</footer>

<div id="popup-message" class="<?php echo $messageType; ?>" style="<?php echo !empty($message) ? 'display:block;' : 'display:none;'; ?>">
    <?php if (!empty($message)) echo $message; ?>
</div>

<script src="frontjs.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.getElementById('toggle-additional-params').addEventListener('click', function() {
        const additionalParams = document.getElementById('additional-params');
        additionalParams.style.display = additionalParams.style.display === 'block' ? 'none' : 'block';
    });
</script>
</body>
</html>