<?php
// includes/sidebar.php
require_once __DIR__ . '/../config/kobold_control.php';

$koboldSide = new KoboldControl();
$isOnline = $koboldSide->estaActivo();
$estadoMotor = $isOnline ? 'ONLINE' : 'OFFLINE';
$colorStatus = $isOnline ? '#00e676' : '#ff4d4d';
$bgStatus = $isOnline ? 'rgba(0, 230, 118, 0.15)' : 'rgba(255, 77, 77, 0.15)';
?>

<aside style="width: 240px; background: #1a1d29; border-right: 1px solid var(--border-color, #2a2d3d); height: 100vh; display: flex; flex-direction: column; padding: 20px 10px; box-sizing: border-box;">
    <div style="padding: 0 10px 20px 10px; border-bottom: 1px solid var(--border-color, #2a2d3d); margin-bottom: 20px;">
        <h3 style="margin: 0; color: #fff; font-size: 1.2rem;">Nexus System</h3>
        <span style="font-size: 0.75rem; color: #8a8f9d;">Panel de Control</span>
    </div>

    <nav style="display: flex; flex-direction: column; gap: 8px; flex: 1;">
        <a href="index.php?mod=dashboard" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">
            📊 <span>Dashboard</span>
        </a>
        <a href="index.php?mod=chat" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">
            🤖 <span>Chat NIAH</span>
        </a>
        <a href="index.php?mod=tareas" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">
            📋 <span>Gestor de Tareas</span>
        </a>
        <a href="index.php?mod=supervision" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">
            ⚙️ <span>Supervisión y Motor</span>
        </a>
    </nav>

    <!-- Tarjeta del motor con ID para actualización en tiempo real vía JavaScript -->
    <div id="sidebar-motor-card" style="padding: 12px; background: #0f111a; border: 1px solid var(--border-color, #2a2d3d); border-radius: 8px; text-align: center; margin-top: auto; background-color: <?php echo $bgStatus; ?>;">
        <div style="font-size: 0.8rem; color: #8a8f9d; margin-bottom: 6px;">Motor KoboldCpp</div>
        <div id="sidebar-motor-status" style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; color: <?php echo $colorStatus; ?>;">
            ● <?php echo $estadoMotor; ?>
        </div>
    </div>
</aside>