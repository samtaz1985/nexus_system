<?php
// config/agente.php

function analizarIntencion($mensajeUsuario) {
    $mensajeLower = mb_strtolower($mensajeUsuario);
    
    // Detección de intenciones para el clima
    if (strpos($mensajeLower, 'clima') !== false || strpos($mensajeLower, 'temperatura') !== false) {
        return ['accion' => 'obtener_clima', 'parametro' => extraerUbicacion($mensajeLower)];
    }
    
    // Detección para creación de tareas
    if (strpos($mensajeLower, 'crear tarea') !== false || strpos($mensajeLower, 'anota') !== false || strpos($mensajeLower, 'recuérdame') !== false) {
        $textoTarea = trim(str_ireplace(['crear tarea', 'anota', 'recuérdame', 'que'], '', $mensajeUsuario));
        return ['accion' => 'crear_tarea', 'parametro' => $textoTarea];
    }

    // Detección para búsquedas web o consultas generales
    if (strpos($mensajeLower, 'buscar') !== false || strpos($mensajeLower, 'internet') !== false) {
        return ['accion' => 'busqueda_web', 'parametro' => $mensajeUsuario];
    }
    
    return ['accion' => 'chat_interno', 'parametro' => null];
}

function extraerUbicacion($texto) {
    // Detectar ubicación específica si se menciona
    $textoLower = mb_strtolower($texto);
    if (strpos($textoLower, 'san bernardo') !== false) {
        return 'san_bernardo';
    }
    return 'santiago'; // Por defecto
}

function ejecutarHerramienta($intencion) {
    // 1. Herramienta de Clima (Open-Meteo)
    if ($intencion['accion'] === 'obtener_clima') {
        $ubicacion = $intencion['parametro'];
        
        $lat = -33.5935; // San Bernardo por defecto
        $lon = -70.7024;
        
        if ($ubicacion === 'santiago') {
            $lat = -33.4489;
            $lon = -70.6693;
        }

        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,relative_humidity_2m,weather_code";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || !$response) {
            return "DATOS EXTERNOS: No se pudo conectar con la API de clima localmente ({$curlError}).";
        }

        $data = json_decode($response, true);

        if (isset($data['current']['temperature_2m'])) {
            $temp = $data['current']['temperature_2m'];
            $humedad = $data['current']['relative_humidity_2m'] ?? 'desconocida';
            $nombreUbicacion = ($ubicacion === 'san_bernardo') ? 'San Bernardo' : 'Santiago';
            
            return "DATOS REALES EXTERNOS (API Open-Meteo): El clima actual en {$nombreUbicacion} es de {$temp}°C con una humedad del {$humedad}%.";
        }

        return "DATOS EXTERNOS: La respuesta de la API de clima no tiene el formato esperado.";
    }
    
    // 2. Herramienta de Creación de Tareas
    if ($intencion['accion'] === 'crear_tarea') {
        global $pdo;
        if (!isset($pdo) || !$pdo) {
            return "DATOS INTERNOS: No hay conexión a la base de datos disponible para guardar la tarea.";
        }
        
        $texto = $intencion['parametro'];
        try {
            $stmt = $pdo->prepare("INSERT INTO tareas (texto, completada) VALUES (?, 0)");
            $stmt->execute([$texto]);
            return "ACCIÓN REALIZADA CON ÉXITO: Se ha creado y guardado la tarea: '{$texto}' en la base de datos.";
        } catch (Exception $e) {
            return "ERROR INTERNO: No se pudo registrar la tarea en la base de datos.";
        }
    }

    // 3. Herramienta de Búsqueda Web (DuckDuckGo)
    if ($intencion['accion'] === 'busqueda_web') {
        $query = urlencode($intencion['parametro']);
        $url = "https://api.duckduckgo.com/?q={$query}&format=json&no_html=1&skip_disambig=1";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || !$response) {
            return "DATOS EXTERNOS: No se pudo realizar la búsqueda web ({$curlError}).";
        }

        $data = json_decode($response, true);
        $resumen = $data['AbstractText'] ?? '';

        if (empty($resumen) && !empty($data['RelatedTopics'])) {
            foreach ($data['RelatedTopics'] as $topic) {
                if (isset($topic['Text'])) {
                    $resumen = $topic['Text'];
                    break;
                }
            }
        }

        if (!empty($resumen)) {
            return "DATOS REALES DE INTERNET (DuckDuckGo): {$resumen}";
        }

        return "DATOS EXTERNOS: Se consultó la web para '{$intencion['parametro']}', pero no se encontró un resumen directo.";
    }
    
    return "";
}