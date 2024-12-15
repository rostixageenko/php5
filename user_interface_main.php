<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once('server.php');
include_once('parts.php');



// Подсчет количества запчастей
$numResults = count($part);


$login = $_SESSION['login'];
$user_id = $_SESSION['user_id'];

// Используем подготовленное выражение для безопасного выполнения запроса
$sql = 'SELECT * FROM customers WHERE login = ?';
$stmt = mysqli_prepare($db, $sql);

if ($stmt) {
    // Привязываем параметр
    mysqli_stmt_bind_param($stmt, 's', $login);
    
    // Выполняем запрос
    mysqli_stmt_execute($stmt);
    
    // Получаем результат
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            $customerId = $row['id'];
            $_SESSION['customerId']= $customerId;
        } else {
            // Обработка случая, если пользователь не найден
            die("Пользователь не найден.");
        }
    } else {
        die("Ошибка выполнения запроса: " . mysqli_error($db));
    }

    // Закрываем подготовленное выражение
    mysqli_stmt_close($stmt);
} else {
    die("Ошибка подготовки запроса: " . mysqli_error($db));
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Интерфейс пользователя</title>
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
            display: none; /* Скрыто по умолчанию */
            border-radius: 8px; /* Скругленные края */
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
        const carModels = {
            'Toyota': ['Camry', 'RAV4', 'Highlander', 'Corolla'],
            'Honda': ['Accord', 'CR-V', 'Pilot', 'Civic'],
            'Ford': ['Focus', 'Escape', 'Explorer', 'Mustang'],
            'Chevrolet': ['Malibu', 'Equinox', 'Tahoe', 'Camaro'],
            'Nissan': ['Altima', 'Rogue', 'Murano', '370Z'],
            'Volkswagen': ['Passat', 'Tiguan', 'Jetta', 'Golf'],
            'Hyundai': ['Sonata', 'Tucson', 'Elantra', 'Santa Fe'],
            'Kia': ['Optima', 'Sportage', 'Forte', 'Seltos'],
            'BMW': ['3 Series', 'X5', 'X3', 'X1'],
            'Audi': ['A4', 'Q5', 'A6', 'Q3'],
            'Subaru': ['Legacy', 'Forester', 'Outback'],
            'Mazda': ['6', 'CX-5', 'CX-9'],
            'Fiat': ['500', 'Panda'],
            'Volvo': ['S60', 'XC60']
        };

        function updateModels() {
            const brandSelect = document.getElementById('car_brand');
            const modelSelect = document.getElementById('car_model');
            const selectedBrand = brandSelect.value;

            // Очистка предыдущих моделей
            modelSelect.innerHTML = '';

            if (selectedBrand) {
                const models = carModels[selectedBrand];
                models.forEach(model => {
                    const option = document.createElement('option');
                    option.value = model;
                    option.textContent = model;
                    modelSelect.appendChild(option);
                });
                modelSelect.disabled = false; // Разблокируем выбор модели
            } else {
                modelSelect.disabled = true; // Блокируем выбор модели
            }
        }

        function toggleAdditionalParams() {
            const additionalParams = document.getElementById('additional-params');
            additionalParams.style.display = additionalParams.style.display === 'none' || additionalParams.style.display === '' ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const brandSelect = document.getElementById('car_brand');
            brandSelect.addEventListener('change', updateModels);
            const toggleButton = document.getElementById('toggle-additional-params');
            toggleButton.addEventListener('click', toggleAdditionalParams);
        });
    </script>
</head>
<body>
<header class="header-user">
    <a href="user_interface_main.php">
        <img src="image/logo_new.png" alt="Логотип" class="logo">
    </a>
    <nav>
        <?php if (!isset($_SESSION['login'])): ?>
            <a href="login.php" class="custom_button_second">Войти</a>
        <?php else: ?>
            <a href="personal_cabinet_users.php" class="custom_button_second">
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

<div id="popup-message" class="<?php echo $messageType; ?>" style="<?php echo !empty($message) ? 'display:block;' : 'display:none;'; ?>">
    <?php if (!empty($message)) echo $message; ?>
</div>

<main class="custom-main">
    <div class="container">
        <div>
            <div class="header-form">
                <h2 class="header-form-title">Поиск</h2>
            </div>
            <div class="form-container-users">
                <h2 class="results-header"><?php echo "Найдено запчастей: {$numResults}"; ?></h2>
                <form method="POST" action="">
                    <div class="input-group-users">
                        <label for="car_brand">Марка</label>
                        <input list="car_brands" name="search_car_brand" id="car_brand" placeholder="Введите марку или выберите">
                        <datalist id="car_brands">
                            <option value="Toyota">
                            <option value="Honda">
                            <option value="Ford">
                            <option value="Chevrolet">
                            <option value="Nissan">
                            <option value="Volkswagen">
                            <option value="Hyundai">
                            <option value="Kia">
                            <option value="BMW">
                            <option value="Audi">
                            <option value="Subaru">
                            <option value="Mazda">
                            <option value="Fiat">
                            <option value="Volvo">
                        </datalist>
                    </div>
                    <div class="input-group-users">
                        <label for="car_model">Модель</label>
                        <select name="search_car_model" id="car_model" disabled>
                            <option value="">Модель</option>
                        </select>
                    </div>
                    <div class="input-group-users">
                        <label for="spare_parts">Выберите запчасть</label>
                        <select name="spare_parts" id="spare_parts">
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
                    <div class="toggle-button" id="toggle-additional-params" style="font-size: 0.8em; cursor: pointer;">
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
            <form method="POST" action="">
            <form method="POST" action="">
                <div class="sort_container">
                    <div class="custom_input-group">
                        <label for="sort_options">Сортировать:</label>
                        <select name="sort_options" id="sort_options" class="custom-select" onchange="this.form.submit()">
                            <option value="date" <?php echo (isset($sortOption) && $sortOption === 'date') ? 'selected' : ''; ?>>По дате поступления</option>
                            <option value="price_asc" <?php echo (isset($sortOption) && $sortOption === 'price_asc') ? 'selected' : ''; ?>>По возрастанию цены</option>
                            <option value="price_desc" <?php echo (isset($sortOption) && $sortOption === 'price_desc') ? 'selected' : ''; ?>>По убыванию цены</option>
                        </select>
                    </div>
                </div>
            </form>
            <div class="parts-list">
                <?php echo $autoPartsManager->renderTable($part, $customerId); // Вызов метода отображения запчастей ?>
            </div>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2024 Radiator</p>
</footer>

<!-- Подключаем JavaScript -->
<script src="frontjs.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>
</html>