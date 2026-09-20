<?php
// config/db.php

$host = "localhost";
$usuario = "root";
$password = "";
$bd = "nexus_db";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$bd;charset=utf8mb4", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>