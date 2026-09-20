// js/subsistema.js

document.addEventListener('DOMContentLoaded', () => {
    const btnIniciar = document.getElementById('btn-iniciar-ia');
    const btnDetener = document.getElementById('btn-detener-ia');
    const badgeEstado = document.getElementById('estado-ia-badge');

    // Función para consultar el estado del motor local
    function verificarEstadoIA() {
        fetch('api/subsistema.php?accion=estado')
            .then(res => res.json())
            .then(data => {
                if (badgeEstado) {
                    if (data.activo) {
                        badgeEstado.textContent = 'En Línea';
                        badgeEstado.className = 'badge bg-success';
                    } else {
                        badgeEstado.textContent = 'Fuera de Línea';
                        badgeEstado.className = 'badge bg-danger';
                    }
                }
            })
            .catch(err => console.error('Error al consultar estado de IA:', err));
    }

    // Encender motor local (KoboldCpp)
    if (btnIniciar) {
        btnIniciar.addEventListener('click', () => {
            btnIniciar.disabled = true;
            fetch('api/subsistema.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ accion: 'iniciar' })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.mensaje || 'Comando de inicio enviado.');
                setTimeout(verificarEstadoIA, 3000);
            })
            .catch(err => console.error('Error al iniciar IA:', err))
            .finally(() => btnIniciar.disabled = false);
        });
    }

    // Detener motor local
    if (btnDetener) {
        btnDetener.addEventListener('click', () => {
            btnDetener.disabled = true;
            fetch('api/subsistema.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ accion: 'detener' })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.mensaje || 'Comando de detención enviado.');
                setTimeout(verificarEstadoIA, 1000);
            })
            .catch(err => console.error('Error al detener IA:', err))
            .finally(() => btnDetener.disabled = false);
        });
    }

    // Monitoreo inicial de estado
    verificarEstadoIA();
});