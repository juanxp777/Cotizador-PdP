<?php
// generar_hash.php - Genera el hash correcto para la contraseña

$contraseña = 'admin123';
$hash = password_hash($contraseña, PASSWORD_DEFAULT);

echo "<h1>🔐 Generador de Hash de Contraseña</h1>";
echo "<p><strong>Contraseña:</strong> " . htmlspecialchars($contraseña) . "</p>";
echo "<p><strong>Hash generado:</strong></p>";
echo "<code style='background: #f0f0f0; padding: 10px; display: block; word-break: break-all;'>" . htmlspecialchars($hash) . "</code>";

echo "<hr>";
echo "<h2>Instrucciones:</h2>";
echo "<ol>";
echo "<li>Copia el hash de arriba</li>";
echo "<li>Ve a phpMyAdmin en Hostinger</li>";
echo "<li>Selecciona tu base de datos 'u196943154_coticlaude'</li>";
echo "<li>Abre la tabla 'usuarios'</li>";
echo "<li>Haz click en 'Editar' en la fila del usuario 'admin'</li>";
echo "<li>En el campo 'password', borra todo y pega el hash</li>";
echo "<li>Click en 'Guardar'</li>";
echo "<li>Luego intenta acceder con usuario: <strong>admin</strong> y contraseña: <strong>admin123</strong></li>";
echo "</ol>";
?>