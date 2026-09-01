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
        . '<p><code>sudo apt-get install php8.5-sqlite3</code></p>'
        . '</div></body></html>';
    exit;
}

secureSession();

$user = null;
if (!empty($_SESSION['user_id'])) {
    $user = $db->getUserById((int) $_SESSION['user_id']);
    if (!$user) {
        unset($_SESSION['user_id']);
    }
}

$plantImages = [];
foreach ($db->getPlants() as $p) {
    $img = (string) ($p['image'] ?? '');
    if ($img !== '') {
        $plantImages[normalizeLower((string) $p['name'])] = $img;
    }
}

$requestId = trim((string) ($_GET['id'] ?? ''));
$requestName = trim((string) ($_GET['nombre'] ?? ''));

$hortaliza = null;
foreach (loadHortalizas() as $h) {
    if ($requestId !== '' && (string) ($h['id'] ?? '') === $requestId) {
        $hortaliza = $h;
        break;
    }
    if ($requestName !== '' && normalizeLower($h['nombre'] ?? '') === normalizeLower($requestName)) {
        $hortaliza = $h;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $hortaliza ? e($hortaliza['nombre']) . ' · ' . e(APP_NAME) : e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="app-header">
    <h1><?= e(APP_NAME) ?></h1>
    <nav class="tabs">
        <a href="?tab=seguimiento">Seguimiento</a>
        <a href="?tab=calendario">Calendario</a>
        <a href="index.php?tab=siembra" class="active">Calendario de siembra</a>
        <a href="?tab=calculadora">Calculadora de crecimiento</a>
    </nav>
    <div class="user-area">
        <?php if ($user): ?>
            <span class="user-chip">👤 <?= e($user['name']) ?></span>
        <?php else: ?>
            <a href="index.php?tab=login" class="btn btn-ghost btn-sm">Iniciar sesión</a>
        <?php endif; ?>
    </div>
</header>

<main>
    <?php if (!$hortaliza): ?>
        <div class="panel">
            <h2>Ficha no encontrada</h2>
            <p>No existe ninguna hortaliza que coincida con la búsqueda.</p>
            <a class="btn btn-ghost" href="index.php?tab=siembra">← Volver al calendario de siembra</a>
        </div>
    <?php else:
        $ficha = $hortaliza['ficha'] ?? [];
        $foto = $plantImages[normalizeLower((string) ($hortaliza['nombre'] ?? ''))] ?? ($hortaliza['foto'] ?? '');
    ?>
        <nav class="detail-back">
            <a href="index.php?tab=siembra">← Volver al calendario de siembra</a>
        </nav>

        <section class="panel">
            <div class="detail-hero">
                <div class="foto">
                    <span class="foto-fallback"><?= e(substr($hortaliza['nombre'], 0, 1)) ?></span>
                    <?php if ($foto !== ''): ?>
                        <img src="<?= e($foto) ?>" alt="<?= e($hortaliza['nombre']) ?>" loading="lazy"
                             onerror="this.remove()">
                    <?php endif; ?>
                </div>
                <div>
                    <h2><?= e($hortaliza['nombre']) ?></h2>
                    <em><?= e($hortaliza['nombre_cientifico'] ?? '') ?></em>
                    <p class="familia"><?= e($hortaliza['familia'] ?? '') ?></p>
                    <span class="badge <?= dificultadClass($hortaliza['dificultad'] ?? '') ?>"><?= e($hortaliza['dificultad'] ?? '') ?></span>
                    <div class="chips">
                        <?php foreach ($ficha['meses_siembra'] ?? [] as $m): ?>
                            <span class="chip"><?= e($m) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="ficha-grid">
                <div><dt>Meses cosecha</dt><dd><?= e(implode(', ', $ficha['meses_cosecha'] ?? [])) ?></dd></div>
                <div><dt>Días a cosecha</dt><dd><?= e($ficha['dias_cosecha'] ?? '') ?></dd></div>
                <div><dt>Temp. suelo mín.</dt><dd><?= e($ficha['temperatura_suelo_minima'] ?? '') ?></dd></div>
                <div><dt>Temp. óptima</dt><dd><?= e($ficha['temperatura_optima'] ?? '') ?></dd></div>
                <div><dt>Fase lunar</dt><dd><?= e($ficha['fase_lunar'] ?? '') ?></dd></div>
                <div><dt>Profundidad siembra</dt><dd><?= e($ficha['profundidad_siembra'] ?? '') ?></dd></div>
                <div><dt>Marco plantación</dt><dd><?= e($ficha['marco_plantacion'] ?? '') ?></dd></div>
                <div><dt>Método siembra</dt><dd><?= e($hortaliza['metodo_siembra'] ?? '') ?></dd></div>
            </div>

            <?php if (!empty($ficha['asociaciones_buenas'])): ?>
                <h4>Asociaciones favorables</h4>
                <div class="chips">
                    <?php foreach ($ficha['asociaciones_buenas'] as $a): ?>
                        <span class="chip chip-good"><?= e($a) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($ficha['asociaciones_malas'])): ?>
                <h4>Asociaciones desfavorables</h4>
                <div class="chips">
                    <?php foreach ($ficha['asociaciones_malas'] as $a): ?>
                        <span class="chip chip-bad"><?= e($a) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($hortaliza['plagas'])): ?>
                <h4>Plagas</h4>
                <div class="chips">
                    <?php foreach ($hortaliza['plagas'] as $a): ?>
                        <span class="chip"><?= e($a) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($hortaliza['enfermedades'])): ?>
                <h4>Enfermedades</h4>
                <div class="chips">
                    <?php foreach ($hortaliza['enfermedades'] as $a): ?>
                        <span class="chip"><?= e($a) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($ficha['riego_clave'])): ?>
                <h4>Riego</h4>
                <p class="ficha-text">💧 <?= e($ficha['riego_clave']) ?></p>
            <?php endif; ?>

            <?php if (!empty($ficha['abono_recomendado'])): ?>
                <h4>Abonado</h4>
                <p class="ficha-text">🌱 <?= e($ficha['abono_recomendado']) ?></p>
            <?php endif; ?>

            <?php if (!empty($ficha['observaciones'])): ?>
                <h4>Observaciones</h4>
                <p class="ficha-text">📝 <?= e($ficha['observaciones']) ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<footer class="app-footer">
    <span>Datos almacenados localmente en <code>plants.db</code> (SQLite).</span>
</footer>
</body>
</html>