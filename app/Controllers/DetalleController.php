<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class DetalleController extends Controller
{
    public function handle(): void
    {
        $requestId = trim((string) ($_GET['id'] ?? ''));
        $requestName = trim((string) ($_GET['nombre'] ?? ''));

        $hortaliza = null;
        if ($requestId !== '') {
            $hortaliza = $this->catalog->resolve('hortalizas', $requestId);
        } elseif ($requestName !== '') {
            $hortaliza = $this->catalog->findByName('hortalizas', $requestName);
        }

        $plantImages = [];
        foreach ($this->db->getPlants() as $p) {
            $img = (string) ($p['image'] ?? '');
            if ($img !== '') {
                $plantImages[normalizeLower((string) $p['name'])] = $img;
            }
        }

        $foto = '';
        if ($hortaliza) {
            $foto = $plantImages[normalizeLower((string) ($hortaliza['nombre'] ?? ''))] ?? ($hortaliza['foto'] ?? '');
        }

        $this->render('detalle', [
            'hortaliza' => $hortaliza,
            'foto' => $foto,
        ], 'detalle');
    }
}