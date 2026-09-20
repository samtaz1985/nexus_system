<?php
// modulos/tareas.php
require_once __DIR__ . '/../config/db.php';

// Manejo de acciones directas desde la interfaz
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear' && !empty($_POST['texto'])) {
        $stmt = $pdo->prepare("INSERT INTO tareas (texto, completada) VALUES (?, 0)");
        $stmt->execute([trim($_POST['texto'])]);
    } elseif ($accion === 'completar' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE tareas SET completada = 1 WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
    } elseif ($accion === 'reactivar' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE tareas SET completada = 0 WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Consultar tareas activas y completadas
$pendientes = $pdo->query("SELECT * FROM tareas WHERE completada = 0 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$completadas = $pdo->query("SELECT * FROM tareas WHERE completada = 1 ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="modulo-tareas">
    <h2>Gestión de Tareas</h2>
    
    <!-- Formulario de creación -->
    <form method="POST" class="form-tarea">
        <input type="hidden" name="accion" value="crear">
        <input type="text" name="texto" placeholder="Nueva tarea..." required autocomplete="off">
        <button type="submit">Agregar</button>
    </form>

    <!-- Listado Pendientes -->
    <h3>Pendientes</h3>
    <ul class="lista-tareas">
        <?php foreach ($pendientes as $t): ?>
            <li>
                <span>[ID <?= $t['id'] ?>] <?= htmlspecialchars($t['texto']) ?></span>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="accion" value="completar">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" class="btn-check">✓ Completar</button>
                </form>
            </li>
        <?php endforeach; ?>
        <?php if (empty($pendientes)): ?>
            <li class="vacio">No hay tareas pendientes.</li>
        <?php endif; ?>
    </ul>

    <!-- Listado Completadas -->
    <?php if (!empty($completadas)): ?>
        <h3>Completadas Recientemente</h3>
        <ul class="lista-tareas completadas">
            <?php foreach ($completadas as $t): ?>
                <li>
                    <del>[ID <?= $t['id'] ?>] <?= htmlspecialchars($t['texto']) ?></del>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="accion" value="reactivar">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button type="submit" class="btn-undo">↩ Reabrir</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
