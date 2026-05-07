<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $columns = [
        'title' => 'VARCHAR(255) NULL',
        'description' => 'TEXT NULL',
        'password' => 'VARCHAR(255) NULL',
        'view_count' => 'INTEGER DEFAULT 0',
        'report_count' => 'INTEGER DEFAULT 0',
        'mime_type' => 'VARCHAR(100) NULL'
    ];
    
    foreach ($columns as $col => $type) {
        try {
            $pdo->exec("ALTER TABLE images ADD COLUMN $col $type");
            echo "Added column: $col\n";
        } catch (Exception $e) {
            echo "Column $col might already exist: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Migration completed.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
