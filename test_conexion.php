<?php
// test_conexion.php - Archivo de prueba

define('DB_HOST', 'localhost');
define('DB_NAME', 'u196943154_coticlaude');
define('DB_USER', 'u196943154_juan');
define('DB_PASS', '6@9ZdKtckN6ACSi'); // Reemplaza con tu contraseña real
define('DB_CHARSET', 'utf8mb4');

echo "<h1>🧪 Test de Conexión</h1>";

// Test 1: Conexión a base de datos
echo "<h2>Test 1: Conexión a Base de Datos</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "✅ Conexión exitosa a la base de datos";
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
    exit;
}

// Test 2: Verificar tabla usuarios
echo "<h2>Test 2: Tabla Usuarios</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM usuarios");
    $usuarios = $stmt->fetchAll();
    echo "✅ Se encontraron " . count($usuarios) . " usuario(s)<br>";
    foreach ($usuarios as $user) {
        echo "- Usuario: " . htmlspecialchars($user['username']) . " | Nombre: " . htmlspecialchars($user['nombre']) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// Test 3: Verificar tabla parametros
echo "<h2>Test 3: Tabla Parámetros</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM parametros");
    $result = $stmt->fetch();
    echo "✅ Se encontraron " . $result['total'] . " parámetro(s)";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// Test 4: Verificar tabla cotizaciones
echo "<h2>Test 4: Tabla Cotizaciones</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cotizaciones");
    $result = $stmt->fetch();
    echo "✅ Se encontraron " . $result['total'] . " cotización(es)";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// Test 5: Verificar password
echo "<h2>Test 5: Verificar Password</h2>";
try {
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    
    if ($user) {
        $password_hash = $user['password'];
        $test_password = 'admin123';
        
        if (password_verify($test_password, $password_hash)) {
            echo "✅ La contraseña 'admin123' es correcta";
        } else {
            echo "❌ La contraseña no coincide";
            echo "<br>Hash en BD: " . htmlspecialchars($password_hash);
        }
    } else {
        echo "❌ Usuario admin no encontrado";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<hr>";
echo "<p>Si todos los tests pasan con ✅, tu sistema debería funcionar correctamente.</p>";
?>