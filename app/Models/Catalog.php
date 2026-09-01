<?php

declare(strict_types=1);

namespace App\Models;

final class Catalog
{
    /** @var array<string, array<int, array>> */
    private array $cache = [];

    /**
     * Elementos de una categoría (archivo data/{$category}.json).
     */
    public function category(string $category): array
    {
        $category = trim($category);
        if ($category === '' || !preg_match('/^[a-z0-9_-]+$/i', $category)) {
            return [];
        }
        if (!array_key_exists($category, $this->cache)) {
            $this->cache[$category] = $this->load($category);
        }
        return $this->cache[$category];
    }

    /**
     * Categorías disponibles en el directorio de datos.
     *
     * @return array<int, string>
     */
    public function available(): array
    {
        $cats = [];
        foreach (glob(DATA_DIR . '/*.json') ?: [] as $file) {
            $cats[] = basename($file, '.json');
        }
        sort($cats);
        return $cats;
    }

    public function findById(string $category, string $id): ?array
    {
        foreach ($this->category($category) as $item) {
            if ((string) ($item['id'] ?? '') === $id) {
                return $item;
            }
        }
        return null;
    }

    public function findByName(string $category, string $name): ?array
    {
        $target = self::normalize($name);
        foreach ($this->category($category) as $item) {
            if (self::normalize((string) ($item['nombre'] ?? '')) === $target) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Resuelve un elemento por id o por nombre.
     */
    public function resolve(string $category, string $value): ?array
    {
        $byId = $this->findById($category, $value);
        if ($byId !== null) {
            return $byId;
        }
        return $this->findByName($category, $value);
    }

    /**
     * @return array<int, array>
     */
    private function load(string $category): array
    {
        $file = DATA_DIR . '/' . $category . '.json';
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) {
            return [];
        }
        // Formato envuelto: {"hortalizas": [...]} junto a archivos de lista pura.
        if (isset($data[$category]) && is_array($data[$category])) {
            return array_values($data[$category]);
        }
        return array_values($data);
    }

    private static function normalize(?string $value): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $value)) : strtolower(trim((string) $value));
        return strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
    }
}