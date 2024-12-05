<?php
require_once 'error_log.php';
require_once 'db.php';

try {
    $db = Database::getInstance();
    if ($db->migrateData()) {
        echo "Database initialized and data migrated successfully!";
    }
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage();
}
?>
