<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Models\Database;

header('Content-Type: text/plain; charset=UTF-8');

function line(string $s): void
{
    echo "  - $s\n";
}

echo "=== LaGreen health check ===\n\n";

echo "Entorno\n";
line('PHP version: ' . PHP_VERSION);
line('pdo_sqlite cargada: ' . (extension_loaded('pdo_sqlite') ? 'sí' : 'NO'));
line('sqlite3 cargada: ' . (extension_loaded('sqlite3') ? 'sí' : 'NO'));

echo "\nArchivos de datos\n";
$horta = __DIR__ . '/data/hortalizas.json';
line('SEED_FILE: ' . SEED_FILE);
line('  existe: ' . (file_exists(SEED_FILE) ? 'sí' : 'NO'));
line('  legible: ' . (is_readable(SEED_FILE) ? 'sí' : 'NO'));
line('data/hortalizas.json: ' . $horta . '  existe: ' . (file_exists($horta) ? 'sí' : 'NO'));

echo "\nBase de datos\n";
line('DB_PATH: ' . DB_PATH);
line('  existe: ' . (file_exists(DB_PATH) ? 'sí' : 'NO'));
if (file_exists(DB_PATH)) {
    line('  escribible: ' . (is_writable(DB_PATH) ? 'sí' : 'NO'));
} else {
    line('  directorio escribible (permitiría creación): ' . (is_writable(dirname(DB_PATH)) ? 'sí' : 'NO'));
}

try {
    $db = new Database();
    line('Conexión: sí');
    $count = $db->countPlants();
    line('Plantas: ' . $count);
    if ($count === 0 && !file_exists(SEED_FILE)) {
        line('  PROBLEMA: falta example_plants.json para el auto-import (tabla vacía).');
    }
    $plants = $db->getPlants();
    if ($plants) {
        $sample = array_map(static fn (array $p): string => (string) $p['name'], array_slice($plants, 0, 5));
        line('Muestra: ' . implode(', ', $sample) . (count($plants) > 5 ? ', …' : ''));
    }
} catch (Throwable $e) {
    line('Conexión: NO');
    line('Error: ' . $e->getMessage());
}

echo "\nListo. Si algún punto marca NO, revisa la configuración del servidor.\n";
echo "Nota: elimina o restringe este script en producción.\n";