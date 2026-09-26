<?php
// src/niah_motor.php
class NiahMotor {
    private $pdo;

    public function __construct($pdo) {
        $pdo = new PDO('mysql:host=localhost;dbname=niah_db;charset=utf8', 'root', ''); // Reemplaza con tus credenciales

        if (!$pdo) {
            throw new Exception("Error al conectar a la base de datos.");

        }    
        if ($pdo->errorCode() !== '00000') {
            throw new Exception("Error al conectar a la base de datos: " . $pdo->errorInfo()[2]);
            
}
$this->pdo = $pdo;
    }

    public function registrarReflexion($pensamiento, $tipo = 'general') {
        $stmt = $this->pdo->prepare("INSERT INTO niah_reflexiones (pensamiento, tipo) VALUES (?, ?)");
        return $stmt->execute([$pensamiento, $tipo]);
    }

    public function evaluarEstadoSistema() {
        // Consulta el estado de las tareas pendientes
        $stmt = $this->pdo->query("SELECT COUNT(*) as pendientes FROM tareas WHERE completada = 0");
        $res = $stmt->fetch();
        
        $pendientes = $res['pendientes'] ?? 0;
        
        if ($pendientes > 0) {
            $this->registrarReflexion("Se detectaron {$pendientes} tareas pendientes. Iniciando ciclo de análisis lógico.", 'cognicion');
        } else {
            $this->registrarReflexion("Sistema estable. Sin tareas pendientes en cola.", 'equilibrio');
        }
    }
}

// Ejecución del ciclo
$niah = new NiahMotor($pdo);
$niah->evaluarEstadoSistema();
echo "[NIAH] Ciclo cognitivo ejecutado y registrado correctamente.";
?>