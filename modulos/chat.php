<!-- modulos/chat.php -->
<div class="card">
    <h2>🤖 Chat Nexus System / NIAH</h2>
    
    <div id="chat-box" style="width: 100%; height: 420px; overflow-y: auto; background: #0f111a; border: 1px solid var(--border-color, #2a2d3d); border-radius: 8px; padding: 15px; margin: 15px 0; display: flex; flex-direction: column; gap: 12px;">
        <div style="color: #8a8f9d; font-size: 0.85rem; text-align: center;">Cargando historial de conversación...</div>
    </div>

    <div style="display: flex; gap: 10px; width: 100%;">
        <input type="text" id="chat-input" placeholder="Escribe un mensaje para NIAH..." style="flex: 1; padding: 12px; background: #0f111a; border: 1px solid var(--border-color, #2a2d3d); color: #fff; border-radius: 6px; font-size: 0.95rem;" onkeypress="if(event.key === 'Enter') enviarMensajeChat()">
        <button id="btn-chat-send" onclick="enviarMensajeChat()" style="padding: 12px 25px; background: #00d2ff; color: #000; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; font-size: 0.95rem;">Enviar</button>
    </div>
</div>

<script>
(function() {
    const chatBox = document.getElementById('chat-box');
    const chatInput = document.getElementById('chat-input');
    const btnSend = document.getElementById('btn-chat-send');
    let cargandoRespuesta = false;

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
                chatBox.innerHTML = '<div style="color: #8a8f9d; font-size: 0.85rem; text-align: center;">No hay mensajes previos. ¡Inicia la conversación!</div>';
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
        div.style.background = esUsuario ? '#00d2ff' : '#1a1d29';
        div.style.color = esUsuario ? '#000' : '#fff';
        div.style.border = esUsuario ? 'none' : '1px solid var(--border-color, #2a2d3d)';
        div.style.fontStyle = esTemporal ? 'italic' : 'normal';
        
        div.innerHTML = texto.replace(/\n/g, '<br>');
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
        if (!texto || cargandoRespuesta) return;

        cargandoRespuesta = true;
        chatInput.value = '';
        chatInput.disabled = true;
        btnSend.disabled = true;
        btnSend.innerText = 'Pensando...';
        btnSend.style.opacity = '0.6';

        agregarBurbuja('usuario', texto);
        agregarBurbuja('ia', '⏳ NIAH está procesando tu respuesta...', true);

        try {
            const formData = new FormData();
            formData.append('mensaje', texto);
            const res = await fetch('config/acciones.php?accion=enviar_mensaje', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            quitarBurbujaEspera();
            if (data.status === 'ok') {
                agregarBurbuja('ia', data.respuesta);
            } else {
                agregarBurbuja('ia', '❌ Error: ' + (data.mensaje || 'Error en el motor'));
            }
        } catch (e) {
            quitarBurbujaEspera();
            agregarBurbuja('ia', '❌ Error de comunicación con el servidor.');
        } finally {
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