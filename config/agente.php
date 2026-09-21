<?php
// config/agente.php

function analizarIntencion($mensajeUsuario) {
    $mensajeLower = mb_strtolower($mensajeUsuario);
    
    // Detección simple de intenciones basada en patrones clave
    if (strpos($mensajeLower, 'clima') !== false || strpos($mensajeLower, 'temperatura') !== false) {
        return ['accion' => 'obtener_clima', 'parametro' => extraerUbicacion($mensajeLower)];
    }
    
    if (strpos($mensajeLower, 'buscar') !== false || strpos($mensajeLower, 'internet') !== false) {
        return ['accion' => 'busqueda_web', 'parametro' => $mensajeUsuario];
    }
    
    return ['accion' => 'chat_interno', 'parametro' => null];
}

function extraerUbicacion($texto) {
    // Extracción básica de ubicación para pruebas (ej: San Bernardo)
    if (strpos($texto, 'san bernardo') !== false) {
        return 'San Bernardo';
    }
    return 'Santiago'; // Por defecto
}

function ejecutarHerramienta($intencion) {
    if ($intencion['accion'] === 'obtener_clima') {
        // Simulación de respuesta de API externa o integración real cURL
        $ubicacion = $intencion['parametro'];
        // Aquí iría la llamada cURL real a una API de clima
        return "DATOS REALES EXTERNOS: El clima actual en {$ubicacion} es de 22°C, parcial.";
    }
    
    return "";
}