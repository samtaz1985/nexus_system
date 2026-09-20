<?php
// modulos/tareas.php
require_once __DIR__ . '/../config/db.php';

// Manejo de acciones POST tradicionales (Agregar / Completar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_tarea'])) {
    if ($_POST['accion_tarea'] === 'agregar' && !empty($_POST['texto'])) {
        $stmt = $pdo->prepare("INSERT INTO tareas (texto, completada) VALUES (?, 0)");
        $stmt->execute([trim($_POST['texto'])]);
    } elseif ($_POST['accion_tarea'] === 'completar' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE tareas SET completada = 1 WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    }
    header("Location: dashboard.php?mod=tareas");
    exit;
}

// Consultar tareas
$tareas = [];
if (isset($pdo)) {
    $stmt = $pdo->query("SELECT * FROM tareas ORDER BY completada ASC, id DESC");
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="module-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>📋 Gestión Autónoma de Tareas</h2>
    <button id="btnProcesarIA" class="btn-primary" style="background: var(--accent-color); color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: bold;">
        ⚡ Procesar Siguiente Tarea con NIAH
    </button>
</div>

<!-- Formulario para agregar tarea -->
<div class="card" style="background: var(--card-bg, #1e2230); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <form method="POST" style="display: flex; gap: 10px;">
        <input type="hidden" name="accion_tarea" value="agregar">
        <input type="text" name="texto" placeholder="Escribe una nueva tarea..." required style="flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #333; background: #0f111a; color: #fff;">
        <button type="submit" style="padding: 10px 20px; border-radius: 6px; background: #28a745; color: #fff; border: none; cursor: pointer;">Guardar Tarea</button>
    </form>
</div>

<!-- Lista de Tareas -->
<div class="tareas-container" style="display: flex; flex-direction: column; gap: 12px;">
    <?php if (empty($tareas)): ?>
        <p style="color: #888;">No hay tareas registradas en el sistema.</p>
    <?php else: ?>
        <?php foreach ($tareas as $t): ?>
            <div class="card" style="background: var(--card-bg, #1e2230); padding: 16px; border-radius: 8px; border-left: 4px solid <?= $t['completada'] ? '#28a745' : '#ffc107' ?>;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: #888;">[ID <?= $t['id'] ?>]</strong>
                        <span style="<?= $t['completada'] ? 'text-decoration: line-through; color: #888;' : 'color: #fff;' ?>">
                            <?= htmlspecialchars($t['texto']) ?>
                        </span>
                    </div>
                    <?php if (!$t['completada']): ?>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="accion_tarea" value="completar">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" style="background: transparent; border: 1px solid #28a745; color: #28a745; padding: 4px 10px; border-radius: 4px; cursor: pointer;">
                                ✓ Marcar Completada
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Mapeo del Análisis de NIAH -->
                <?php if (!empty($t['resultado_ia'])): ?>
                    <div style="margin-top: 12px; padding: 10px; background: #0f111a; border-radius: 6px; font-size: 0.9em; border: 1px solid #2a2e3d;">
                        <strong style="color: var(--accent-color, #4e73df);">🤖 Análisis / Solución de NIAH:</strong>
                        <p style="margin: 6px 0 0 0; color: #d1d5db; white-space: pre-line;"><?= htmlspecialchars($t['resultado_ia']) ?></p>
                        <small style="color: #666; font-size: 0.8em; display: block; margin-top: 4px;">Procesado el: <?= $t['fecha_procesado'] ?></small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.getElementById('btnProcesarIA').addEventListener('click', async () => {
    const btn = document.getElementById('btnProcesarIA');
    btn.disabled = true;
    btn.innerText = '⏳ Procesando con NIAH...';

    try {
        const res = await fetch('cron/procesar_tareas.php');
        const data = await res.json();

        if (data.status === 'ok') {
            alert('Procesamiento completado: ' + (data.mensaje || 'Tarea ID ' + data.tarea_id + ' analizada.'));
            window.location.reload();
        } else {
            alert('Error: ' + data.mensaje);
        }
    } catch (e) {
        alert('Error al conectar con el motor autónomo.');
    } finally {
        btn.disabled = false;
        btn.innerText = '⚡ Procesar Siguiente Tarea con NIAH';
    }
});
</script>