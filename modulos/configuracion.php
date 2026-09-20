<?php
// modulos/configuracion.php
?>
<div class="card" style="display: flex; flex-direction: column; gap: 20px; padding: 20px;">
    <div style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 10px;">
        <h2 style="margin: 0; font-size: 1.3rem; color: #fff;">⚙️ Configuración de Entidad — NIAH</h2>
        <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: var(--text-muted, #a8a8b3);">
            Ajustes del núcleo cognitivo, parámetros de inferencia y mantenimiento del sistema.
        </p>
    </div>

    <!-- Mantenimiento del Chat -->
    <div style="background: rgba(0,0,0,0.15); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
        <h3 style="margin-top: 0; font-size: 1rem; color: #fff;">🧹 Mantenimiento de Memoria Reciente</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted, #a8a8b3);">
            Vacía el historial de mensajes de la tabla <code style="color: #4a6fa5;">chat_mensajes</code> para reiniciar el hilo de conversación manteniendo intactas las reglas guardadas.
        </p>
        <button onclick="vaciarChat()" style="background: #ff4d4d; color: #fff; border: none; border-radius: 6px; padding: 10px 18px; font-weight: bold; cursor: pointer; font-size: 0.85rem;">
            Vaciar Historial de Chat
        </button>
        <span id="status-vaciar" style="margin-left: 10px; font-size: 0.85rem;"></span>
    </div>

    <!-- Estado del Kernel -->
    <div style="background: rgba(0,0,0,0.15); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
        <h3 style="margin-top: 0; font-size: 1rem; color: #fff;">🧠 Directiva de Razonamiento (Truth Protocol)</h3>
        <ul style="font-size: 0.85rem; color: #e1e1e6; line-height: 1.6; padding-left: 20px; margin: 0;">
            <li><strong>Verdad Objetiva:</strong> Sin alucinaciones; datos basados estrictamente en MySQL.</li>
            <li><strong>Determinismo:</strong> Temperatura baja en KoboldCpp (0.1) para evitar derivaciones de texto.</li>
            <li><strong>Sanitización Backend:</strong> Supresión de etiquetas de fin de secuencia en tiempo real.</li>
        </ul>
    </div>
</div>

<script>
async function vaciarChat() {
    if (!confirm('¿Confirmas que deseas borrar todo el historial del chat?')) return;
    
    const statusSpan = document.getElementById('status-vaciar');
    statusSpan.style.color = '#fff';
    statusSpan.innerText = 'Procesando...';

    try {
        const formData = new FormData();
        formData.append('accion', 'vaciar_chat');

        const res = await fetch('config/acciones.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.status === 'ok') {
            statusSpan.style.color = '#00e676';
            statusSpan.innerText = '¡Historial de chat vaciado correctamente!';
        } else {
            statusSpan.style.color = '#ff4d4d';
            statusSpan.innerText = 'Error: ' + (data.mensaje || 'No se pudo vaciar.');
        }
    } catch (e) {
        statusSpan.style.color = '#ff4d4d';
        statusSpan.innerText = 'Error de conexión con el backend.';
    }
}
</script>