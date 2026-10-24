<?php

namespace Tests\Feature;

use App\Jobs\ProcessDrawingJob;
use App\Jobs\ProcessQuickAuditJob;
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

    public function test_user_can_create_project_with_automatic_defaults(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson(route('api.v1.projects.store'), [
            'name' => 'Kampala Heights Commercial Tower',
            'occupancy_class' => 'Class B (Commercial)',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Kampala Heights Commercial Tower');

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
        $floor = $block->floors()->create(['floor_number' => 0, 'name' => 'Ground Floor']);

        $dummyPdf = UploadedFile::fake()->create('ground_floor_plan.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')->postJson(route('api.v1.drawings.upload'), [
            'floor_id' => $floor->id,
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
        $floor = $block->floors()->create(['floor_number' => 0, 'name' => 'Ground Floor']);

        $drawing = Drawing::create([
            'floor_id' => $floor->id,
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
        $groundFloor = $block->floors()->create(['floor_number' => 0, 'name' => 'Ground Floor']);
        $firstFloor = $block->floors()->create(['floor_number' => 1, 'name' => 'First Floor', 'gross_floor_area_sqm' => 450.00]);

        $drawing = Drawing::create([
            'floor_id' => $groundFloor->id,
            'discipline' => 'architectural',
        ]);

        $version = DrawingVersion::create([
            'drawing_id' => $drawing->id,
            'uploaded_by' => $user->id,
            'version_number' => 1,
            'file_name' => 'drawings/ground_floor.pdf',
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
                'gross_floor_area_sqm' => 550.00,
                'net_internal_area_sqm' => 484.00,
                'source' => 'title_block_schedule',
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
        $this->assertDatabaseHas('floors', [
            'id' => $groundFloor->id,
            'gross_floor_area_sqm' => 550.00,
        ]);

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
        $reportResponse = $this->actingAs($user, 'sanctum')->getJson(route('api.v1.projects.report', $project));
        $reportResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.floor_area_analysis.total_project_floor_area_sqm', 1000)
            ->assertJsonPath('data.floor_area_analysis.estimated_statutory_fee_ugx', 4000000)
            ->assertJsonPath('data.floor_area_analysis.currency', 'UGX');
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

        $response = $this->actingAs($user, 'sanctum')->postJson(route('api.v1.copilot.ask'), [
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
}
