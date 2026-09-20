<?php
// config/kobold_control.php

class KoboldControl {
    private $host = 'http://127.0.0.1:5001';
    private $launcherPath;
    private $workingDir;

    public function __construct() {
        // Normalizar rutas para evitar fallos de escape en Windows
        $this->workingDir = __DIR__;
        $this->launcherPath = __DIR__ . DIRECTORY_SEPARATOR . 'lanzador_ia.exe';
    }

    /**
     * Verifica si el puerto 5001 está respondiendo.
     */
    public function estaActivo() {
        $ch = curl_init($this->host . '/api/v1/model');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200);
    }

    /**
     * Inicia el subsistema ejecutando lanzador_ia.exe forzando el directorio de trabajo.
     */
    public function iniciar() {
        if ($this->estaActivo()) {
            return ["status" => "ok", "message" => "El subsistema ya está activo."];
        }

        if (!file_exists($this->launcherPath)) {
            return ["status" => "error", "message" => "No se encontró el ejecutable: " . $this->launcherPath];
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // cd /d impone el directorio donde reside lanzador_ia.exe antes de invocarlo
            $cmd = 'cmd /c "cd /d "' . $this->workingDir . '" && start /B "" "' . $this->launcherPath . '""';
            pclose(popen($cmd, "r"));
        } else {
            $cmd = 'cd "' . $this->workingDir . '" && "' . $this->launcherPath . '" > /dev/null 2>&1 &';
            exec($cmd);
        }

        return ["status" => "ok", "message" => "Iniciando subsistema IA en segundo plano..."];
    }

    /**
     * Detiene el proceso koboldcpp.exe y el lanzador si sigue en ejecución.
     */
    public function detener() {
        if (!$this->estaActivo()) {
            return ["status" => "ok", "message" => "El subsistema ya está apagado."];
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("taskkill /F /IM koboldcpp.exe /T 2>&1");
            exec("taskkill /F /IM lanzador_ia.exe /T 2>&1");
        } else {
            exec("pkill -f koboldcpp");
            exec("pkill -f lanzador_ia");
        }

        return ["status" => "ok", "message" => "Subsistema detenido correctamente."];
    }
}