<!-- modulos/chat.php -->
<div class="card">
    <h2>🤖 Chat Nexus System / NIAH</h2>
    
    <!-- Ventana de conversación -->
    <div id="chat-box" style="width: 100%; height: 420px; overflow-y: auto; background: #0f111a; border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; margin: 15px 0; display: flex; flex-direction: column; gap: 12px;">
        <div style="color: var(--text-muted); font-size: 0.85rem; text-align: center;">Cargando historial de conversación...</div>
    </div>

    <!-- Área de entrada -->
    <div style="display: flex; gap: 10px; width: 100%;">
        <input type="text" id="chat-input" placeholder="Escribe un mensaje para NIAH..." style="flex: 1; padding: 12px; background: #0f111a; border: 1px solid var(--border-color); color: #fff; border-radius: 6px; font-size: 0.95rem;" onkeypress="if(event.key === 'Enter') enviarMensajeChat()">
        <button id="btn-chat-send" onclick="enviarMensajeChat()" style="padding: 12px 25px; background: var(--accent-blue); color: #000; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; font-size: 0.95rem;">Enviar</button>
    </div>
</div>

<script>
(function() {
    const chatBox = document.getElementById('chat-box');
    const chatInput = document.getElementById('chat-input');
    const btnSend = document.getElementById('btn-chat-send');
    let cargandoRespuesta = false;

    // Cargar historial al iniciar
    async function cargarHistorialChat() {
        try {
            const res = await fetch('config/acciones.php?accion=obtener_historial');
            const data = await res.json();
            
            chatBox.innerHTML = '';
            if (data.status === 'ok' && data.mensajes && data.mensajes.length > 0) {
                data.mensajes.forEach(msg => {
                    const emisor = msg.remitente || msg.emisor;
                    agregarBurbuja(emisor, msg.mensaje);
                });
            } else {
                chatBox.innerHTML = '<div style="color: var(--text-muted); font-size: 0.85rem; text-align: center;">No hay mensajes previos. ¡Inicia la conversación!</div>';
            }
            scrollToBottom();
        } catch (e) {
            chatBox.innerHTML = '<div style="color: #ff4d4d; font-size: 0.85rem; text-align: center;">Error al cargar el historial.</div>';
        }
    }

    function agregarBurbuja(emisor, texto, esTemporal = false) {
        const div = document.createElement('div');
        const esUsuario = emisor === 'usuario' || emisor === 'user';
        
        if (esTemporal) div.id = 'burbuja-espera';

        div.style.maxWidth = '80%';
        div.style.padding = '10px 14px';
        div.style.borderRadius = '8px';
        div.style.fontSize = '0.9rem';
        div.style.lineHeight = '1.4';
        div.style.alignSelf = esUsuario ? 'flex-end' : 'flex-start';
        div.style.background = esUsuario ? 'var(--accent-blue)' : '#1a1d29';
        div.style.color = esUsuario ? '#000' : '#fff';
        div.style.border = esUsuario ? 'none' : '1px solid var(--border-color)';
        div.style.fontStyle = esTemporal ? 'italic' : 'normal';
        
        div.innerText = texto;
        chatBox.appendChild(div);
        scrollToBottom();
        return div;
    }

    function quitarBurbujaEspera() {
        const temp = document.getElementById('burbuja-espera');
        if (temp) temp.remove();
    }

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    window.enviarMensajeChat = async function() {
        const texto = chatInput.value.trim();
        
        // Prevenir envíos múltiples o vacíos
        if (!texto || cargandoRespuesta) return;

        // 1. Bloquear controles
        cargandoRespuesta = true;
        chatInput.value = '';
        chatInput.disabled = true;
        btnSend.disabled = true;
        btnSend.innerText = 'Pensando...';
        btnSend.style.opacity = '0.6';

        // 2. Renderizar mensaje del usuario y mensaje de espera
        agregarBurbuja('usuario', texto);
        agregarBurbuja('ia', '⏳ NIAH está procesando tu respuesta...', true);

        try {
            let respuestaTexto = '';

            // 3. Canalizar a través de js/subsistema.js si está disponible
            if (typeof SubsistemaIA !== 'undefined' && typeof SubsistemaIA.generarRespuesta === 'function') {
                respuestaTexto = await SubsistemaIA.generarRespuesta(texto);
            } else {
                // Fallback a acciones.php
                const formData = new FormData();
                formData.append('mensaje', texto);
                const res = await fetch('config/acciones.php?accion=enviar_mensaje', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'ok') {
                    respuestaTexto = data.respuesta;
                } else {
                    throw new Error(data.mensaje || 'Error en el motor');
                }
            }

            quitarBurbujaEspera();
            agregarBurbuja('ia', respuestaTexto);

        } catch (e) {
            quitarBurbujaEspera();
            agregarBurbuja('ia', '❌ Error: ' + e.message);
        } finally {
            // 4. Desbloquear interfaz
            cargandoRespuesta = false;
            chatInput.disabled = false;
            btnSend.disabled = false;
            btnSend.innerText = 'Enviar';
            btnSend.style.opacity = '1';
            chatInput.focus();
        }
    };

    cargarHistorialChat();
})();
</script>