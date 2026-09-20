<?php
// api/tareas.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// LISTAR TAREAS
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, texto, completada, fecha_creacion FROM tareas ORDER BY id DESC");
    echo json_encode(['status' => 'ok', 'tareas' => $stmt->fetchAll()]);
    exit;
}

// CREAR TAREA
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $texto = trim($input['texto'] ?? '');

    if ($texto === '') {
        echo json_encode(['status' => 'error', 'message' => 'El texto no puede estar vacío.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO tareas (texto) VALUES (?)");
    $stmt->execute([$texto]);

    echo json_encode(['status' => 'ok', 'id' => $pdo->lastInsertId(), 'texto' => $texto]);
    exit;
}

// CAMBIAR ESTADO (COMPLETADA)
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    $completada = isset($input['completada']) ? intval($input['completada']) : 0;

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tareas SET completada = ? WHERE id = ?");
    $stmt->execute([$completada, $id]);

    echo json_encode(['status' => 'ok']);
    exit;
}

// ELIMINAR TAREA
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'ok']);
    exit;
}