<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegulatoryChunk;
use App\Models\RegulatoryDocument;
use App\Services\CopilotPersonaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CopilotPageController extends Controller
{
    /**
     * Display the dedicated full-page Statutory AI Copilot workspace.
     */
    public function index(Request $request): View
    {
        $totalDocuments = RegulatoryDocument::where('is_active', true)->count();
        $totalChunks = RegulatoryChunk::count();

        // Retrieve personas that have 'enabled' => true
        $personas = CopilotPersonaService::getEnabled();

        $recentDocuments = RegulatoryDocument::where('is_active', true)
            ->withCount('chunks')
            ->latest()
            ->take(6)
            ->get();

        return view('admin.copilot.index', compact(
            'personas',
            'totalDocuments',
            'totalChunks',
            'recentDocuments'
        ));
    }
}
