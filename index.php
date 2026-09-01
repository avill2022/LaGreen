<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\SeguimientoController;
use App\Models\Catalog;
use App\Models\Database;

try {
    $db = new Database();
} catch (Throwable $e) {
    error_log('[LaGreen] Fatal: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
        . '<title>' . e(APP_NAME) . '</title>'
        . '<style>body{background:#1b1b1b;color:#e8e8e8;font-family:sans-serif;display:flex;'
        . 'justify-content:center;align-items:center;min-height:100vh;margin:0}'
        . '.box{max-width:560px;background:#2b2b2b;border:1px solid #3a3a3a;border-radius:10px;padding:24px}'
        . 'h1{color:#4CAF50;font-size:18px}p{line-height:1.5}code{background:#323232;padding:2px 6px;border-radius:4px}</style>'
        . '</head><body><div class="box"><h1>' . e(APP_NAME) . '</h1>'
        . '<p>' . e($e->getMessage()) . '</p>'
        . '<p>Instale la extensión y pruebe de nuevo con:</p>'
        . '<p><code>sudo apt-get install php8.5-sqlite3</code></p>'
        . '</div></body></html>';
    exit;
}

secureSession();

if ($db->countPlants() === 0) {
    if (file_exists(SEED_FILE)) {
        try {
            $db->importFromJson(SEED_FILE);
        } catch (Throwable $e) {
            error_log('[LaGreen] Import inicial fallido: ' . $e->getMessage());
        }
    } else {
        error_log('[LaGreen] ' . SEED_FILE . ' no existe y la tabla plants está vacía. Copie example_plants.json junto a config.php o ejecute php seed.php.');
    }
}

$user = null;
if (!empty($_SESSION['user_id'])) {
    $user = $db->getUserById((int) $_SESSION['user_id']);
    if (!$user) {
        unset($_SESSION['user_id']);
    }
}

$allowedTabs = ['seguimiento', 'calendario', 'siembra', 'calculadora', 'detalle', 'login', 'registro'];
$activeTab = $_GET['tab'] ?? ($user ? 'seguimiento' : 'siembra');
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = $user ? 'seguimiento' : 'siembra';
}

$loginRedirect = 'seguimiento';
if (in_array($activeTab, ['seguimiento', 'calendario'], true)) {
    $loginRedirect = $activeTab;
    if (!$user) {
        $activeTab = 'login';
    }
} elseif ($user && in_array($activeTab, ['login', 'registro'], true)) {
    $activeTab = 'seguimiento';
}

$catalog = new Catalog();
$router = new Router();

$class = null;
$action = trim((string) ($_POST['action'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array($action, ['login', 'register', 'logout'], true)) {
        $class = AuthController::class;
    } elseif (in_array($action, ['add', 'delete'], true)) {
        $class = SeguimientoController::class;
    }
}
if ($class === null) {
    $class = $router->controllerFor($activeTab);
}

if ($class === null) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
        . '<title>404</title><style>body{background:#1b1b1b;color:#e8e8e8;font-family:sans-serif;'
        . 'display:flex;justify-content:center;align-items:center;min-height:100vh}'
        . 'a{color:#4CAF50}</style></head><body><h1>Página no encontrada · <a href="index.php">Volver</a></h1></body></html>';
    exit;
}

/** @var \App\Core\Controller $controller */
$controller = new $class($db, $catalog, $user);
if ($controller instanceof AuthController) {
    $controller->setLoginRedirect($loginRedirect);
}

$controller->handle();