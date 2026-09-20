<?php
declare(strict_types=1);

namespace App\Service;

final class CacheService
{
    public function __construct(
        private readonly ?string $cacheDir = null,
        private readonly int $defaultTtl = 3600
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->cacheFile($key);

        if (!is_file($file)) {
            return $default;
        }

        try {
            $data = file_get_contents($file);

            if ($data === false) {
                return $default;
            }

            $cache = unserialize($data, ['allowed_classes' => true]);

            if (
                !is_array($cache)
                || !isset($cache['expires'], $cache['value'])
                || (int) $cache['expires'] < time()
            ) {
                $this->delete($key);
                return $default;
            }

            return $cache['value'];
        } catch (\Throwable) {
            $this->delete($key);
            return $default;
        }
    }

    public function set(
        string $key,
        mixed $value,
        ?int $ttl = null
    ): bool {
        $ttl = $ttl ?? $this->defaultTtl;
        $directory = $this->directory();

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false;
        }

        $payload = serialize([
            'expires' => time() + max(0, $ttl),
            'value' => $value,
        ]);

        return file_put_contents(
            $this->cacheFile($key),
            $payload,
            LOCK_EX
        ) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->cacheFile($key);

        return !is_file($file) || unlink($file);
    }

    public function clear(): bool
    {
        $files = glob($this->directory() . DIRECTORY_SEPARATOR . '*.cache') ?: [];
        $success = true;

        foreach ($files as $file) {
            if (is_file($file) && !unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    public function has(string $key): bool
    {
        return $this->get($key, null) !== null;
    }

    private function directory(): string
    {
        return $this->cacheDir
            ?? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'viteetgourmand_cache');
    }

    private function cacheFile(string $key): string
    {
        return $this->directory()
            . DIRECTORY_SEPARATOR
            . hash('sha256', $key)
            . '.cache';
    }
}
