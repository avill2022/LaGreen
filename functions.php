<?php

declare(strict_types=1);

const PHASE_COLORS = [
    'germinación' => '#4CAF50',
    'germinacion' => '#4CAF50',
    'plántula' => '#81C784',
    'plantula' => '#81C784',
    'vegetativa' => '#2E7D32',
    'floración' => '#FF9800',
    'floracion' => '#FF9800',
    'cosecha' => '#795548',
];

const FALLBACK_PHASE_COLORS = [
    '#4CAF50', '#81C784', '#2E7D32', '#FF9800',
    '#795548', '#42A5F5', '#AB47BC', '#EF5350',
];

const MONTHS_ABBR = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

const MONTHS_ES = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

function loadHortalizas(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $file = __DIR__ . '/hortalizas.json';
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);
    $cache = $data['hortalizas'] ?? [];
    return $cache;
}

function monthNumber(?string $name): ?int
{
    if ($name === null || trim($name) === '') {
        return null;
    }
    $needle = strtolower(trim($name));
    foreach (MONTHS_ES as $n => $label) {
        if (strtolower($label) === $needle) {
            return $n;
        }
    }
    return null;
}

function normalizeLower(?string $value): string
{
    $value = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $value)) : strtolower(trim((string) $value));
    return strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
}

function findHortalizaByName(string $name): ?array
{
    $target = normalizeLower($name);
    foreach (loadHortalizas() as $h) {
        if (normalizeLower($h['nombre'] ?? '') === $target) {
            return $h;
        }
    }
    return null;
}

/**
 * Calcula el cronograma de fases de una planta desde la fecha de germinación.
 */
function plantPhaseSchedule(array $plant, string $germDateStr): array
{
    $germ = parseDate($germDateStr) ?? new DateTime('today');
    $phases = $plant['phases'] ?? [];
    [$curIdx, , $curProgress] = currentPhase($plant, $germDateStr);

    $schedule = [];
    $offset = 0;
    $total = 0;
    foreach ($phases as $i => $ph) {
        $dMin = (int) ($ph['duration_min_days'] ?? 0);
        $dMax = (int) ($ph['duration_max_days'] ?? 0);
        if ($dMax === 0) {
            $dMax = $dMin !== 0 ? $dMin : 30;
        }
        $total += $dMax;

        $start = (clone $germ)->modify('+' . $offset . ' days');
        $end = (clone $germ)->modify('+' . ($offset + $dMax) . ' days');
        $isCurrent = $curIdx !== null && $curIdx === $i;
        $progress = $curIdx === null
            ? 0.0
            : ($i < $curIdx ? 1.0 : ($i === $curIdx ? $curProgress : 0.0));

        $schedule[] = [
            'name' => $ph['name'] ?: 'Fase ' . ($i + 1),
            'color' => phaseColor((string) $ph['name'], $i),
            'durMin' => $dMin,
            'durMax' => $dMax,
            'effectiveDays' => $dMax,
            'start' => $start,
            'end' => $end,
            'startStr' => $start->format('d/m/Y'),
            'endStr' => $end->format('d/m/Y'),
            'isCurrent' => $isCurrent,
            'progress' => $progress,
            'chips' => phaseChips($ph),
            'notes' => $ph['notes'] ?? '',
        ];
        $offset += $dMax;
    }

    $harvest = null;
    if ($schedule) {
        $harvest = $schedule[count($schedule) - 1]['end'];
    } else {
        $harvest = estimateHarvestDate($plant, $germDateStr);
    }

    return [
        'germDate' => $germ,
        'totalDays' => $total,
        'harvestDate' => $harvest,
        'today' => new DateTime('today'),
        'currentIdx' => $curIdx,
        'phases' => $schedule,
    ];
}

function phaseColor(string $name, int $index = 0): string
{
    $key = function_exists('mb_strtolower') ? mb_strtolower(trim($name)) : strtolower(trim($name));
    return PHASE_COLORS[$key] ?? FALLBACK_PHASE_COLORS[$index % count(FALLBACK_PHASE_COLORS)];
}

