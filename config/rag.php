<?php
// config/rag.php
require_once __DIR__ . '/db.php';

function obtenerContextoRelevante($mensajeUsuario, $limit = 5) {
    global $pdo;
    if (!isset($pdo)) return "";

    // Sanitizar y extraer palabras clave significativas (longitud > 3)
    $palabras = preg_split('/\s+/', mb_strtolower($mensajeUsuario));
    $palabrasClave = array_filter($palabras, function($p) {
        return mb_strlen($p) > 3;
    });

    if (empty($palabrasClave)) {
        // Si la frase es muy corta o genérica, traer las memorias con mayor relevancia
        $stmt = $pdo->prepare("SELECT tipo, clave, valor FROM nexus_memoria ORDER BY relevancia DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $memorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Construir consulta dinámica con LIKE por cada palabra clave
        $whereClauses = [];
        $params = [];

        foreach ($palabrasClave as $p) {
            $whereClauses[] = "(LOWER(clave) LIKE ? OR LOWER(valor) LIKE ? OR LOWER(tipo) LIKE ?)";
            $term = '%' . $p . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql = "SELECT tipo, clave, valor, relevancia FROM nexus_memoria WHERE " . implode(' OR ', $whereClauses) . " ORDER BY relevancia DESC LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $memorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si no hay coincidencias específicas, recurrir a las memorias más relevantes por defecto
        if (empty($memorias)) {
            $stmtDef = $pdo->prepare("SELECT tipo, clave, valor FROM nexus_memoria ORDER BY relevancia DESC LIMIT ?");
            $stmtDef->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmtDef->execute();
            $memorias = $stmtDef->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (empty($memorias)) return "";

    $mLista = [];
    foreach ($memorias as $m) {
        $mLista[] = "- " . strtoupper($m['tipo']) . " (" . $m['clave'] . "): " . $m['valor'];
    }

    return implode("\n", $mLista);
}