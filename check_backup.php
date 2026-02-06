<?php
/**
 * Script untuk melihat struktur data dalam backup JSON
 */

$backupFile = __DIR__ . '/../backup_2026-02-04_063350.json';
$data = json_decode(file_get_contents($backupFile), true);

echo "=== STRUKTUR DATA BACKUP ===\n\n";

foreach (array_keys($data) as $key) {
    echo $key . ': ' . count($data[$key]) . " records\n";
}
