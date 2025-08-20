<?php
// config.php - Versión con puerto MySQL especificado
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');          // ✅ Puerto MySQL agregado
define('DB_USER', 'root');
define('DB_PASS', 'adrian123');
define('DB_NAME', 'comentarios_db');

function getDBConnection() {
    try {
        // DSN con puerto especificado
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        // En lugar de die(), lanzamos excepción para manejarla mejor
        throw new Exception("Error de conexión MySQL puerto 3306: " . $e->getMessage());
    }
}

// Crear tabla de comentarios si no existe
function createCommentsTable() {
    try {
        $pdo = getDBConnection();
        $sql = "CREATE TABLE IF NOT EXISTS comentarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            comentario TEXT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        return true;
    } catch(Exception $e) {
        return false;
    }
}

// Ejecutar la creación de tabla solo si es necesario
createCommentsTable();
?>

<?php
// get_comments.php - Versión con mejor manejo de errores
// ⚠️ IMPORTANTE: No debe haber NADA antes de esta línea PHP

// Deshabilitar la visualización de errores para evitar HTML no deseado
error_reporting(0);
ini_set('display_errors', 0);

// Headers primero
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Capturar cualquier salida no deseada
ob_start();

try {
    require_once 'config.php';
    
    $pdo = getDBConnection();
    $sql = "SELECT * FROM comentarios ORDER BY fecha_creacion DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear los comentarios para el frontend
    $formattedComments = array_map(function($comment) {
        return [
            'id' => $comment['id'],
            'name' => $comment['nombre'],
            'email' => $comment['email'],
            'text' => $comment['comentario'],
            'date' => $comment['fecha_creacion']
        ];
    }, $comentarios);
    
    // Limpiar cualquier salida no deseada
    ob_clean();
    
    // Enviar respuesta JSON
    echo json_encode(['success' => true, 'comments' => $formattedComments]);
    
} catch(Exception $e) {
    // Limpiar cualquier salida no deseada
    ob_clean();
    
    // Enviar error como JSON
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Finalizar el buffer
ob_end_flush();
?>

<?php
// save_comment.php - Versión con mejor manejo de errores
// ⚠️ IMPORTANTE: No debe haber NADA antes de esta línea PHP

// Deshabilitar la visualización de errores para evitar HTML no deseado
error_reporting(0);
ini_set('display_errors', 0);

// Headers primero
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Capturar cualquier salida no deseada
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    require_once 'config.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    $nombre = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $comentario = trim($input['comment'] ?? '');
    
    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($comentario)) {
        throw new Exception('Todos los campos son requeridos');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    $pdo = getDBConnection();
    $sql = "INSERT INTO comentarios (nombre, email, comentario) VALUES (:nombre, :email, :comentario)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':comentario', $comentario);
    
    if ($stmt->execute()) {
        // Limpiar cualquier salida no deseada
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Comentario guardado exitosamente']);
    } else {
        throw new Exception('Error al guardar el comentario');
    }
    
} catch(Exception $e) {
    // Limpiar cualquier salida no deseada
    ob_clean();
    
    // Enviar error como JSON
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Finalizar el buffer
ob_end_flush();
?>

<?php
// debug_response.php - Archivo para debuggear las respuestas
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug de Respuestas</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #fff; }
        .container { background: #2d2d2d; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .success { color: #4CAF50; } .error { color: #f44336; } .info { color: #2196F3; }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { background: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #45a049; }
        .warning { color: #ff9800; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Debug de Respuestas del Sistema</h1>
        
        <h3>1. Test de get_comments.php:</h3>
        <button onclick="testGetComments()">🔍 Probar get_comments.php</button>
        <div id="getResult"></div>
        
        <h3>2. Test de save_comment.php:</h3>
        <button onclick="testSaveComment()">💾 Probar save_comment.php</button>
        <div id="saveResult"></div>
        
        <h3>3. Test de conexión PHP con puerto 3306:</h3>
        <div id="phpTest">
            <?php
            echo "<p class='info'>✅ PHP está funcionando</p>";
            echo "<p>📊 Versión PHP: " . phpversion() . "</p>";
            echo "<p>🔌 Conectando a MySQL en puerto 3306...</p>";
            
            try {
                require_once 'config.php';
                $pdo = getDBConnection();
                echo "<p class='success'>✅ Conexión a MySQL puerto 3306 exitosa</p>";
                echo "<p class='info'>🏠 Host: " . DB_HOST . ":" . DB_PORT . "</p>";
                echo "<p class='info'>🗄️ Base de datos: " . DB_NAME . "</p>";
                
                // Verificar tabla
                $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios'");
                if ($stmt->rowCount() > 0) {
                    echo "<p class='success'>✅ Tabla 'comentarios' existe</p>";
                    
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM comentarios");
                    $total = $stmt->fetch()['total'];
                    echo "<p class='info'>📊 Total de comentarios: " . $total . "</p>";
                } else {
                    echo "<p class='warning'>⚠️ Tabla 'comentarios' no existe, creando...</p>";
                    createCommentsTable();
                    echo "<p class='success'>✅ Tabla creada</p>";
                }
                
            } catch(Exception $e) {
                echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
                echo "<p class='warning'>💡 Verifica que MySQL esté ejecutándose en puerto 3306</p>";
            }
            ?>
        </div>
    </div>

    <script>
        async function testGetComments() {
            const resultDiv = document.getElementById('getResult');
            resultDiv.innerHTML = '<p class="info">🔄 Probando get_comments.php...</p>';
            
            try {
                const response = await fetch('get_comments.php');
                const text = await response.text();
                
                resultDiv.innerHTML = `
                    <p class="info"><strong>Status:</strong> ${response.status}</p>
                    <p class="info"><strong>Content-Type:</strong> ${response.headers.get('Content-Type')}</p>
                    <p class="info"><strong>Respuesta cruda:</strong></p>
                    <pre>${text}</pre>
                `;
                
                // Intentar parsear como JSON
                try {
                    const json = JSON.parse(text);
                    resultDiv.innerHTML += `<p class="success">✅ JSON válido</p><pre>${JSON.stringify(json, null, 2)}</pre>`;
                } catch(e) {
                    resultDiv.innerHTML += `<p class="error">❌ No es JSON válido: ${e.message}</p>`;
                }
                
            } catch(error) {
                resultDiv.innerHTML = `<p class="error">❌ Error de red: ${error.message}</p>`;
            }
        }
        
        async function testSaveComment() {
            const resultDiv = document.getElementById('saveResult');
            resultDiv.innerHTML = '<p class="info">🔄 Probando save_comment.php...</p>';
            
            const testData = {
                name: 'Test Usuario',
                email: 'test@example.com',
                comment: 'Este es un comentario de prueba'
            };
            
            try {
                const response = await fetch('save_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(testData)
                });
                
                const text = await response.text();
                
                resultDiv.innerHTML = `
                    <p class="info"><strong>Status:</strong> ${response.status}</p>
                    <p class="info"><strong>Content-Type:</strong> ${response.headers.get('Content-Type')}</p>
                    <p class="info"><strong>Respuesta cruda:</strong></p>
                    <pre>${text}</pre>
                `;
                
                // Intentar parsear como JSON
                try {
                    const json = JSON.parse(text);
                    resultDiv.innerHTML += `<p class="success">✅ JSON válido</p><pre>${JSON.stringify(json, null, 2)}</pre>`;
                } catch(e) {
                    resultDiv.innerHTML += `<p class="error">❌ No es JSON válido: ${e.message}</p>`;
                }
                
            } catch(error) {
                resultDiv.innerHTML = `<p class="error">❌ Error de red: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>