<?php
// config/rag.php
require_once __DIR__ . '/db.php';

function obtenerContextoRelevante($mensajeUsuario, $pdoParam = null, $limit = 5) {
    global $pdo;
    $db = $pdoParam ?? $pdo;

    if (!isset($db)) {
        return "";
    }

    // Extraer palabras clave significativas (longitud > 3)
    $palabras = preg_split('/\s+/', mb_strtolower($mensajeUsuario));
    $palabrasClave = array_filter($palabras, function($p) {
        return mb_strlen($p) > 3;
    });

    if (empty($palabrasClave)) {
        // Frase corta o genérica: traer memorias con mayor relevancia
        $stmt = $db->prepare("SELECT tipo, clave, valor FROM nexus_memoria ORDER BY relevancia DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $memorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Consulta dinámica con LIKE por palabras clave en clave, valor o tipo
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
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $memorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Respaldar con memorias por defecto si no hay coincidencia
        if (empty($memorias)) {
            $stmtDef = $db->prepare("SELECT tipo, clave, valor FROM nexus_memoria ORDER BY relevancia DESC LIMIT ?");
            $stmtDef->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmtDef->execute();
            $memorias = $stmtDef->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (empty($memorias)) {
        return "";
    }

    $mLista = [];
    foreach ($memorias as $m) {
        $mLista[] = "- " . strtoupper($m['tipo']) . " (" . $m['clave'] . "): " . $m['valor'];
    }

    return implode("\n", $mLista);
}