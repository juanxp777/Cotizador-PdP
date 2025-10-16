<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// Cargar parámetros
$params = [];
$stmt = $db->query("SELECT nombre_param, valor FROM parametros");
while ($row = $stmt->fetch()) {
    $params[$row['nombre_param']] = $row['valor'];
}

// Procesar cotización si se envió el formulario
$resultado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calcular'])) {
    $tipo_producto = $_POST['tipo_producto'];
    $cantidad = intval($_POST['cantidad']);
    $paginas = intval($_POST['paginas']);
    $plastificado = $_POST['plastificado'];
    $encuadernacion = $_POST['encuadernacion'];
    $acabado_especial = $_POST['acabado_especial'];
    $incluye_transporte = isset($_POST['incluye_transporte']);
    $cliente_nombre = $_POST['cliente_nombre'] ?? '';
    $cliente_email = $_POST['cliente_email'] ?? '';
    $cliente_telefono = $_POST['cliente_telefono'] ?? '';
    
    // Calcular cotización
    $resultado = calcularCotizacion($params, $tipo_producto, $cantidad, $paginas, $plastificado, $encuadernacion, $acabado_especial, $incluye_transporte);
    
    // Guardar en base de datos
    if (isset($_POST['guardar']) && $resultado) {
        $folio = generateFolio();
        $stmt = $db->prepare("
            INSERT INTO cotizaciones (
                folio, cliente_nombre, cliente_email, cliente_telefono,
                tipo_producto, cantidad, paginas, plastificado, encuadernacion, acabado_especial, incluye_transporte,
                metodo_impresion, costo_planchas, detalle_planchas, costo_impresion, costo_papel, costo_acabados, costo_empaque, costo_transporte, subtotal, total, precio_unitario,
                usuario_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $folio, $cliente_nombre, $cliente_email, $cliente_telefono,
            $tipo_producto, $cantidad, $paginas, $plastificado, $encuadernacion, $acabado_especial, $incluye_transporte,
            $resultado['metodo_impresion'], $resultado['costo_planchas'], $resultado['detalle_planchas'], $resultado['costo_impresion'], $resultado['costo_papel'], $resultado['costo_acabados'], $resultado['costo_empaque'], $resultado['costo_transporte'], $resultado['subtotal'], $resultado['total'], $resultado['precio_unitario'],
            $_SESSION['user_id']
        ]);
        $mensaje_guardado = "Cotización guardada con folio: <strong>$folio</strong>";
    }
}

