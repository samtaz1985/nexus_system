<?php
// config/tools.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';

function tool_ejecutar_sql($query, $params = []) {
    global $pdo;
    if (!$pdo) return ['status' => 'error', 'mensaje' => 'Sin conexión a la base de datos.'];

    $queryTrim = trim($query);
    if (preg_match('/^\s*(DROP|TRUNCATE|ALTER)\b/i', $queryTrim)) {
        return ['status' => 'error', 'mensaje' => 'Operación SQL restringida por políticas de seguridad.'];
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        if (stripos($queryTrim, 'SELECT') === 0) {
            return ['status' => 'ok', 'tipo' => 'select', 'filas' => $stmt->rowCount(), 'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } else {
            return ['status' => 'ok', 'tipo' => 'escritura', 'filas_afectadas' => $stmt->rowCount()];
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'mensaje' => 'Error SQL: ' . $e->getMessage()];
    }
}

function tool_leer_archivo($rutaRelativa) {
    if (empty($rutaRelativa)) return ['status' => 'error', 'mensaje' => 'Ruta no especificada.'];

    $baseDir = realpath(__DIR__ . '/../');
    $rutaAbsoluta = realpath($baseDir . '/' . ltrim($rutaRelativa, '/\\'));

    if (!$rutaAbsoluta || strpos($rutaAbsoluta, $baseDir) !== 0 || !file_exists($rutaAbsoluta)) {
        return ['status' => 'error', 'mensaje' => "El archivo '{$rutaRelativa}' no existe o está fuera del alcance."];
    }

    return ['status' => 'ok', 'archivo' => $rutaRelativa, 'contenido' => file_get_contents($rutaAbsoluta)];
}

function tool_escribir_archivo($rutaRelativa, $contenido) {
    if (empty($rutaRelativa)) return ['status' => 'error', 'mensaje' => 'Ruta de destino no especificada.'];

    $baseDir = realpath(__DIR__ . '/../');
    $rutaAbsoluta = $baseDir . DIRECTORY_SEPARATOR . ltrim($rutaRelativa, '/\\');
    $dirDestino = dirname($rutaAbsoluta);

    if (!is_dir($dirDestino)) {
        @mkdir($dirDestino, 0755, true);
    }

    if (file_exists($rutaAbsoluta)) {
        @copy($rutaAbsoluta, $rutaAbsoluta . '.bak');
    }

    if (file_put_contents($rutaAbsoluta, $contenido) !== false) {
        return ['status' => 'ok', 'mensaje' => "Archivo '{$rutaRelativa}' guardado con éxito."];
    }
    return ['status' => 'error', 'mensaje' => "Error al escribir en '{$rutaRelativa}'."];
}

function tool_backup_tabla($tabla) {
    global $pdo;
    if (!$pdo) return ['status' => 'error', 'mensaje' => 'Sin conexión a BD.'];

    $tablaSanitizada = preg_replace('/[^a-zA-Z0-9_]/', '', $tabla);
    if (empty($tablaSanitizada)) return ['status' => 'error', 'mensaje' => 'Nombre de tabla inválido.'];

    $tablaBackup = $tablaSanitizada . '_bak_' . date('Ymd_His');

    try {
        $pdo->exec("CREATE TABLE {$tablaBackup} LIKE {$tablaSanitizada}");
        $pdo->exec("INSERT INTO {$tablaBackup} SELECT * FROM {$tablaSanitizada}");
        return ['status' => 'ok', 'mensaje' => "Backup creado: {$tablaBackup}"];
    } catch (Exception $e) {
        return ['status' => 'error', 'mensaje' => 'Error al crear backup: ' . $e->getMessage()];
    }
}

function ejecutarHerramientaNIAH($nombreTool, $parametros = []) {
    if (function_exists('registrarLog')) {
        registrarLog('niah_tools', 'ejecucion', "Invocando herramienta: {$nombreTool}", 'info');
    }

    $ruta = $parametros['ruta'] ?? $parametros['archivo'] ?? '';

    switch ($nombreTool) {
        case 'ejecutar_sql':
            return tool_ejecutar_sql($parametros['query'] ?? '', $parametros['params'] ?? []);
        case 'leer_archivo':
            return tool_leer_archivo($ruta);
        case 'escribir_archivo':
            return tool_escribir_archivo($ruta, $parametros['contenido'] ?? '');
        case 'backup_tabla':
            return tool_backup_tabla($parametros['tabla'] ?? '');
        default:
            return ['status' => 'error', 'mensaje' => "Herramienta '{$nombreTool}' no reconocida."];
    }
}