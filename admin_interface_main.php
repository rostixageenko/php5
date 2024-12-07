<?php
include('table_func.php'); // Подключаем файл с функциями и классами
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
        /* Стили для поля загрузки изображения */
        .upload-photo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%; /* Ширина 100% */
            height: 100px; /* Высота области загрузки */
            border: 2px solid gray; /* Обычная рамка */
            border-radius: 5px; /* Скругленные углы */
            cursor: pointer; /* Указатель при наведении */
            position: relative; /* Для абсолютного позиционирования изображения */
            overflow: hidden; /* Убедитесь, что изображение не выходит за границы */
        }
        .upload-photo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain; /* Сохраняет соотношение сторон */
            display: none; /* Скрываем изображение по умолчанию */
        }
        .upload-photo:hover {
            border-color: #3c763d; /* Изменение цвета рамки при наведении */
            background-color: rgba(76, 175, 80, 0.1); /* Легкий фон при наведении */
        }
        .upload-icon {
            font-size: 30px; /* Размер значка */
            color: gray; /* Цвет значка */
            position: absolute; /* Позволяет центровать иконку внутри рамки */
        }
        .garage-input {
            display: none; /* Скрываем поле по умолчанию */
        }
    </style>
    <script>
        function toggleGarageInput() {
            const roleSelect = document.querySelector('select[name="type_role"]');
            const garageInput = document.querySelector('.garage-input');
            if (roleSelect.value === "2") { // Если выбран "Сотрудник"
                garageInput.style.display = 'block'; // Показываем поле
            } else {
                garageInput.style.display = 'none'; // Скрываем поле
            }
        }
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
            </div>
        </div>
        <a href="activity_log.php" class="button">История операций</a>
        <a href="sql_queries.php" class="button">SQL Запросы</a>
        <a href="personal_cabinet.php" class="button">Личный кабинет</a>
    </div>
    <p><a href="index.php?logout='1'" class="button">Выйти</a></p>
</header>
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
                        <select name="type_role" required onchange="toggleGarageInput();changeColor(this);" class="custom-select">
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
                    case 'inventory':
                        $inventory = $inventoryTable->fetchLimited($rowCount);
                        $inventoryTable->renderTable($inventory, 'Инвентарь');
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