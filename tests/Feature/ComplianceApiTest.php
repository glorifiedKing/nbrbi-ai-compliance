<?php

namespace Tests\Feature;

use App\Jobs\ProcessDrawingJob;
use App\Jobs\ProcessQuickAuditJob;
use App\Models\ComplianceResult;
use App\Models\Drawing;
use App\Models\DrawingVersion;
use App\Models\Project;
use App\Models\QuickAudit;
use App\Models\RegulatoryChunk;
use App\Models\RegulatoryDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplianceApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_register_and_authenticate(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Eng. Raymond Kigozi',
            'email' => 'raymond_'.uniqid().'@bims.ug',
            'password' => 'SecurePass123!',
            'bims_id' => 'BIMS-PE-'.rand(1000, 9999),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'token', 'user' => ['id', 'name', 'email', 'bims_id', 'role']]);
    }

    protected function apiGuard(): string
    {
        return config('app.in_autologin') ? 'api' : 'sanctum';
    }

    public function test_user_can_create_project_with_automatic_defaults(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, $this->apiGuard())->postJson(route('api.v1.projects.store'), [
            'name' => 'Kampala Heights Commercial Tower',
            'occupancy_class' => 'Class B (Commercial)',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Kampala Heights Commercial Tower')
            ->assertJsonPath('data.blocks.0.floors.0.floor_name', 'Ground Floor');

        $this->assertDatabaseHas('projects', [
            'name' => 'Kampala Heights Commercial Tower',
            'owner_id' => $user->id,
        ]);
    }

    public function test_drawing_upload_creates_version_and_dispatches_job(): void
    {
        Storage::fake('local');
        Queue::fake([ProcessDrawingJob::class]);

        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Gulu Medical Center',
            'reference_number' => 'NBRB-GUL-'.uniqid(),
            'share_token' => uniqid('token_'),
        ]);

        $block = $project->blocks()->create(['name' => 'Main Ward']);

        $dummyPdf = UploadedFile::fake()->create('ground_floor_plan.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user, $this->apiGuard())->postJson(route('api.v1.drawings.upload'), [
            'block_id' => $block->id,
            'discipline' => 'architectural',
            'drawing_file' => $dummyPdf,
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.version_number', 1)
            ->assertJsonPath('data.discipline', 'architectural');

        Queue::assertPushed(ProcessDrawingJob::class);
    }

    public function test_quick_single_drawing_audit_can_be_submitted_via_api(): void
    {
        Storage::fake('local');
        Queue::fake([ProcessQuickAuditJob::class]);

        $dummyPdf = UploadedFile::fake()->create('structural_beam_detail.pdf', 800, 'application/pdf');

        $response = $this->postJson(route('api.v1.quick-audit.store'), [
            'discipline' => 'structural',
            'occupancy_class' => 'Class B (Residential & Commercial)',
            'drawing_file' => $dummyPdf,
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.discipline', 'structural')
            ->assertJsonPath('data.occupancy_class', 'Class B (Residential & Commercial)');

        Queue::assertPushed(ProcessQuickAuditJob::class);

        $this->assertDatabaseHas('quick_audits', [
            'discipline' => 'structural',
            'status' => 'pending',
        ]);
    }

    public function test_internal_compliance_webhook_updates_quick_audit(): void
    {
        $quickAudit = QuickAudit::create([
            'file_name' => 'quick_audits/sample.pdf',
            'original_name' => 'sample_arch.pdf',
            'discipline' => 'architectural',
            'occupancy_class' => 'Class B (Residential & Commercial)',
            'status' => 'analyzing',
        ]);

        $payload = [
            'drawing_version_id' => $quickAudit->id,
            'overall_status' => 'PASS',
            'preflight_metrics' => ['is_valid' => true, 'scale' => '1:50', 'dpi' => 300],
            'summary_metrics' => ['pass_count' => 6, 'fail_count' => 0, 'warning_count' => 0],
            'discrepancies' => [],
            'extracted_geometry' => ['openings_detected_count' => 5],
        ];

        $secret = config('services.fastapi.secret', 'bims-secure-internal-secret');

        $response = $this->postJson(route('api.internal.compliance-webhook'), $payload, [
            'X-Internal-Secret' => $secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('type', 'quick_audit');

        $this->assertDatabaseHas('quick_audits', [
            'id' => $quickAudit->id,
            'status' => 'completed',
            'overall_status' => 'PASS',
        ]);
    }

    public function test_internal_compliance_webhook_updates_status_and_promotes_version(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Entebbe Eco Estate',
            'reference_number' => 'NBRB-EBB-'.uniqid(),
            'share_token' => uniqid('token_'),
        ]);

        $block = $project->blocks()->create(['name' => 'Block A']);

        $drawing = Drawing::create([
            'block_id' => $block->id,
            'discipline' => 'architectural',
        ]);

        $version = DrawingVersion::create([
            'drawing_id' => $drawing->id,
            'uploaded_by' => $user->id,
            'version_number' => 1,
            'file_name' => 'drawings/test.pdf',
            'file_hash' => 'dummyhash123',
            'status' => 'analyzing',
        ]);

        $payload = [
            'drawing_version_id' => $version->id,
            'overall_status' => 'PASS',
            'preflight_metrics' => ['is_valid' => true, 'scale' => '1:100', 'dpi' => 300],
            'summary_metrics' => ['pass_count' => 5, 'fail_count' => 0, 'warning_count' => 0],
            'discrepancies' => [],
            'extracted_geometry' => ['openings_detected_count' => 4],
        ];

        $secret = config('services.fastapi.secret', 'bims-secure-internal-secret');

        $response = $this->postJson(route('api.internal.compliance-webhook'), $payload, [
            'X-Internal-Secret' => $secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('type', 'drawing_version');

        $this->assertDatabaseHas('drawing_versions', [
            'id' => $version->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('drawings', [
            'id' => $drawing->id,
            'current_version_id' => $version->id,
        ]);

        $this->assertDatabaseHas('compliance_results', [
            'drawing_version_id' => $version->id,
            'overall_status' => 'PASS',
        ]);
    }

    public function test_compliance_webhook_updates_floor_area_and_recalculates_project_fees(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Kampala Apex Commercial Plaza',
            'reference_number' => 'NBRB-KLA-'.uniqid(),
            'occupancy_class' => 'Class A (Complex & Public Buildings)',
            'share_token' => uniqid('token_'),
        ]);

        $block = $project->blocks()->create(['name' => 'Tower 1']);

        $drawing = Drawing::create([
            'block_id' => $block->id,
            'discipline' => 'architectural',
        ]);

        $version = DrawingVersion::create([
            'drawing_id' => $drawing->id,
            'uploaded_by' => $user->id,
            'version_number' => 1,
            'file_name' => 'drawings/tower_1_plans.pdf',
            'file_hash' => 'dummyhash999',
            'status' => 'analyzing',
        ]);

        $payload = [
            'drawing_version_id' => $version->id,
            'overall_status' => 'PASS',
            'preflight_metrics' => ['is_valid' => true, 'scale' => '1:100', 'dpi' => 300],
            'summary_metrics' => ['pass_count' => 8, 'fail_count' => 0, 'warning_count' => 0],
            'discrepancies' => [],
            'extracted_geometry' => ['openings_detected_count' => 6],
            'floor_area_metrics' => [
                'is_floor_plan' => true,
                'gross_floor_area_sqm' => 1000.00,
                'net_internal_area_sqm' => 884.00,
                'source' => 'multimodal_multi_floor_cumulation',
                'floors' => [
                    [
                        'page' => 1,
                        'floor_number' => 0,
                        'floor_name' => 'Ground Floor',
                        'gross_floor_area_sqm' => 550.00,
                        'net_internal_area_sqm' => 484.00,
                    ],
                    [
                        'page' => 2,
                        'floor_number' => 1,
                        'floor_name' => 'First Floor',
                        'gross_floor_area_sqm' => 450.00,
                        'net_internal_area_sqm' => 400.00,
                    ],
                ],
            ],
        ];

        $secret = config('services.fastapi.secret', 'bims-secure-internal-secret');

        $response = $this->postJson(route('api.internal.compliance-webhook'), $payload, [
            'X-Internal-Secret' => $secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // Total floor area should be 550 (ground) + 450 (first) = 1,000 m²
        // Class A rate is 4,000 UGX/m², fee = 1,000 * 4,000 = 4,000,000 UGX
        $block->refresh();
        $this->assertCount(2, $block->floors);
        $this->assertSame(0, $block->floors[0]['floor_number']);
        $this->assertSame('Ground Floor', $block->floors[0]['floor_name']);
        $this->assertSame(550.0, (float) $block->floors[0]['gross_floor_area_sqm']);
        $this->assertSame(1, $block->floors[1]['floor_number']);
        $this->assertSame('First Floor', $block->floors[1]['floor_name']);
        $this->assertSame(450.0, (float) $block->floors[1]['gross_floor_area_sqm']);

        $this->assertDatabaseHas('blocks', [
            'id' => $block->id,
            'total_floor_area_sqm' => 1000.00,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'total_floor_area_sqm' => 1000.00,
            'estimated_statutory_fee_ugx' => 4000000.00,
        ]);

        // Verify consolidated project report returns the floor area and fee breakdown
        $reportResponse = $this->actingAs($user, $this->apiGuard())->getJson(route('api.v1.projects.report', $project));
        $reportResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.floor_area_analysis.total_project_floor_area_sqm', 1000)
            ->assertJsonPath('data.floor_area_analysis.estimated_statutory_fee_ugx', 4000000)
            ->assertJsonPath('data.floor_area_analysis.currency', 'UGX')
            ->assertJsonPath('data.floor_area_analysis.blocks.0.floors.0.floor_name', 'Ground Floor')
            ->assertJsonPath('data.floor_area_analysis.blocks.0.floors.1.floor_name', 'First Floor');
    }

    public function test_compliance_webhook_does_not_update_block_floors_for_non_architectural_drawing(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Mbale Structural Complex',
            'reference_number' => 'NBRB-MBL-'.uniqid(),
            'occupancy_class' => 'Class B (Residential & Commercial)',
            'share_token' => uniqid('token_'),
        ]);

        $block = $project->blocks()->create([
            'name' => 'Block S1',
            'floors' => [
                ['floor_number' => 0, 'floor_name' => 'Ground Floor', 'name' => 'Ground Floor', 'gross_floor_area_sqm' => 300.00],
            ],
            'total_floor_area_sqm' => 300.00,
        ]);

        $drawing = Drawing::create([
            'block_id' => $block->id,
            'discipline' => 'structural',
        ]);

        $version = DrawingVersion::create([
            'drawing_id' => $drawing->id,
            'uploaded_by' => $user->id,
            'version_number' => 1,
            'file_name' => 'drawings/structural_plan.pdf',
            'file_hash' => 'structhash111',
            'status' => 'analyzing',
        ]);

        $payload = [
            'drawing_version_id' => $version->id,
            'overall_status' => 'PASS',
            'preflight_metrics' => ['is_valid' => true, 'scale' => '1:100', 'dpi' => 300],
            'summary_metrics' => ['pass_count' => 5, 'fail_count' => 0, 'warning_count' => 0],
            'discrepancies' => [],
            'extracted_geometry' => [],
            'floor_area_metrics' => [
                'is_floor_plan' => true,
                'gross_floor_area_sqm' => 999.00,
                'floors' => [
                    ['floor_number' => 0, 'floor_name' => 'Structural Sheet 1', 'gross_floor_area_sqm' => 999.00],
                ],
            ],
        ];

        $secret = config('services.fastapi.secret', 'bims-secure-internal-secret');

        $response = $this->postJson(route('api.internal.compliance-webhook'), $payload, [
            'X-Internal-Secret' => $secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $block->refresh();
        $this->assertSame('Ground Floor', $block->floors[0]['floor_name']);
        $this->assertSame(300.0, (float) $block->floors[0]['gross_floor_area_sqm']);
        $this->assertSame(300.0, (float) $block->total_floor_area_sqm);
    }

    public function test_block_floors_jsonb_can_be_managed_via_api(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Jinja Industrial Park',
            'reference_number' => 'NBRB-JIN-'.uniqid(),
            'share_token' => uniqid('token_'),
        ]);

        $blockResponse = $this->actingAs($user, $this->apiGuard())->postJson(route('api.v1.blocks.store', $project), [
            'name' => 'Warehouse Block B',
            'description' => 'Storage and Mezzanine',
            'floors' => [
                ['floor_number' => 0, 'floor_name' => 'Ground Floor', 'gross_floor_area_sqm' => 600.00],
            ],
        ]);

        $blockResponse->assertStatus(201)
            ->assertJsonPath('data.floors.0.floor_name', 'Ground Floor');

        $blockId = $blockResponse->json('data.id');

        $floorResponse = $this->actingAs($user, $this->apiGuard())->postJson(route('api.v1.floors.store', $blockId), [
            'floor_number' => 1,
            'floor_name' => 'Mezzanine Level',
            'gross_floor_area_sqm' => 200.00,
        ]);

        $floorResponse->assertStatus(201)
            ->assertJsonPath('data.floor_name', 'Mezzanine Level');

        $this->assertDatabaseHas('blocks', [
            'id' => $blockId,
            'total_floor_area_sqm' => 800.00,
        ]);

        // Update block name and floor schedule via API (PUT /api/v1/blocks/{block})
        $updateBlockResponse = $this->actingAs($user, $this->apiGuard())->putJson(route('api.v1.blocks.update', $blockId), [
            'name' => 'Warehouse Block B - Expanded',
            'description' => 'Updated storage and mezzanine levels',
            'floors' => [
                ['floor_number' => 0, 'floor_name' => 'Ground Floor', 'gross_floor_area_sqm' => 700.00],
                ['floor_number' => 1, 'floor_name' => 'Mezzanine Level', 'gross_floor_area_sqm' => 250.00],
            ],
        ]);

        $updateBlockResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Warehouse Block B - Expanded');

        $this->assertDatabaseHas('blocks', [
            'id' => $blockId,
            'total_floor_area_sqm' => 950.00,
        ]);
    }

    public function test_quick_audit_calculates_floor_area_and_statutory_fee(): void
    {
        $quickAudit = QuickAudit::create([
            'file_name' => 'quick_audits/arch_commercial.pdf',
            'original_name' => 'arch_commercial.pdf',
            'discipline' => 'architectural',
            'occupancy_class' => 'Class B (Residential & Commercial)',
            'status' => 'analyzing',
        ]);

        $payload = [
            'drawing_version_id' => $quickAudit->id,
            'overall_status' => 'PASS',
            'preflight_metrics' => ['is_valid' => true, 'scale' => '1:50', 'dpi' => 300],
            'summary_metrics' => ['pass_count' => 4, 'fail_count' => 0, 'warning_count' => 0],
            'discrepancies' => [],
            'extracted_geometry' => ['openings_detected_count' => 3],
            'floor_area_metrics' => [
                'is_floor_plan' => true,
                'gross_floor_area_sqm' => 300.00,
                'source' => 'room_schedule_summation',
            ],
        ];

        $secret = config('services.fastapi.secret', 'bims-secure-internal-secret');

        $response = $this->postJson(route('api.internal.compliance-webhook'), $payload, [
            'X-Internal-Secret' => $secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('gross_floor_area_sqm', 300)
            ->assertJsonPath('estimated_statutory_fee_ugx', 750000); // 300 m² * 2,500 UGX/m² = 750,000 UGX

        // Verify API show endpoint returns floor area and statutory fee
        $showResponse = $this->getJson(route('api.v1.quick-audit.show', $quickAudit));
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.gross_floor_area_sqm', 300)
            ->assertJsonPath('data.estimated_statutory_fee_ugx', 750000)
            ->assertJsonPath('data.currency', 'UGX');
    }

    public function test_copilot_assistant_returns_grounded_response_for_personas(): void
    {
        $user = User::factory()->create();

        $doc = RegulatoryDocument::create([
            'title' => 'Building Control Regulations 2020',
            'category' => 'architectural',
            'edition_year' => '2020',
            'file_path' => 'regulatory/reg2020.pdf',
            'status' => 'indexed',
            'is_active' => true,
            'uploaded_by' => $user->id,
        ]);

        RegulatoryChunk::create([
            'regulatory_document_id' => $doc->id,
            'clause_number' => 'Reg 14.3',
            'clause_title' => 'Ventilation of Habitable Rooms',
            'content' => 'Every habitable room shall have openings for natural light and ventilation equal to not less than 10 percent of floor area.',
        ]);

        $response = $this->actingAs($user, $this->apiGuard())->postJson(route('api.v1.copilot.ask'), [
            'query' => 'What is the required ventilation percentage for habitable rooms?',
            'persona' => 'architect_namubiru',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.persona', 'architect_namubiru');
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($regularUser)->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_login_and_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin_test_'.uniqid().'@nbrb.go.ug',
            'password' => Hash::make('AdminSecret123!'),
            'role' => 'admin',
        ]);

        // Login via form
        $loginResponse = $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'AdminSecret123!',
        ]);

        $loginResponse->assertRedirect(route('admin.dashboard'));

        // Access dashboard authenticated as admin
        $dashboardResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashboardResponse->assertStatus(200)
            ->assertSee('AI Compliance');
    }

    public function test_admin_can_view_regulatory_documents_and_quick_audit_console(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        RegulatoryDocument::create([
            'title' => 'The Building Control Regulations 2020',
            'category' => 'general_building_control',
            'edition_year' => '2020',
            'file_path' => 'regulatory/sample.pdf',
            'status' => 'indexed',
            'is_active' => true,
            'uploaded_by' => $admin->id,
        ]);

        $regResponse = $this->actingAs($admin)->get(route('admin.regulatory.index'));
        $regResponse->assertStatus(200)
            ->assertSee('The Building Control Regulations 2020');

        $quickResponse = $this->actingAs($admin)->get(route('admin.quick-audit.index'));
        $quickResponse->assertStatus(200)
            ->assertSee('Instant Single Drawing Compliance Audit');
    }

    public function test_admin_can_logout_securely(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_admin_can_manage_projects_blocks_drawings_and_view_report_from_dashboard(): void
    {
        Storage::fake('local');
        Queue::fake([ProcessDrawingJob::class]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // 1. View projects index
        $indexResponse = $this->actingAs($admin)->get(route('admin.projects.index'));
        $indexResponse->assertStatus(200)
            ->assertSee('Register New Building Project');

        // 2. Create project via Admin Dashboard
        $storeResponse = $this->actingAs($admin)->post(route('admin.projects.store'), [
            'name' => 'Makerere Innovation Hub',
            'occupancy_class' => 'Class A (Complex & Public Buildings)',
        ]);

        $project = Project::where('name', 'Makerere Innovation Hub')->firstOrFail();
        $storeResponse->assertRedirect(route('admin.projects.show', $project));

        // 3. Add a new block with floors JSONB
        $blockResponse = $this->actingAs($admin)->post(route('admin.projects.blocks.store', $project), [
            'name' => 'Science Wing',
            'description' => 'Laboratories and Lecture Halls',
            'floors' => [
                ['floor_number' => 0, 'floor_name' => 'Ground Floor', 'gross_floor_area_sqm' => 500],
                ['floor_number' => 1, 'floor_name' => 'First Floor', 'gross_floor_area_sqm' => 450],
            ],
        ]);
        $blockResponse->assertRedirect(route('admin.projects.show', $project));

        $scienceBlock = $project->blocks()->where('name', 'Science Wing')->firstOrFail();
        $this->assertCount(2, $scienceBlock->floors);
        $this->assertSame(950.0, (float) $scienceBlock->total_floor_area_sqm);

        // 4. Edit existing block and floor details
        $updateBlockResponse = $this->actingAs($admin)->put(route('admin.projects.blocks.update', [$project, $scienceBlock]), [
            'name' => 'Science & Research Wing',
            'description' => 'Updated wing description',
            'floors' => [
                ['floor_number' => 0, 'floor_name' => 'Ground Floor', 'gross_floor_area_sqm' => 600],
                ['floor_number' => 1, 'floor_name' => 'First Floor', 'gross_floor_area_sqm' => 400],
            ],
        ]);
        $updateBlockResponse->assertRedirect(route('admin.projects.show', $project));

        $scienceBlock->refresh();
        $this->assertSame('Science & Research Wing', $scienceBlock->name);
        $this->assertSame(1000.0, (float) $scienceBlock->total_floor_area_sqm);

        // 5. Upload drawing to block and trigger ProcessDrawingJob
        $pdf = UploadedFile::fake()->create('science_wing_arch.pdf', 600, 'application/pdf');
        $uploadResponse = $this->actingAs($admin)->post(route('admin.projects.drawings.upload', [$project, $scienceBlock]), [
            'discipline' => 'architectural',
            'drawing_file' => $pdf,
        ]);
        $uploadResponse->assertRedirect(route('admin.projects.show', $project));

        Queue::assertPushed(ProcessDrawingJob::class);

        // 6. Simulate completed compliance result with discrepancy & view Consolidated Project Compliance Report
        $drawing = Drawing::where('block_id', $scienceBlock->id)->where('discipline', 'architectural')->firstOrFail();
        $version = $drawing->versions()->firstOrFail();
        $drawing->update(['current_version_id' => $version->id]);
        $version->update(['status' => 'completed']);

        ComplianceResult::create([
            'drawing_version_id' => $version->id,
            'overall_status' => 'FAIL',
            'preflight_metrics' => ['is_valid' => true, 'scale' => '1:100'],
            'summary_metrics' => ['pass_count' => 5, 'fail_count' => 1, 'warning_count' => 1],
            'extracted_geometry' => ['openings_detected_count' => 8],
            'discrepancies' => [
                [
                    'element_id' => 'Corridor C-102',
                    'room_or_grid' => 'Grid B-4',
                    'clause_reference' => 'Reg 42(1) Egress Width',
                    'required_value' => '1500 mm',
                    'observed_value' => '1100 mm',
                    'severity' => 'ERROR',
                    'recommendation' => 'Widen main corridor to at least 1500 mm for Class A occupancy.',
                ],
            ],
            'analyzed_at' => now(),
        ]);

        $reportResponse = $this->actingAs($admin)->get(route('admin.projects.report', $project));
        $reportResponse->assertStatus(200)
            ->assertSee('Makerere Innovation Hub')
            ->assertSee('Science & Research Wing')
            ->assertSee('1,000.00')
            ->assertSee('View Floor Breakdown')
            ->assertSee('Statutory Discrepancies & Compliance Audit Log', false)
            ->assertSee('All Disciplines (1)')
            ->assertSee('toggleDisciplineFilter', false)
            ->assertSee('data-discipline="architectural"', false)
            ->assertSee('Discipline & Version', false)
            ->assertSee('Corridor C-102')
            ->assertSee('Reg 42(1) Egress Width')
            ->assertSee('1500 mm')
            ->assertSee('1100 mm');
    }
}
