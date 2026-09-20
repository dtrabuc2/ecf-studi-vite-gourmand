<?php
declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function isLogged(): bool
    {
        return Session::isAuthenticated();
    }

    public static function isUser(): bool
    {
        return Session::role() === 'user';
    }

    public static function isAdmin(): bool
    {
        return Session::role() === 'admin';
    }

    public static function getCurrentUserId(): int|bool
    {
        return Session::id() ?? false;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function verifyCsrf(): bool
    {
        $token = $_POST['token'] ?? '';
        $storedToken = $_SESSION['csrf_token'] ?? null;

        return is_string($token)
            && is_string($storedToken)
            && hash_equals($storedToken, $token);
    }
}
