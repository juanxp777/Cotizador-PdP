<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header('Location: historial.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM cotizaciones WHERE id = ?");
$stmt->execute([$id]);
$cot = $stmt->fetch();

if (!$cot) {
    header('Location: historial.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estado'])) {
    $nuevo_estado = $_POST['estado'];
    $notas = $_POST['notas'] ?? '';
    
    $stmt = $db->prepare("UPDATE cotizaciones SET estado = ?, notas = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $notas, $id]);
    
    header("Location: ver_cotizacion.php?id=$id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización <?php echo htmlspecialchars($cot['folio']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .back-link { display: inline-block; color: #667eea; text-decoration: none; margin-bottom: 20px; font-weight: 600; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h2 { color: #2d3748; margin-bottom: 20px; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .info-item { background: #f7fafc; padding: 15px; border-radius: 8px; }
        .info-label { color: #718096; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
        .info-value { color: #2d3748; font-size: 16px; font-weight: 600; }
        .detalle-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
        .metodo-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px; }
        .total-section { background: #48bb78; color: white; padding: 20px; border-radius: 10px; text-align: center; margin-top: 15px; }
        .total-section .precio { font-size: 32px; font-weight: bold; }
        .badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
        .badge-pendiente { background: #fef3c7; color: #92400e; }
        .badge-aprobada { background: #d1fae5; color: #065f46; }
        .badge-rechazada { background: #fee2e2; color: #991b1b; }
        .estado-form { background: #f7fafc; padding: 20px; border-radius: 10px; }
        .estado-form select, .estado-form textarea { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; margin-bottom: 10px; }
        .btn-actualizar { background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; }
        @media print { .back-link, .estado-form, .btn-print { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <a href="historial.php" class="back-link">← Volver al historial</a>
        
        <div class="header">
            <h1>Cotización <?php echo htmlspecialchars($cot['folio']); ?></h1>
            <p style="color: #718096; font-size: 14px;">Fecha: <?php echo date('d/m/Y H:i', strtotime($cot['fecha'])); ?></p>
            <span class="badge badge-<?php echo $cot['estado']; ?>"><?php echo ucfirst($cot['estado']); ?></span>
        </div>
        
        <?php if ($cot['cliente_nombre']): ?>
        <div class="card">
            <h2>📋 Información del Cliente</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nombre</div>
                    <div class="info-value"><?php echo htmlspecialchars($cot['cliente_nombre']); ?></div>
                </div>
                <?php if ($cot['cliente_email']): ?>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($cot['cliente_email']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>💰 Detalle de Costos</h2>
            <div class="metodo-badge">Método: <?php echo htmlspecialchars($cot['metodo_impresion']); ?></div>
            <div>
                <div class="detalle-item">
                    <div>Impresión</div>
                    <div><?php echo formatCurrency($cot['costo_impresion']); ?></div>
                </div>
                <div class="detalle-item">
                    <div>Papel</div>
                    <div><?php echo formatCurrency($cot['costo_papel']); ?></div>
                </div>
                <div class="detalle-item">
                    <div style="border-top: 3px solid white; padding-top: 10px; font-size: 18px;"><strong>TOTAL</strong></div>
                    <div style="border-top: 3px solid white; padding-top: 10px; font-size: 20px;"><strong><?php echo formatCurrency($cot['total']); ?></strong></div>
                </div>
            </div>
            <div class="total-section">
                <h3>Precio por Unidad</h3>
                <div class="precio"><?php echo formatCurrency($cot['precio_unitario']); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>📌 Gestión de Estado</h2>
            <form method="POST" class="estado-form">
                <label style="font-weight: 600; margin-bottom: 5px; display: block;">Cambiar estado:</label>
                <select name="estado" required>
                    <option value="pendiente" <?php echo $cot['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="aprobada" <?php echo $cot['estado'] === 'aprobada' ? 'selected' : ''; ?>>Aprobada</option>
                    <option value="rechazada" <?php echo $cot['estado'] === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                </select>
                <textarea name="notas" placeholder="Notas..."><?php echo htmlspecialchars($cot['notas']); ?></textarea>
                <button type="submit" class="btn-actualizar">💾 Actualizar</button>
            </form>
        </div>
    </div>
</body>
</html>