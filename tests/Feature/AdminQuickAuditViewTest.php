<?php

namespace Tests\Feature;

use App\Enums\BuildingClass;
use App\Models\QuickAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminQuickAuditViewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_quick_audit_with_multi_floor_metrics_popup(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $quickAudit = QuickAudit::create([
            'file_name' => 'quick_audits/multi_story.pdf',
            'original_name' => 'Kampala_Commercial_Tower.pdf',
            'discipline' => 'architectural',
            'occupancy_class' => BuildingClass::CLASS_A->value,
            'status' => 'completed',
            'overall_status' => 'PASS',
            'gross_floor_area_sqm' => 785.0,
            'estimated_statutory_fee_ugx' => 3140000.0,
            'summary_metrics' => ['pass_count' => 6, 'fail_count' => 0, 'warning_count' => 0],
            'floor_area_metrics' => [
                'is_floor_plan' => true,
                'gross_floor_area_sqm' => 785.0,
                'net_internal_area_sqm' => 695.0,
                'cumulated_floors_count' => 2,
                'source' => 'multimodal_multi_floor_cumulation',
                'confidence_score' => 0.95,
                'floors' => [
                    [
                        'page' => 1,
                        'floor_name' => 'Ground Floor Plan',
                        'gross_floor_area_sqm' => 405.0,
                        'net_internal_area_sqm' => 360.0,
                        'room_breakdown' => [
                            ['name' => 'Main Reception', 'area_sqm' => 45.0],
                            ['name' => 'Open Plan Office', 'area_sqm' => 220.0],
                        ],
                    ],
                    [
                        'page' => 2,
                        'floor_name' => 'First Floor Plan',
                        'gross_floor_area_sqm' => 380.0,
                        'net_internal_area_sqm' => 335.0,
                        'room_breakdown' => [
                            ['name' => 'Executive Suite', 'area_sqm' => 38.0],
                            ['name' => 'Boardroom', 'area_sqm' => 52.0],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.quick-audit.show', $quickAudit));

        $response->assertStatus(200);
        $response->assertSee('View Floor Breakdown');
        $response->assertSee('2 floors');
        $response->assertSee('Floor Area Metrics Breakdown');
        $response->assertSee('Ground Floor Plan');
        $response->assertSee('First Floor Plan');
        $response->assertSee('405.00');
        $response->assertSee('380.00');
        $response->assertSee('Main Reception');
        $response->assertSee('Executive Suite');
        $response->assertSee('Multimodal Multi Floor Cumulation');
        $response->assertSee('Class A: Complex and Public Buildings');
        $response->assertSee('openFloorAreaModal');
        $response->assertSee('closeFloorAreaModal');
    }

    public function test_admin_can_view_quick_audit_with_single_floor_metrics(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $quickAudit = QuickAudit::create([
            'file_name' => 'quick_audits/bungalow.pdf',
            'original_name' => 'Residential_Bungalow.pdf',
            'discipline' => 'architectural',
            'occupancy_class' => BuildingClass::CLASS_B->value,
            'status' => 'completed',
            'overall_status' => 'PASS',
            'gross_floor_area_sqm' => 250.0,
            'estimated_statutory_fee_ugx' => 625000.0,
            'summary_metrics' => ['pass_count' => 4, 'fail_count' => 0, 'warning_count' => 0],
            'floor_area_metrics' => [
                'is_floor_plan' => true,
                'gross_floor_area_sqm' => 250.0,
                'net_internal_area_sqm' => 215.0,
                'floor_name' => 'Ground Floor Residence',
                'source' => 'title_block_schedule',
                'room_breakdown' => [
                    ['name' => 'Living Room', 'area_sqm' => 32.5],
                    ['name' => 'Master Bedroom', 'area_sqm' => 24.0],
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.quick-audit.show', $quickAudit));

        $response->assertStatus(200);
        $response->assertSee('View Floor Breakdown');
        $response->assertSee('Floor Area Metrics Breakdown');
        $response->assertSee('Ground Floor Residence');
        $response->assertSee('250.00');
        $response->assertSee('Living Room');
        $response->assertSee('Class B: Standard Residential and Commercial Buildings');
    }

    public function test_admin_can_view_quick_audit_with_class_c_temporary_structure(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $quickAudit = QuickAudit::create([
            'file_name' => 'quick_audits/utility_kiosk.pdf',
            'original_name' => 'Utility_Kiosk.pdf',
            'discipline' => 'architectural',
            'occupancy_class' => BuildingClass::CLASS_C->value,
            'status' => 'completed',
            'overall_status' => 'PASS',
            'gross_floor_area_sqm' => 24.0,
            'estimated_statutory_fee_ugx' => 250000.0, // Minimum fee
            'summary_metrics' => ['pass_count' => 3, 'fail_count' => 0, 'warning_count' => 0],
            'floor_area_metrics' => [
                'is_floor_plan' => true,
                'gross_floor_area_sqm' => 24.0,
                'floor_name' => 'Kiosk Booth',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.quick-audit.show', $quickAudit));

        $response->assertStatus(200);
        $response->assertSee('Class C: Minor and Temporary Buildings');
        $response->assertSee('24.00');
    }

    public function test_admin_can_view_quick_audit_without_floor_area_metrics(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $quickAudit = QuickAudit::create([
            'file_name' => 'quick_audits/structural.pdf',
            'original_name' => 'Column_Details.pdf',
            'discipline' => 'structural',
            'occupancy_class' => BuildingClass::CLASS_B->value,
            'status' => 'completed',
            'overall_status' => 'PASS',
            'gross_floor_area_sqm' => null,
            'estimated_statutory_fee_ugx' => 0,
            'summary_metrics' => ['pass_count' => 2, 'fail_count' => 0, 'warning_count' => 0],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.quick-audit.show', $quickAudit));

        $response->assertStatus(200);
        $response->assertDontSee('id="floorAreaModal"', false);
    }
}
