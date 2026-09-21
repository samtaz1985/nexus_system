<?php
// config/acciones.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/rag.php';
require_once __DIR__ . '/tools.php';

if (file_exists(__DIR__ . '/kobold_control.php')) {
    require_once __DIR__ . '/kobold_control.php';
}

// -------------------------------------------------------------------------
// CAPTURADOR DE PETICIONES AJAX
// -------------------------------------------------------------------------
if (isset($_GET['accion'])) {
    header('Content-Type: application/json; charset=utf-8');

    // 1. Cargar historial
    if ($_GET['accion'] === 'obtener_historial') {
        if (!$pdo) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Sin conexión a la base de datos.']);
            exit;
        }

        try {
            $stmt = $pdo->query("SELECT emisor, mensaje, fecha FROM nexus_chat_historial ORDER BY id ASC LIMIT 50");
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'ok',
                'mensajes' => $mensajes
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ok', 'mensajes' => []]);
        }
        exit;
    }

    // 2. Enviar mensaje
    if ($_GET['accion'] === 'enviar_mensaje') {
        $mensajeUsuario = $_POST['mensaje'] ?? '';

        if (empty(trim($mensajeUsuario))) {
            echo json_encode(['status' => 'error', 'mensaje' => 'El mensaje está vacío.']);
            exit;
        }

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO nexus_chat_historial (emisor, mensaje) VALUES ('usuario', ?)");
                $stmt->execute([$mensajeUsuario]);
            } catch (Exception $e) {}
        }

        $respuestaIA = procesarAccionIA($mensajeUsuario);

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO nexus_chat_historial (emisor, mensaje) VALUES ('ia', ?)");
                $stmt->execute([$respuestaIA]);
            } catch (Exception $e) {}
        }

        echo json_encode([
            'status' => 'ok',
            'respuesta' => $respuestaIA
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -------------------------------------------------------------------------
// MOTOR PRINCIPAL DE PROCESAMIENTO IA
// -------------------------------------------------------------------------
function procesarAccionIA($prompt) {
    global $pdo;
    $promptTrim = trim($prompt);

    // Auto-activación de KoboldCpp y obtención de estado real
    $estadoMotorStr = "OFFLINE";
    if (class_exists('KoboldControl')) {
        $kobold = new KoboldControl();
        if (!$kobold->estaActivo()) {
            $kobold->iniciar();
            sleep(2);
        }
        $estadoMotorStr = $kobold->estaActivo() ? "ONLINE (Operativo en puerto 5001)" : "OFFLINE (Detenido)";
    }

    // Contexto de Tareas
    $contextoTareas = "";
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT id, texto, completada FROM tareas WHERE completada = 0 ORDER BY id DESC LIMIT 5");
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($tareas)) {
                $tLista = [];
                foreach ($tareas as $t) {
                    $tLista[] = "- [ID {$t['id']}] {$t['texto']}";
                }
                $contextoTareas = "TAREAS PENDIENTES:\n" . implode("\n", $tLista) . "\n";
            }
        } catch (Exception $e) {}
    }

    // Contexto RAG
    $contextoMemoria = function_exists('consultarMemoriaRAG') ? consultarMemoriaRAG($promptTrim) : "";

    // System prompt con telemetría real inyectada para evitar alucinaciones
    $systemPrompt = "Eres NIAH, la inteligencia artificial central de Nexus System.\n"
                  . "Responde siempre de forma precisa y directa al usuario basándote en la información del sistema.\n\n"
                  . "=== TELEMETRÍA DEL SISTEMA EN TIEMPO REAL ===\n"
                  . "- Estado del Motor IA (KoboldCpp): " . $estadoMotorStr . "\n"
                  . $contextoTareas . "\n"
                  . $contextoMemoria;

    $promptCompleto = "### System:\n" . $systemPrompt . "\n\n### User:\n" . $promptTrim . "\n\n### Assistant:\n";

    $payload = json_encode([
        'prompt' => $promptCompleto,
        'max_context_length' => 2048,
        'max_length' => 512,
        'temperature' => 0.16,
        'top_p' => 0.85,
        'repeat_penalty' => 1.15,   // Evita que repita frases o bloques completos
        'rep_pen_range' => 380,     // Rango de penalización para evitar bucles largos
        'stop_sequence' => ["### User:", "### System:", "### Assistant:", "===", "TAREAS PENDIENTES:"]
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
        return "Error de conexión con el motor local (KoboldCpp): " . $curl_error;
    }

    $data = json_decode($response, true);

    if (is_array($data) && isset($data['results'][0]['text'])) {
        $resultadoIA = trim($data['results'][0]['text']);
        return trim($resultadoIA);
    }

    return "No se obtuvo respuesta del motor IA.";
}