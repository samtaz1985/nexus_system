<?php
// modulos/logs.php
require_once __DIR__ . '/../config/db.php';

$logs = [];
if (isset($pdo)) {
    $stmt = $pdo->query("SELECT * FROM nexus_logs ORDER BY id DESC LIMIT 50");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="module-header" style="margin-bottom: 20px;">
    <h2>📜 Auditoría y Registro de Logs</h2>
</div>

<div class="card" style="background: var(--card-bg, #1e2230); padding: 20px; border-radius: 8px; overflow-x: auto;">
    <?php if (empty($logs)): ?>
        <p style="color: #888;">No hay eventos registrados en la tabla de logs.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9em;">
            <thead>
                <tr style="border-bottom: 2px solid #2a2e3d; color: #888;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Origen</th>
                    <th style="padding: 10px;">Acción</th>
                    <th style="padding: 10px;">Detalle</th>
                    <th style="padding: 10px;">Estado</th>
                    <th style="padding: 10px;">Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): 
                    $badgeColor = '#17a2b8';
                    if ($log['estado'] === 'success') $badgeColor = '#28a745';
                    if ($log['estado'] === 'error') $badgeColor = '#dc3545';
                ?>
                    <tr style="border-bottom: 1px solid #2a2e3d; color: #d1d5db;">
                        <td style="padding: 10px; color: #666;"><?= $log['id'] ?></td>
                        <td style="padding: 10px;"><code><?= htmlspecialchars($log['origen']) ?></code></td>
                        <td style="padding: 10px; font-weight: bold;"><?= htmlspecialchars($log['accion']) ?></td>
                        <td style="padding: 10px; color: #aaa;"><?= htmlspecialchars($log['detalle']) ?></td>
                        <td style="padding: 10px;">
                            <span style="background: <?= $badgeColor ?>; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8em;">
                                <?= htmlspecialchars($log['estado']) ?>
                            </span>
                        </td>
                        <td style="padding: 10px; color: #666; font-size: 0.85em;"><?= $log['fecha'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>