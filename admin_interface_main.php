<?php
include('table_func.php'); // Подключаем файл с функциями и классами

$message = "";
$messageType = "success"; // По умолчанию тип сообщения

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
            $message = "Данные успешно загружены.";
            $messageType = "success";
        }
    }
}
?>

<!DOCTYPE html>
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
</script>
</head>
<body>
<header>
    <img src="image/logo5.png" alt="Логотип" class="logo">
    <div class="menu">
        <div class="dropdown">
            <button class="button">База данных</button>
            <div class="dropdown-content">
                <a href="?table=users">Пользователи</a>
                <a href="?table=auto_parts">Запчасти</a>
                <a href="?table=orders">Заказы</a>
                <a href="?table=customers">Покупатели</a>
                <a href="?table=staff">Сотрудники</a>
                <a href="?table=suppliers">Поставщики</a>
                <a href="?table=inventory">Инвентарь</a>
                <a href="?table=cars">Автомобили</a>
                <button id="openModal" class="custom-button">Выгрузить таблицу</button>
                <button id="openUploadModal" class="custom-button">Загрузить таблицу</button> <!-- Новая кнопка -->
            </div>
        </div>
        <a href="activity_log.php" class="button">История операций</a>
        <a href="sql_queries.php" class="button">SQL Запросы</a>
        <a href="personal_cabinet.php" class="button">Личный кабинет</a>
    </div>
    <p><a href="index.php?logout='1'" class="button">Выйти</a></p>
</header>

<!-- Модальное окно для выгрузки -->
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

<main>
    <!-- сортировка -->
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
                echo "<option value='name_parts'>Название запчасти</option>";
                echo "<option value='article'>Артикул</option>";
                echo "<option value='purchase_price'>Цена</option>";
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
                        <select name="type_role" required onchange="toggleGarageInput(this);changeColor(this)" class="custom-select">
                            <option value="" disabled selected style="color: gray;">Выберите тип роли</option>
                            <option value="0">Покупатель</option>
                            <option value="1">Администратор</option>
                            <option value="2">Сотрудник</option>
                        </select>
                    </div>
                    <div class="input-group garage-input" style="display: none;">
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
                            <input type="file" id="file-input" name="photo" accept="image/*" required style="display:none;" onchange="previewImage(this)">
                            <img src="" alt="Предварительный просмотр изображения" /> <!-- Элемент для предварительного просмотра изображения -->
                        </div>
                    </div>
                    <button type="submit" class="btn">Добавить запчасть</button>
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
                        <input type="text" name="search_order_id" placeholder="ID заказа (необязательно)">
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
                    <button type="submit" class="btn" name="search_parts">Поиск запчастей</button>
                </form>

                <!-- добавление заказа -->
                <h2>Добавить заказ</h2>
                <form method="POST" action="?table=orders" enctype="multipart/form-data">
                    
                    <div class="input-group">
                        <select name="type_order" class="custom-select" required>
                            <option value="" disabled selected style="color: gray;">Тип заказа</option>
                            <option value="Самовывоз">Самовывоз</option>
                            <option value="Доставка">Доставка</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <select name="status" class="custom-select" required>
                            <option value="" disabled selected style="color: gray;">Статус</option>
                            <option value="Ожидается подтверждение">Ожидается подтверждение</option>
                            <option value="Отправка со склада">Отправка со склада</option>
                            <option value="В пути">В пути</option>
                            <option value="Готов к получению">Готов к получению</option>
                            <option value="Отменён">Отменён</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="text" name="purchase_price" placeholder="Итоговая цена заказа" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="id_customer" placeholder="ID покупателя" required>
                    </div>
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
                        <input type="text" name="edit_purchase_price" placeholder="Новая цена покупки">
                    </div>
                    <button type="submit" name="edit_order" class="btn">Изменить</button>
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
                $rowCount = isset($_POST['row_count']) ? intval($_POST['row_count']) : 25; // По умолчанию 25 строк

                switch ($selectedTable) {
                    case 'users':
                        if (!isset($_POST['search_users'])&&!isset($_POST['sort_table'])) {
                            $users = $usersTable->fetchLimited($rowCount);
                        }
                        $usersTable->renderTable($users, 'Пользователи');
                        break;
                    case 'auto_parts':
                        if (!isset($_POST['search_parts'])&&!isset($_POST['sort_table'])) {
                            $parts = $partsTable->fetchLimited($rowCount);
                        }
                        $partsTable->renderTable($parts, 'Запчасти');
                        break;
                    case 'orders':
                        $orders = $ordersTable->fetchLimited($rowCount);
                        $ordersTable->renderTable($orders, 'Заказы');
                        break;
                    case 'customers':
                        $customers = $customersTable->fetchLimited($rowCount);
                        $customersTable->renderTable($customers, 'Покупатели');
                        break;
                    case 'staff':
                        $staffs = $staffsTable->fetchLimited($rowCount);
                        $staffsTable->renderTable($staffs, 'Сотрудники');
                        break;
                    case 'suppliers':
                        $suppliers = $suppliersTable->fetchLimited($rowCount);
                        $suppliersTable->renderTable($suppliers, 'Поставщики');
                        break;
                    case 'cars':
                        $cars = $carsTable->fetchLimited($rowCount);
                        $carsTable->renderTable($cars, 'Автомобили');
                        break;
                    default:
                        echo "<p>Выберите таблицу из базы данных.</p>";
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