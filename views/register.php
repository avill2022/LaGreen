<?php
/** @var string $loginRedirect */
?>

<section class="panel auth-panel">
    <h2>Crear cuenta</h2>
    <p class="panel-sub">
        Al registrarte podrás usar <strong>Seguimiento</strong> y <strong>Calendario</strong> para llevar
        tu propio registro de cultivos.
    </p>

    <form method="post" class="auth-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="redirect" value="<?= e($loginRedirect) ?>">
        <div class="field">
            <label for="reg-name">Nombre</label>
            <input type="text" id="reg-name" name="name" required autocomplete="name"
                   value="<?= e(trim((string) ($_POST['name'] ?? ''))) ?>">
        </div>
        <div class="field">
            <label for="reg-email">Correo electrónico</label>
            <input type="email" id="reg-email" name="email" required autocomplete="email"
                   value="<?= e(trim((string) ($_POST['email'] ?? ''))) ?>">
        </div>
        <div class="field">
            <label for="reg-password">Contraseña</label>
            <input type="password" id="reg-password" name="password" required minlength="6" autocomplete="new-password">
            <small>Mínimo 6 caracteres.</small>
        </div>
        <div class="field">
            <label for="reg-password2">Confirmar contraseña</label>
            <input type="password" id="reg-password2" name="password_confirm" required minlength="6" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Registrarse</button>
    </form>

    <p class="auth-switch">¿Ya tienes cuenta? <a href="?tab=login">Inicia sesión</a></p>
</section>
