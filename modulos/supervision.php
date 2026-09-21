<div class="supervision-container" style="padding: 20px;">
    <div class="card" style="background: var(--bg-dark, #0f111a); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 20px; color: #fff;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 1.2rem;"><i class="fas fa-microchip"></i> Panel de Supervisión y Control - NIAH</h3>
            <span id="estado-motor-badge" style="padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; background: #6c757d; color: #fff;">Verificando...</span>
        </div>
        
        <div class="card-body">
            <p style="color: #a0aec0; font-size: 0.9rem; margin-bottom: 20px;">Gestión independiente del motor de IA mediante `status_motor.php`.</p>
            
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h4 style="font-size: 1rem; margin: 0 0 5px 0; color: #fff;">Estado del Servicio</h4>
                    <p id="detalles-motor" style="font-size: 0.85rem; color: #38bdf8; margin: 0;">Comprobando conectividad...</p>
                </div>
                
                <div>
                    <button id="btn-activar-motor" class="btn btn-success d-none" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;" onclick="status_api_motor_control('iniciar')">
                        <i class="fas fa-play"></i> Iniciar Motor NIAH
                    </button>
                    <button id="btn-detener-motor" class="btn btn-danger d-none" style="background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;" onclick="status_api_motor_control('detener')">
                        <i class="fas fa-stop"></i> Detener Motor
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>