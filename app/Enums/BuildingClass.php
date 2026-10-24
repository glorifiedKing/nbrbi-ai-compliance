<?php

namespace App\Enums;

enum BuildingClass: string
{
    case CLASS_A = 'Class A (Complex & Public Buildings)';
    case CLASS_B = 'Class B (Residential & Commercial)';
    case CLASS_C = 'Class C (Minor & Temporary Buildings)';

    /**
     * Official title under the Uganda Building Control Act.
     */
    public function label(): string
    {
        return match ($this) {
            self::CLASS_A => 'Class A: Complex and Public Buildings',
            self::CLASS_B => 'Class B: Standard Residential and Commercial Buildings',
            self::CLASS_C => 'Class C: Minor and Temporary Buildings',
        };
    }

    /**
     * Statutory scope of the building class.
     */
    public function scope(): string
    {
        return match ($this) {
            self::CLASS_A => 'Comprises complex structural forms, multi-story buildings more than 12 meters high, public buildings, and building complexes (mixtures of uses).',
            self::CLASS_B => 'Comprises residential or commercial buildings with a floor area greater than 30 square meters, or single-to-multi-story buildings up to 12 meters high of simple structural form.',
            self::CLASS_C => 'Comprises minor and temporary building operations with a simple structural form, a single story, and a floor area of less than 30 square meters.',
        };
    }

    /**
     * Examples of buildings falling under this classification.
     */
    public function examples(): string
    {
        return match ($this) {
            self::CLASS_A => 'Schools, hospitals, shopping malls, churches, mosques, hotels, arcades, and buildings of high social impact or in sensitive ecosystems.',
            self::CLASS_B => 'Standard residential bungalows, commercial shop houses, and boundary walls built of bricks, concrete, or permanent solid materials.',
            self::CLASS_C => 'Small temporary structures, kiosks, or minor utility buildings.',
        };
    }

    /**
     * Default statutory plan review fee rate in UGX per square meter.
     */
    public function ratePerSqm(): float
    {
        return match ($this) {
            self::CLASS_A => 4000.00,
            self::CLASS_B => 2500.00,
            self::CLASS_C => 1500.00,
        };
    }

    /**
     * Try to normalize any string representation or legacy occupancy string to a canonical BuildingClass.
     */
    public static function tryNormalize(?string $value): ?self
    {
        if (! $value) {
            return null;
        }

        $trimmed = trim($value);

        foreach (self::cases() as $case) {
            if (strcasecmp($trimmed, $case->value) === 0 || strcasecmp($trimmed, $case->name) === 0) {
                return $case;
            }
        }

        $lower = strtolower($trimmed);

        // Check Class C: Minor / Temporary / Kiosk (<30m²)
        if (
            str_contains($lower, 'class c') ||
            str_contains($lower, 'minor') ||
            str_contains($lower, 'temporary') ||
            str_contains($lower, 'kiosk')
        ) {
            return self::CLASS_C;
        }

        // Check Class A: Complex, Public, School, Hospital, Mall, Church, Mosque, Hotel, Arcade, High-Rise (>12m)
        if (
            str_contains($lower, 'complex') ||
            str_contains($lower, 'public') ||
            str_contains($lower, 'hospital') ||
            str_contains($lower, 'school') ||
            str_contains($lower, 'mall') ||
            str_contains($lower, 'hotel') ||
            str_contains($lower, 'arcade') ||
            str_contains($lower, 'church') ||
            str_contains($lower, 'mosque') ||
            str_contains($lower, 'tower') ||
            str_contains($lower, 'high-rise')
        ) {
            return self::CLASS_A;
        }

        // If explicitly named Class A (without legacy residential override)
        if (str_contains($lower, 'class a')) {
            if (str_contains($lower, 'residential')) {
                // In Uganda, standard residential is Class B
                return self::CLASS_B;
            }

            return self::CLASS_A;
        }

        // Check Class B: Standard Residential and Commercial (>30m², up to 12m)
        if (
            str_contains($lower, 'class b') ||
            str_contains($lower, 'residential') ||
            str_contains($lower, 'bungalow') ||
            str_contains($lower, 'commercial') ||
            str_contains($lower, 'office') ||
            str_contains($lower, 'shop')
        ) {
            return self::CLASS_B;
        }

        return null;
    }
}
