<?php

namespace Tests\Feature;

use App\Models\CopilotWebsite;
use App\Models\RegulatoryChunk;
use App\Models\RegulatoryDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CopilotWebsiteEmbedTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_toggle_and_manage_external_copilot_websites(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // 1. View External Copilot Websites management page
        $indexResponse = $this->actingAs($admin)->get(route('admin.copilot-websites.index'));
        $indexResponse->assertStatus(200)
            ->assertSee('External Copilot Websites & Embed Scripts', false);

        // 2. Register an external website with a custom slug
        $slug = 'kcca-permit-portal-'.uniqid();
        $storeResponse = $this->actingAs($admin)->post(route('admin.copilot-websites.store'), [
            'name' => 'KCCA Building Permit Portal',
            'slug' => $slug,
            'domain' => 'https://permits.kcca.go.ug',
            'welcome_message' => 'Welcome to the KCCA & NBRB Building Code Assistant.',
            'is_active' => 1,
        ]);

        $storeResponse->assertRedirect(route('admin.copilot-websites.index'));

        $website = CopilotWebsite::where('slug', $slug)->firstOrFail();
        $this->assertTrue($website->is_active);
        $this->assertSame('KCCA Building Permit Portal', $website->name);

        // Verify the embed script snippet is displayed on the admin page
        $afterStoreIndex = $this->actingAs($admin)->get(route('admin.copilot-websites.index'));
        $afterStoreIndex->assertStatus(200)
            ->assertSee($slug)
            ->assertSee(route('copilot.embed.script', ['site' => $slug]), false);

        // 3. Fetch the embed JS when website is ENABLED (both by slug and by UUID)
        $enabledScriptResponse = $this->get(route('copilot.embed.script', ['site' => $website->slug]));
        $enabledScriptResponse->assertStatus(200)
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->assertSee('nbrb-ext-copilot-fab', false)
            ->assertSee('KCCA Building Permit Portal', false);

        $enabledByUuidResponse = $this->get(route('copilot.embed.script', ['site' => $website->id]));
        $enabledByUuidResponse->assertStatus(200)
            ->assertSee('nbrb-ext-copilot-fab', false);

        // 4. Ask a question via the external embed endpoint when website is ENABLED
        $doc = RegulatoryDocument::create([
            'title' => 'Building Control Regulations 2020',
            'category' => 'architectural',
            'edition_year' => '2020',
            'file_path' => 'regulatory/reg2020.pdf',
            'status' => 'indexed',
            'is_active' => true,
            'uploaded_by' => $admin->id,
        ]);

        RegulatoryChunk::create([
            'regulatory_document_id' => $doc->id,
            'clause_number' => 'Reg 14.3',
            'clause_title' => 'Ventilation of Habitable Rooms',
            'content' => 'Every habitable room shall have openings for natural light and ventilation equal to not less than 10 percent of floor area.',
        ]);

        $askResponse = $this->postJson(route('api.v1.copilot.embed.ask', ['site' => $website->slug]), [
            'query' => 'What is the required ventilation percentage for habitable rooms?',
            'persona' => 'inspector_okello',
        ]);

        $askResponse->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.website', $website->slug)
            ->assertJsonPath('data.persona', 'inspector_okello');

        $website->refresh();
        $this->assertSame(1, $website->requests_count);
        $this->assertNotNull($website->last_active_at);

        // 5. Disable the external website from Admin Backend
        $toggleResponse = $this->actingAs($admin)->post(route('admin.copilot-websites.toggle-active', $website));
        $toggleResponse->assertRedirect(route('admin.copilot-websites.index'));

        $website->refresh();
        $this->assertFalse($website->is_active);

        // 6. Verify that when website is DISABLED, the embed script does NOT load the chatbot
        $disabledScriptResponse = $this->get(route('copilot.embed.script', ['site' => $website->slug]));
        $disabledScriptResponse->assertStatus(200)
            ->assertSee('External website widget is disabled', false)
            ->assertSee('Chatbot will not load', false)
            ->assertDontSee('nbrb-ext-copilot-fab', false);

        // 7. Verify that when website is DISABLED, the ask API endpoint returns 403 disabled
        $disabledAskResponse = $this->postJson(route('api.v1.copilot.embed.ask', ['site' => $website->slug]), [
            'query' => 'What is the required ventilation percentage?',
        ]);
        $disabledAskResponse->assertStatus(403)
            ->assertJsonPath('status', 'disabled');
    }
}
