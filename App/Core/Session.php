<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function id(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;

        return is_numeric($id) && (int) $id > 0
            ? (int) $id
            : null;
    }

    public static function role(): ?string
    {
        $role = $_SESSION['role'] ?? null;

        return is_string($role) && $role !== ''
            ? $role
            : null;
    }

    public static function isAuthenticated(): bool
    {
        return self::id() !== null;
    }

    public static function login(
        int $userId,
        string $role,
        array $identity = []
    ): void {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;

        foreach ($identity as $key => $value) {
            $_SESSION[$key] = $value;
        }

        self::csrfToken();
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }

    public static function csrfToken(): string
    {
        if (
            !isset($_SESSION['csrf_token'])
            || !is_string($_SESSION['csrf_token'])
            || strlen($_SESSION['csrf_token']) !== 64
        ) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pullFlash(
        string $key,
        mixed $default = null
    ): mixed {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}