function calcularCotizacion($params, $tipo_producto, $cantidad, $paginas, $plastificado, $encuadernacion, $acabado_especial, $incluye_transporte) {
    $resultado = [
        'metodo_impresion' => '',
        'costo_planchas' => 0,
        'detalle_planchas' => '',
        'costo_impresion' => 0,
        'costo_papel' => 0,
        'costo_acabados' => 0,
        'costo_empaque' => 0,
        'costo_transporte' => 0,
        'subtotal' => 0,
        'total' => 0,
        'precio_unitario' => 0
    ];
    
    $umbral = $params['umbral_digital'];
    $paginas_interiores = $paginas - 4;
    
    // Determinar método de impresión
    if ($tipo_producto === 'cuaderno' && $cantidad <= $umbral) {
        // Híbrido: interiores offset + portadas digital
        $resultado['metodo_impresion'] = 'Híbrido (Interiores Offset + Portadas Digital)';
        
        // Portadas en digital
        $resultado['costo_impresion'] += $params['digital_portada'] * $cantidad;
        
        // Interiores en offset
        $pliegos_necesarios = ceil($paginas_interiores / 4);
        $planchas_necesarias = ceil($pliegos_necesarios / 2);
        
        $resultado['costo_planchas'] = $planchas_necesarias * $params['offset_plancha_cuarto'];
        $resultado['detalle_planchas'] = "$planchas_necesarias planchas cuarto de pliego (interiores)";
        $resultado['costo_impresion'] += $params['offset_montaje'] + ($params['offset_tiraje_interior'] * $paginas_interiores * $cantidad);
        
    } elseif ($cantidad <= $umbral) {
        // Digital completo
        $resultado['metodo_impresion'] = 'Digital';
        $resultado['costo_impresion'] = ($params['digital_portada'] + ($params['digital_pagina_interior'] * $paginas_interiores)) * $cantidad;
        
    } else {
        // Offset completo
        $resultado['metodo_impresion'] = 'Offset';
        
        // Planchas portada (medio pliego)
        $planchas_portada = 2;
        $resultado['costo_planchas'] += $planchas_portada * $params['offset_plancha_medio'];
        
        // Planchas interiores (cuarto pliego)
        $pliegos_necesarios = ceil($paginas_interiores / 4);
        $planchas_interiores = ceil($pliegos_necesarios / 2);
        $resultado['costo_planchas'] += $planchas_interiores * $params['offset_plancha_cuarto'];
        $resultado['detalle_planchas'] = "$planchas_portada planchas medio pliego (portada) + $planchas_interiores planchas cuarto pliego (interiores)";
        
        // Costo de tiraje
        $resultado['costo_impresion'] = $params['offset_montaje'] + 
            ($params['offset_tiraje_portada'] * $cantidad) + 
            ($params['offset_tiraje_interior'] * $paginas_interiores * $cantidad);
    }
    
    // Papel
    $resultado['costo_papel'] = ($params['papel_portada'] + ($params['papel_interior'] * $paginas_interiores)) * $cantidad;
    
    // Acabados
    $costo_acabados = 0;
    if ($plastificado !== 'ninguno') {
        $costo_acabados += $params['plastificado_' . $plastificado] * $cantidad;
    }
    
    $encuadernacion_map = [
        'grapado' => 'encuadernacion_grapado',
        'espiral_plastico' => 'encuadernacion_espiral_plastico',
        'espiral_metal' => 'encuadernacion_espiral_metal',
        'encolado' => 'encuadernacion_encolado',
        'tapa_dura' => 'encuadernacion_tapa_dura'
    ];
    $costo_acabados += $params[$encuadernacion_map[$encuadernacion]] * $cantidad;
    
    if ($acabado_especial !== 'ninguno') {
        $acabado_map = [
            'uv' => 'acabado_uv',
            'relieve' => 'acabado_relieve',
            'hot_stamping' => 'acabado_hot_stamping'
        ];
        $costo_acabados += $params[$acabado_map[$acabado_especial]] * $cantidad;
    }
    $resultado['costo_acabados'] = $costo_acabados;
    
    // Empaque
    $resultado['costo_empaque'] = $params['empaque_unidad'] * $cantidad;
    
    // Transporte
    if ($incluye_transporte) {
        $resultado['costo_transporte'] = $params['transporte_base'] + ($params['transporte_unidad'] * $cantidad);
    }
    
    // Totales
    $resultado['subtotal'] = $resultado['costo_impresion'] + $resultado['costo_planchas'] + $resultado['costo_papel'] + $resultado['costo_acabados'] + $resultado['costo_empaque'];
    $resultado['total'] = $resultado['subtotal'] + $resultado['costo_transporte'];
    $resultado['precio_unitario'] = round($resultado['total'] / $cantidad);
    
    return $resultado;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
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
        
        .user-info {
            color: #718096;
            font-size: 14px;
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
        
        .nav a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
        }
        
        .resultado {
            background: #f7fafc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .resultado-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .resultado-item:last-child {
            border-bottom: none;
        }
        
        .resultado-label {
            color: #4a5568;
            font-weight: 600;
        }
        
        .resultado-value {
            color: #2d3748;
            font-weight: bold;
        }
        
        .metodo-impresion {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .total-section {
            background: #48bb78;
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 15px;
        }
        
        .total-section h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .total-section .precio {
            font-size: 32px;
            font-weight: bold;
        }
        
        .total-section .precio-unitario {
            font-size: 16px;
            margin-top: 10px;
            opacity: 0.9;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🖨️ <?php echo EMPRESA_NOMBRE; ?></h1>
                <p style="color: #718096; font-size: 14px;">Sistema de Cotización</p>
            </div>
            <div class="user-info">
                Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong> | 
                <a href="logout.php" style="color: #e53e3e;">Salir</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="index.php">📊 Cotizar</a>
            <a href="historial.php">📋 Historial</a>
            <a href="parametros.php">⚙️ Parámetros</a>
        </div>
        
        <?php if (isset($mensaje_guardado)): ?>
            <div class="alert alert-success"><?php echo $mensaje_guardado; ?></div>
        <?php endif; ?>
        
        <div class="main-grid">
            <!-- Formulario -->
            <div class="card">
                <h2>Datos del Producto</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Información del Cliente (Opcional)</label>
                        <input type="text" name="cliente_nombre" placeholder="Nombre del cliente">
                    </div>
                    
                    <div class="form-group">
                        <input type="email" name="cliente_email" placeholder="Email del cliente">
                    </div>
                    
                    <div class="form-group">
                        <input type="tel" name="cliente_telefono" placeholder="Teléfono del cliente">
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Producto</label>
                        <select name="tipo_producto" required>
                            <option value="agenda" <?php echo (isset($_POST['tipo_producto']) && $_POST['tipo_producto'] === 'agenda') ? 'selected' : ''; ?>>📅 Agenda</option>
                            <option value="cuaderno" <?php echo (isset($_POST['tipo_producto']) && $_POST['tipo_producto'] === 'cuaderno') ? 'selected' : ''; ?>>📓 Cuaderno</option>
                            <option value="revista" <?php echo (isset($_POST['tipo_producto']) && $_POST['tipo_producto'] === 'revista') ? 'selected' : ''; ?>>📖 Revista</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cantidad de Ejemplares</label>
                        <input type="number" name="cantidad" min="1" value="<?php echo $_POST['cantidad'] ?? 100; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Número de Páginas (múltiplo de 4)</label>
                        <input type="number" name="paginas" min="4" step="4" value="<?php echo $_POST['paginas'] ?? 200; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Plastificado de Portada</label>
                        <select name="plastificado">
                            <option value="ninguno">Sin plastificado</option>
                            <option value="mate">Mate</option>
                            <option value="brillante">Brillante</option>
                            <option value="soft">Soft Touch</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Encuadernación</label>
                        <select name="encuadernacion">
                            <option value="grapado">Grapado</option>
                            <option value="espiral_plastico">Espiral Plástico</option>
                            <option value="espiral_metal">Espiral Metálico</option>
                            <option value="encolado">Encolado (Perfect Bound)</option>
                            <option value="tapa_dura">Tapa Dura</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Acabado Especial</label>
                        <select name="acabado_especial">
                            <option value="ninguno">Ninguno</option>
                            <option value="uv">Barniz UV Selectivo</option>
                            <option value="relieve">Relieve</option>
                            <option value="hot_stamping">Hot Stamping</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="incluye_transporte" id="transporte" <?php echo (isset($_POST['incluye_transporte']) || !isset($_POST['calcular'])) ? 'checked' : ''; ?>>
                            <label for="transporte" style="margin: 0;">Incluir transporte</label>
                        </div>
                    </div>
                    
                    <button type="submit" name="calcular" class="btn btn-primary">Calcular Cotización</button>
                    
                    <?php if ($resultado): ?>
                        <button type="submit" name="guardar" class="btn btn-success">💾 Guardar Cotización</button>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Resultado -->
            <div class="card">
                <h2>Resultado de la Cotización</h2>
                
                <?php if ($resultado): ?>
                    <div class="metodo-impresion">
                        <strong>Método de Impresión</strong><br>
                        <?php echo htmlspecialchars($resultado['metodo_impresion']); ?>
                    </div>
                    
                    <div class="resultado">
                        <?php if ($resultado['costo_planchas'] > 0): ?>
                            <div class="resultado-item">
                                <div>
                                    <div class="resultado-label">Planchas Litográficas</div>
                                    <small style="color: #718096;"><?php echo htmlspecialchars($resultado['detalle_planchas']); ?></small>
                                </div>
                                <div class="resultado-value"><?php echo formatCurrency($resultado['costo_planchas']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="resultado-item">
                            <div class="resultado-label">Impresión</div>
                            <div class="resultado-value"><?php echo formatCurrency($resultado['costo_impresion']); ?></div>
                        </div>
                        
                        <div class="resultado-item">
                            <div class="resultado-label">Papel</div>
                            <div class="resultado-value"><?php echo formatCurrency($resultado['costo_papel']); ?></div>
                        </div>
                        
                        <div class="resultado-item">
                            <div class="resultado-label">Acabados</div>
                            <div class="resultado-value"><?php echo formatCurrency($resultado['costo_acabados']); ?></div>
                        </div>
                        
                        <div class="resultado-item">
                            <div class="resultado-label">Empaque</div>
                            <div class="resultado-value"><?php echo formatCurrency($resultado['costo_empaque']); ?></div>
                        </div>
                        
                        <?php if ($resultado['costo_transporte'] > 0): ?>
                            <div class="resultado-item">
                                <div class="resultado-label">Transporte</div>
                                <div class="resultado-value"><?php echo formatCurrency($resultado['costo_transporte']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="resultado-item" style="border-top: 3px solid #e2e8f0; margin-top: 10px; padding-top: 15px;">
                            <div class="resultado-label" style="font-size: 18px;">TOTAL</div>
                            <div class="resultado-value" style="font-size: 20px; color: #667eea;"><?php echo formatCurrency($resultado['total']); ?></div>
                        </div>
                    </div>
                    
                    <div class="total-section">
                        <h3>Precio por Unidad</h3>
                        <div class="precio"><?php echo formatCurrency($resultado['precio_unitario']); ?></div>
                        <div class="precio-unitario">Base: <?php echo intval($_POST['cantidad']); ?> ejemplares</div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <p>Completa los datos y haz clic en<br><strong>"Calcular Cotización"</strong></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>