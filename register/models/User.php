<?php

class User {
    public static function create(array $data): int {
        return Database::insert('users', $data);
    }

    public static function findById(int $id): ?array {
        return Database::fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(int $schoolId, string $email): ?array {
        return Database::fetch(
            'SELECT * FROM users WHERE school_id = ? AND email = ?',
            [$schoolId, $email]
        );
    }

    public static function findByEmailAnySchool(string $email): ?array {
        return Database::fetch(
            'SELECT u.*, s.name as school_name FROM users u JOIN schools s ON u.school_id = s.id WHERE u.email = ? AND u.is_active = 1',
            [$email]
        );
    }

    public static function findByInviteToken(string $token): ?array {
        return Database::fetch(
            'SELECT u.*, s.name as school_name FROM users u JOIN schools s ON u.school_id = s.id WHERE u.invite_token = ? AND u.invite_expires > NOW()',
            [$token]
        );
    }

    public static function listBySchool(int $schoolId): array {
        return Database::fetchAll(
            'SELECT * FROM users WHERE school_id = ? ORDER BY role DESC, first_name ASC',
            [$schoolId]
        );
    }

    public static function activate(int $id, string $passwordHash): void {
        Database::update('users', [
            'password_hash' => $passwordHash,
            'is_active' => 1,
            'invite_token' => null,
            'invite_expires' => null,
        ], 'id = ?', [$id]);
    }

    public static function delete(int $id, int $schoolId): void {
        Database::delete('users', 'id = ? AND school_id = ?', [$id, $schoolId]);
    }
}
