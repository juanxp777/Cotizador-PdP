<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// Actualizar parámetros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['param'] as $nombre => $valor) {
        $stmt = $db->prepare("UPDATE parametros SET valor = ? WHERE nombre_param = ?");
        $stmt->execute([floatval($valor), $nombre]);
    }
    $mensaje = "Parámetros actualizados correctamente";
}

// Cargar parámetros por categoría
$categorias = [];
$stmt = $db->query("SELECT * FROM parametros ORDER BY categoria, nombre_param");
while ($row = $stmt->fetch()) {
    $categorias[$row['categoria']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parámetros - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2d3748;
            font-size: 24px;
        }
        
        .nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .nav a {
            padding: 12px 24px;
            background: white;
            color: #4a5568;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .nav a:hover, .nav a.active {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .param-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .param-item {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .param-item label {
            display: block;
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .param-item small {
            display: block;
            color: #718096;
            font-size: 12px;
            margin-top: 5px;
        }
        
        input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            color: #2d3748;
        }
        
        input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }
        
        .categoria-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .badge-digital { background: #e9d5ff; color: #6b21a8; }
        .badge-offset { background: #dbeafe; color: #1e40af; }
        .badge-papel { background: #fef3c7; color: #92400e; }
        .badge-plastificado { background: #fce7f3; color: #9f1239; }
        .badge-encuadernacion { background: #d1fae5; color: #065f46; }
        .badge-acabados { background: #fed7aa; color: #9a3412; }
        .badge-logistica { background: #e0e7ff; color: #3730a3; }
        .badge-general { background: #e2e8f0; color: #1a202c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>⚙️ Configuración de Parámetros</h1>
                <p style="color: #718096; font-size: 14px;">Ajusta los costos de impresión</p>
            </div>
            <div style="color: #718096; font-size: 14px;">
                <?php echo htmlspecialchars($_SESSION['nombre']); ?> | 
                <a href="logout.php" style="color: #e53e3e;">Salir</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="index.php">📊 Cotizar</a>
            <a href="historial.php">📋 Historial</a>
            <a href="parametros.php" class="active">⚙️ Parámetros</a>
        </div>
        
        <?php if (isset($mensaje)): ?>
            <div class="alert">✅ <?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php 
            $categoria_nombres = [
                'digital' => ['nombre' => 'Impresión Digital', 'badge' => 'badge-digital'],
                'offset' => ['nombre' => 'Impresión Offset', 'badge' => 'badge-offset'],
                'papel' => ['nombre' => 'Papel', 'badge' => 'badge-papel'],
                'plastificado' => ['nombre' => 'Plastificado', 'badge' => 'badge-plastificado'],
                'encuadernacion' => ['nombre' => 'Encuadernación', 'badge' => 'badge-encuadernacion'],
                'acabados' => ['nombre' => 'Acabados Especiales', 'badge' => 'badge-acabados'],
                'logistica' => ['nombre' => 'Empaque y Transporte', 'badge' => 'badge-logistica'],
                'general' => ['nombre' => 'General', 'badge' => 'badge-general']
            ];
            
            foreach ($categorias as $categoria => $params): 
                $cat_info = $categoria_nombres[$categoria] ?? ['nombre' => $categoria, 'badge' => 'badge-general'];
            ?>
                <div class="card">
                    <span class="categoria-badge <?php echo $cat_info['badge']; ?>"><?php echo $cat_info['nombre']; ?></span>
                    <h2><?php echo $cat_info['nombre']; ?></h2>
                    <div class="param-grid">
                        <?php foreach ($params as $param): ?>
                            <div class="param-item">
                                <label><?php echo htmlspecialchars($param['descripcion']); ?></label>
                                <input type="number" 
                                       name="param[<?php echo htmlspecialchars($param['nombre_param']); ?>]" 
                                       value="<?php echo $param['valor']; ?>" 
                                       step="0.01" 
                                       min="0"
                                       required>
                                <small>Última actualización: <?php echo date('d/m/Y H:i', strtotime($param['fecha_actualizacion'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn-save">💾 Guardar Cambios</button>
        </form>
    </div>
</body>
</html>