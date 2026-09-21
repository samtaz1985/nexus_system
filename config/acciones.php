<?php
// config/acciones.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/rag.php';

header('Content-Type: application/json; charset=utf-8');

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$mensaje = trim($_POST['mensaje'] ?? '');

switch ($accion) {

    // 1. CARGAR MENSAJES DEL CHAT
    case 'cargar_mensajes':
        if (!isset($pdo)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Sin conexión a la base de datos.']);
            exit;
        }
        try {
            $stmt = $pdo->query("SELECT id, remitente, mensaje, timestamp FROM chat_mensajes ORDER BY id ASC LIMIT 50");
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'ok', 'mensajes' => $mensajes]);
        } catch (Exception $e) {
            registrarLog('chat_backend', 'error_cargar', $e->getMessage(), 'error');
            echo json_encode(['status' => 'error', 'mensaje' => 'Error al obtener mensajes.']);
        }
        exit;

    // 2. ENVIAR MENSAJE Y PROCESAR ACCIONES DE NIAH
    case 'enviar_mensaje':
        if (empty($mensaje)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'El mensaje está vacío.']);
            exit;
        }

        // Registrar mensaje del usuario en MySQL
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO chat_mensajes (remitente, mensaje) VALUES ('user', ?)");
                $stmt->execute([$mensaje]);
            } catch (Exception $e) {
                registrarLog('chat_backend', 'error_bd_user', $e->getMessage(), 'error');
            }
        }

        // --- INTERCEPTORES DE ACCIÓN DIRECTA (Ejecución de Comandos) ---
        if (preg_match('/(?:agrega|crear|añadir|nueva)\s+tarea\s*:?\s*(.+)/i', $mensaje, $coincidencia)) {
            $textoTarea = trim($coincidencia[1]);
            try {
                $stmt = $pdo->prepare("INSERT INTO tareas (texto, completada) VALUES (?, 0)");
                $stmt->execute([$textoTarea]);
                $idTarea = $pdo->lastInsertId();
                $respuestaIA = "He registrado la nueva tarea exitosamente con el ID {$idTarea}: '{$textoTarea}'.";
            } catch (Exception $e) {
                $respuestaIA = "Error al intentar registrar la tarea en la base de datos.";
            }
        } elseif (preg_match('/(?:completar|completa|marcar)\s+tarea\s*(?:id)?\s*(\d+)/i', $mensaje, $coincidencia)) {
            $idTarea = intval($coincidencia[1]);
            try {
                $stmt = $pdo->prepare("UPDATE tareas SET completada = 1 WHERE id = ?");
                $stmt->execute([$idTarea]);
                $respuestaIA = "La tarea con ID {$idTarea} ha sido marcada como completada.";
            } catch (Exception $e) {
                $respuestaIA = "Error al actualizar la tarea ID {$idTarea}.";
            }
        } else {
            // --- INFERENCIA CON KOBOLDCPP (Si no hay comandos directos) ---
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

            // Inyección de memoria RAG
            $contextoMemoriaRaw = function_exists('obtenerContextoRelevante') ? obtenerContextoRelevante($mensaje, $pdo) : '';
            $contextoMemoria = !empty($contextoMemoriaRaw) 
                ? "\n\nReglas y memorias activas del sistema:\n" . $contextoMemoriaRaw 
                : "";

            $systemPrompt = "Eres NIAH, la inteligencia artificial integrada a Nexus System.\n"
              . "Antes de responder, analiza la solicitud dentro de las etiquetas <pensamiento>...</pensamiento> guiándote por estas reglas:\n"
              . "1. Analiza los datos en CONTEXTO REAL e interpreta las tareas y memorias proporcionadas abajo.\n"
              . "2. NO generes código SQL de respuesta si los datos ya están disponibles en el contexto; responde directamente al usuario con información procesada.\n"
              . "3. Formula la respuesta final fuera de las etiquetas de forma técnica, limpia y concisa en español.\n\n"
              . "=== CONTEXTO REAL ===\n"
              . $contextoTareas . "\n"
              . $contextoMemoria;

            $promptCompleto = "### System:\n" . $systemPrompt . "\n\n"
                            . "### User:\n" . $mensaje . "\n\n"
                            . "### Assistant:\n";

            $payload = json_encode([
                'prompt' => $promptCompleto,
                'max_context_length' => 2048,
                'max_length' => 512,
                'temperature' => 0.16,
                'rep_pen' => 1.18,
                'stop_sequence' => ["### User:", "### System:", "### Assistant:", "=== CONTEXTO", "=== END"]
            ]);

            $url = 'http://127.0.0.1:5001/api/v1/generate';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            $respuestaIA = trim($data['results'][0]['text'] ?? 'Sin respuesta del motor de IA.');

            // Extracción y log del pensamiento interno de NIAH
            if (preg_match('/<pensamiento>(.*?)<\/pensamiento>/s', $respuestaIA, $matches)) {
                $pensamientoInterno = trim($matches[1]);
                if (function_exists('registrarLog')) {
                    registrarLog('niah_reasoning', 'pensamiento', $pensamientoInterno, 'info');
                }
                $respuestaIA = preg_replace('/<pensamiento>(.*?)<\/pensamiento>/s', '', $respuestaIA);
            }

            // Sanitización estricta contra alucinaciones de cierre
            $puntosDeCorte = ['=== CONTEXTO', '=== END', '### User:', 'SIEMPRE EN ESPAÑOL'];
            foreach ($puntosDeCorte as $corte) {
                if (($pos = strpos($respuestaIA, $corte)) !== false) {
                    $respuestaIA = substr($respuestaIA, 0, $pos);
                }
            }

            // Regex para barrer etiquetas remanentes
            $respuestaIA = trim(preg_replace('/===\s*END.*$/is', '', $respuestaIA));
            $respuestaIA = trim(preg_replace('/###\s*(User|Assistant|System):?/i', '', $respuestaIA));
        }

        // Guardar la respuesta de NIAH en la base de datos
        if (isset($pdo) && !empty($respuestaIA)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO chat_mensajes (remitente, mensaje) VALUES ('nexus', ?)");
                $stmt->execute([$respuestaIA]);
            } catch (Exception $e) {
                registrarLog('chat_backend', 'error_bd_nexus', $e->getMessage(), 'error');
            }
        }

        echo json_encode(['status' => 'ok', 'respuesta' => $respuestaIA]);
        exit;

    // 3. LIMPIAR HISTORIAL DEL CHAT
    case 'limpiar_chat':
        if (isset($pdo)) {
            try {
                $pdo->exec("TRUNCATE TABLE chat_mensajes");
                echo json_encode(['status' => 'ok', 'mensaje' => 'Historial de chat vaciado.']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Error al vaciar historial.']);
            }
        }
        exit;

    default:
        echo json_encode(['status' => 'error', 'mensaje' => 'Acción no válida o no especificada.']);
        exit;
}