<?php

namespace Tests\Feature;

use App\Models\RegulatoryChunk;
use App\Models\RegulatoryDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCopilotPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_copilot_workspace(): void
    {
        $response = $this->get(route('admin.copilot.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_admin_can_access_dedicated_copilot_workspace(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $doc = RegulatoryDocument::create([
            'title' => 'Building Control Regulations 2020 SI No 45',
            'category' => 'REGULATION',
            'edition_year' => 2020,
            'file_path' => 'regulatory/test.pdf',
            'classification' => 'STATUTORY_INSTRUMENT',
            'total_chunks' => 1,
            'status' => 'INDEXED',
            'is_active' => true,
            'uploaded_by' => $admin->id,
        ]);

        RegulatoryChunk::create([
            'regulatory_document_id' => $doc->id,
            'clause_number' => 'Reg 14',
            'clause_title' => 'Ventilation Requirements',
            'content' => 'Every habitable room shall be provided with window openings of at least 5% of floor area.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.copilot.index'));

        $response->assertOk();
        $response->assertViewIs('admin.copilot.index');
        $response->assertViewHas('personas');
        $response->assertViewHas('totalDocuments', 1);
        $response->assertViewHas('totalChunks', 1);
        $response->assertSee('Statutory AI Copilot Workspace');
        $response->assertSee('Inspector Okello');
        $response->assertSee('Arch. Namubiru');
        $response->assertSee('Eng. Kigozi');
        $response->assertSee('Building Control Regulations 2020 SI No 45');
    }

    public function test_admin_sidebar_navigation_includes_copilot_workspace_link(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.copilot.index'));
        $response->assertSee('Statutory AI Copilot');
    }

    public function test_disabled_persona_does_not_show_up_on_dedicated_copilot_workspace(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Disable engineer_kigozi
        config(['copilot.personas.engineer_kigozi.enabled' => false]);

        $response = $this->actingAs($admin)->get(route('admin.copilot.index'));

        $response->assertOk();
        $response->assertSee('Inspector Okello');
        $response->assertSee('Arch. Namubiru');
        $response->assertDontSee('data-persona="engineer_kigozi"', false);
    }

    public function test_disabled_persona_does_not_show_up_on_floating_chatbot(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Disable architect_namubiru
        config(['copilot.personas.architect_namubiru.enabled' => false]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('data-persona="inspector_okello"', false);
        $response->assertSee('data-persona="engineer_kigozi"', false);
        $response->assertDontSee('data-persona="architect_namubiru"', false);
    }

    public function test_submitting_query_for_disabled_persona_fails_validation(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        config(['copilot.personas.engineer_kigozi.enabled' => false]);

        $response = $this->actingAs($admin)->postJson(route('admin.copilot'), [
            'query' => 'What is the required foundation footing thickness?',
            'persona' => 'engineer_kigozi',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['persona']);
    }
}
