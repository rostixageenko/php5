<?php
include('table_func.php');

$numResults = 0;

// Функция для отображения запчастей и подсчета количества
function renderTable($parts) {
    global $numResults; // Используем глобальную переменную

    $numResults = 0; // Обнуляем переменную в начале функции

    if (empty($parts)) {
        return '<p>Запчасти не найдены.</p>';
    }

    $output = '<div class="parts-list">';
    
    foreach ($parts as $part) {
        $output .= '<div class="part-card">';
        $output .= '<div class="part-image-container">';
        $output .= '<img src="' . htmlspecialchars($part['image']) . '" alt="' . htmlspecialchars($part['name_parts']) . '" class="part-image">';
        $output .= '</div>'; // .part-image-container
        $output .= '<div class="part-details">';
        $output .= '<h3>' . htmlspecialchars($part['name_parts']) . '</h3>';
        $output .= '<p><strong>Марка:</strong> ' . htmlspecialchars($part['brand']) . '</p>';
        $output .= '<p><strong>Модель:</strong> ' . htmlspecialchars($part['model']) . '</p>';
        $output .= '<p><strong>Год выпуска:</strong> ' . htmlspecialchars($part['year_production']) . '</p>';
        $output .= '<p class="part-price">' . htmlspecialchars($part['purchase_price']) . ' р.</p>';
        $output .= '<p class="part-description">' . htmlspecialchars($part['description']) . '</p>';
        $output .= '<p><strong>Артикул:</strong> ' . htmlspecialchars($part['article']) . '</p>';
        $output .= '<button class="add-to-cart-btn">Добавить в корзину</button>';
        $output .= '</div>'; // .part-details
        $output .= '</div>'; // .part-card
        
        $numResults++; // Увеличиваем счетчик
    }

    $output .= '</div>'; // .parts-list

    return $output; // Возвращаем только HTML-код
}

// Запрос для получения запчастей и соответствующих автомобилей
$query = "
    SELECT ap.*, c.brand, c.model, c.year_production, c.engine_volume, 
           c.body_type, c.fuel_type, c.transmission_type
    FROM auto_parts ap
    JOIN cars c ON ap.idcar = c.id
";

$result = $db->query($query);
if (!$result) {
    die("Ошибка запроса: " . $db->error);
}

$parts = [];
if ($result->num_rows > 0) {
    while ($part = $result->fetch_assoc()) {
        $image = !empty($part['photo']) ? 'data:image/jpeg;base64,' . base64_encode($part['photo']) : 'default_image.jpg';
        $part['image'] = $image;
        $parts[] = $part;
    }
} else {
    echo '<p>Запчасти не найдены.</p>';
}

// Закрываем соединение с БД
$db->close();

