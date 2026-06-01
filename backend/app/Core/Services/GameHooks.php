<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\Log;

class GameHooks
{
    protected static array $listeners = [];

    public static function listen(string $hook, callable $callback, int $priority = 10): void
    {
        static::$listeners[$hook][$priority][] = $callback;
        ksort(static::$listeners[$hook]);
    }

    public static function apply(string $hook, mixed $payload = []): mixed
    {
        $result = $payload;

        if (!isset(static::$listeners[$hook])) {
            return $result;
        }

        foreach (static::$listeners[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $result = call_user_func($callback, $result);
                } catch (\Throwable $e) {
                    Log::warning("GameHooks: Listener for '{$hook}' failed: " . $e->getMessage());
                }
            }
        }

        return $result;
    }

    public static function hasListeners(string $hook): bool
    {
        return isset(static::$listeners[$hook]) && !empty(static::$listeners[$hook]);
    }

    public static function removeAll(string $hook): void
    {
        unset(static::$listeners[$hook]);
    }

    public static function getListeners(string $hook): array
    {
        return static::$listeners[$hook] ?? [];
    }

    public static function define(string $hook, array $schema = [], string $version = '1.0', string $stability = 'stable'): void
    {
        HookRegistry::define($hook, $schema, $version, $stability);
    }

    public static function allListeners(): array
    {
        return static::$listeners;
    }
}
