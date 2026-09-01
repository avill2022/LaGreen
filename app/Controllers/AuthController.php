<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class AuthController extends Controller
{
    public function handle(): void
    {
        $action = trim((string) ($_POST['action'] ?? ''));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'register') {
                $this->register();
            } elseif ($action === 'login') {
                $this->login();
            } elseif ($action === 'logout') {
                $this->logout();
            } else {
                $this->form();
            }
            return;
        }
        $this->form();
    }

    private function form(): void
    {
        if ($this->user !== null) {
            $this->redirect('index.php?tab=seguimiento');
        }
        $tab = $_GET['tab'] ?? 'login';
        $view = $tab === 'registro' ? 'register' : 'login';
        $this->render($view, ['loginRedirect' => $this->loginRedirect], $tab);
    }

    private function register(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password_confirm'] ?? '');
        $redirectTab = validTab((string) ($_POST['redirect'] ?? '')) ? (string) $_POST['redirect'] : 'seguimiento';

        if (!verifyCsrf()) {
            $this->error = 'La sesión expiró. Vuelva a intentarlo.';
        } elseif (loginBlocked()) {
            $this->error = 'Demasiados intentos. Espere unos minutos y vuelva a intentarlo.';
        } elseif ($name === '') {
            $this->error = 'El nombre es obligatorio.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error = 'Ingrese un correo electrónico válido.';
        } elseif (strlen($pass) < 6) {
            $this->error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($pass !== $pass2) {
            $this->error = 'Las contraseñas no coinciden.';
        } elseif ($this->db->getUserByEmail($email) !== null) {
            $this->error = 'Ya existe una cuenta con ese correo.';
        } else {
            $userId = $this->db->registerUser($name, $email, password_hash($pass, PASSWORD_DEFAULT));
            session_regenerate_id(true);
            resetLoginRateLimit();
            $_SESSION['user_id'] = $userId;
            $this->redirect('index.php?tab=' . urlencode($redirectTab));
        }

        $this->render('register', ['loginRedirect' => $this->loginRedirect], 'registro');
    }

    private function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $redirectTab = validTab((string) ($_POST['redirect'] ?? '')) ? (string) $_POST['redirect'] : 'seguimiento';
        $found = $this->db->getUserByEmail($email);

        if (!verifyCsrf()) {
            $this->error = 'La sesión expiró. Vuelva a intentarlo.';
        } elseif (loginBlocked()) {
            $this->error = 'Demasiados intentos. Espere unos minutos y vuelva a intentarlo.';
        } elseif (!$found || !password_verify($pass, $found['password_hash'])) {
            registerLoginFailure();
            $this->error = 'Correo o contraseña incorrectos.';
        } else {
            session_regenerate_id(true);
            resetLoginRateLimit();
            $_SESSION['user_id'] = (int) $found['id'];
            $this->redirect('index.php?tab=' . urlencode($redirectTab));
        }

        $this->render('login', ['loginRedirect' => $this->loginRedirect], 'login');
    }

    private function logout(): void
    {
        if (!verifyCsrf()) {
            $this->error = 'La sesión expiró. Vuelva a intentarlo.';
            $this->form();
            return;
        }
        session_unset();
        session_destroy();
        $this->redirect('index.php?tab=siembra');
    }
}