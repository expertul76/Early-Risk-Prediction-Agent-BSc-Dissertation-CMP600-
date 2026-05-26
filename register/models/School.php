<?php

class School {
    public static function create(string $name, string $adminEmail): int {
        $slug = self::generateSlug($name);
        return Database::insert('schools', [
            'name' => $name,
            'slug' => $slug,
            'admin_email' => $adminEmail,
        ]);
    }

    public static function findById(int $id): ?array {
        return Database::fetch('SELECT * FROM schools WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array {
        return Database::fetch('SELECT * FROM schools WHERE slug = ?', [$slug]);
    }

    private static function generateSlug(string $name): string {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $base = $slug;
        $i = 1;
        while (Database::fetch('SELECT id FROM schools WHERE slug = ?', [$slug])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
