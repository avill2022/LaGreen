<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class CalculadoraController extends Controller
{
    public function handle(): void
    {
        $plants = $this->db->getPlants();
        $plantId = (int) ($_GET['plant_id'] ?? 0);
        $calcDate = trim((string) ($_GET['date'] ?? '')) ?: date('Y-m-d');

        $plantSel = null;
        $result = null;
        $horta = null;
        $calcError = '';
        $slug = '';

        if ($plantId > 0) {
            $plantSel = $this->db->getPlant($plantId);
            if (!$plantSel) {
                $calcError = 'Planta no encontrada.';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $calcDate)) {
                $calcError = 'Ingrese una fecha válida (YYYY-MM-DD).';
            } else {
                $result = plantPhaseSchedule($plantSel, $calcDate);
                $horta = $this->catalog->findByName('hortalizas', $plantSel['name']);
                $slug = plantSlug($plantSel['name']);
            }
        }

        $this->render('calculadora', [
            'plants' => $plants,
            'plantId' => $plantId,
            'calcDate' => $calcDate,
            'plantSel' => $plantSel,
            'result' => $result,
            'horta' => $horta,
            'calcError' => $calcError,
            'slug' => $slug,
        ], 'calculadora');
    }
}