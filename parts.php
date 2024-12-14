<?php
class AutoPartsManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Метод для отображения запчастей
    public function renderTable($parts) {
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
            $output .= '<h3>' . htmlspecialchars($part['name_parts']) . '</h3>';
            $output .= '<p><strong>Марка:</strong> ' . htmlspecialchars($part['brand']) . '</p>';
            $output .= '<p><strong>Модель:</strong> ' . htmlspecialchars($part['model']) . '</p>';
            $output .= '<p class="part-price">' . htmlspecialchars($part['purchase_price']) . ' р.</p>';
            
            // Вывод описания
            if (!empty($part['description']) && $this->is_json($part['description'])) {
                $descriptionData = json_decode($part['description'], true);
                $output .= '<p><strong> </strong> <pre>' . htmlspecialchars($this->formatJsonWithoutQuotes($descriptionData)) . '</pre></p>';
            } else {
                $output .= '<p><strong>Описание:</strong> недоступно</p>';
            }
            
            // Вывод состояния
            $output .= '<p><strong>Состояние:</strong> ' . htmlspecialchars($part['condition']) . '</p>';
            
            $output .= '<button class="add-to-cart-btn" onclick="addToCart(this, ' . $part['id'] . ')">Добавить в корзину</button>';
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
        // Преобразуем массив в строку без кавычек и индексов
        $result = '';
    
        if (is_array($jsonData)) {
            foreach ($jsonData as $key => $value) {
                // Добавляем название группы (ключ)
                $result .= "$key:\n";
                
                if (is_array($value)) {
                    // Если значение - массив, добавляем его элементы
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            // Рекурсивный вызов для вложенных массивов
                            $result .= $this->formatJsonWithoutQuotes($item);
                        } else {
                            // Добавляем только значение
                            $result .= "  - $item\n"; // Используем отступ для лучшего форматирования
                        }
                    }
                } else {
                    // Если значение не массив, просто добавляем его
                    $result .= "  - $value\n"; // Используем отступ для лучшего форматирования
                }
            }
        }
    
        return trim($result); // Удаляем лишние пробелы в конце
    }
    
    // Метод для получения всех запчастей
    public function getAllParts() {
        $query = "
            SELECT ap.*, c.brand, c.model, c.year_production
            FROM auto_parts ap
            JOIN cars c ON ap.idcar = c.id
        ";

        $result = $this->db->query($query);
        if (!$result) {
            die("Ошибка запроса: " . $this->db->error);
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
        // Начинаем с базового запроса
        $query = "SELECT * FROM auto_parts a join cars c on a.idcar=c.id";
        
        // Если есть условия поиска, добавляем их в запрос
        if (!empty($searchConditions)) {
            $query .= " WHERE " . implode(" AND ", $searchConditions);
        }

        // Выполняем запрос
        $result = mysqli_query($this->db, $query);

        // Проверяем на ошибки
        if (!$result) {
            die("Ошибка выполнения запроса: " . mysqli_error($this->db));
        }

        // Получаем все запчасти в виде массива
        $parts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $parts[] = $row;
        }

        return $parts;
    }
}
?>