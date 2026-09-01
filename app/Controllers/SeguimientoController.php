<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class SeguimientoController extends Controller
{
    public function handle(): void
    {
        if ($this->user === null) {
            $this->redirect('index.php?tab=login');
        }

        $action = trim((string) ($_POST['action'] ?? ''));
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'delete'], true)) {
            if ($action === 'add') {
                $this->add();
            } else {
                $this->delete();
            }
            if ($this->error !== '') {
                $this->index();
            }
            return;
        }

        $this->index();
    }

    private function index(): void
    {
        $plants = $this->db->getPlants();
        $gps = $this->db->getGerminationPlants((int) $this->user['id']);
        $submitted = [
            'plant_id' => (int) ($_POST['plant_id'] ?? 0),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'germination_date' => trim((string) ($_POST['germination_date'] ?? '')) ?: date('Y-m-d'),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        $tracked = [];
        foreach ($gps as $gp) {
            $plant = $this->db->getPlant((int) $gp['plant_id']);
            if (!$plant) {
                continue;
            }
            [$phaseIdx, $phaseName, $progress, $currentPhase] = currentPhase($plant, $gp['germination_date']);
            $tracked[] = [
                'gp' => $gp,
                'plant' => $plant,
                'phaseIdx' => $phaseIdx,
                'phaseName' => $phaseName,
                'progress' => $progress,
                'currentPhase' => $currentPhase,
                'statusColor' => $phaseName !== '' ? '#4CAF50' : '#FF9800',
                'phaseStatus' => $phaseName !== '' ? 'Fase actual: ' . $phaseName : 'Completada',
            ];
        }

        $this->render('seguimiento', [
            'plants' => $plants,
            'gps' => $gps,
            'tracked' => $tracked,
            'submitted' => $submitted,
        ], 'seguimiento');
    }

    private function add(): void
    {
        $plantId = (int) ($_POST['plant_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $date = trim((string) ($_POST['germination_date'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $tab = $_POST['tab'] ?? 'seguimiento';
        $plant = $plantId > 0 ? $this->db->getPlant($plantId) : null;

        if (!$this->user) {
            $this->error = 'Debe iniciar sesión para gestionar el seguimiento.';
        } elseif (!verifyCsrf()) {
            $this->error = 'La sesión expiró. Vuelva a intentarlo.';
        } elseif (!$plant) {
            $this->error = 'Seleccione un tipo de planta.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error = 'Ingrese una fecha válida (YYYY-MM-DD).';
        } else {
            if ($name === '') {
                $name = $plant['name'];
            }
            $this->db->addGerminationPlant($plantId, (int) $this->user['id'], $name, $date, $notes);
            $this->success = 'Planta añadida al seguimiento.';
            $this->redirect('index.php?tab=' . urlencode((string) $tab));
        }
    }

    private function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $tab = $_POST['tab'] ?? 'seguimiento';

        if (!$this->user) {
            $this->error = 'Debe iniciar sesión para gestionar el seguimiento.';
        } elseif (!verifyCsrf()) {
            $this->error = 'La sesión expiró. Vuelva a intentarlo.';
        } else {
            $deleted = $this->db->deleteGerminationPlant($id, (int) $this->user['id']);
            $this->success = $deleted ? 'Planta eliminada del seguimiento.' : 'No se pudo eliminar la planta.';
            $this->redirect('index.php?tab=' . urlencode((string) $tab));
        }
    }
}