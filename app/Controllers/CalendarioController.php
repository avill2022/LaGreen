<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class CalendarioController extends Controller
{
    public function handle(): void
    {
        if ($this->user === null) {
            $this->redirect('index.php?tab=login');
        }

        $gps = $this->db->getGerminationPlants((int) $this->user['id']);
        $plantData = [];
        foreach ($gps as $gp) {
            $germ = parseDate($gp['germination_date']);
            if (!$germ) {
                continue;
            }
            $plant = $this->db->getPlant((int) $gp['plant_id']);
            if (!$plant) {
                continue;
            }
            $plantData[] = [
                'gp' => $gp,
                'plant' => $plant,
                'germ' => $germ,
                'total' => phaseTotalDays($plant),
            ];
        }

        $this->render('calendario', ['gps' => $gps, 'plantData' => $plantData], 'calendario');
    }
}