<?php

namespace App\Services;

use App\Models\Project;

class FeeCalculationService
{
    /**
     * Standard statutory rates per square meter (UGX/m²) under Uganda Building Control Regulations.
     */
    public const DEFAULT_MINIMUM_FEE_UGX = 250000.00;

    public const RATES_PER_SQM = [
        'Class A (Residential)' => 2500.00,
        'Class B (Commercial & Offices)' => 4000.00,
        'Class B (Commercial)' => 4000.00,
        'Class C (Industrial)' => 5000.00,
        'Class C (Industrial & Storage)' => 5000.00,
        'Class D (Hazardous)' => 6500.00,
        'Class E (Institutional)' => 3000.00,
        'Class E (Institutional / Assembly)' => 3000.00,
    ];

    public const DEFAULT_RATE_PER_SQM = 3500.00;

    /**
     * Resolves statutory fee rate per m² based on the building occupancy class.
     */
    public static function getRatePerSqm(?string $occupancyClass): float
    {
        if (! $occupancyClass) {
            return self::DEFAULT_RATE_PER_SQM;
        }

        foreach (self::RATES_PER_SQM as $classKey => $rate) {
            if (stripos($occupancyClass, $classKey) !== false || stripos($classKey, $occupancyClass) !== false) {
                return $rate;
            }
        }

        // Generic classification keyword matching
        if (stripos($occupancyClass, 'residential') !== false) {
            return 2500.00;
        }

        if (stripos($occupancyClass, 'commercial') !== false || stripos($occupancyClass, 'office') !== false) {
            return 4000.00;
        }

        if (stripos($occupancyClass, 'industrial') !== false || stripos($occupancyClass, 'warehouse') !== false) {
            return 5000.00;
        }

        if (stripos($occupancyClass, 'hazardous') !== false) {
            return 6500.00;
        }

        if (stripos($occupancyClass, 'institutional') !== false || stripos($occupancyClass, 'school') !== false || stripos($occupancyClass, 'hospital') !== false) {
            return 3000.00;
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
