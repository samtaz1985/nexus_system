<?php
// cron/procesar_tareas.php
error_reporting(0);
ini_set('display_errors', 0);

if (php_sapi_name() !== 'cli') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/db.php';

if (!isset($pdo)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Sin conexión a la base de datos.']);
    exit;
}

// 1. Obtener la tarea pendiente más antigua
try {
    $stmt = $pdo->query("SELECT id, texto FROM tareas WHERE completada = 0 ORDER BY id ASC LIMIT 1");
    $tarea = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tarea) {
        echo json_encode(['status' => 'ok', 'mensaje' => 'No hay tareas pendientes por procesar.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error al consultar tareas: ' . $e->getMessage()]);
    exit;
}

// 2. Obtener directivas de memoria
$contextoMemoria = "";
try {
    $stmtMem = $pdo->query("SELECT tipo, clave, valor FROM nexus_memoria ORDER BY relevancia DESC LIMIT 5");
    $memorias = $stmtMem->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($memorias)) {
        $mLista = [];
        foreach ($memorias as $m) {
            $mLista[] = "- " . strtoupper($m['tipo']) . " (" . $m['clave'] . "): " . $m['valor'];
        }
        $contextoMemoria = implode("\n", $mLista);
    }
} catch (Exception $e) {}

// 3. System Prompt para procesamiento autónomo
$systemPrompt = "Eres NIAH, el motor autónomo de Nexus System.\n"
              . "Tu objetivo es analizar la siguiente tarea y generar un plan técnico breve o la solución directa para resolverla.\n"
              . "Reglas: Sé conciso, técnico, directo y responde en español.\n\n"
              . "=== REGLAS DEL SISTEMA ===\n" . $contextoMemoria;

$promptCompleto = "### System:\n" . $systemPrompt . "\n\n"
                . "### User:\nAnaliza y propone la solución técnica para la tarea ID [" . $tarea['id'] . "]: " . $tarea['texto'] . "\n\n"
                . "### Assistant:\n";

// 4. Petición a KoboldCpp
$payload = json_encode([
    'prompt' => $promptCompleto,
    'max_context_length' => 2048,
    'max_length' => 200,
    'temperature' => 0.1,
    'top_p' => 0.85,
    'rep_pen' => 1.2
]);

$ch = curl_init('http://127.0.0.1:5001/api/v1/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error cURL: ' . $curl_error]);
    exit;
}

$data = json_decode($response, true);

if (is_array($data) && isset($data['results'][0]['text'])) {
    $resultadoIA = trim($data['results'][0]['text']);

    // Sanitización
    if (($pos = strpos($resultadoIA, '=== REGLAS DEL SISTEMA ===')) !== false) {
        $resultadoIA = substr($resultadoIA, 0, $pos);
    }
    $resultadoIA = trim(preg_replace('/### (User|Assistant|System):/i', '', $resultadoIA));

    // Guardar resultado y marcar como analizada
    try {
        $update = $pdo->prepare("UPDATE tareas SET resultado_ia = ?, fecha_procesado = NOW() WHERE id = ?");
        $update->execute([$resultadoIA, $tarea['id']]);

        echo json_encode([
            'status' => 'ok',
            'tarea_id' => $tarea['id'],
            'analisis' => $resultadoIA
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Error al actualizar BD: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'mensaje' => 'Respuesta no válida de KoboldCpp.']);
}
exit;