// Вызов функции для отображения запчастей
$tableHtml = renderTable($parts);   
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Interface</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Ваши стили остаются здесь */
        #additional-params {
            display: none;
            margin-top: 10px;
        }
        .toggle-button {
            cursor: pointer;
            color: blue;
            text-decoration: underline;
            margin-top: 10px;
            margin-bottom: 10px;
        }
       body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        header {
            width: 97.3%;
            background:rgb(10, 41, 166);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .custom-main {
            background-color:rgba(183, 192, 255, 0.66); 
            width: 98.7%;
            border-top-left-radius: 0; /* Прямой верхний левый угол */
            border-top-right-radius: 0; /* Прямой верхний правый угол */
            border-bottom-left-radius: 8px; /* Закругленный нижний левый угол */
            border-bottom-right-radius: 8px; /* Закругленный нижний правый угол */
            padding: 10px; /* Отступ внутри элемента */
            box-shadow: none; /* Убираем тень */
        }
        .container {
            display: flex;
            width: 100%;
            max-width: 1500px;/* Максимальная ширина контейнера */
        
        }

        .form-container {
            flex: 1;
            max-width: 300px; /* Уменьшаем ширину окна поиска */
            margin-right: 20px; /* Отступы по бокам */
            margin-top: 10px;
            margin-left: 10px;
            padding: 20px;
            height: auto;
            max-height: 1000px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        } 


        .catalog-container {
            flex: 5; /* Увеличиваем ширину окна вывода запчастей */
            max-width: 1500px; /* Максимальная ширина */
            padding: 20px;
            margin-right: 10px; /* Отступы по бокам */
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 0px;
            background-color: #fff;
            overflow-y: auto;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .results-header {
            font-weight: bold;
            margin-bottom: 15px;
        }
        .input-group input {
            height: 27px;
            width: 86%;
            padding: 5px 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid gray;
            }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, textarea {
        width: 93%; /* Ширина 100% */
        padding: 5px 10px;
        font-family: Arial, sans-serif; /* Внутренние отступы */
        font-size: 16px; /* Размер шрифта */
        border-radius: 5px; /* Скругленные углы */
        border: 1px solid gray; /* Серый цвет рамки */
        }
        .input-group {
        margin: 10px 0px 10px 0px;
        }

        .sort_container {
            display: flex; /* Используем Flexbox */
            align-items: center; /* Выравнивание по вертикали */
            justify-content: flex-end; /* Располагаем элементы справа */
            width: 100%;
            max-width: 1200px;
            margin-bottom: 20px;
        }

        .custom_input-group {
            display: flex; /* Используем Flexbox для выравнивания */
            align-items: center; /* Выравнивание по вертикали */
        }

        .custom_input-group label {
            margin-right: 10px; /* Отступ между меткой и селектом */
        }
        .custom-select{
            height: 36px; /* Высота выпадающего списка */
        width: 300px; /* Ширина 95% */
        padding: 5px 10px; /* Внутренние отступы */
        font-size: 16px; /* Размер шрифта */
        border-radius: 5px; /* Скругленные углы */
        border: 1px solid rgb(128, 128, 128); /* Серый цвет рамки */
        background-color: white; /* Белый фон */
        cursor: pointer; /* Указатель при наведении */
        appearance: none; /* Убираем стандартный стиль браузера */
        background-image: url('data:image/png;base64,...'); /* Замените на изображение стрелки */
        background-repeat: no-repeat; /* Не повторять изображение */
        background-position: right 10px center; 
        }

        custom-select option {
        padding: 10px; /* Внутренние отступы для опций */
        background-color: white; /* Фон опций */
        color: black; /* Цвет текста */
        }

        /* Эффект при наведении на опции (для некоторых браузеров) */
        custom-select:hover {
        border-color: #888; /* Цвет рамки при наведении */
        }
        /* Стили для выпадающего списка */
        select {
        height: 36px; /* Высота выпадающего списка */
        width: 95%; /* Ширина 95% */
        padding: 5px 10px; /* Внутренние отступы */
        font-size: 16px; /* Размер шрифта */
        border-radius: 5px; /* Скругленные углы */
        border: 1px solid rgb(128, 128, 128); /* Серый цвет рамки */
        background-color: white; /* Белый фон */
        cursor: pointer; /* Указатель при наведении */
        appearance: none; /* Убираем стандартный стиль браузера */
        background-image: url('data:image/png;base64,...'); /* Замените на изображение стрелки */
        background-repeat: no-repeat; /* Не повторять изображение */
        background-position: right 10px center; /* Позиция стрелки */
        }
        /* Стили для опций внутри выпадающего списка */
        select option {
        padding: 10px; /* Внутренние отступы для опций */
        background-color: white; /* Фон опций */
        color: black; /* Цвет текста */
        }

        /* Эффект при наведении на опции (для некоторых браузеров) */
        select:hover {
        border-color: #888; /* Цвет рамки при наведении */
        }
        /* select, input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        } */

        .custom_button_second {
            background-color:#0062cb; /* Основной цвет кнопки */
            color: white; /* Цвет текста */
            padding: 10px 15px; /* Отступы */
            border: none; /* Без рамки */
            border-radius: 5px; /* Скругленные углы */
            cursor: pointer; /* Указатель при наведении */
            font-size: 16px; /* Размер шрифта */
            margin: 5px; /* Отступы между кнопками */
            transition: background-color 0.3s, transform 0.2s; /* Плавные переходы */
        }

            /* Эффект при наведении для всех кнопок */
        .custom_button_second:hover {
            background-color:#0056b3; /* Цвет кнопки при наведении */
            transform: scale(1.05); /* Увеличение при наведении */
        }

            /* Эффект при нажатии для всех кнопок */
        .custom_button_second:active {
            transform: scale(0.95); /* Уменьшение при нажатии */
        }

        .btn {
            background:#007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            padding: 10px;
            width: 100%;
        }
        .btn:hover {
            background:#0056b3;
        }
        .sort-options {
            margin-bottom: 15px;
        }
        .sort-btn {
            background: #007BFF;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .sort-btn:hover {
            background: #0056b3;
        }
        .reset-button {
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }

        .reset-text {
            text-decoration: none; /* Изначально без подчеркивания */
            color: gray; 
        }

        .reset-button:hover .reset-text {
            text-decoration: underline; /* Подчеркивание при наведении */
        }

        .cross-icon {
            margin-left: 5px; /* Отступ между текстом и крестиком */
            text-decoration: none; /* Убедитесь, что крестик не подчеркивается */
            color: gray; 
        }
        .buy-btn {
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            padding: 5px 10px;
        }
        .buy-btn:hover {
            background: #45a049;
        }
    
        .parts-list {
            display: flex;
            flex-direction: column;
            align-items: center; /* Центрируем карточки */
        }
        .part-card {
            display: flex; /* Flexbox для размещения изображения и информации */
            width: 1100px; /* Ширина карточки */
            height: 300px; /* Высота карточки */
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px; /* Отступ между карточками */
            background-color: #fff;
        }
        .part-image-container {
            width: 300px; /* Ширина контейнера для изображения */
            height: 280px; /* Высота изображения */
            overflow: hidden; /* Скрываем переполнение */
            border-radius: 5px 0 0 5px; /* Закругленные углы только слева */
            margin-top: 10px;
            margin-bottom: 10px;
            margin-left: 10px;
        }
        .part-image {
            width: 100%; /* Полная ширина изображения */
            height: auto; /* Авто высота */
        }
        .part-details {
            padding: 15px; /* Отступы внутри блока с информацией */
            flex-grow: 1; /* Занимает оставшееся пространство */
        }
        .part-price {
            font-size: 1.2em; /* Увеличенный размер шрифта для цены */
            color: #28a745; /* Зеленый цвет для цены */
            margin: 10px 0; /* Отступы сверху и снизу */
        }
        .add-to-cart-btn {
            background-color: #007BFF; /* Синий фон для кнопки */
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%; /* Ширина кнопки 100% */
            margin-top: 15px; /* Отступ сверху */
        }
        .add-to-cart-btn:hover {
            background-color: #0056b3; /* Темный синий при наведении */
        }
        nav {
            display: flex; /* Располагаем кнопки в строку */
            align-items: center; /* Выравнивание по вертикали */
        }

        .custom_button_second {
            display: flex; /* Используем Flexbox для кнопок */
            align-items: center; /* Центрируем изображение внутри кнопки */
            margin-right: 15px; /* Отступ между кнопками */
            text-decoration: none; /* Убираем подчеркивание */
        }

        .nav-icon {
            width: 24px; /* Ширина иконки */
            height: 24px; /* Высота иконки */
        }
    </style>
    <script>
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

            // Очистка предыдущих моделей
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
<main class="custom-main">
    <div class="container">
    <div class="form-container">
    <h2 class="results-header"><?php echo "Найдено запчастей: {$numResults}"; ?></h2>
    <form method="POST" action="?table=auto_parts&action=search">
        <div class="input-group">
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
        <div class="input-group">
            <label for="car_model">Модель</label>
            <select name="search_car_model" id="car_model" required>
                <option value="">Модель</option>
            </select>
        </div>
        <div class="input-group">
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
        <div class="input-group">
            <label for="construction_number">Артикул</label>
            <input type="text" name="search_article" id="construction_number" placeholder="Конструкционный номер">
        </div>
        <div class="input-group" style="display: flex; align-items: center;">
            <input type="checkbox" name="new_arrivals" id="new_arrivals" style="width: 15px; height: 15px; margin-right: 3px;">
            <label for="new_arrivals" style="font-size: 0.8em;">Новые поступления</label>
        </div>

        <!-- Кнопка для дополнительных параметров -->
        <div class="toggle-button" id="toggle-additional-params" style="font-size: 0.8em;">
            Дополнительные параметры
        </div>
        
        <!-- Дополнительные параметры -->
        <div id="additional-params">
            <div class="input-group">
                <label for="release_year_start">Год выпуска (начало)</label>
                <input type="number" name="release_year_start" id="release_year_start" placeholder="Начальный год" min="1900" max="2100">
            </div>
            <div class="input-group">
                <label for="release_year_end">Год выпуска (конец)</label>
                <input type="number" name="release_year_end" id="release_year_end" placeholder="Конечный год" min="1900" max="2100">
            </div>
            <div class="input-group">
                <label for="body">Кузов</label>
                <input list="bodies" name="body" id="body">
                <datalist id="bodies">
                    <option value="Седан">
                    <option value="Хэтчбек">
                    <option value="Универсал">
                    <option value="Кроссовер">
                </datalist>
            </div>
            <div class="input-group">
                <label for="item_number">Артикул товара</label>
                <input type="text" name="item_number" id="item_number">
            </div>
            <div class="input-group">
                <input type="checkbox" name="only_with_photo" id="only_with_photo" style="width: 15px; height: 15px; margin-right: 3px;">
                <label for="only_with_photo" style="font-size: 0.8em;">Только с фото</label>
            </div>
        </div>

        <button type="submit" class="btn" name="search_parts">Поиск запчастей</button>
        
        <!-- Кнопка "Сбросить" под кнопкой "Поиск запчастей" -->
        <div class="reset-button" id="reset-params" style="font-size: 0.9em;">
    <span class="reset-text">Сбросить</span><span class="cross-icon"> ✖</span>
</div>
    </form>
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

<!-- Всплывающее сообщение -->
<div id="popup-message" class="<?php echo $messageType; ?>" style="<?php echo !empty($message) ? 'display:block;' : 'display:none;'; ?>">
    <?php if (!empty($message)) echo $message; ?>
</div>

<!-- Подключаем JavaScript -->
<script src="frontjs.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Показать или скрыть дополнительные параметры при нажатии на кнопку
    document.getElementById('toggle-additional-params').addEventListener('click', function() {
        const additionalParams = document.getElementById('additional-params');
        additionalParams.style.display = additionalParams.style.display === 'block' ? 'none' : 'block';
    });
</script>
</body>
</html>