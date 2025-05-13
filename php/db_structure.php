<?php

$host = 'localhost';
$user = 'root';
$password = ''; // à adapter
$port = 3306;

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Liste des bases
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $result = [];

    foreach ($databases as $db) {
        if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;

        $pdo->query("USE `$db`");
        $tablesStmt = $pdo->query("SHOW TABLES");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);


        $tablesInfo = [];

        foreach ($tables as $table) {
            // Nombre de colonnes
            $colsStmt = $pdo->query("DESCRIBE `$table`");
            $columns = $colsStmt->rowCount();
            $columnsInfo = $colsStmt->fetchAll(PDO::FETCH_ASSOC);// +

            // Extraire juste les noms des colonnes (champ 'Field')
            $nomsDesColonnes = array_map(fn($col) => $col['Field'], $columnsInfo);

            // Nombre de lignes
            $rowsStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $rowCount = $rowsStmt->fetchColumn();

            $tablesInfo[] = [
                'name' => $table,
                'columns' => $columns,
                'nomsDesColonnes' => $nomsDesColonnes,
                'rows' => $rowCount
            ];
        }

        $result[] = [
            'name' => $db,
            'tables' => $tablesInfo
        ];
    }

    echo json_encode($result);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
