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

        <!-- Sidebar / Navegación -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Contenido Dinámico de Módulos -->
        <main class="main-content" style="flex: 1; padding: 20px; box-sizing: border-box; overflow-y: auto;">
            <?php 
                // Lista blanca de módulos autorizados
                $modulosPermitidos = ['dashboard', 'chat', 'tareas', 'memoria', 'configuracion', 'logs'];
                
                // Sanitización del parámetro GET
                $modulo = isset($_GET['mod']) && in_array($_GET['mod'], $modulosPermitidos) ? $_GET['mod'] : 'dashboard';
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