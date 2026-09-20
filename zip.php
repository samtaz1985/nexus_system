<?php
// zip.php - Empaquetado de Nexus System v1.1.0
$zip = new ZipArchive();
$filename = "nexus_system_v1.1.0.zip";

if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(__DIR__) + 1);

            if (preg_match('/\.zip$/i', $relativePath) || $relativePath === 'zip.php') {
                continue;
            }

            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
    echo json_encode(['status' => 'ok', 'mensaje' => "Empaquetado exitoso: $filename"]);
} else {
    echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo crear el archivo ZIP.']);
}