function parseDate(?string $value): ?DateTime
{
    if ($value === null || $value === '') {
        return null;
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date === false ? null : $date;
}

function dateOnly(DateTime $date): DateTime
{
    return new DateTime($date->format('Y-m-d'), new DateTimeZone('UTC'));
}

function dayDiff(DateTime $from, DateTime $to): int
{
    $from = dateOnly($from);
    $to = dateOnly($to);
    $diff = $from->diff($to);
    return $diff->invert ? -$diff->days : $diff->days;
}

function daysInMonth(int $year, int $month): int
{
    return cal_days_in_month(CAL_GREGORIAN, $month, $year);
}

/**
 * Calcula la fase actual y el progreso de una planta germinada.
 *
 * @return array{0: int|null, 1: string, 2: float, 3: array|null}
 *         [índice de fase, nombre de fase, progreso 0-1, fase o null]
 */
function currentPhase(array $plant, string $germDateStr): array
{
    $today = new DateTime('today');
    $germ = parseDate($germDateStr) ?? $today;
    $phases = $plant['phases'] ?? [];

    if (!$phases) {
        return [null, 'Sin fases', 0.0, null];
    }

    $totalDays = dayDiff($germ, $today);
    if ($totalDays < 0) {
        $ph = $phases[0];
        return [0, $ph['name'] ?: 'Fase 1', 0.0, $ph];
    }

    $cumulativeMin = 0;
    $cumulativeMax = 0;

    foreach ($phases as $i => $ph) {
        $dMin = (int) ($ph['duration_min_days'] ?? 0);
        $dMax = (int) ($ph['duration_max_days'] ?? 0);
        if ($dMax === 0) {
            $dMax = $dMin !== 0 ? $dMin : 30;
        }

        $cumulativeMax += $dMax;
        if ($totalDays < $cumulativeMax) {
            $phaseStart = $cumulativeMin;
            $duration = $dMax;
            $progress = $duration > 0 ? (float) ($totalDays - $phaseStart) / $duration : 1.0;
            return [$i, $ph['name'] ?: 'Fase ' . ($i + 1), min($progress, 1.0), $ph];
        }

        $cumulativeMin += $dMin;
    }

    return [count($phases), '', 1.0, null];
}

function estimateHarvestDate(array $plant, string $germDateStr): ?DateTime
{
    $germ = parseDate($germDateStr);
    if (!$germ) {
        return null;
    }

    $phases = $plant['phases'] ?? [];
    if ($phases) {
        $total = 0;
        foreach ($phases as $ph) {
            $max = (int) ($ph['duration_max_days'] ?? 0);
            $min = (int) ($ph['duration_min_days'] ?? 0);
            $total += $max !== 0 ? $max : ($min !== 0 ? $min : 30);
        }
        return (clone $germ)->modify('+' . $total . ' days');
    }

    if (!empty($plant['tiempo_cosechar'])) {
        return (clone $germ)->modify('+' . (int) $plant['tiempo_cosechar'] . ' months');
    }

    return null;
}

function phaseTotalDays(array $plant): int
{
    $phases = $plant['phases'] ?? [];
    if ($phases) {
        $total = 0;
        foreach ($phases as $ph) {
            $max = (int) ($ph['duration_max_days'] ?? 0);
            $min = (int) ($ph['duration_min_days'] ?? 0);
            $total += $max !== 0 ? $max : ($min !== 0 ? $min : 30);
        }
        return $total;
    }
    $months = (int) ($plant['tiempo_cosechar'] ?? 0);
    return ($months !== 0 ? $months : 3) * 30;
}

function phaseChips(array $phase): array
{
    $chips = [];
    $v = static fn (?string $s) => trim((string) $s) !== '';

    if ($v($phase['light_indoor'] ?? null)) {
        $chips[] = ['☀', 'Luz interior: ' . $phase['light_indoor'] . 'h'];
    }
    if ($v($phase['light_outdoor'] ?? null)) {
        $chips[] = ['☀', 'Exterior: ' . $phase['light_outdoor']];
    }
    if ($v($phase['water'] ?? null)) {
        $chips[] = ['💧', $phase['water']];
    }
    if (hasValue($phase, 'water_ph_min') && hasValue($phase, 'water_ph_max')) {
        $chips[] = ['⚗', 'pH ' . $phase['water_ph_min'] . '-' . $phase['water_ph_max']];
    } elseif (hasValue($phase, 'water_ph_min')) {
        $chips[] = ['⚗', 'pH ' . $phase['water_ph_min']];
    }
    if (hasValue($phase, 'temp_day_min') && hasValue($phase, 'temp_day_max')) {
        $chips[] = ['🌡', $phase['temp_day_min'] . '-' . $phase['temp_day_max'] . '°C día'];
    }
    if (hasValue($phase, 'temp_night_min') && hasValue($phase, 'temp_night_max')) {
        $chips[] = ['🌡', $phase['temp_night_min'] . '-' . $phase['temp_night_max'] . '°C noche'];
    }
    if (hasValue($phase, 'humidity_min') && hasValue($phase, 'humidity_max')) {
        $chips[] = ['💨', 'Humedad ' . $phase['humidity_min'] . '-' . $phase['humidity_max'] . '%'];
    }

    return $chips;
}

function hasValue(array $phase, string $key): bool
{
    if (!array_key_exists($key, $phase) || $phase[$key] === null || $phase[$key] === '') {
        return false;
    }
    return true;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function plantSlug(string $name): string
{
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
    return trim((string) $slug, '-');
}

function hortDetailUrl(array $h): string
{
    $id = (string) ($h['id'] ?? '');
    if ($id === '') {
        $id = (string) ($h['nombre'] ?? '');
    }
    return 'detail.php?id=' . urlencode($id);
}

function dificultadClass(?string $dificultad): string
{
    $d = strtolower(trim((string) $dificultad));
    return match (true) {
        str_contains($d, 'alta') => 'dif-alta',
        str_contains($d, 'media') => 'dif-media',
        default => 'dif-baja',
    };
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): bool
{
    $sent = (string) ($_POST['csrf'] ?? '');
    return $sent !== '' && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

function secureSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_name('LAGREEN_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/**
 * Rate limiting para login/registro. Devuelve true si la petición está bloqueada.
 */
function loginBlocked(): bool
{
    $g = $_SESSION['login_guard'] ?? null;
    if ($g === null) {
        return false;
    }
    return $g['blocked_until'] > time();
}

function registerLoginFailure(): void
{
    $now = time();
    $g = $_SESSION['login_guard'] ?? ['count' => 0, 'window_start' => $now, 'blocked_until' => 0];
    if ($now - $g['window_start'] >= 900) {
        $g = ['count' => 0, 'window_start' => $now, 'blocked_until' => 0];
    }
    $g['count']++;
    if ($g['count'] >= 5) {
        $g['blocked_until'] = $now + 300;
        $g['count'] = 0;
        $g['window_start'] = $now;
    }
    $_SESSION['login_guard'] = $g;
}

function resetLoginRateLimit(): void
{
    unset($_SESSION['login_guard']);
}

function validTab(string $tab): bool
{
    return in_array($tab, ['seguimiento', 'calendario', 'siembra', 'calculadora', 'login', 'registro'], true);
}
