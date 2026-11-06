<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CopilotWebsite;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CopilotWebsiteController extends Controller
{
    /**
     * List all registered external websites for the embeddable Copilot widget.
     */
    public function index(): View
    {
        $websites = CopilotWebsite::with('creator:id,name,email')
            ->latest()
            ->get();

        $stats = [
            'total' => $websites->count(),
            'active' => $websites->where('is_active', true)->count(),
            'disabled' => $websites->where('is_active', false)->count(),
            'total_requests' => (int) $websites->sum('requests_count'),
        ];

        return view('admin.copilot_websites.index', compact('websites', 'stats'));
    }

    /**
     * Register a new external website and generate its embeddable script slug.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', 'unique:copilot_websites,slug'],
            'domain' => ['nullable', 'string', 'max:255'],
            'welcome_message' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rawSlug = trim((string) ($validated['slug'] ?? ''));
        if ($rawSlug === '') {
            $baseSlug = Str::slug($validated['name']) ?: 'external-site';
            $slug = $baseSlug;
            $counter = 1;
            while (CopilotWebsite::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
        } else {
            $slug = Str::slug($rawSlug);
        }

        $website = CopilotWebsite::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'domain' => ! empty($validated['domain']) ? trim($validated['domain']) : null,
            'welcome_message' => ! empty($validated['welcome_message']) ? trim($validated['welcome_message']) : null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.copilot-websites.index')
            ->with('success', "External website \"{$website->name}\" (slug: {$website->slug}) registered. Copy the embed script below to add the Copilot floating chat to the site.");
    }

    /**
     * Update an existing external website configuration.
     */
    public function update(Request $request, CopilotWebsite $copilotWebsite): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('copilot_websites', 'slug')->ignore($copilotWebsite->id),
            ],
            'domain' => ['nullable', 'string', 'max:255'],
            'welcome_message' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $copilotWebsite->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'domain' => ! empty($validated['domain']) ? trim($validated['domain']) : null,
            'welcome_message' => ! empty($validated['welcome_message']) ? trim($validated['welcome_message']) : null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $copilotWebsite->is_active,
        ]);

        return redirect()
            ->route('admin.copilot-websites.index')
            ->with('success', "External website \"{$copilotWebsite->name}\" updated successfully.");
    }

    /**
     * Toggle whether the Copilot widget is enabled or disabled on the external website.
     */
    public function toggleActive(CopilotWebsite $copilotWebsite): RedirectResponse
    {
        $copilotWebsite->update([
            'is_active' => ! $copilotWebsite->is_active,
        ]);

        $stateLabel = $copilotWebsite->is_active
            ? 'ENABLED (Chatbot will now load on the external website)'
            : 'DISABLED (Chatbot will no longer load on the external website)';

        return redirect()
            ->route('admin.copilot-websites.index')
            ->with('success', "External website \"{$copilotWebsite->name}\" is now {$stateLabel}.");
    }

    /**
     * Delete an external website registration.
     */
    public function destroy(CopilotWebsite $copilotWebsite): RedirectResponse
    {
        $name = $copilotWebsite->name;
        $copilotWebsite->delete();

        return redirect()
            ->route('admin.copilot-websites.index')
            ->with('success', "External website \"{$name}\" removed.");
    }

    /**
     * Preview how the external embed script behaves on a simulated external webpage.
     */
    public function preview(CopilotWebsite $copilotWebsite): View
    {
        return view('admin.copilot_websites.preview', [
            'website' => $copilotWebsite,
        ]);
    }
}

