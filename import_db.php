<?php
// Script temporal para importar base de datos a Railway

$host = 'hopper.proxy.rlwy.net';
$port = 49930;
$database = 'railway';
$username = 'root';
$password = 'jzgcFIVbXHUVeIVbhndvEnLVuWxaUhcc';
$sqlFile = __DIR__ . '/chaty (1).sql';

echo "Conectando a Railway MySQL...\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Conexión exitosa!\n";
    echo "Leyendo archivo SQL...\n";
    
    if (!file_exists($sqlFile)) {
        die("Error: No se encuentra el archivo {$sqlFile}\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        die("Error: No se pudo leer el archivo SQL\n");
    }
    
    echo "Ejecutando SQL...\n";
    
    // Dividir por punto y coma pero mantener los delimitadores
    $statements = explode(';', $sql);
    $executed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (empty($statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignorar errores de tablas que ya existen
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✓ Importación completada!\n";
    echo "Statements ejecutados: {$executed}\n";
    
    // Verificar tablas creadas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTablas en la base de datos:\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
