<?php
include('server.php'); // Подключаем файл с функциями и классами

// Проверка, была ли загружена форма
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json_file'])) {
    $fileContent = file_get_contents($_FILES['json_file']['tmp_name']);
    $data = json_decode($fileContent, true); // Декодируем JSON

    if (json_last_error() !== JSON_ERROR_NONE) {
        $message = 'Ошибка при декодировании JSON: ' . json_last_error_msg();
        $messageType = "error"; // Ошибка
    } else {
        $table = $_POST['table']; // Имя таблицы
        $success = true; // Переменная для отслеживания успеха операций

        foreach ($data as $row) {
            // Подготовка запроса для добавления данных
            $columns = implode(", ", array_keys($row));
            $placeholders = implode(", ", array_fill(0, count($row), '?'));
            $stmt = $db->prepare("INSERT INTO `$table` ($columns) VALUES ($placeholders)");

            // Привязка параметров
            $types = str_repeat('s', count($row)); // Предполагается, что все значения строковые
            $stmt->bind_param($types, ...array_values($row)); // Привязка значений

            if (!$stmt->execute()) {
                $message = "Ошибка: " . $stmt->error;
                $messageType = "error"; // Ошибка
                $success = false; // Установить статус неуспеха
                break; // Прерывание цикла, если произошла ошибка
            }
        }

        if ($success) {
            $login = $_SESSION['login'];
        $id_user = $_SESSION['user_id'];
        $type_role = $_SESSION['type_role'];
            $actStr = "Пользователь $login типа '$type_role'  загрузил таблицу $table.";
            $dbExecutor->insertAction($id_user, $actStr);    
            $message = "Данные успешно загружены.";
            $messageType = "success";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Interface</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            display: flex;
        }
        .form-container {
            margin-right: 20px;
            width: 300px; /* Ширина контейнера формы */
        }
        .tables-container {
            width: 1100px; /* Установите фиксированную ширину */
            max-width: 100%; /* Максимальная ширина 100% для адаптивности */
            overflow-x: auto; /* Добавляет горизонтальную прокрутку при переполнении */
            flex-shrink: 0; /* Не позволяет элементу сжиматься */
        }
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
        .input-group {
            margin-bottom: 15px; /* Отступ между полями */
        }
        /* Стили для кнопок */
        .btn {
            padding: 10px;
            font-size: 15px;
            color: white;
            background: #8e8e8e; /* Серый фон для кнопок */
            border: none;
            border-radius: 5px;
            cursor: pointer; /* Указатель при наведении */
            width: 100%; /* Кнопка занимает всю ширину */
        }
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
        }

        .modal-content { 
            background-color: #fefefe; 
            margin: 15% auto; 
            padding: 20px; 
            border: 1px solid #888; 
            width: 50%; /* Уменьшенная ширина модального окна */
            max-width: 400px; /* Максимальная ширина для больших экранов */
            border-radius: 10px; /* Округлые углы */
        }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }

        .close-upload{
         color: #aaa; float: right; font-size: 28px; font-weight: bold; 
        }
        .close-upload:hover, .close-upload:focus { color: black; text-decoration: none; cursor: pointer; }

        .file-upload {
            display: flex;
            align-items: center; /* Выравнивание по центру */
            margin-bottom: 15px; /* Отступ между полями */
        }

        input[type="file"] {
            height: 25px;
            width: 358px;
         /* margin-left: 10px; Отступ между текстом и полем загрузки */
        }
        .garage-input {
            display: none; /* Скрываем поле по умолчанию */
        }
    </style>
    <script>
       document.addEventListener("DOMContentLoaded", function() {
        // Закрытие модального окна при клике вне
        window.onclick = function(event) {
        const modal = document.getElementById('myModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }

    // Открытие модального окна для выгрузки
    document.getElementById('openModal').onclick = function() {
        document.getElementById('myModal').style.display = 'block';
    }

    // Закрытие модального окна для выгрузки
    document.querySelector('.close').onclick = function() {
        document.getElementById('myModal').style.display = 'none';
    }

    // Открытие модального окна для загрузки
    document.getElementById('openUploadModal').onclick = function() {
        document.getElementById('uploadModal').style.display = 'block';
    }

    // Закрытие модального окна для загрузки
    document.querySelector('.close-upload').onclick = function() {
        document.getElementById('uploadModal').style.display = 'none';
    }
    window.onclick = function(event) {
        const modal = document.getElementById('uploadModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
    // Закрытие модального окна при клике на крестик
    document.querySelectorAll('.close').forEach(function(closeButton) {
        closeButton.onclick = function() {
            const modal = closeButton.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    });
});
function toggleGarageInput(selectElement) {
    const garageInput = document.querySelector('.garage-input');
    if (selectElement.value === "2") { // Если выбран "Сотрудник"
        garageInput.style.display = 'block'; // Показываем поле
    } else {
        garageInput.style.display = 'none'; // Скрываем поле
    }
}
function addPart() {
    const partsContainer = document.getElementById('parts-container');
    const newPartInput = document.createElement('div');
    newPartInput.className = 'input-group part-input';
    newPartInput.style.display = 'flex'; // Устанавливаем флекс для нового поля
    newPartInput.innerHTML = `
        <input type="text" name="parts[]" placeholder="ID автозапчасти" required>
        <button type="button" class="remove-part" onclick="removePart(this)" style="margin-left: 10px;">❌</button>
    `;
    partsContainer.appendChild(newPartInput);
}

function removePart(button) {
    const partInput = button.parentElement;
    partInput.remove();
}
function changeColor(select) {
    if (select.value) {
        select.style.color = 'black'; // Меняем цвет текста на черный при выборе
    } else {
        select.style.color = 'gray'; // Сбрасываем цвет текста на серый
    }
};
    </script>
</head>
<body>
<header>
<a href="admin_interface_main.php">
        <img src="image/logo_new.png" alt="Логотип" class="logo">
    </a>
    <div class="menu">
        <div class="dropdown">
            <button class="button">База данных</button>
            <div class="dropdown-content">
                <a href="?table=users">Пользователи</a>
                <a href="?table=auto_parts">Запчасти</a>
                <a href="?table=orders">Заказы</a>
                <a href="?table=customers">Покупатели</a name_organization, email, contact_phone, contact_person, addressa>
                <a href="?table=staff">Сотрудники</a>
                <a href="?table=suppliers">Поставщики</a>
                <a href="?table=cars">Автомобили</a>
                <a href="?table=history_operations_with_car">История операций с автомобилями</a>
                <a href="?table=history_operations_with_autoparts">История операций с запчастями</a>
                <a href="?table=inventory">Инвентарь</a>
                
                <button id="openModal" class="custom-button">Выгрузить таблицу</button> 
                <button id="openUploadModal" class="custom-button">Загрузить таблицу</button> 
            </div>
        </div>
        <a href="activity_log.php" class="button">История операций</a>
        <a href="sql_queries.php" class="button">SQL Запросы</a>
        <a href="personal_cabinet.php" class="button">Личный кабинет</a>
    </div>
    <p><a href="index.php?logout='1'" class="button">Выйти</a></p>
</header>

<!-- Модальное окно -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Экспорт в JSON</h2>
        <form id="exportForm" method="POST" action="export.php">
            <label for="table">Выберите таблицу:</label>
            <select id="table" name="table" required>
                <option value="auto_parts">auto_parts</option>
                <option value="cars">cars</option>
                <option value="cart">cart</option>
                <option value="cart_auto_parts">cart_auto_parts</option>
                <option value="car_brands">car_brands</option>
                <option value="customers">customers</option>
                <option value="departments">departments</option>
                <option value="garage">garage</option>
                <option value="garage_car_brands">garage_car_brands</option>
                <option value="history_operations_with_autoparts">history_operations_with_autoparts</option>
                <option value="history_operations_with_car">history_operations_with_car</option>
                <option value="inventory">inventory</option>
                <option value="orders">orders</option>
                <option value="posts">posts</option>
                <option value="staff">staff</option>
                <option value="staff_garage">staff_garage</option>
                <option value="suppliers">suppliers</option>
                <option value="sys_activity_log">sys_activity_log</option>
                <option value="users">users</option>
            </select>
            <button type="submit" name="export" class="custom-btn">Выгрузить</button>
        </form>
    </div>
</div>

<!-- Модальное окно для загрузки -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <span class="close-upload">&times;</span>
        <h2>Загрузка из JSON</h2>
        <form id="uploadForm" method="POST" action="admin_interface_main.php" enctype="multipart/form-data">
            <label for="tableUpload">Выберите таблицу:</label>
            <select id="tableUpload" name="table" required>
                <option value="auto_parts">auto_parts</option>
                <option value="cars">cars</option>
                <option value="cart">cart</option>
                <option value="cart_auto_parts">cart_auto_parts</option>
                <option value="car_brands">car_brands</option>
                <option value="customers">customers</option>
                <option value="departments">departments</option>
                <option value="garage">garage</option>
                <option value="garage_car_brands">garage_car_brands</option>
                <option value="history_operations_with_autoparts">history_operations_with_autoparts</option>
                <option value="history_operations_with_car">history_operations_with_car</option>
                <option value="inventory">inventory</option>
                <option value="orders">orders</option>
                <option value="posts">posts</option>
                <option value="staff">staff</option>
                <option value="staff_garage">staff_garage</option>
                <option value="suppliers">suppliers</option>
                <option value="sys_activity_log">sys_activity_log</option>
                <option value="users">users</option>
            </select>
            <label for="jsonFile">Выберите файл JSON:</label>
            <input type="file" id="jsonFile" name="json_file" accept=".json" required>
            <button type="submit" class="custom-btn">Загрузить</button>
        </form>
    </div>
</div>
</body>
</html>

<main>
    <div class="container">
        <div class="form-container">
        <h2>Сортировка данных</h2>
        <form method="POST" action="?table=<?php echo htmlspecialchars($selectedTable); ?>">
    <div class="input-group">
        <select name="sort_field" id="sort_field" class="custom-select" onchange="changeColor(this)">
            <option value="" disabled selected >Выберите поле для сортировки</option>
            <?php
            // Получаем названия полей для сортировки
            switch ($selectedTable) {
                case "users": 
                    echo "<option value='id'>ID</option>";
                    echo "<option value='login'>Логин</option>";
                    echo "<option value='type_role'>Тип роли</option>";
                    break;
                    
                case "auto_parts":
                    echo "<option value='id'>ID</option>";
                    echo "<option value='name_part'>Название запчасти</option>";
                    echo "<option value='article'>Артикул</option>";
                    echo "<option value='purchase_price'>Цена</option>";
                    break;
            
                case "orders":
                    echo "<option value='id'>ID</option>";
                    echo "<option value='type_order'>Тип заказа</option>";
                    echo "<option value='status'>Статус</option>";
                    echo "<option value='datetime'>Дата и время</option>";
                    echo "<option value='purchase_price'>Цена</option>";
                    echo "<option value='idcustomer'>ID клиента</option>";
                    break;
                    
                case "customers":
                    echo "<option value='id'>ID</option>";
                    echo "<option value='login'>Логин</option>";
                    echo "<option value='first_name'>Имя</option>";
                    echo "<option value='second_name'>Фамилия</option>";
                    echo "<option value='email'>Email</option>";
                    echo "<option value='contact_phone'>Телефон</option>";
                    echo "<option value='address'>Адрес</option>";
                    break;
            
                case "staff":
                    echo "<option value='id'>ID</option>";
                    echo "<option value='first_name'>Имя</option>";
                    echo "<option value='second_name'>Фамилия</option>";
                    echo "<option value='login'>Логин</option>";
                    echo "<option value='email'>Email</option>";
                    echo "<option value='contact_phone'>Телефон</option>";
                    echo "<option value='idpost'>ID должности</option>";
                    break;
            
                case "suppliers":
                    echo "<option value='id'>ID</option>";
                    echo "<option value='name_organization'>Название организации</option>";
                    echo "<option value='email'>Email</option>";
                    echo "<option value='contact_phone'>Телефон</option>";
                    echo "<option value='contact_person'>Контактное лицо</option>";
                    echo "<option value='address'>Адрес</option>";
                    break;
            
                case "cars":
                    echo "<option value='id'>ID</option>";
                    echo "<option value='brand'>Марка</option>";
                    echo "<option value='model'>Модель</option>";
                    echo "<option value='year_production'>Год производства</option>";
                    echo "<option value='VIN_number'>VIN номер</option>";
                    echo "<option value='purchase_price'>Цена</option>";
                    echo "<option value='condition'>Состояние</option>";
                    echo "<option value='idgarage'>ID гаража</option>";
                    echo "<option value='idsupplier'>ID поставщика</option>";
                    echo "<option value='mileage'>Пробег</option>";
                    echo "<option value='date_receipt'>Дата получения</option>";
                    echo "<option value='engine_volume'>Объем двигателя</option>";
                    echo "<option value='fuel_type'>Тип топлива</option>";
                    echo "<option value='transmission_type'>Тип трансмиссии</option>";
                    echo "<option value='body_type'>Тип кузова</option>";
                    break;
                    case "history_operations_with_autoparts":
                        echo "<option value='id'>ID операции</option>";
                        echo "<option value='type_operation_parts'>Тип операции</option>";
                        echo "<option value='description'>Описание</option>";
                        echo "<option value='datetime'>Дата и время операции</option>";
                        echo "<option value='idautoparts'>ID запчасти</option>";
                        echo "<option value='idstaff'>ID сотрудника</option>";
                        break;
            }
            // Добавьте другие таблицы при необходимости
            ?>
        </select>
    </div>
    <div class="input-group">
        <select name="sort_order" id="sort_order" class="custom-select" onchange="changeColor(this)">
        <option value="" disabled selected >Выберите порядок сортировки</option>
            <option value="ASC">По возрастанию</option>
            <option value="DESC">По убыванию</option>
        </select>
    </div>
    <div class="input-group">
        <button type="submit" class="btn" name="sort_table">Отсортировать</button>
    </div>
</form>

            <?php if ($selectedTable === 'users'): ?>
                <!-- Поиск пользователей -->
                <h2>Поиск пользователей</h2>
                <form method="POST" action="?table=users">
                    <div class="input-group">
                        <input type="text" name="id" placeholder="ID пользователя">
                    </div>
                    <div class="input-group">
                        <input type="text" name="login" placeholder="Логин">
                    </div>
                    <div class="input-group">
                        <select name="type_role" class="custom-select" id="mySelect" onchange="changeColor(this)">
                            <option value="" disabled selected style="color: gray;">Выберите тип роли (необязательно)</option>
                            <option value="0">Покупатель</option>
                            <option value="1">Администратор</option>
                            <option value="2">Сотрудник</option>
                        </select>
                    </div>
                    <button type="submit" name="search_users" class="btn">Поиск</button>
                </form>
                <h2>Добавить пользователя</h2>
                <form method="POST" action="?table=users">
                    <div class="input-group">
                        <input type="text" name="login" placeholder="Логин" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Пароль" required>
                    </div>
                    <div class="input-group">
    <select name="type_role" required onchange="toggleGarageInput(this)" class="custom-select">
        <option value="" disabled selected style="color: gray;">Выберите тип роли</option>
        <option value="0">Покупатель</option>
        <option value="1">Администратор</option>
        <option value="2">Сотрудник</option>
    </select>
</div>
<div class="input-group garage-input">
    <input type="text" name="garage_id" placeholder="ID гаража (для сотрудника)">
</div>
                    <button type="submit" name="add_users" class="btn">Добавить</button>
                </form>
                    <!-- Изменить пароль пользователя -->
                <h2>Изменить пароль пользователя</h2>
                <form method="POST" action="?table=users&action=change_password">
                    <div class="input-group">
                        <input type="text" name="change_login" placeholder="Логин пользователя" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="new_password" placeholder="Новый пароль" required>
                    </div>
                    <button type="submit" class="btn">Изменить</button>
                </form>

            <?php elseif ($selectedTable === 'auto_parts'): ?>
                <h2>Поиск запчастей</h2>
                <form method="POST" action="?table=auto_parts&action=search">
                    <div class="input-group">
                        <input type="text" name="search_part_id" placeholder="ID запчасти (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_article" placeholder="Артикул (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_part_name" placeholder="Название запчасти (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_car_id" placeholder="ID автомобиля (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_garage_id" placeholder="ID гаража (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_status" placeholder="Статус" required>
                    </div>
                    <button type="submit" class="btn" name="search_parts">Поиск запчастей</button>
                </form>
                <h2>Добавить запчасть</h2>
                <form method="POST" action="?table=auto_parts" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="text" name="part_name" placeholder="Название запчасти" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="article" placeholder="Артикул" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="condition" placeholder="Состояние" required>
                    </div>
                    <div class="input-group">
                        <input type="number" name="price" placeholder="Цена" required>
                    </div>
                    <div class="input-group">
                        <textarea name="description" placeholder="Описание" required></textarea>
                    </div>
                    <div class="input-group">
                        <input type="text" name="car_id" placeholder="ID автомобиля" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="garage_id" placeholder="ID гаража" required>
                    </div>
                    <div class="input-group">
                        <label>Добавить изображение</label>
                        <div class="upload-photo" onclick="document.getElementById('file-input').click();">
                            <span class="upload-icon">+</span>
                            <input type="file" id="file-input" name="photo" accept="image/*" style="display:none;" onchange="previewImage(this)">
                            <img src="" alt="Предварительный просмотр изображения" style="display: none;" />
                        </div>
                    </div>
                    <button type="submit" class="btn" name="add_part">Добавить запчасть</button>
                </form>

                <h2>Изменить запчасть</h2>
                <form method="POST" action="?table=auto_parts&action=update_part">
                    <div class="input-group">
                        <select name="search_field" required class="custom-select" id="mySelect" onchange="changeColor(this)">
                            <option value="" disabled selected style="color: gray;">Выберите поле для поиска</option>
                            <option value="id">ID запчасти</option>
                            <option value="article">Артикул</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_value" placeholder="Введите значение для поиска" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="new_part_name" placeholder="Новое название (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="new_article" placeholder="Новый артикул (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="new_condition" placeholder="Новое состояние (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="number" name="new_price" placeholder="Новая цена (необязательно)">
                    </div>
                    <div class="input-group">
                        <textarea name="new_description" placeholder="Новое описание (необязательно)"></textarea>
                    </div>
                    <div class="input-group">
                        <input type="text" name="new_car_id" placeholder="ID автомобиля (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="new_garage_id" placeholder="ID гаража (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="new_status" placeholder="Статус" required>
                    </div>
                    <button type="submit" class="btn" name="update_part">Изменить запчасть</button>
                </form>

                <h2>Изменение картинки</h2>
                <form method="POST" action="?table=auto_parts&action=update_image" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="text" name="image_part_id" placeholder="ID запчасти" required>
                    </div>
                    <div class="input-group">
                        <label>Загрузить новое изображение</label>
                        <div class="upload-photo" onclick="document.getElementById('file-input-image').click();">
                            <span class="upload-icon">+</span>
                            <input type="file" id="file-input-image" name="photo" accept="image/*" style="display:none;" onchange="previewImage(this)">
                            <img src="" alt="Предварительный просмотр изображения" style="display: none;" />
                        </div>
                    </div>
                    <button type="submit" class="btn" name="update_image">Изменить картинку</button>
                </form>

                
                <?php elseif ($selectedTable === 'orders'): ?>
                    <!-- поиск заказа -->
                <h2>Поиск заказа</h2>
                <form method="POST" action="?table=orders&action=search">
                    <div class="input-group">
                        <input type="text" name="search_id" placeholder="ID заказа (необязательно)">
                    </div>
                    <div class="input-group">
                        <select name="search_type_order" class="custom-select" onchange="changeColor(this)">
                            <option value="" disabled selected style="color: gray;">Тип заказа (необязательно) </option>
                            <option value="Самовывоз">Самовывоз</option>
                            <option value="Доставка">Доставка</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <select name="search_status" class="custom-select" onchange="changeColor(this)">
                            <option value="" disabled selected style="color: gray;">Статус заказа (необязательно) </option>
                            <option value="Ожидается подтверждение">Ожидается подтверждение</option>
                            <option value="Отправка со склада">Отправка со склада</option>
                            <option value="В пути">В пути</option>
                            <option value="Готов к получению">Готов к получению</option>
                            <option value="Отменён">Отменён</option>
                        </select>
                    </div>
        
                    <div class="input-group">
                        <input type="date" name="search_start_interval" >
                    </div>
                    <div class="input-group">
                        <input type="date" name="search_end_interval" >
                    </div>
        
                    <div class="input-group">
                        <input type="text" name="search_purchase_price" placeholder="Итоговая цена заказа (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_id_customer" placeholder="ID покупателя (необязательно)">
                    </div>
                    <button type="submit" class="btn" name="search_order">Поиск заказ</button>
                </form>

                <!-- добавление заказа -->
                <h2>Добавить заказ</h2>
                <form method="POST" action="?table=orders" enctype="multipart/form-data">
                    <div class="input-group">
                        <select name="type_order" class="custom-select" required  onchange="changeColor(this)">
                            <option value="" disabled selected style="color: gray;">Тип заказа</option>
                            <option value="Самовывоз">Самовывоз</option>
                            <option value="Доставка">Доставка</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <select name="status" class="custom-select" required  onchange="changeColor(this)">
                            <option value="" disabled selected style="color: gray;">Статус</option>
                            <option value="Ожидается подтверждение">Ожидается подтверждение</option>
                            <option value="Отправка со склада">Отправка со склада</option>
                            <option value="В пути">В пути</option>
                            <option value="Готов к получению">Готов к получению</option>
                            <option value="Отменён">Отменён</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="number" name="purchase_price" placeholder="Итоговая цена заказа" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="id_customer" placeholder="ID покупателя" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="address" placeholder="Адрес доставки" required>
                    </div>
                    <div id="parts-container" style="display: flex; flex-direction: column;">
                        <h4>Автозапчасти в заказе</h4>
                        <div class="input-group part-input" style="display: flex; align-items: center;">
                            <input type="text" name="parts[]" placeholder="ID автозапчасти" required>
                            <button type="button" class="add-part" onclick="addPart()" style="margin-left: 10px;">➕</button>
                        </div>
                    </div>
                    
                    <br>
                    <button type="submit" class="btn" name="add_order">Добавить заказ</button>
                </form>


                                <!-- изменение заказа -->
                <h2>Изменить данные заказа</h2>
                <form method="POST" action="?table=orders&action=edit">
                    <div class="input-group">
                        <input type="text" name="edit_id" placeholder="ID заказа" required>
                    </div>
                    <div class="input-group">
                        <select name="edit_type_order" class="custom-select" onchange="changeColor(this)">
                            <option value="" disabled selected style="color: gray;">Выберите новый тип заказа </option>
                            <option value="Самовывоз">Самовывоз</option>
                            <option value="Доставка">Доставка</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <select name="edit_status" class="custom-select" onchange="changeColor(this)">
                            <option value="" disabled selected style="color: gray;">Выберите новый статус (необязательно)</option>
                            <option value="Ожидается подтверждение">Ожидается подтверждение</option>
                            <option value="Отправка со склада">Отправка со склада</option>
                            <option value="В пути">В пути</option>
                            <option value="Готов к получению">Готов к получению</option>
                            <option value="Отменён">Отменён</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="number" name="edit_purchase_price" placeholder="Новая цена покупки">
                    </div>

                    <button type="submit" name="edit_order" class="btn">Изменить</button>
                </form>
            

                <?php  elseif ($selectedTable === 'customers'): ?>
                <!-- Поиск покупателей -->
                <h2>Поиск покупателя</h2>
                <form method="POST" action="?table=customers&action=search">
                    <div class="input-group">
                        <input type="text" name="search_id" placeholder="ID покупателя (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_login" placeholder="Логин покупателя (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_first_name" placeholder="Имя (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_second_name" placeholder="Фамилия (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_email" placeholder="Email (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_contact_phone" placeholder="Телефон (необязательно)">
                    </div>
                    <button type="submit" class="btn" name="search_customer">Поиск покупателя</button>
                </form>

                <!-- Добавление покупателя -->
                <h2>Добавить покупателя</h2>
                <form method="POST" action="?table=customers" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="text" name="login" placeholder="Логин" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Пароль" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="first_name" placeholder="Имя" >
                    </div>
                    <div class="input-group">
                        <input type="text" name="second_name" placeholder="Фамилия" >
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email" >
                    </div>
                    <div class="input-group">
                        <input type="text" name="contact_phone" placeholder="Контактный телефон" >
                    </div>
                    <div class="input-group">
                        <input type="text" name="address" placeholder="Адрес" >
                    </div>
                    <button type="submit" class="btn" name="add_customer">Добавить покупателя</button>
                </form>

                <!-- Изменение данных покупателя -->
                <h2>Изменить данные покупателя</h2>
                <form method="POST" action="?table=customers&action=edit">
                    <div class="input-group">
                        <input type="text" name="edit_id" placeholder="ID покупателя" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_login" placeholder="Логин">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_first_name" placeholder="Имя">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_second_name" placeholder="Фамилия">
                    </div>
                    <div class="input-group">
                        <input type="email" name="edit_email" placeholder="Email">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_contact_phone" placeholder="Контактный телефон">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_address" placeholder="Адрес">
                    </div>
                    <button type="submit" class="btn" name="edit_customer">Изменить покупателя</button>
                </form>

                <?php elseif ($selectedTable === 'staff'): ?>
                <!-- Поиск сотрудников -->
                <h2>Поиск сотрудника</h2>
                <form method="POST" action="?table=staff&action=search">
                    <div class="input-group">
                        <input type="text" name="search_id" placeholder="ID сотрудника (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_login" placeholder="Логин сотрудника (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_first_name" placeholder="Имя (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_second_name" placeholder="Фамилия (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="email" name="search_email" placeholder="Email (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_contact_phone" placeholder="Телефон (необязательно)">
                    </div>
                    <button type="submit" class="btn" name="search_staff">Поиск сотрудника</button>
                </form>

                <!-- Добавление сотрудника -->
                <h2>Добавить сотрудника</h2>
                <form method="POST" action="?table=staff" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="text" name="first_name" placeholder="Имя" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="second_name" placeholder="Фамилия" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="login" placeholder="Логин" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="password" placeholder="Пароль" required>
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email">
                    </div>
                    <div class="input-group">
                        <input type="text" name="contact_phone" placeholder="Контактный телефон">
                    </div>
                    <div class="input-group">
                        <input type="number" name="salary" placeholder="Заработная плата" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="idpost" placeholder="ID Должности"required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="idgarage" placeholder="ID гараж"required>
                    </div>
                    <button type="submit" class="btn" name="add_staff">Добавить сотрудника</button>
                </form>

                <!-- Изменение данных сотрудника -->
                <h2>Изменить данные сотрудника</h2>
                <form method="POST" action="?table=staff&action=edit">
                    <div class="input-group">
                        <input type="text" name="edit_id" placeholder="ID сотрудника" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_first_name" placeholder="Имя">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_second_name" placeholder="Фамилия">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_login" placeholder="Логин">
                    </div>
                    <div class="input-group">
                        <input type="email" name="edit_email" placeholder="Email">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_contact_phone" placeholder="Контактный телефон">
                    </div>
                    <div class="input-group">
                        <input type="text" name="salary" placeholder="Заработная плата" >
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_idpost" placeholder="ID Должности">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_idgarage" placeholder="ID  Гаража">
                    </div>
                    <button type="submit" class="btn" name="edit_staff">Изменить сотрудника</button>
                </form>


                <?php elseif ($selectedTable === 'suppliers'): ?>
                <!-- Поиск поставщика -->
                <h2>Поиск поставщика</h2>
                <form method="POST" action="?table=suppliers&action=search">
                    <div class="input-group">
                        <input type="text" name="search_id" placeholder="ID поставщика (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_name_organization" placeholder="Название организации (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="email" name="search_email" placeholder="Email (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_contact_phone" placeholder="Телефон (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_contact_person" placeholder="Контактное лицо (необязательно)">
                    </div>
                    <button type="submit" class="btn" name="search_supplier">Поиск поставщика</button>
                </form>

                <!-- Добавление поставщика -->
                <h2>Добавить поставщика</h2>
                <form method="POST" action="?table=suppliers" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="text" name="name_organization" placeholder="Название организации" required>
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="contact_phone" placeholder="Контактный телефон" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="contact_person" placeholder="Контактное лицо">
                    </div>
                    <div class="input-group">
                        <input type="text" name="address" placeholder="Адрес">
                    </div>
                    <button type="submit" class="btn" name="add_supplier">Добавить поставщика</button>
                </form>

                <!-- Изменение данных поставщика -->
                <h2>Изменить данные поставщика</h2>
                <form method="POST" action="?table=suppliers&action=edit">
                    <div class="input-group">
                        <input type="text" name="edit_id" placeholder="ID поставщика" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_name_organization" placeholder="Название организации">
                    </div>
                    <div class="input-group">
                        <input type="email" name="edit_email" placeholder="Email">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_contact_phone" placeholder="Контактный телефон">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_contact_person" placeholder="Контактное лицо">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_address" placeholder="Адрес">
                    </div>
                    <button type="submit" class="btn" name="edit_supplier">Изменить поставщика</button>
                </form>

                <?php elseif ($selectedTable === 'cars'): ?>
                <!-- Поиск автомобиля -->
                <h2>Поиск автомобиля</h2>
                <form method="POST" action="?table=cars&action=search">
                    <div class="input-group">
                        <input type="text" name="search_id" placeholder="ID автомобиля (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_brand" placeholder="Марка автомобиля (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_model" placeholder="Модель автомобиля (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_year_start" placeholder="с какого года (необязательно)" 
                            pattern="\d{4}" title="Введите 4 цифры (например, 2023)" 
                            min="1900" max="<?php echo date('Y'); ?>" >
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_year_end" placeholder="до какого года (необязательно)" 
                            pattern="\d{4}" title="Введите 4 цифры (например, 2023)" 
                            min="1900" max="<?php echo date('Y'); ?>" >
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_VIN" placeholder="VIN номер (необязательно)">
                    </div>
                    <button type="submit" class="btn" name="search_car">Поиск автомобиля</button>
                </form>

                <!-- Добавление автомобиля -->
                <h2>Добавить автомобиль</h2>
                <form method="POST" action="?table=cars" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="text" name="brand" placeholder="Марка автомобиля" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="model" placeholder="Модель автомобиля" required>
                    </div>
                    <div class="input-group">
                        <input type="number" name="year_production" placeholder="Год производства" required min="1886" max="<?php echo date('Y'); ?>">
                    </div>
                    <div class="input-group">
                        <input type="text" name="VIN_number" placeholder="VIN номер" required maxlength="17">
                    </div>
                    <div class="input-group">
                        <input type="text" name="purchase_price" placeholder="Цена покупки" required>
                    </div>
                    <div class="input-group">
                        <textarea name="condition" placeholder="Состояние (цвет,статус,особенности)"></textarea>
                    </div>
                    <div class="input-group">
                        <input type="number" name="idgarage" placeholder="ID гаража" required>
                    </div>
                    <div class="input-group">
                        <input type="number" name="idsupplier" placeholder="ID поставщика" required>
                    </div>
                    <div class="input-group">
                        <input type="number" name="mileage" placeholder="Пробег" >
                    </div>
                    <div class="input-group">
                        <input type="text" name="engine_volume" placeholder="Объем двигателя (л)">
                    </div>
                    <div class="input-group">
                        <label for="fuel_type">Тип топлива:</label>
                        <select name="fuel_type" id="fuel_type" onchange="changeColor(this)" required>
                            <option value="">Выберите тип топлива</option>
                            <option value="бензин">Бензин</option>
                            <option value="дизель">Дизель</option>
                            <option value="газ">Газ</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="transmission_type">Тип трансмиссии:</label>
                        <select name="transmission_type" id="transmission_type" onchange="changeColor(this)" required>
                            <option value="">Выберите тип трансмиссии</option>
                            <option value="механика">Механика</option>
                            <option value="автомат">Автомат</option>
                            <option value="робот">Робот</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="body_type">Тип кузова:</label>
                        <select name="body_type" id="body_type"  onchange="changeColor(this)" required>
                            <option value="">Выберите тип кузова</option>
                            <option value="седан">Седан</option>
                            <option value="кроссовер">Кроссовер</option>
                            <option value="хэтчбек">Хэтчбек</option>
                            <option value="купэ">Купэ</option>
                            <option value="универсал">Универсал</option>
                            <option value="SUV">SUV</option>
                            <option value="пикап">Пикап</option>
                        </select>
                    </div>
                    <button type="submit" class="btn" name="add_car">Добавить автомобиль</button>
                </form>

                <!-- Изменение данных автомобиля -->
                <h2>Изменить данные автомобиля</h2>
                <form method="POST" action="?table=cars&action=edit">
                    <div class="input-group">
                        <input type="text" name="edit_id" placeholder="ID автомобиля" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_brand" placeholder="Марка автомобиля">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_model" placeholder="Модель автомобиля">
                    </div>
                    <div class="input-group">
                        <input type="number" name="edit_year_production" placeholder="Год производства">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_VIN_number" placeholder="VIN номер" maxlength="17">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_purchase_price" placeholder="Цена покупки">
                    </div>
                    <div class="input-group">
                        <textarea name="edit_condition" placeholder="Состояние (цвет,статус,особенности)"></textarea>
                    </div>
                    <div class="input-group">
                        <input type="number" name="edit_idgarage" placeholder="ID гаража">
                    </div>
                    <div class="input-group">
                        <input type="number" name="edit_idsupplier" placeholder="ID поставщика">
                    </div>
                    <div class="input-group">
                        <input type="number" name="edit_mileage" placeholder="Пробег">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_engine_volume" placeholder="Объем двигателя (л)">
                    </div>
                    <div class="input-group">
                        <label for="fuel_type">Тип топлива:</label>
                        <select name="fuel_type" id="fuel_type" onchange="changeColor(this)" >
                            <option value="">Выберите тип топлива</option>
                            <option value="бензин">Бензин</option>
                            <option value="дизель">Дизель</option>
                            <option value="газ">Газ</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="transmission_type">Тип трансмиссии:</label>
                        <select name="transmission_type" id="transmission_type" onchange="changeColor(this)" >
                            <option value="">Выберите тип трансмиссии</option>
                            <option value="механика">Механика</option>
                            <option value="автомат">Автомат</option>
                            <option value="робот">Робот</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="body_type">Тип кузова:</label>
                        <select name="body_type" id="body_type"  onchange="changeColor(this)" >
                            <option value="">Выберите тип кузова</option>
                            <option value="седан">Седан</option>
                            <option value="кроссовер">Кроссовер</option>
                            <option value="хэтчбек">Хэтчбек</option>
                            <option value="купэ">Купэ</option>
                            <option value="универсал">Универсал</option>
                            <option value="SUV">SUV</option>
                            <option value="пикап">Пикап</option>
                        </select>
                    </div>
                    <button type="submit" class="btn" name="edit_car">Изменить автомобиль</button>
                </form>

                <?php elseif ($selectedTable === 'history_operations_with_autoparts'): ?>
                <!-- Поиск истории операций с автозапчастями -->
                <h2>Поиск истории операций</h2>
                <form method="POST" action="?table=history_operations_with_autoparts&action=search">
                    <div class="input-group">
                        <input type="text" name="search_id" placeholder="ID операции (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_type_operation" placeholder="Тип операции (необязательно)">
                    </div>
                    <div class="input-group">
                        <textarea name="search_description" placeholder="Описание (необязательно)"></textarea>
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_idautoparts" placeholder="ID запчасти (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_idstaff" placeholder="ID сотрудника (необязательно)">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_date_start" placeholder="С какого числа (необязательно)" 
                            pattern="\d{4}-\d{2}-\d{2}" title="Введите дату в формате ГГГГ-ММ-ДД">
                    </div>
                    <div class="input-group">
                        <input type="text" name="search_date_end" placeholder="До какого числа (необязательно)" 
                            pattern="\d{4}-\d{2}-\d{2}" title="Введите дату в формате ГГГГ-ММ-ДД">
                    </div>
                    <button type="submit" class="btn" name="search_history">Поиск истории операций</button>
                </form>

                <h2>Добавить историю операций с запчастями</h2>
                <form method="POST" action="?table=history_operations_with_autoparts" enctype="multipart/form-data">
                    <div class="input-group">
                        <label for="type_operation_parts">Тип операции:</label>
                        <input type="text" name="type_operation_parts" placeholder="Тип операции (покупка, продажа, установка)" required>
                    </div>
                    <div class="input-group">
                        <label for="description">Описание:</label>
                        <textarea name="description" placeholder="Описание операции (например, детали, дата и т.д.)" required></textarea>
                    </div>
                    <div class="input-group">
                        <label for="idautoparts">ID запчасти:</label>
                        <input type="number" name="idautoparts" placeholder="ID запчасти" required>
                    </div>
                    <div class="input-group">
                        <label for="idstaff">ID сотрудника:</label>
                        <input type="number" name="idstaff" placeholder="ID сотрудника" required>
                    </div>
                    <button type="submit" class="btn" name="add_history">Добавить историю операции</button>
                </form>
                <!-- Изменение данных истории операций -->
                <h2>Изменить данные истории операций</h2>
                <form method="POST" action="?table=history_operations_with_autoparts&action=edit">
                    <div class="input-group">
                        <input type="text" name="edit_id" placeholder="ID операции" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_type_operation_parts" placeholder="Тип операции">
                    </div>
                    <div class="input-group">
                        <textarea name="edit_description" placeholder="Описание"></textarea>
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_idautoparts" placeholder="ID запчасти">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_idstaff" placeholder="ID сотрудника">
                    </div>
                    <div class="input-group">
                        <input type="text" name="edit_datetime" placeholder="Дата и время операции" 
                            pattern="\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}" 
                            title="Введите дату и время в формате ГГГГ-ММ-ДД ЧЧ:ММ:СС">
                    </div>
                    <button type="submit" class="btn" name="edit_history">Изменить историю операции</button>
                </form>

            <?php else: ?>

                <p>Выберите таблицу из базы данных для отображения соответствующих форм.</p>
            <?php endif; ?>
            <h2>Вывод данных</h2>
            <form method="POST" action="?table=<?php echo $selectedTable; ?>">
                <div class="input-group">
                    <input type="number" name="row_count" placeholder="Количество строк" required min="1">
                </div>
                <button type="submit" class="btn">Вывести</button>
            </form>
        </div>

        <div class="tables-container">
            <div class="table-scroll">
                <?php
                // Вывод данных с учетом ограничения
                $rowCount = isset($_POST['row_count']) ? intval($_POST['row_count']) : 50; // По умолчанию 25 строк

                switch ($selectedTable) {
                    case 'users':
                        if (!isset($_POST['search_users']) && !isset($_POST['sort_table'])) {
                            $users = $usersTable->fetchLimited($rowCount);
                        }
                        $usersTable->renderTable($users, 'Пользователи');
                        break;
                
                    case 'auto_parts':
                        if (!isset($_POST['search_parts']) && !isset($_POST['sort_table'])) {
                            $parts = $partsTable->fetchLimited($rowCount);
                        }
                        $partsTable->renderTable($parts, 'Запчасти');
                        break;
                
                    case 'orders':
                        if (!isset($_POST['search_order']) && !isset($_POST['sort_table'])) {
                            $orders = $ordersTable->fetchLimited($rowCount);
                        }
                        $ordersTable->renderTable($orders, 'Заказы');
                        break;
                
                    case 'customers':
                        if (!isset($_POST['search_customer']) && !isset($_POST['sort_table'])) {
                            $customers = $customersTable->fetchLimited($rowCount);
                        }
                        $customersTable->renderTable($customers, 'Покупатели');
                        break;
                
                    case 'staff':
                        if (!isset($_POST['search_staff']) && !isset($_POST['sort_table'])) {
                            $staffs = $staffsTable->fetchLimited($rowCount);
                        }
                        $staffsTable->renderTable($staffs, 'Сотрудники');
                        break;
                
                    case 'suppliers':
                        if (!isset($_POST['search_supplier']) && !isset($_POST['sort_table'])) {
                            $suppliers = $suppliersTable->fetchLimited($rowCount);
                        }
                        $suppliersTable->renderTable($suppliers, 'Поставщики');
                        break;
                
                    case 'cars':
                        if (!isset($_POST['search_car']) && !isset($_POST['sort_table'])) {
                            $cars = $carsTable->fetchLimited($rowCount);
                        }
                        $carsTable->renderTable($cars, 'Автомобили');
                        break;
                    case 'history_operations_with_autoparts':
                        if (!isset($_POST['search_history']) && !isset($_POST['sort_table'])) {
                            $cars = $carsTable->fetchLimited($rowCount);
                        }
                        $historyOperationsWithAutopartTable->renderTable($historyOperationsWithAutopart, 'История операций с запчастями');
                        break;
                    default:
                        // Обработка случая, когда выбранная таблица не поддерживается
                        $message = "Ошибка: выбранная таблица не поддерживается.";
                        $messageType = "error";
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</main>

<!-- Всплывающее сообщение -->
<div id="popup-message" class="<?php echo $messageType; ?>" style="<?php echo !empty($message) ? 'display:block;' : ''; ?>">
    <?php if (!empty($message)) echo $message; ?>
</div>

<!-- Подключаем JavaScript -->
<script src="frontjs.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>
</html>