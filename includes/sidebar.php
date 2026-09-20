<!-- includes/sidebar.php -->
<aside class="sidebar">
    <div class="brand">
        <h2 style="color: var(--accent-blue, #00d2ff); margin: 0; font-size: 1.2rem;">Nexus System</h2>
        <span style="color: #4cd137; font-size: 0.75rem;">● Sistema Operativo</span>
    </div>
    
    <!-- Control del Subsistema Nexus IA -->
    <div id="subsistema-control" style="margin: 20px 10px 10px 10px; padding: 12px; background: #1a1d29; border-radius: 8px; border: 1px solid var(--border-color, #2a2d3d); display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span style="color: #6c757d; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">Motor IA</span>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span class="ia-status-dot" style="width: 8px; height: 8px; border-radius: 50%; background: #ff4d4d; display: inline-block;"></span>
                <span class="ia-status-text" style="color: #fff; font-size: 0.8rem; font-weight: bold;">Offline</span>
            </div>
        </div>
        <button class="ia-status-btn" onclick="toggleMotorIA()" style="background: var(--accent-blue, #00d2ff); color: #000; font-weight: bold; border: none; padding: 8px; border-radius: 5px; cursor: pointer; font-size: 0.8rem; width: 100%; transition: all 0.2s;">
            Encender Motor
        </button>
    </div>

    <nav class="sidebar-nav" style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
        <a href="index.php?mod=dashboard" style="color: #fff; text-decoration: none; padding: 10px; border-radius: 6px; background: rgba(255,255,255,0.05); display: block;">📊 Dashboard</a>
        <a href="index.php?mod=chat" style="color: #fff; text-decoration: none; padding: 10px; border-radius: 6px; background: rgba(255,255,255,0.05); display: block;">🤖 Chat IA Local</a>
        <a href="index.php?mod=tareas" style="color: #fff; text-decoration: none; padding: 10px; border-radius: 6px; background: rgba(255,255,255,0.05); display: block;">📋 Tareas y Notas</a>
        <a href="index.php?mod=memoria" style="color: #fff; text-decoration: none; padding: 10px; border-radius: 6px; background: rgba(255,255,255,0.05); display: block;">🧠 Memoria</a>
        <a href="index.php?mod=configuracion" style="color: #fff; text-decoration: none; padding: 10px; border-radius: 6px; background: rgba(255,255,255,0.05); display: block;">⚙️ Configuración</a>
        <a href="index.php?mod=logs" style="color: #fff; text-decoration: none; padding: 10px; border-radius: 6px; background: rgba(255,255,255,0.05); display: block;">📜 Auditoría Logs</a>
    </nav>
</aside>