const estadoMotorGlobal = {
    fase: 'verificando', // 'verificando', 'offline', 'iniciando', 'online', 'deteniendo'
    timerSneo: null,
    ultimoCambioManual: 0
};

document.addEventListener("DOMContentLoaded", function() {
    // Primera comprobación al cargar la página
    status_api_motor_fase_verificar();

    // Comprobación de fondo cada 6 segundos, solo si el usuario no ha tocado nada recientemente
    setInterval(() => {
        const ahora = Date.now();
        // Si pasaron menos de 10 segundos desde la última acción manual, ignoramos el intervalo de fondo
        if (ahora - estadoMotorGlobal.ultimoCambioManual < 10000) return;
        
        if (estadoMotorGlobal.fase === 'offline' || estadoMotorGlobal.fase === 'online') {
            status_api_motor_fase_verificar(true);
        }
    }, 6000);
});

function status_api_motor_fase_verificar(silencioso = false) {
    if (estadoMotorGlobal.fase === 'iniciando' || estadoMotorGlobal.fase === 'deteniendo') return;

    fetch('api/status_motor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'estado' })
    })
    .then(response => response.json())
    .then(data => {
        if (data && typeof data.activo !== 'undefined') {
            if (data.activo && estadoMotorGlobal.fase !== 'online') {
                estadoMotorGlobal.fase = 'online';
                status_api_motor_renderizarUI(true);
            } else if (!data.activo && estadoMotorGlobal.fase !== 'offline') {
                estadoMotorGlobal.fase = 'offline';
                status_api_motor_renderizarUI(false);
            }
        }
    })
    .catch(error => console.error('Error de red en verificación:', error));
}

function status_api_motor_control(accion) {
    estadoMotorGlobal.ultimoCambioManual = Date.now();

    if (accion === 'iniciar') {
        estadoMotorGlobal.fase = 'iniciando';
        status_api_motor_renderizarUI_procesando('Iniciando motor, esperando puerto 5001...');

        fetch('api/status_motor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'iniciar' })
        })
        .then(response => response.json())
        .then(() => {
            let intentos = 0;
            if (estadoMotorGlobal.timerSneo) clearInterval(estadoMotorGlobal.timerSneo);

            // Sondeo estricto cada 2 segundos hasta 12 intentos (24 segundos)
            estadoMotorGlobal.timerSneo = setInterval(() => {
                intentos++;
                fetch('api/status_motor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'estado' })
                })
                .then(res => res.json())
                .then(estadoData => {
                    if (estadoData.activo) {
                        clearInterval(estadoMotorGlobal.timerSneo);
                        estadoMotorGlobal.fase = 'online';
                        status_api_motor_renderizarUI(true);
                    } else if (intentos >= 12) {
                        clearInterval(estadoMotorGlobal.timerSneo);
                        estadoMotorGlobal.fase = 'offline';
                        status_api_motor_renderizarUI(false);
                    }
                })
                .catch(() => {
                    if (intentos >= 12) {
                        clearInterval(estadoMotorGlobal.timerSneo);
                        estadoMotorGlobal.fase = 'offline';
                        status_api_motor_renderizarUI(false);
                    }
                });
            }, 2000);
        })
        .catch(error => {
            console.error('Error al iniciar:', error);
            estadoMotorGlobal.fase = 'offline';
            status_api_motor_renderizarUI(false);
        });

    } else if (accion === 'detener') {
        estadoMotorGlobal.fase = 'deteniendo';
        if (estadoMotorGlobal.timerSneo) clearInterval(estadoMotorGlobal.timerSneo);
        status_api_motor_renderizarUI_procesando('Deteniendo servicio...');

        fetch('api/status_motor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'detener' })
        })
        .then(response => response.json())
        .then(() => {
            setTimeout(() => {
                estadoMotorGlobal.fase = 'offline';
                status_api_motor_renderizarUI(false);
            }, 2000);
        })
        .catch(error => {
            console.error('Error al detener:', error);
            estadoMotorGlobal.fase = 'offline';
            status_api_motor_renderizarUI(false);
        });
    }
}

function status_api_motor_renderizarUI_procesando(mensaje) {
    const badgeSup = document.getElementById('estado-motor-badge');
    const btnActivar = document.getElementById('btn-activar-motor');
    const btnDetener = document.getElementById('btn-detener-motor');
    const detalles = document.getElementById('detalles-motor');

    if (btnActivar) btnActivar.disabled = true;
    if (btnDetener) btnDetener.disabled = true;

    if (badgeSup) {
        badgeSup.style.background = '#f59e0b';
        badgeSup.textContent = 'PROCESANDO...';
    }
    if (detalles) detalles.textContent = mensaje;
}

function status_api_motor_renderizarUI(activo) {
    const badgeSup = document.getElementById('estado-motor-badge');
    const btnActivar = document.getElementById('btn-activar-motor');
    const btnDetener = document.getElementById('btn-detener-motor');
    const detalles = document.getElementById('detalles-motor');

    if (badgeSup && btnActivar && btnDetener) {
        btnActivar.disabled = false;
        btnDetener.disabled = false;

        if (activo) {
            badgeSup.style.background = '#10b981';
            badgeSup.textContent = 'ONLINE (Operativo)';
            btnActivar.style.display = 'none';
            btnDetener.style.display = 'inline-block';
            if (detalles) detalles.textContent = 'El motor de IA está activo y respondiendo en el puerto 5001.';
        } else {
            badgeSup.style.background = '#ef4444';
            badgeSup.textContent = 'OFFLINE (Detenido)';
            btnActivar.style.display = 'inline-block';
            btnDetener.style.display = 'none';
            if (detalles) detalles.textContent = 'El servicio se encuentra inactivo. Presione iniciar.';
        }
    }

    const elemIA = document.getElementById('dash-ia-status');
    if (elemIA) {
        elemIA.innerText = activo ? 'ONLINE' : 'OFFLINE';
        elemIA.style.color = activo ? '#00e676' : '#ff4d4d';
    }

    const sidebarStatus = document.getElementById('sidebar-motor-status');
    const sidebarCard = document.getElementById('sidebar-motor-card');
    if (sidebarStatus) {
        sidebarStatus.textContent = (activo ? '● ONLINE' : '● OFFLINE');
        sidebarStatus.style.color = (activo ? '#00e676' : '#ff4d4d');
    }
    if (sidebarCard) {
        sidebarCard.style.background = (activo ? 'rgba(0, 230, 118, 0.15)' : 'rgba(255, 77, 77, 0.15)');
    }
}