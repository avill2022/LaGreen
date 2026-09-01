<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class SiembraController extends Controller
{
    public function handle(): void
    {
        $hortalizas = $this->catalog->category('hortalizas');

        $plantImages = [];
        foreach ($this->db->getPlants() as $p) {
            $img = (string) ($p['image'] ?? '');
            if ($img !== '') {
                $plantImages[normalizeLower((string) $p['name'])] = $img;
            }
        }

        $currentMonthNum = (int) date('n');
        $currentYear = (int) date('Y');
        $currentMonthName = MONTHS_ES[$currentMonthNum];

        $thisMonth = [];
        foreach ($hortalizas as $h) {
            foreach ($h['ficha']['meses_siembra'] ?? [] as $m) {
                if (monthNumber($m) === $currentMonthNum) {
                    $thisMonth[] = $h;
                    break;
                }
            }
        }

        $this->render('siembra', [
            'hortalizas' => $hortalizas,
            'plantImages' => $plantImages,
            'currentMonthNum' => $currentMonthNum,
            'currentYear' => $currentYear,
            'currentMonthName' => $currentMonthName,
            'thisMonth' => $thisMonth,
        ], 'siembra');
    }
}