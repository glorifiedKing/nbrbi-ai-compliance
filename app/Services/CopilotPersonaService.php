<?php

namespace App\Services;

class CopilotPersonaService
{
    /**
     * Get all defined personas regardless of enabled state.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return config('copilot.personas', []);
    }

    /**
     * Get all enabled personas that should show up on view pages.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getEnabled(): array
    {
        return array_filter(self::all(), function (array $persona): bool {
            return (bool) ($persona['enabled'] ?? true);
        });
    }

    /**
     * Get the array of enabled persona keys (e.g., ['inspector_okello', 'architect_namubiru']).
     *
     * @return list<string>
     */
    public static function getEnabledKeys(): array
    {
        return array_values(array_keys(self::getEnabled()));
    }

    /**
     * Check if a specific persona is currently enabled.
     */
    public static function isEnabled(string $personaKey): bool
    {
        $persona = config("copilot.personas.{$personaKey}");

        if (! is_array($persona)) {
            return false;
        }

        return (bool) ($persona['enabled'] ?? true);
    }

    /**
     * Get the default persona key (first enabled persona, or fallback to 'inspector_okello').
     */
    public static function getDefaultPersonaKey(): string
    {
        $enabledKeys = self::getEnabledKeys();

        return $enabledKeys[0] ?? 'inspector_okello';
    }

    /**
     * Find metadata for a specific persona.
     *
     * @return array<string, mixed>|null
     */
    public static function find(string $personaKey): ?array
    {
        return config("copilot.personas.{$personaKey}");
    }
}
