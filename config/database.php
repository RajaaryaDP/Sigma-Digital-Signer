<?php

$driver  = 'mysql';
$host    = 'localhost';
$db_name = 'pbl_ti_2025_c_denidwiprasetya';
$user    = 'root';
$pass    = '';

try {
    $conn = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $user, $pass);
} catch (PDOException $e) {
    // Fallback ke tik_pbl jika dibutuhkan
    $conn = new PDO("mysql:host={$host};dbname=tik_pbl;charset=utf8mb4", $user, $pass);
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
