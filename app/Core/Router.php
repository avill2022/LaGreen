<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\CalendarioController;
use App\Controllers\CalculadoraController;
use App\Controllers\DetalleController;
use App\Controllers\SeguimientoController;
use App\Controllers\SiembraController;

final class Router
{
    public function controllerFor(string $tab): ?string
    {
        return match ($tab) {
            'seguimiento' => SeguimientoController::class,
            'calendario' => CalendarioController::class,
            'siembra' => SiembraController::class,
            'calculadora' => CalculadoraController::class,
            'detalle' => DetalleController::class,
            'login', 'registro' => AuthController::class,
            default => null,
        };
    }
}