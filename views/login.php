<?php
/** @var string $loginRedirect */
?>

<section class="panel auth-panel">
    <h2>Iniciar sesión</h2>
    <p class="panel-sub">
        Las secciones <strong>Seguimiento</strong> y <strong>Calendario</strong> están reservadas a usuarios registrados.
        Si aún no tienes cuenta, puedes <a href="?tab=registro">registrarte aquí</a>.
    </p>

    <form method="post" class="auth-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="redirect" value="<?= e($loginRedirect) ?>">
        <div class="field">
            <label for="login-email">Correo electrónico</label>
            <input type="email" id="login-email" name="email" required autocomplete="email"
                   value="<?= e(trim((string) ($_POST['email'] ?? ''))) ?>">
        </div>
        <div class="field">
            <label for="login-password">Contraseña</label>
            <input type="password" id="login-password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary">Iniciar sesión</button>
    </form>

    <p class="auth-switch">¿No tienes cuenta? <a href="?tab=registro">Regístrate</a></p>
</section>
