<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('table_func.php');

$numResults = 0;
$customersTable = new TableFunction($db, 'customers');

// Функция для отображения запчастей и подсчета количества
function renderTable($parts) {
    global $numResults; // Используем глобальную переменную
    global $db; // Доступ к переменной БД

    $numResults = 0; // Обнуляем переменную в начале функции

    if (empty($parts)) {
        return '<p>Запчасти не найдены.</p>';
    }

    $output = '<div class="parts-list">';

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $login = isset($_SESSION['login']) ? $_SESSION['login'] : null;
    $table_customers = new TableFunction($db, 'customers');
    $customerId = $table_customers->getCustomerByLogin($login);

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

        // Проверяем, есть ли корзина у покупателя
        if ($customerId) {
            $cartId = getCartId($customerId);
            $isPartInCart = checkPartInCart($cartId, $part['id']);

            if ($isPartInCart) {
                $output .= '<button class="go-to-cart-btn" onclick="window.location.href=\'cart.php\'">Перейти в корзину</button>';
            } else {
                $output .= '<button class="add-to-cart-btn" onclick="addToCart(this, ' . $part['id'] . ')">Добавить в корзину</button>';
            }
        } else {
            $output .= '<button class="add-to-cart-btn disabled" onclick="alert(\'Пожалуйста, авторизуйтесь для добавления в корзину.\')">Добавить в корзину</button>';
        }

        $output .= '</div>'; // .part-details
        $output .= '</div>'; // .part-card

        $numResults++; // Увеличиваем счетчик
    }

    $output .= '</div>'; // .parts-list

    return $output; // Возвращаем только HTML-код
}

// Функция для получения ID корзины покупателя
function getCartId($customerId) {
    global $db;
    $query = "SELECT id FROM cart WHERE idcustomer = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['id'];
    }

    return null; // Корзина не найдена
}

// Функция для проверки, добавлена ли запчасть в корзину
function checkPartInCart($cartId, $partId) {
    global $db;
    $query = "SELECT * FROM cart_auto_parts WHERE idcart = ? AND idautoparts = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $cartId, $partId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0; // Возвращаем true, если запчасть найдена
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
echo $tableHtml; // Отправляем HTML-код обратно
?>