<?php
// config/planner.php
require_once __DIR__ . '/memoria.php';

class PlannerManager {
    private $conexion;
    private $memoria;

    public function __construct($db) {
        $this->conexion = $db;
        $this->memoria = new MemoriaManager($db);
    }

    public function obtenerMemoria() {
        return $this->memoria;
    }

    public function analizarEstado() {
        $totalTareas = $this->conexion->query("SELECT COUNT(*) FROM tareas")->fetchColumn();
        $pendientes = $this->conexion->query("SELECT COUNT(*) FROM tareas WHERE completada = 0")->fetchColumn();
        $completadas = $this->conexion->query("SELECT COUNT(*) FROM tareas WHERE completada = 1")->fetchColumn();

        return [
            'metricas' => [
                'total' => $totalTareas,
                'pendientes' => $pendientes,
                'completadas' => $completadas
            ]
        ];
    }

    public function construirPromptSupervisor($contextoBase) {
        $analisis = $this->analizarEstado();
        $contextoMemoria = $this->memoria->obtenerContextoMemoria();
        
        $promptEnriquecido = $contextoBase . "\n\n";
        $promptEnriquecido .= "=== ESTADO DE MEMORIA E IDENTIDAD DE NEXUS ===\n";
        $promptEnriquecido .= $contextoMemoria . "\n\n";
        $promptEnriquecido .= "=== DIAGNÓSTICO EN TIEMPO REAL ===\n";
        $promptEnriquecido .= "- Tareas pendientes en sistema: {$analisis['metricas']['pendientes']}\n";

        return $promptEnriquecido;
    }
}