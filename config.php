<?php

declare(strict_types=1);

const APP_NAME = 'Gestor de Fases de Plantas';
const DB_PATH = __DIR__ . '/plants.db';
const SEED_FILE = __DIR__ . '/example_plants.json';
const DATA_DIR = __DIR__ . '/data';
const VIEWS_DIR = __DIR__ . '/views';
const MIN_PX_PER_DAY = 3;
const GANTT_MARGIN_LEFT = 150;
const GANTT_MARGIN_RIGHT = 60;
const GANTT_ROW_HEIGHT = 50;
const GANTT_MONTH_BAR_HEIGHT = 25;

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $path = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) {
        require $path;
    }
});