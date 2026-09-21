<?php
// config/kobold_control.php

class KoboldControl {
    private $puerto = 5001;
    private $host = '127.0.0.1';

    // Verifica si KoboldCpp responde en el puerto 5001
    public function estaActivo() {
        $connection = @fsockopen($this->host, $this->puerto, $errno, $errstr, 1.5);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        return false;
    }

    // Ejecuta el lanzador o el binario .exe directamente
    public function iniciar() {
        if ($this->estaActivo()) {
            return true; 
        }

        $baseDir = __DIR__;
        
        if (stristr(PHP_OS, 'WIN')) {
            // Busca el ejecutable .exe en la carpeta config o la ruta que manejes
            // Puedes apuntar directamente al ejecutable compilado
            $cmd = "start /B \"\" \"" . $baseDir . "\\lanzador_ia.exe\" > \"" . $baseDir . "\\kobold.log\" 2>&1";
            pclose(popen($cmd, "r"));
        } else {
            $cmd = "nohup \"" . $baseDir . "/lanzador_ia\" > \"" . $baseDir . "/kobold.log\" 2>&1 &";
            exec($cmd);
        }

        sleep(3); 
        return $this->estaActivo();
    }

    // Cierra el proceso si se requiere
    public function detener() {
        if (stristr(PHP_OS, 'WIN')) {
            exec("taskkill /F /IM lanzador_ia.exe /T 2>NUL");
            exec("taskkill /F /IM koboldcpp.exe /T 2>NUL");
        } else {
            exec("pkill -f lanzador_ia");
            exec("pkill -f koboldcpp");
        }
    }
}