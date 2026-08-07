<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

$db = new Database();

if ($db->countPlants() > 0) {
    echo "La base de datos ya contiene plantas. No se importó nada.\n";
    exit(0);
}

try {
    $count = $db->importFromJson(SEED_FILE);
    echo "Se importaron $count plantas desde example_plants.json\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
