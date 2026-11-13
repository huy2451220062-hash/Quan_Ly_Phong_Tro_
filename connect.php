<?php
$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "quan_ly_phong_tro";
$port = 3307;

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database,
    $port
);

if ($conn->connect_error) {
    die("Lỗi kết nối CSDL: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
