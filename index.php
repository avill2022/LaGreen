<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/functions.php';

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

$error = '';
$success = '';

secureSession();

if ($db->countPlants() === 0) {
    if (file_exists(SEED_FILE)) {
        try {
            $db->importFromJson(SEED_FILE);
        } catch (Throwable $e) {
            $error = 'No se pudieron cargar los datos iniciales: ' . $e->getMessage();
        }
    } else {
        error_log('[LaGreen] ' . SEED_FILE . ' no existe y la tabla plants está vacía. Copie example_plants.json junto a Database.php o ejecute php seed.php.');
        $error = 'No hay plantas en la base de datos y falta el archivo de datos iniciales (' . SEED_FILE . '). Copie example_plants.json junto a Database.php o ejecute php seed.php.';
    }
}

$user = null;
if (!empty($_SESSION['user_id'])) {
    $user = $db->getUserById((int) $_SESSION['user_id']);
    if (!$user) {
        unset($_SESSION['user_id']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tab = $_POST['tab'] ?? 'seguimiento';
    $redirect = null;

    if ($action === 'register') {
        $regName = trim((string) ($_POST['name'] ?? ''));
        $regEmail = trim((string) ($_POST['email'] ?? ''));
        $regPass = (string) ($_POST['password'] ?? '');
        $regPass2 = (string) ($_POST['password_confirm'] ?? '');
        $regRedirect = validTab((string) ($_POST['redirect'] ?? '')) ? (string) $_POST['redirect'] : 'seguimiento';

        if (!verifyCsrf()) {
            $error = 'La sesión expiró. Vuelva a intentarlo.';
        } elseif (loginBlocked()) {
            $error = 'Demasiados intentos. Espere unos minutos y vuelva a intentarlo.';
        } elseif ($regName === '') {
            $error = 'El nombre es obligatorio.';
        } elseif (!filter_var($regEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ingrese un correo electrónico válido.';
        } elseif (strlen($regPass) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($regPass !== $regPass2) {
            $error = 'Las contraseñas no coinciden.';
        } elseif ($db->getUserByEmail($regEmail) !== null) {
            $error = 'Ya existe una cuenta con ese correo.';
        } else {
            $userId = $db->registerUser($regName, $regEmail, password_hash($regPass, PASSWORD_DEFAULT));
            session_regenerate_id(true);
            resetLoginRateLimit();
            $_SESSION['user_id'] = $userId;
            $user = $db->getUserById($userId);
            $success = 'Cuenta creada. ¡Bienvenido!';
            $redirect = $regRedirect;
        }
    } elseif ($action === 'login') {
        $logEmail = trim((string) ($_POST['email'] ?? ''));
        $logPass = (string) ($_POST['password'] ?? '');
        $logRedirect = validTab((string) ($_POST['redirect'] ?? '')) ? (string) $_POST['redirect'] : 'seguimiento';
        $found = $db->getUserByEmail($logEmail);

        if (!verifyCsrf()) {
            $error = 'La sesión expiró. Vuelva a intentarlo.';
        } elseif (loginBlocked()) {
            $error = 'Demasiados intentos. Espere unos minutos y vuelva a intentarlo.';
        } elseif (!$found || !password_verify($logPass, $found['password_hash'])) {
            registerLoginFailure();
            $error = 'Correo o contraseña incorrectos.';
        } else {
            session_regenerate_id(true);
            resetLoginRateLimit();
            $_SESSION['user_id'] = (int) $found['id'];
            $user = $found;
            $success = 'Sesión iniciada.';
            $redirect = $logRedirect;
        }
    } elseif ($action === 'logout') {
        if (!verifyCsrf()) {
            $error = 'La sesión expiró. Vuelva a intentarlo.';
        } else {
            session_unset();
            session_destroy();
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=siembra');
            exit;
        }
    } elseif ($action === 'add' || $action === 'delete') {
        if (!$user) {
            $error = 'Debe iniciar sesión para gestionar el seguimiento.';
        } elseif (!verifyCsrf()) {
            $error = 'La sesión expiró. Vuelva a intentarlo.';
        } elseif ($action === 'add') {
            $plantId = (int) ($_POST['plant_id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $date = trim((string) ($_POST['germination_date'] ?? ''));
            $notes = trim((string) ($_POST['notes'] ?? ''));
            $plant = $plantId > 0 ? $db->getPlant($plantId) : null;

            if (!$plant) {
                $error = 'Seleccione un tipo de planta.';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $error = 'Ingrese una fecha válida (YYYY-MM-DD).';
            } else {
                if ($name === '') {
                    $name = $plant['name'];
                }
                $db->addGerminationPlant($plantId, (int) $user['id'], $name, $date, $notes);
                $success = 'Planta añadida al seguimiento.';
                $redirect = $tab;
            }
        } else {
            $deleted = $db->deleteGerminationPlant((int) ($_POST['id'] ?? 0), (int) $user['id']);
            $success = $deleted ? 'Planta eliminada del seguimiento.' : 'No se pudo eliminar la planta.';
            $redirect = $tab;
        }
    }

    if ($error === '' && $redirect !== null) {
        $query = '?tab=' . urlencode($redirect);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . $query);
        exit;
    }
}

$allowedTabs = ['seguimiento', 'calendario', 'siembra', 'calculadora', 'login', 'registro'];
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

$plants = $db->getPlants();
$gps = $user ? $db->getGerminationPlants((int) $user['id']) : [];
$todayStr = date('Y-m-d');

$submitted = [
    'plant_id' => (int) ($_POST['plant_id'] ?? 0),
    'name' => trim((string) ($_POST['name'] ?? '')),
    'germination_date' => trim((string) ($_POST['germination_date'] ?? '')) ?: $todayStr,
    'notes' => trim((string) ($_POST['notes'] ?? '')),
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="app-header">
    <h1><?= e(APP_NAME) ?></h1>
    <nav class="tabs">
        <a href="?tab=seguimiento" class="<?= $activeTab === 'seguimiento' ? 'active' : '' ?>">Seguimiento</a>
        <a href="?tab=calendario" class="<?= $activeTab === 'calendario' ? 'active' : '' ?>">Calendario</a>
        <a href="?tab=siembra" class="<?= $activeTab === 'siembra' ? 'active' : '' ?>">Calendario de siembra</a>
        <a href="?tab=calculadora" class="<?= $activeTab === 'calculadora' ? 'active' : '' ?>">Calculadora de crecimiento</a>
    </nav>
    <div class="user-area">
        <?php if ($user): ?>
            <span class="user-chip">👤 <?= e($user['name']) ?></span>
            <form method="post" class="inline-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-ghost btn-sm">Cerrar sesión</button>
            </form>
        <?php else: ?>
            <a href="?tab=login" class="btn btn-ghost btn-sm">Iniciar sesión</a>
            <a href="?tab=registro" class="btn btn-primary btn-sm">Registrarse</a>
        <?php endif; ?>
    </div>
</header>

<main>
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($activeTab === 'login'): ?>
        <?php include __DIR__ . '/views/login.php'; ?>
    <?php elseif ($activeTab === 'registro'): ?>
        <?php include __DIR__ . '/views/register.php'; ?>
    <?php elseif ($activeTab === 'seguimiento'): ?>
        <?php include __DIR__ . '/views/seguimiento.php'; ?>
    <?php elseif ($activeTab === 'calendario'): ?>
        <?php include __DIR__ . '/views/calendario.php'; ?>
    <?php elseif ($activeTab === 'calculadora'): ?>
        <?php include __DIR__ . '/views/calculadora.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/views/siembra.php'; ?>
    <?php endif; ?>
</main>

<footer class="app-footer">
    <span>Datos almacenados localmente en <code>plants.db</code> (SQLite).</span>
</footer>
</body>
</html>
