<?php
// Test directo de conexión MySQL sin Symfony/Doctrine
header('Content-Type: application/json');

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'env_check' => [],
    'connection_test' => []
];

// 1. Verificar variables de entorno
$result['env_check']['DATABASE_URL'] = isset($_ENV['DATABASE_URL']) ? 'exists' : 'missing';
$result['env_check']['APP_ENV'] = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'not_set';

// 2. Parsear DATABASE_URL
$dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
if ($dbUrl) {
    $result['env_check']['DATABASE_URL_prefix'] = substr($dbUrl, 0, 30) . '...';
    
    // Parsear la URL
    if (preg_match('/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $dbUrl, $matches)) {
        $user = $matches[1];
        $pass = $matches[2];
        $host = $matches[3];
        $port = $matches[4];
        $dbname = explode('?', $matches[5])[0];
        
        $result['connection_test']['parsed'] = [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'dbname' => $dbname,
            'password_length' => strlen($pass)
        ];
        
        // 3. Intentar conexión con PDO
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $result['connection_test']['status'] = 'connected';
            
            // Test query
            $stmt = $pdo->query('SELECT 1 as test, NOW() as now');
            $row = $stmt->fetch();
            $result['connection_test']['test_query'] = $row;
            
            // Check tables
            $stmt = $pdo->query('SHOW TABLES');
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $result['connection_test']['tables_count'] = count($tables);
            $result['connection_test']['tables'] = $tables;
            
            $result['success'] = true;
            
        } catch (PDOException $e) {
            $result['connection_test']['status'] = 'failed';
            $result['connection_test']['error'] = $e->getMessage();
            $result['connection_test']['error_code'] = $e->getCode();
            $result['success'] = false;
        }
    } else {
        $result['connection_test']['error'] = 'Cannot parse DATABASE_URL';
        $result['success'] = false;
    }
} else {
    $result['connection_test']['error'] = 'DATABASE_URL not found';
    $result['success'] = false;
}

echo json_encode($result, JSON_PRETTY_PRINT);
