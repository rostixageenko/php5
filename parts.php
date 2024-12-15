<?php
class AutoPartsManager {
    private $dbase;

    public function __construct() {
        $this->dbase = new mysqli('localhost', 'root', '', 'auto_disassembly_station');
        if ($this->dbase->connect_error) {
            die("Connection failed: " . $this->dbase->connect_error);
        }
    }
    public function sortParts($parts, $sortOption) {
    if (empty($parts)) {
        return []; // Возврат пустого массива, если нет запчастей
    }

    switch ($sortOption) {
        case 'date':
            usort($parts, function($a, $b) {
                return strtotime($b['date_receipt']) - strtotime($a['date_receipt']);
            });
            break;
        case 'price_asc':
            usort($parts, function($a, $b) {
                return $a['purchase_price'] - $b['purchase_price'];
            });
            break;
        case 'price_desc':
            usort($parts, function($a, $b) {
                return $b['purchase_price'] - $a['purchase_price'];
            });
            break;
        default:
            return $parts; // Если сортировка не определена, возврат без изменений
    }
    return $parts; // Возврат отсортированного массива
}
    // Метод для отображения запчастей
    public function renderTable($parts, $customerId) {
        $output = '';
    
        foreach ($parts as $part) {
            $output .= '<div class="part-card">';
            $output .= '<div class="part-image-container">';
    
            // Вывод фото в формате BLOB
            if (!empty($part['photo'])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_buffer($finfo, $part['photo']);
                finfo_close($finfo);
                $output .= '<img src="data:' . $mimeType . ';base64,' . base64_encode($part['photo']) . '" alt="' . htmlspecialchars($part['name_parts']) . '" class="part-image">';
            } else {
                $output .= 'Изображение недоступно';
            }
    
            $output .= '</div>'; // .part-image-container
            $output .= '<div class="part-details">';
            
            // Название запчасти, марка и модель
            $output .= '<h3>' . htmlspecialchars($part['name_parts']) . ' (' . htmlspecialchars($part['brand']) . ' ' . htmlspecialchars($part['model'] . ' '. htmlspecialchars($part['year_production'])) . ')</h3>';
            
            // Серый текст для характеристик
            $output .= '<p style="color: gray;"><strong>Артикул:</strong> ' . htmlspecialchars($part['article']) . '</p>';
            $output .= '<p style="color: gray;"><strong>Кузов:</strong> ' . htmlspecialchars($part['body_type']) . '</p>';
            $output .= '<p style="color: gray;"><strong>Тип трансмиссии:</strong> ' . htmlspecialchars($part['transmission_type']) . '</p>';
            $output .= '<p style="color: gray;"><strong>Тип топлива:</strong> ' . htmlspecialchars($part['fuel_type']) . '</p>';
            $output .= '<p style="color: gray;"><strong>Объём двигателя:</strong> ' . htmlspecialchars($part['engine_volume']) . ' л</p>';
            
            // Цена размещена справа
            $output .= '<p class="part-price" style="float: right;">' . htmlspecialchars($part['purchase_price']) . ' р.</p>';
    
            // Вывод описания
            if (!empty($part['description']) && $this->is_json($part['description'])) {
                $descriptionData = json_decode($part['description'], true);
                $output .= '<p><strong></strong> <pre>' . htmlspecialchars($this->formatJsonWithoutQuotes($descriptionData)) . '</pre></p>';
            } else {
                $output .= '<p><strong>Описание:</strong> недоступно</p>';
            }
    
            // Вывод состояния
            $output .= '<p style="color: gray;"><strong>Состояние:</strong> ' . htmlspecialchars($part['condition']) . '</p>';
    
            // Проверка наличия запчасти в корзине
            $query = "SELECT * FROM cart_auto_parts WHERE idcart = (SELECT id FROM cart WHERE idcustomer = ?) AND idautoparts = ?";
            $stmt = mysqli_prepare($this->dbase, $query);
            $partId = $part['id']; // Получаем идентификатор запчасти
            mysqli_stmt_bind_param($stmt, 'ii', $customerId, $partId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
    
            // Формы для управления корзиной
            if (isset($_SESSION['cart'])) {
                $output .= '<form method="POST" action="cart.php">';
                $output .= '<input type="hidden" name="part_id" value="' . htmlspecialchars($part['id']) . '">';
                $output .= '<input type="hidden" name="customer_id" value="' . htmlspecialchars($customerId) . '">';
                $output .= '<button type="submit" name="delete_from_cart" class="remove-btn">удалить из корзины</button>';
                $output .= '</form>';
            } elseif (mysqli_num_rows($result) > 0) {
                // Если запчасть уже в корзине
                $output .= '<button onclick="window.location.href=\'cart.php\'" class="go-to-cart-btn">Перейти в корзину</button>';
            } else {
                // Форма для добавления в корзину
                $output .= '<form method="POST" action="user_interface_main.php">';
                $output .= '<input type="hidden" name="part_id" value="' . htmlspecialchars($part['id']) . '">';
                $output .= '<input type="hidden" name="customer_id" value="' . htmlspecialchars($customerId) . '">';
                $output .= '<button type="submit" name="add_to_cart" class="add-to-cart-btn">Добавить в корзину</button>';
                $output .= '</form>';
            }
    
            $output .= '</div>'; // .part-details
            $output .= '</div>'; // .part-card
        }
    
        return $output;
    }
    public function is_json($string) {
        return is_string($string) && !empty($string) && ($string[0] === '{' || $string[0] === '[') && json_last_error() === JSON_ERROR_NONE;
    }

    // Функция для форматирования JSON без кавычек
    private function formatJsonWithoutQuotes($jsonData) {
        $result = '';
        if (is_array($jsonData)) {
            foreach ($jsonData as $key => $value) {
                $result .= "$key:\n";
                if (is_array($value)) {
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $result .= $this->formatJsonWithoutQuotes($item);
                        } else {
                            $result .= "  - $item\n";
                        }
                    }
                } else {
                    $result .= "  - $value\n";
                }
            }
        }
        return trim($result);
    }

    // Метод для получения всех запчастей
    public function getAllParts() {
        $query = "
            SELECT ap.*, c.brand, c.model, c.year_production, c.date_receipt, engine_volume, fuel_type, transmission_type, body_type
            FROM auto_parts ap
            JOIN cars c ON ap.idcar = c.id
        ";

        $result = $this->dbase->query($query);
        if (!$result) {
            die("Ошибка запроса: " . $this->dbase->error);
        }

        $parts = [];
        if ($result->num_rows > 0) {
            while ($part = $result->fetch_assoc()) {
                $image = !empty($part['photo']) ? 'data:image/jpeg;base64,' . base64_encode($part['photo']) : 'default_image.jpg';
                $part['image'] = $image;
                $parts[] = $part;
            }
        }
        return $parts;
    }

    public function fetchParts($searchConditions) {
        $query = "SELECT ap.id,name_parts, article, ap.`condition`, ap.purchase_price, description, idcar, ap.idgarage, photo, idorder, status, brand, model, year_production, VIN_number,  mileage, date_receipt, engine_volume, fuel_type, transmission_type, body_type FROM auto_parts ap JOIN cars c ON ap.idcar = c.id";
        
        if (!empty($searchConditions)) {
            $query .= " WHERE " . implode(" AND ", $searchConditions);
        }

        $result = mysqli_query($this->dbase, $query);
        if (!$result) {
            die("Ошибка выполнения запроса: " . mysqli_error($this->dbase));
        }

        $parts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $parts[] = $row;
        }

        return $parts;
    }
}
?>