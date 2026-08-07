<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

final class Database
{
    private PDO $pdo;

    public function __construct(string $dbPath = DB_PATH)
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException(
                'La extensión "pdo_sqlite" no está disponible. '
                . 'Instálala con: sudo apt-get install php8.5-sqlite3'
            );
        }
        $this->pdo = new PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->createTables();
    }

    private function createTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS plants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                plant_group TEXT DEFAULT '',
                month_plant_min INTEGER,
                month_plant_max INTEGER,
                siembra_semillero INTEGER DEFAULT 0,
                siembra_directa INTEGER DEFAULT 0,
                tiempo_cosechar INTEGER,
                clima_templado INTEGER DEFAULT 0,
                clima_frio INTEGER DEFAULT 0,
                fase_cosechar TEXT DEFAULT ''
            );

            CREATE TABLE IF NOT EXISTS plant_phases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                plant_id INTEGER NOT NULL,
                name TEXT NOT NULL DEFAULT '',
                phase_order INTEGER DEFAULT 0,
                duration_min_days INTEGER,
                duration_max_days INTEGER,
                light_indoor TEXT DEFAULT '',
                light_outdoor TEXT DEFAULT '',
                water TEXT DEFAULT '',
                water_ph_min REAL,
                water_ph_max REAL,
                temp_day_min REAL,
                temp_day_max REAL,
                temp_night_min REAL,
                temp_night_max REAL,
                humidity_min REAL,
                humidity_max REAL,
                notes TEXT DEFAULT '',
                FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS germination_plants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                plant_id INTEGER NOT NULL,
                name TEXT NOT NULL DEFAULT '',
                germination_date TEXT NOT NULL,
                notes TEXT DEFAULT '',
                FOREIGN KEY (plant_id) REFERENCES plants(id)
            );

            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
        ");
    }

    public function getPlants(): array
    {
        $rows = $this->pdo->query('SELECT * FROM plants ORDER BY name')->fetchAll();
        foreach ($rows as &$row) {
            $row['phases'] = $this->getPhases((int) $row['id']);
        }
        return $rows;
    }

    public function getPlant(int $plantId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM plants WHERE id = ?');
        $stmt->execute([$plantId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['phases'] = $this->getPhases($plantId);
        return $row;
    }

    public function getPhases(int $plantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM plant_phases WHERE plant_id = ? ORDER BY phase_order'
        );
        $stmt->execute([$plantId]);
        return $stmt->fetchAll();
    }

    public function getGerminationPlants(): array
    {
        return $this->pdo->query(
            "SELECT g.*, p.name AS plant_name
             FROM germination_plants g
             JOIN plants p ON p.id = g.plant_id
             ORDER BY g.germination_date"
        )->fetchAll();
    }

    public function addGerminationPlant(int $plantId, string $name, string $germinationDate, string $notes = ''): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO germination_plants (plant_id, name, germination_date, notes) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$plantId, $name, $germinationDate, $notes]);
    }

    public function deleteGerminationPlant(int $gpId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM germination_plants WHERE id = ?');
        $stmt->execute([$gpId]);
    }

    public function countPlants(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM plants')->fetchColumn();
    }

    public function importFromJson(string $filepath): int
    {
        if (!file_exists($filepath)) {
            throw new RuntimeException("No se encontró el archivo de datos: $filepath");
        }
        $data = json_decode((string) file_get_contents($filepath), true, 512, JSON_THROW_ON_ERROR);

        $exists = $this->pdo->prepare('SELECT id FROM plants WHERE name = ?');
        $insPlant = $this->pdo->prepare(
            'INSERT INTO plants (name, plant_group, month_plant_min, month_plant_max,
                 siembra_semillero, siembra_directa, tiempo_cosechar,
                 clima_templado, clima_frio, fase_cosechar)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insPhase = $this->pdo->prepare(
            'INSERT INTO plant_phases (plant_id, name, phase_order,
                 duration_min_days, duration_max_days, light_indoor, light_outdoor,
                 water, water_ph_min, water_ph_max, temp_day_min, temp_day_max,
                 temp_night_min, temp_night_max, humidity_min, humidity_max, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $count = 0;
        foreach ($data as $item) {
            $exists->execute([$item['name'] ?? '']);
            if ($exists->fetch()) {
                continue;
            }

            $insPlant->execute([
                $item['name'] ?? '',
                $item['plant_group'] ?? '',
                $item['month_plant_min'] ?? null,
                $item['month_plant_max'] ?? null,
                (int) !empty($item['siembra_semillero']),
                (int) !empty($item['siembra_directa']),
                $item['tiempo_cosechar'] ?? null,
                (int) !empty($item['clima_templado']),
                (int) !empty($item['clima_frio']),
                $item['fase_cosechar'] ?? '',
            ]);
            $plantId = (int) $this->pdo->lastInsertId();

            foreach ($item['phases'] ?? [] as $i => $ph) {
                $insPhase->execute([
                    $plantId,
                    $ph['name'] ?? '',
                    $ph['phase_order'] ?? $i,
                    $ph['duration_min_days'] ?? null,
                    $ph['duration_max_days'] ?? null,
                    $ph['light_indoor'] ?? '',
                    $ph['light_outdoor'] ?? '',
                    $ph['water'] ?? '',
                    $ph['water_ph_min'] ?? null,
                    $ph['water_ph_max'] ?? null,
                    $ph['temp_day_min'] ?? null,
                    $ph['temp_day_max'] ?? null,
                    $ph['temp_night_min'] ?? null,
                    $ph['temp_night_max'] ?? null,
                    $ph['humidity_min'] ?? null,
                    $ph['humidity_max'] ?? null,
                    $ph['notes'] ?? '',
                ]);
            }
            $count++;
        }
        return $count;
    }

    // ---------- Usuarios ----------

    public function registerUser(string $name, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $passwordHash]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
