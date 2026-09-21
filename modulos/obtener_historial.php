<?php
// modulos/obtener_historial.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (!$pdo) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Sin conexión a base de datos.']);
    exit;
}

try {
    // Consulta los últimos 30 mensajes ordenados cronológicamente
    $stmt = $pdo->query("SELECT id, emisor, mensaje, fecha FROM nexus_chat_historial ORDER BY id ASC LIMIT 30");
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'ok',
        'mensajes' => $mensajes
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'mensaje' => 'Error al obtener historial: ' . $e->getMessage()
    ]);
}