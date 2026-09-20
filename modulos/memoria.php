<?php
// modulos/memoria.php
require_once __DIR__ . '/../config/db.php';

// Manejo de acciones directas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear' && !empty($_POST['clave']) && !empty($_POST['valor'])) {
        $tipo = $_POST['tipo'] ?? 'regla';
        $relevancia = (int)($_POST['relevancia'] ?? 1);
        
        $stmt = $pdo->prepare("INSERT INTO nexus_memoria (tipo, clave, valor, relevancia) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tipo, trim($_POST['clave']), trim($_POST['valor']), $relevancia]);
    } elseif ($accion === 'eliminar' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("DELETE FROM nexus_memoria WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Consultar memorias registradas
$memorias = $pdo->query("SELECT * FROM nexus_memoria ORDER BY relevancia DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="modulo-tareas" style="margin-top: 20px;">
    <h2>Memoria del Sistema (Nexus)</h2>
    
    <!-- Formulario para agregar reglas/memorias -->
    <form method="POST" class="form-tarea" style="flex-wrap: wrap;">
        <input type="hidden" name="accion" value="crear">
        
        <select name="tipo" style="background: #0f111a; color: #fff; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px;">
            <option value="regla">Regla</option>
            <option value="preferencia">Preferencia</option>
            <option value="aprendizaje">Aprendizaje</option>
            <option value="meta">Meta</option>
        </select>
        
        <input type="text" name="clave" placeholder="Clave (ej: idioma, tono)" required style="flex: 1; min-width: 150px;">
        <input type="text" name="valor" placeholder="Valor / Instrucción..." required style="flex: 2; min-width: 200px;">
        
        <button type="submit">Guardar Memoria</button>
    </form>

    <!-- Listado de Memorias -->
    <h3>Reglas y Memorias Activas</h3>
    <ul class="lista-tareas">
        <?php foreach ($memorias as $m): ?>
            <li>
                <span>
                    <strong style="color: #4a6fa5;">[<?= strtoupper(htmlspecialchars($m['tipo'])) ?>]</strong> 
                    <?= htmlspecialchars($m['clave']) ?>: <em><?= htmlspecialchars($m['valor']) ?></em>
                </span>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn-undo" style="background: #c62828; color: #fff;">Eliminar</button>
                </form>
            </li>
        <?php endforeach; ?>
        <?php if (empty($memorias)): ?>
            <li class="vacio">No hay registros de memoria definidos.</li>
        <?php endif; ?>
    </ul>
</div>