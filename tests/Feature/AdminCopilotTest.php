<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CopilotController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminCopilotTest extends TestCase
{
    public function test_copilot_controller_returns_successful_response_for_inspector_okello(): void
    {
        $controller = new CopilotController;

        $request = Request::create('/admin/copilot/ask', 'POST', [
            'query' => 'What is the required window ventilation percentage for habitable rooms?',
            'persona' => 'inspector_okello',
        ]);

        $response = $controller($request);

        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);

        $this->assertSame('success', $data['status']);
        $this->assertSame('inspector_okello', $data['data']['persona']);
        $this->assertStringContainsString('Inspector Okello', $data['data']['persona_name']);
        $this->assertNotEmpty($data['data']['response']);
        $this->assertIsArray($data['data']['referenced_clauses']);
    }

    public function test_copilot_controller_supports_architect_namubiru_persona(): void
    {
        $controller = new CopilotController;

        $request = Request::create('/admin/copilot/ask', 'POST', [
            'query' => 'What are the natural lighting and PWD accessibility standards?',
            'persona' => 'architect_namubiru',
        ]);

        $response = $controller($request);

        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);

        $this->assertSame('success', $data['status']);
        $this->assertSame('architect_namubiru', $data['data']['persona']);
        $this->assertStringContainsString('Arch. Namubiru', $data['data']['persona_name']);
        $this->assertNotEmpty($data['data']['response']);
    }

    public function test_copilot_controller_supports_engineer_kigozi_persona(): void
    {
        $controller = new CopilotController;

        $request = Request::create('/admin/copilot/ask', 'POST', [
            'query' => 'What are the concrete column and structural foundation load requirements?',
            'persona' => 'engineer_kigozi',
        ]);

        $response = $controller($request);

        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);

        $this->assertSame('success', $data['status']);
        $this->assertSame('engineer_kigozi', $data['data']['persona']);
        $this->assertStringContainsString('Eng. Kigozi', $data['data']['persona_name']);
        $this->assertNotEmpty($data['data']['response']);
    }

    public function test_copilot_controller_validates_required_query(): void
    {
        $this->expectException(ValidationException::class);

        $controller = new CopilotController;

        $request = Request::create('/admin/copilot/ask', 'POST', [
            'query' => '',
            'persona' => 'inspector_okello',
        ]);

        $controller($request);
    }

    public function test_copilot_controller_validates_allowed_personas(): void
    {
        $this->expectException(ValidationException::class);

        $controller = new CopilotController;

        $request = Request::create('/admin/copilot/ask', 'POST', [
            'query' => 'Valid query',
            'persona' => 'invalid_persona_name',
        ]);

        $controller($request);
    }

    public function test_admin_blade_view_includes_floating_chatbot(): void
    {
        $admin = new User([
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Admin Officer',
            'email' => 'admin@nbrb.go.ug',
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $rendered = view('admin.dashboard', [
            'totalProjects' => 0,
            'totalDocuments' => 0,
            'totalChunks' => 0,
            'totalVersions' => 0,
            'passCount' => 0,
            'failCount' => 0,
            'warningCount' => 0,
            'complianceRate' => 0,
            'recentDocuments' => collect(),
            'recentSubmissions' => collect(),
            'fastApiHealthy' => true,
            'totalAuditedFloorAreaSqm' => 0.0,
            'totalEstimatedFeesUgx' => 0.0,
        ])->render();

        $this->assertStringContainsString('id="nbrb-copilot-root"', $rendered);
        $this->assertStringContainsString('id="nbrb-copilot-toggle"', $rendered);
        $this->assertStringContainsString('id="nbrb-copilot-window"', $rendered);
        $this->assertStringContainsString('NBRB Statutory Copilot', $rendered);
        $this->assertStringContainsString('data-persona="inspector_okello"', $rendered);
        $this->assertStringContainsString('data-persona="architect_namubiru"', $rendered);
        $this->assertStringContainsString('data-persona="engineer_kigozi"', $rendered);
        $this->assertStringContainsString('id="copilot-input"', $rendered);
    }

    public function test_guest_is_redirected_when_accessing_admin_copilot(): void
    {
        $response = $this->post(route('admin.copilot'), [
            'query' => 'What is the required window ventilation percentage?',
        ]);

        $response->assertRedirect(route('admin.login'));
    }
}
