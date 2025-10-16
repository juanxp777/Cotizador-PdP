<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// Filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$buscar = $_GET['buscar'] ?? '';

// Construir query
$where = [];
$params = [];

if ($filtro_tipo) {
    $where[] = "tipo_producto = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_estado) {
    $where[] = "estado = ?";
    $params[] = $filtro_estado;
}

if ($buscar) {
    $where[] = "(folio LIKE ? OR cliente_nombre LIKE ? OR cliente_email LIKE ?)";
    $buscar_param = "%$buscar%";
    $params[] = $buscar_param;
    $params[] = $buscar_param;
    $params[] = $buscar_param;
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $db->prepare("SELECT * FROM cotizaciones $where_clause ORDER BY fecha DESC LIMIT 50");
$stmt->execute($params);
$cotizaciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - <?php echo SITE_NAME; ?></title>
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
            max-width: 1400px;
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
        }
        
        .filtros {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            background: #f7fafc;
            padding: 20px;
            border-radius: 10px;
        }
        
        .filtros select,
        .filtros input {
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .filtros select:focus,
        .filtros input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-filtrar {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-filtrar:hover {
            background: #5568d3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }
        
        tr:hover {
            background: #f7fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pendiente {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-aprobada {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-rechazada {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .producto-icon {
            font-size: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }
        
        .btn-ver {
            background: #667eea;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .btn-ver:hover {
            background: #5568d3;
        }
        
        @media (max-width: 768px) {
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 10px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📋 Historial de Cotizaciones</h1>
                <p style="color: #718096; font-size: 14px;">Últimas 50 cotizaciones</p>
            </div>
            <div style="color: #718096; font-size: 14px;">
                <?php echo htmlspecialchars($_SESSION['nombre']); ?> | 
                <a href="logout.php" style="color: #e53e3e;">Salir</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="index.php">📊 Cotizar</a>
            <a href="historial.php" class="active">📋 Historial</a>
            <a href="parametros.php">⚙️ Parámetros</a>
        </div>
        
        <div class="card">
            <!-- Filtros -->
            <form method="GET" class="filtros">
                <input type="text" name="buscar" placeholder="Buscar por folio, cliente..." value="<?php echo htmlspecialchars($buscar); ?>">
                
                <select name="tipo">
                    <option value="">Todos los productos</option>
                    <option value="agenda" <?php echo $filtro_tipo === 'agenda' ? 'selected' : ''; ?>>Agendas</option>
                    <option value="cuaderno" <?php echo $filtro_tipo === 'cuaderno' ? 'selected' : ''; ?>>Cuadernos</option>
                    <option value="revista" <?php echo $filtro_tipo === 'revista' ? 'selected' : ''; ?>>Revistas</option>
                </select>
                
                <select name="estado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?php echo $filtro_estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="aprobada" <?php echo $filtro_estado === 'aprobada' ? 'selected' : ''; ?>>Aprobada</option>
                    <option value="rechazada" <?php echo $filtro_estado === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                </select>
                
                <button type="submit" class="btn-filtrar">🔍 Filtrar</button>
            </form>
            
            <?php if (count($cotizaciones) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotizaciones as $cot): 
                            $producto_icons = [
                                'agenda' => '📅',
                                'cuaderno' => '📓',
                                'revista' => '📖'
                            ];
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cot['folio']); ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cot['fecha'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($cot['cliente_nombre'] ?: 'Sin cliente'); ?><br>
                                    <small style="color: #718096;"><?php echo htmlspecialchars($cot['cliente_email'] ?: ''); ?></small>
                                </td>
                                <td>
                                    <span class="producto-icon"><?php echo $producto_icons[$cot['tipo_producto']]; ?></span>
                                    <?php echo ucfirst($cot['tipo_producto']); ?>
                                </td>
                                <td><?php echo number_format($cot['cantidad']); ?> unds.</td>
                                <td><strong><?php echo formatCurrency($cot['total']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $cot['estado']; ?>">
                                        <?php echo ucfirst($cot['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="ver_cotizacion.php?id=<?php echo $cot['id']; ?>" class="btn-ver">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 80px; height: 80px; margin: 0 auto 20px; opacity: 0.5;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p>No se encontraron cotizaciones</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>