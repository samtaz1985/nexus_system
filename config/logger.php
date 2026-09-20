<?php
// config/logger.php
require_once __DIR__ . '/db.php';

function registrarLog($origen, $accion, $detalle = '', $estado = 'info') {
    global $pdo;
    if (!isset($pdo)) return false;

    try {
        $stmt = $pdo->prepare("INSERT INTO nexus_logs (origen, accion, detalle, estado) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$origen, $accion, $detalle, $estado]);
    } catch (Exception $e) {
        return false;
    }
}