<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

require_once __DIR__ . '/../../config.php';

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
        $this->migrate();
        $this->createIndexes();
        $this->backfillPlantImages();
    }

    private function backfillPlantImages(): void
    {
        $byName = [];
        if (file_exists(SEED_FILE)) {
            $seed = json_decode((string) file_get_contents(SEED_FILE), true);
            if (is_array($seed)) {
                foreach ($seed as $item) {
                    $byName[(string) ($item['name'] ?? '')] = (string) ($item['image'] ?? '');
                }
            }
        }

        $rows = $this->pdo->query("SELECT id, name, image FROM plants WHERE image = '' OR image IS NULL")->fetchAll();
        $upd = $this->pdo->prepare('UPDATE plants SET image = ? WHERE id = ?');
        foreach ($rows as $row) {
            $name = (string) $row['name'];
            $image = $byName[$name] ?? ('https://avillsoftware.com/img/lagreen/' . self::slug($name) . '.png');
            if ($image !== '') {
                $upd->execute([$image, (int) $row['id']]);
            }
        }
    }

    private static function slug(string $name): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
        return trim((string) $slug, '-');
    }

    private function migrate(): void
    {
        $this->addColumnIfMissing('germination_plants', 'user_id', 'INTEGER REFERENCES users(id) ON DELETE CASCADE');
        $this->addColumnIfMissing('plants', 'image', "TEXT DEFAULT ''");
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $columns = $this->pdo->query("PRAGMA table_info($table)")->fetchAll();
        foreach ($columns as $col) {
            if ($col['name'] === $column) {
                return;
            }
        }
        $this->pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
    }

    private function createIndexes(): void
    {
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_gp_user ON germination_plants(user_id)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_phases_plant ON plant_phases(plant_id)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)');
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
                fase_cosechar TEXT DEFAULT '',
                image TEXT DEFAULT ''
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
                user_id INTEGER,
                name TEXT NOT NULL DEFAULT '',
                germination_date TEXT NOT NULL,
                notes TEXT DEFAULT '',
                FOREIGN KEY (plant_id) REFERENCES plants(id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
        $this->attachPhases($rows);
        return $rows;
    }

    private function attachPhases(array &$rows): void
    {
        if (!$rows) {
            return;
        }
        $ids = array_map('intval', array_column($rows, 'id'));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM plant_phases WHERE plant_id IN ($placeholders) ORDER BY phase_order"
        );
        $stmt->execute($ids);
        $byPlant = [];
        foreach ($stmt->fetchAll() as $ph) {
            $byPlant[(int) $ph['plant_id']][] = $ph;
        }
        foreach ($rows as &$row) {
            $row['phases'] = $byPlant[(int) $row['id']] ?? [];
        }
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

    public function getGerminationPlants(?int $userId = null): array
    {
        if ($userId === null) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            "SELECT g.*, p.name AS plant_name
             FROM germination_plants g
             JOIN plants p ON p.id = g.plant_id
             WHERE g.user_id = ?
             ORDER BY g.germination_date"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function addGerminationPlant(int $plantId, int $userId, string $name, string $germinationDate, string $notes = ''): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO germination_plants (plant_id, user_id, name, germination_date, notes) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$plantId, $userId, $name, $germinationDate, $notes]);
    }

    public function deleteGerminationPlant(int $gpId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM germination_plants WHERE id = ? AND user_id = ?');
        $stmt->execute([$gpId, $userId]);
        return $stmt->rowCount() > 0;
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
                 clima_templado, clima_frio, fase_cosechar, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insPhase = $this->pdo->prepare(
            'INSERT INTO plant_phases (plant_id, name, phase_order,
                 duration_min_days, duration_max_days, light_indoor, light_outdoor,
                 water, water_ph_min, water_ph_max, temp_day_min, temp_day_max,
                 temp_night_min, temp_night_max, humidity_min, humidity_max, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $count = 0;
        $this->pdo->beginTransaction();
        try {
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
                $item['image'] ?? '',
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
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
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
