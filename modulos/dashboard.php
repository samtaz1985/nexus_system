<?php
// modulos/dashboard.php
require_once __DIR__ . '/../config/db.php';

// Consultas directas de métricas para precargar datos rápido
$totalTareas = 0;
$totalMensajes = 0;
$totalMemorias = 0;

if (isset($pdo)) {
    try {
        // Tareas pendientes
        $stmtT = $pdo->query("SELECT COUNT(*) FROM tareas WHERE completada = 0");
        $totalTareas = $stmtT->fetchColumn();

        // Total mensajes
        $stmtM = $pdo->query("SELECT COUNT(*) FROM chat_mensajes");
        $totalMensajes = $stmtM->fetchColumn();

        // Total memorias/reglas activas
        $stmtMem = $pdo->query("SELECT COUNT(*) FROM nexus_memoria");
        $totalMemorias = $stmtMem->fetchColumn();
    } catch (Exception $e) {
        // Manejo silencioso de errores de BD
    }
}
?>

<div class="card">
    <h2>📊 Visión General del Sistema</h2>
    <p style="color: var(--text-muted, #a8a8b3); font-size: 0.9rem; margin-top: 5px;">
        Panel principal de administración de Nexus System.
    </p>

    <div class="grid-dashboard" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 15px;">
        
        <div class="metric-card" style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
            <h3 style="margin: 0; font-size: 0.85rem; color: var(--text-muted, #a8a8b3); text-transform: uppercase;">Estado del Motor</h3>
            <p id="dash-ia-status" style="font-size: 1.6rem; font-weight: bold; margin: 10px 0 0 0; color: #ff4d4d;">Cargando...</p>
        </div>

        <div class="metric-card" style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
            <h3 style="margin: 0; font-size: 0.85rem; color: var(--text-muted, #a8a8b3); text-transform: uppercase;">Tareas Pendientes</h3>
            <p id="dash-total-tareas" style="font-size: 1.6rem; font-weight: bold; margin: 10px 0 0 0; color: #4a6fa5;"><?= $totalTareas ?></p>
        </div>

        <div class="metric-card" style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
            <h3 style="margin: 0; font-size: 0.85rem; color: var(--text-muted, #a8a8b3); text-transform: uppercase;">Mensajes Guardados</h3>
            <p id="dash-total-chat" style="font-size: 1.6rem; font-weight: bold; margin: 10px 0 0 0; color: #fff;"><?= $totalMensajes ?></p>
        </div>

        <div class="metric-card" style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
            <h3 style="margin: 0; font-size: 0.85rem; color: var(--text-muted, #a8a8b3); text-transform: uppercase;">Memorias Activas</h3>
            <p id="dash-total-memoria" style="font-size: 1.6rem; font-weight: bold; margin: 10px 0 0 0; color: #00e676;"><?= $totalMemorias ?></p>
        </div>

    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <h2>🚀 Acceso Rápido</h2>
    <div style="display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap;">
        <a href="index.php?mod=chat" style="padding: 10px 20px; background: #4a6fa5; color: #fff; font-weight: bold; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">🤖 Ir al Chat IA</a>
        <a href="index.php?mod=tareas" style="padding: 10px 20px; background: rgba(255,255,255,0.08); color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">📋 Ver Tareas</a>
        <a href="index.php?mod=memoria" style="padding: 10px 20px; background: rgba(255,255,255,0.08); color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">💾 Gestionar Memoria</a>
        <!-- Nuevo acceso a Logs -->
        <a href="index.php?mod=logs" style="padding: 10px 20px; background: rgba(255,255,255,0.08); color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">📜 Auditoría Logs</a>
    </div>
</div>

<script>
(function() {
    async function cargarEstadoMotor() {
        try {
            const resIA = await fetch('api/subsistema.php?accion=estado');
            const dataIA = await resIA.json();
            const elem = document.getElementById('dash-ia-status');
            if (elem) {
                elem.innerText = dataIA.activo ? 'ONLINE' : 'OFFLINE';
                elem.style.color = dataIA.activo ? '#00e676' : '#ff4d4d';
            }
        } catch (e) {
            const elem = document.getElementById('dash-ia-status');
            if (elem) {
                elem.innerText = 'OFFLINE';
                elem.style.color = '#ff4d4d';
            }
        }
    }

    cargarEstadoMotor();
})();
</script>