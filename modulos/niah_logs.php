<?php
// modulos/niah_logs.php
require_once __DIR__ . '/../config/db.php';

try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT id, evento, mensaje, fecha FROM nexus_logs WHERE modulo = 'niah_reasoning' ORDER BY id DESC LIMIT 50");
        $logsNiah = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw new Exception("Conexión PDO no disponible.");
    }
} catch (Exception $e) {
    $logsNiah = [];
    $errorLogs = $e->getMessage();
}

// Endpoint JSON para la sincronización AJAX en segundo plano
if (isset($_GET['fetch_json']) && $_GET['fetch_json'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'ok',
        'logs' => $logsNiah,
        'error' => $errorLogs ?? null
    ], JSON_THROW_ON_ERROR);
    exit;
}
?>