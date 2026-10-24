<?php

namespace App\Services;

use App\Enums\BuildingClass;
use App\Models\Project;

class FeeCalculationService
{
    /**
     * Standard statutory rates per square meter (UGX/m²) under Uganda Building Control Regulations.
     */
    public const DEFAULT_MINIMUM_FEE_UGX = 250000.00;

    /**
     * Official Uganda statutory rates for Class A, Class B, and Class C under the Uganda Building Control Act.
     */
    public const RATES_PER_SQM = [
        // Uganda Building Control Act Official Classes
        'Class A (Complex & Public Buildings)' => 4000.00,
        'Class A: Complex and Public Buildings' => 4000.00,
        'Class A' => 4000.00,

        'Class B (Residential & Commercial)' => 2500.00,
        'Class B: Standard Residential and Commercial Buildings' => 2500.00,
        'Class B' => 2500.00,

        'Class C (Minor & Temporary Buildings)' => 1500.00,
        'Class C: Minor and Temporary Buildings' => 1500.00,
        'Class C' => 1500.00,

        // Legacy compatibility entries
        'Class B (Commercial & Offices)' => 4000.00,
        'Class B (Commercial)' => 4000.00,
        'Class A (Residential)' => 2500.00,
        'Class C (Industrial)' => 4000.00,
        'Class C (Industrial & Storage)' => 4000.00,
        'Class D (Hazardous)' => 5000.00,
        'Class E (Institutional)' => 4000.00,
        'Class E (Institutional / Assembly)' => 4000.00,
    ];

    public const DEFAULT_RATE_PER_SQM = 2500.00;

    /**
     * Resolves statutory fee rate per m² based on the building class.
     */
    public static function getRatePerSqm(?string $occupancyClass): float
    {
        if (! $occupancyClass) {
            return self::DEFAULT_RATE_PER_SQM;
        }

        // 1. Direct match in lookup table
        foreach (self::RATES_PER_SQM as $classKey => $rate) {
            if (strcasecmp($occupancyClass, $classKey) === 0) {
                return $rate;
            }
        }

        // 2. Resolve via official Uganda BuildingClass enum
        $enumClass = BuildingClass::tryNormalize($occupancyClass);
        if ($enumClass) {
            return $enumClass->ratePerSqm();
        }

        // 3. Fallback partial match in lookup table
        foreach (self::RATES_PER_SQM as $classKey => $rate) {
            if (stripos($occupancyClass, $classKey) !== false || stripos($classKey, $occupancyClass) !== false) {
                return $rate;
            }
        }

        return self::DEFAULT_RATE_PER_SQM;
    }

    /**
     * Calculates the estimated statutory plan examination fee in UGX.
     */
    public static function calculateFee(float $areaSqm, ?string $occupancyClass): float
    {
        if ($areaSqm <= 0) {
            return 0.00;
        }

        $rate = self::getRatePerSqm($occupancyClass);
        $computedFee = $areaSqm * $rate;

        return round(max($computedFee, self::DEFAULT_MINIMUM_FEE_UGX), 2);
    }

    /**
     * Recomputes floor area aggregations for all blocks and the overall project,
     * updating the statutory fee accordingly.
     */
    public static function recalculateProjectTotals(Project $project): Project
    {
        $project->loadMissing(['blocks.floors']);

        $totalProjectArea = 0.00;

        foreach ($project->blocks as $block) {
            $blockArea = $block->floors->sum(function ($floor) {
                return (float) ($floor->gross_floor_area_sqm ?? 0.00);
            });

            $block->updateQuietly([
                'total_floor_area_sqm' => round($blockArea, 2),
            ]);

            $totalProjectArea += $blockArea;
        }

        $rate = self::getRatePerSqm($project->occupancy_class);
        $totalProjectArea = round($totalProjectArea, 2);
        $estimatedFee = self::calculateFee($totalProjectArea, $project->occupancy_class);

        $project->update([
            'total_floor_area_sqm' => $totalProjectArea,
            'fee_rate_per_sqm_ugx' => $rate,
            'estimated_statutory_fee_ugx' => $estimatedFee,
        ]);

        return $project;
    }
}
