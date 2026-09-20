// js/subsistema.js

const SubsistemaIA = {
    async generarRespuesta(promptUsuario) {
        const formData = new FormData();
        formData.append('mensaje', promptUsuario);

        const res = await fetch('config/acciones.php?accion=enviar_mensaje', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        if (data.status === 'ok') {
            return data.respuesta;
        } else {
            throw new Error(data.mensaje || 'Error en el motor local.');
        }
    }
};

async function verificarEstadoIA() {
    try {
        const res = await fetch('api/subsistema.php?accion=estado');
        const data = await res.json();
        actualizarUIMotor(data.activo);
    } catch (e) {
        actualizarUIMotor(false);
    }
}

function actualizarUIMotor(activo) {
    const dots = document.querySelectorAll('.ia-status-dot');
    const texts = document.querySelectorAll('.ia-status-text');
    const btns = document.querySelectorAll('.ia-status-btn');

    dots.forEach(dot => {
        dot.style.background = activo ? '#00e676' : '#ff4d4d';
    });

    texts.forEach(text => {
        text.innerText = activo ? 'Online' : 'Offline';
    });

    btns.forEach(btn => {
        if (activo) {
            btn.innerText = 'Apagar Motor';
            btn.style.background = '#d32f2f';
            btn.style.color = '#fff';
        } else {
            btn.innerText = 'Encender Motor';
            btn.style.background = 'var(--accent-blue, #00d2ff)';
            btn.style.color = '#000';
        }
        btn.disabled = false;
    });
}

async function toggleMotorIA() {
    const btns = document.querySelectorAll('.ia-status-btn');
    const texts = document.querySelectorAll('.ia-status-text');

    btns.forEach(btn => btn.disabled = true);
    texts.forEach(text => text.innerText = 'Procesando...');

    const estaActivo = Array.from(btns).some(b => b.innerText.includes('Apagar'));
    const accion = estaActivo ? 'detener' : 'iniciar';

    try {
        await fetch(`api/subsistema.php?accion=${accion}`);
        setTimeout(verificarEstadoIA, 4000);
    } catch (e) {
        verificarEstadoIA();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    verificarEstadoIA();
    setInterval(verificarEstadoIA, 5000);
});