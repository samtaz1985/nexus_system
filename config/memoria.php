<?php
// config/memoria.php

class MemoriaManager {
    private $conexion;

    public function __construct($db) {
        $this->conexion = $db;
        $this->inicializarTabla();
    }

    private function inicializarTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS nexus_memoria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(50) NOT NULL,
            clave VARCHAR(100) NOT NULL,
            valor TEXT NOT NULL,
            relevancia INT DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_clave (clave)
        )";
        $this->conexion->exec($sql);
    }

    /**
     * Guarda o actualiza un recuerdo/regla en la memoria de Nexus.
     */
    public function recordar($tipo, $clave, $valor, $relevancia = 1) {
        $sql = "INSERT INTO nexus_memoria (tipo, clave, valor, relevancia) 
                VALUES (:tipo, :clave, :valor, :relevancia)
                ON DUPLICATE KEY UPDATE valor = :valor_upd, relevancia = :relevancia_upd";
        
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':tipo' => $tipo,
            ':clave' => $clave,
            ':valor' => $valor,
            ':relevancia' => $relevancia,
            ':valor_upd' => $valor,
            ':relevancia_upd' => $relevancia
        ]);
    }

    /**
     * Obtiene recuerdos relevantes para inyectar en el pensamiento de Nexus.
     */
    public function obtenerContextoMemoria() {
        $stmt = $this->conexion->query("SELECT tipo, clave, valor FROM nexus_memoria ORDER BY relevancia DESC, id DESC LIMIT 15");
        $recuerdos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($recuerdos)) {
            return "No hay recuerdos almacenados en el núcleo de memoria.";
        }

        $contexto = "RECUERDOS Y REGLAS APRENDIDAS (NÚCLEO DE MEMORIA NEXUS):\n";
        foreach ($recuerdos as $r) {
            $contexto .= "- [{$r['tipo']}] {$r['clave']}: {$r['valor']}\n";
        }

        return $contexto;
    }
}