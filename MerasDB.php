<?php
$host = "localhost";
$username = "root";
$password = "root";
$database = "meras";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("تعذر الاتصال بقاعدة البيانات.");
}

$conn->set_charset("utf8mb4");
?>
