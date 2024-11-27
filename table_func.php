<?php
class TableFunction {
    protected $db;
    protected $tableName;

    public function __construct($dbConnection, $tableName) {
        $this->db = $dbConnection;
        $this->tableName = $tableName;
    }

    public function fetch() {
        $query = "SELECT * FROM " . mysqli_real_escape_string($this->db, $this->tableName) ;
        return $this->executeQuery($query);
    }
    // Метод для получения данных из таблицы
    public function selectTable() {
        $query = "SELECT * FROM " . mysqli_real_escape_string($this->db, $this->tableName);
        return $this->executeQuery($query);
    }

    // Метод для выполнения запроса и получения результатов
    private function executeQuery($query) {
        $result = mysqli_query($this->db, $query);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // Метод для отображения таблицы
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
?>