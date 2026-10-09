<?php

namespace Tests\Feature;

use App\Jobs\ProcessDrawingJob;
use App\Models\Drawing;
use App\Models\DrawingVersion;
use App\Models\Project;
use App\Models\RegulatoryChunk;
use App\Models\RegulatoryDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
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
            ->assertJsonStructure(['status', 'token', 'user' => ['id', 'name', 'email', 'bims_id']]);
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
            ->assertJsonPath('status', 'success');

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

    public function test_admin_regulatory_document_listing_renders_successfully(): void
    {
        $user = User::factory()->create();
        RegulatoryDocument::create([
            'title' => 'The Building Control Regulations 2020',
            'category' => 'general_building_control',
            'edition_year' => '2020',
            'file_path' => 'regulatory/sample.pdf',
            'status' => 'indexed',
            'is_active' => true,
            'uploaded_by' => $user->id,
        ]);

        $response = $this->get(route('admin.regulatory.index'));

        $response->assertStatus(200)
            ->assertSee('The Building Control Regulations 2020');
    }
}
