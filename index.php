<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus System</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div class="app-container" style="display: flex; height: 100vh; width: 100%;">

        <!-- El sidebar debe estar dentro del contenedor flex para alinearse correctamente -->
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content" style="flex: 1; padding: 20px; box-sizing: border-box; overflow-y: auto;">
            <?php 
                $modulo = isset($_GET['mod']) ? $_GET['mod'] : 'dashboard';
                $archivo = "modulos/{$modulo}.php";

                if (file_exists($archivo)) {
                    include $archivo;
                } else {
                    echo "<div class='card'><h2>Módulo no encontrado</h2></div>";
                }
            ?>
        </main>
    
    </div>

    <script src="js/subsistema.js"></script>
</body>
</html>