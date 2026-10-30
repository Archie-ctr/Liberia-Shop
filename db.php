<?php

$host_name = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
$is_local = ($host_name === 'localhost' || str_starts_with($host_name, 'localhost:') || $host_name === '127.0.0.1' || str_starts_with($host_name, '127.0.0.1:'));

if ($is_local) {
    $servername = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'onlineshop';
} else {
    $servername = getenv('DB_HOST') ?: '';
    $username = getenv('DB_USER') ?: '';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_NAME') ?: '';

    if ($servername === '' || $username === '' || $password === '' || $database === '') {
        http_response_code(500);
        die('Production database environment variables are not configured.');
    }
}

mysqli_report(MYSQLI_REPORT_OFF);
$con = mysqli_connect($servername, $username, $password, $database);

if (!$con) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    die($is_local ? 'Connection failed: ' . mysqli_connect_error() : 'The store is temporarily unavailable. Please try again later.');
}

mysqli_set_charset($con, 'utf8mb4');
?>
