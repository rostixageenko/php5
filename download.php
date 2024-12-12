<?php
if (isset($_GET['file'])) {
    $file = $_GET['file'];
    $filepath = 'sql_queries/' . basename($file);

    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        echo "Файл не найден.";
    }
}
?>