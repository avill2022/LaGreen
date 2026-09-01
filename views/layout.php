<?php
/** @var string $content */
/** @var ?array $user */
/** @var string $error */
/** @var string $success */
/** @var string $activeTab */

$navTab = in_array($activeTab, ['detalle'], true) ? 'siembra' : $activeTab;
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
        <a href="index.php?tab=seguimiento" class="<?= $navTab === 'seguimiento' ? 'active' : '' ?>">Seguimiento</a>
        <a href="index.php?tab=calendario" class="<?= $navTab === 'calendario' ? 'active' : '' ?>">Calendario</a>
        <a href="index.php?tab=siembra" class="<?= $navTab === 'siembra' ? 'active' : '' ?>">Calendario de siembra</a>
        <a href="index.php?tab=calculadora" class="<?= $navTab === 'calculadora' ? 'active' : '' ?>">Calculadora de crecimiento</a>
    </nav>
    <div class="user-area">
        <?php if ($user): ?>
            <span class="user-chip">👤 <?= e($user['name']) ?></span>
            <form method="post" class="inline-form" action="index.php?tab=seguimiento">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-ghost btn-sm">Cerrar sesión</button>
            </form>
        <?php else: ?>
            <a href="index.php?tab=login" class="btn btn-ghost btn-sm">Iniciar sesión</a>
            <a href="index.php?tab=registro" class="btn btn-primary btn-sm">Registrarse</a>
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

    <?= $content ?>
</main>

<footer class="app-footer">
    <span>Datos almacenados localmente en <code>plants.db</code> (SQLite).</span>
</footer>
</body>
</html>