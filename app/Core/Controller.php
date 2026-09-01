<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Catalog;
use App\Models\Database;

abstract class Controller
{
    protected Database $db;
    protected Catalog $catalog;
    protected ?array $user;
    protected string $error = '';
    protected string $success = '';
    protected string $loginRedirect = 'seguimiento';

    public function __construct(Database $db, Catalog $catalog, ?array $user)
    {
        $this->db = $db;
        $this->catalog = $catalog;
        $this->user = $user;
    }

    abstract public function handle(): void;

    public function setLoginRedirect(string $tab): void
    {
        $this->loginRedirect = $tab;
    }

    protected function render(string $view, array $data = [], string $activeTab = ''): void
    {
        $content = $this->renderView($view, $data);
        $user = $this->user;
        $error = $this->error;
        $success = $this->success;
        include VIEWS_DIR . '/layout.php';
    }

    protected function renderView(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include VIEWS_DIR . '/' . $view . '.php';
        return (string) ob_get_clean();
    }

    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    protected function redirectSamePath(string $query): never
    {
        $uri = $_SERVER['REQUEST_URI'] ?? 'index.php';
        $base = strtok($uri, '?') ?: 'index.php';
        $this->redirect($base . '?' . $query);
    }
}