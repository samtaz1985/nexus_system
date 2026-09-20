<?php
// config/acciones.php
error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

// Carga la conexión PDO, el registrador de logs y RAG
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/rag.php';

// Capturar acción desde GET, POST o JSON
$inputJSON = json_decode(file_get_contents('php://input'), true) ?? [];
$accion = $_REQUEST['accion'] ?? $inputJSON['accion'] ?? '';
$mensaje = $_POST['mensaje'] ?? $inputJSON['mensaje'] ?? '';
$mensaje = trim($mensaje);

switch ($accion) {

    // 1. OBTENER HISTORIAL DE MENSAJES (PDO)
    case 'obtener_historial':
        try {
            if (isset($pdo)) {
                $stmt = $pdo->query("SELECT remitente, mensaje, fecha FROM chat_mensajes ORDER BY id ASC");
                $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['status' => 'ok', 'mensajes' => $mensajes]);
            } else {
                echo json_encode(['status' => 'ok', 'mensajes' => []]);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Error BD: ' . $e->getMessage()]);
        }
        exit;

    // 2. ENVIAR MENSAJE Y PROCESAR CON KOBOLDCPP
    case 'enviar_mensaje':
        if (empty($mensaje)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'El mensaje está vacío.']);
            exit;
        }

        // Guardar mensaje del usuario en la BD
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO chat_mensajes (remitente, mensaje) VALUES ('user', ?)");
                $stmt->execute([$mensaje]);
            } catch (Exception $e) {
                registrarLog('chat_backend', 'error_bd_user', $e->getMessage(), 'error');
            }
        }

        // Consultar tareas pendientes
        $contextoTareas = "No hay tareas pendientes en la base de datos.";
        if (isset($pdo)) {
            try {
                $stmt = $pdo->query("SELECT id, texto FROM tareas WHERE completada = 0 ORDER BY id DESC LIMIT 10");
                $tareasBD = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($tareasBD)) {
                    $lista = [];
                    foreach ($tareasBD as $t) {
                        $lista[] = "- [ID " . $t['id'] . "] " . $t['texto'];
                    }
                    $contextoTareas = "Lista actual de tareas pendientes registradas en el sistema:\n" . implode("\n", $lista);
                }
            } catch (Exception $e) {
                $contextoTareas = "Error al consultar la tabla de tareas.";
            }
        }

        // Consultar memorias activas dinámicamente mediante RAG
        $contextoMemoriaRaw = obtenerContextoRelevante($mensaje, 5);
        $contextoMemoria = !empty($contextoMemoriaRaw) 
            ? "\n\nReglas y memorias activas del sistema:\n" . $contextoMemoriaRaw 
            : "";

        // System Prompt
        $systemPrompt = "Eres NIAH, una IA integrada al panel local 'Nexus System'.\n"
                      . "REGLAS FUNDAMENTALES:\n"
                      . "1. Responde SIEMPRE en español de forma directa, técnica y concisa.\n"
                      . "2. Utiliza la información del CONTEXTO REAL para responder al usuario. Si no hay datos suficientes, indícalo de forma breve.\n"
                      . "3. No repitas saludos, ni uses adornos innecesarios.\n\n"
                      . "=== CONTEXTO DEL SISTEMA ===\n"
                      . $contextoTareas . "\n"
                      . $contextoMemoria;

        $promptCompleto = "### System:\n" . $systemPrompt . "\n\n"
                        . "### User:\n" . $mensaje . "\n\n"
                        . "### Assistant:\n";

        // Payload con secuencias de corte explícitas para KoboldCpp
        $payload = json_encode([
            'prompt' => $promptCompleto,
            'max_context_length' => 2048,
            'max_length' => 256,
            'temperature' => 0.1,
            'stop_sequence' => ["### User:", "### System:", "### Assistant:", "=== CONTEXTO DEL SISTEMA ==="]
        ]);

        $url = 'http://127.0.0.1:5001/api/v1/generate';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            registrarLog('chat_backend', 'error_curl', $curl_error, 'error');
            echo json_encode(['status' => 'error', 'mensaje' => 'Error al conectar con KoboldCpp.']);
            exit;
        }

        $data = json_decode($response, true);

        if (is_array($data) && isset($data['results'][0]['text'])) {
            $respuestaIA = trim($data['results'][0]['text']);

            // Puntos de corte estrictos para detener la cadena en el primer intento de alucinacion
            $puntosDeCorte = [
                '=== CONTEXTO',
                '=== CONTEXTO REAL',
                '=== CONTEXTO DEL SISTEMA ===',
                'Reglas y memorias activas',
                'Lista actual de tareas',
                '### User:',
                '### System:',
                '### Assistant:'
            ];

            foreach ($puntosDeCorte as $corte) {
                if (($pos = strpos($respuestaIA, $corte)) !== false) {
                    $respuestaIA = substr($respuestaIA, 0, $pos);
                }
            }

            // Sanitización regex para eliminar residuos de etiquetas
            $patronesLimpieza = [
                '/=== (BEGINNING|END) OF (CONTEXT|RESPONSE) ===/i',
                '/=== CONTEXTO (DEL SISTEMA|REAL|SISTEMA)? ===?/i',
                '/=== CONTEXTO.*/i',
                '/### (User|Assistant|System):/i'
            ];
            $respuestaIA = trim(preg_replace($patronesLimpieza, '', $respuestaIA));

            // Guardar respuesta de NIAH en la BD
            if (isset($pdo) && !empty($respuestaIA)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO chat_mensajes (remitente, mensaje) VALUES ('nexus', ?)");
                    $stmt->execute([$respuestaIA]);
                    
                    registrarLog('chat_backend', 'mensaje_procesado', 'Respuesta generada y guardada correctamente.', 'success');
                } catch (Exception $e) {
                    registrarLog('chat_backend', 'error_bd_nexus', $e->getMessage(), 'error');
                }
            }

            echo json_encode([
                'status' => 'ok',
                'respuesta' => $respuestaIA
            ]);
        } else {
            registrarLog('chat_backend', 'error_respuesta', 'Respuesta inválida o vacía de KoboldCpp', 'error');
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Respuesta no válida de KoboldCpp.'
            ]);
        }
        exit;

    // 3. VACIAR HISTORIAL DE CHAT
    case 'vaciar_chat':
        try {
            if (isset($pdo)) {
                $pdo->exec("TRUNCATE TABLE chat_mensajes");
                registrarLog('chat_backend', 'vaciar_chat', 'Historial de mensajes eliminado.', 'info');
                echo json_encode(['status' => 'ok', 'mensaje' => 'Historial eliminado.']);
            } else {
                echo json_encode(['status' => 'error', 'mensaje' => 'Sin conexión a la BD.']);
            }
        } catch (Exception $e) {
            registrarLog('chat_backend', 'error_vaciar_chat', $e->getMessage(), 'error');
            echo json_encode(['status' => 'error', 'mensaje' => 'Error BD: ' . $e->getMessage()]);
        }
        exit;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida: ' . $accion]);
        exit;
}