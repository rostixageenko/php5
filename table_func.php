<?php
class TableFunction {
    protected $db;
    protected $tableName;

    public function __construct($dbConnection, $tableName) {
        $this->db = $dbConnection;
        $this->tableName = $tableName;
    }

    public function fetch() {
        $query = "SELECT * FROM " . mysqli_real_escape_string($this->db, $this->tableName);
        return $this->executeQuery($query);
    }

    private function executeQuery($query) {
        $result = mysqli_query($this->db, $query);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public function renderTable($data, $title) {
        echo "<h2>$title</h2>";
        if (count($data) > 0) {
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($data[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $cell) {
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Нет данных для отображения.</p>";
        }
    }
}

// Подключение к базе данных
include('server.php');

$selectedTable = isset($_GET['table']) ? $_GET['table'] : 'users';

$usersTable = new TableFunction($db, 'users');
$partsTable = new TableFunction($db, 'auto_parts');
$ordersTable = new TableFunction($db, 'orders');
$customersTable = new TableFunction($db, 'Customers');
$staffsTable = new TableFunction($db, 'Staff');
$suppliersTable = new TableFunction($db, 'suppliers');
$inventoryTable = new TableFunction($db, 'Inventory');
$carsTable = new TableFunction($db, 'Cars');

$users = $usersTable->fetch();
$parts = $partsTable->fetch();
$orders = $ordersTable->fetch();
$customers = $customersTable->fetch();
$staffs = $staffsTable->fetch();
$suppliers = $suppliersTable->fetch();
$inventory = $inventoryTable->fetch();
$cars = $carsTable->fetch();

$message = "";
$messageType = "success"; // По умолчанию тип сообщения

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedTable === 'users') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    $type_role = trim($_POST['type_role']);

    if (!empty($login) && !empty($password) && !empty($type_role)) {
        $hashedPassword = md5($password);

        $stmt = $db->prepare("INSERT INTO users ( login, password, type_role) VALUES ( ?, ?, ?)");
        $stmt->bind_param("sss", $login, $hashedPassword, $type_role);
        
        try {
            if ($stmt->execute()) {
                $message = "Пользователь добавлен успешно.";
                $messageType = "success"; // Успешное сообщение
                $users = $usersTable->fetch(); // Обновляем данные
            }
        } catch (mysqli_sql_exception $e) {
            // Обрабатываем ошибку уникальности
            if ($e->getCode() === 1062) { // Код ошибки для дублирования
                $message = "Пользователь с таким логином уже существует.";
                $messageType = "error"; // Ошибка
            } else {
                $message = "Ошибка добавления пользователя: " . $e->getMessage();
                $messageType = "error"; // Ошибка
            }
        }
        
        $stmt->close();
    } else {
        $message = "Пожалуйста, заполните все поля.";
        $messageType = "error"; // Ошибка
    }
}
?>