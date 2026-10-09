<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplianceResult;
use App\Models\DrawingVersion;
use App\Models\Project;
use App\Models\RegulatoryChunk;
use App\Models\RegulatoryDocument;
use App\Services\FastApiService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(FastApiService $fastApiService): View
    {
        $totalProjects = Project::count();
        $totalDocuments = RegulatoryDocument::count();
        $totalChunks = RegulatoryChunk::count();
        $totalVersions = DrawingVersion::count();

        $passCount = ComplianceResult::where('overall_status', 'PASS')->count();
        $failCount = ComplianceResult::whereIn('overall_status', ['FAIL', 'REJECTED_PREFLIGHT'])->count();
        $warningCount = ComplianceResult::where('overall_status', 'WARNING')->count();
        $totalAnalyzed = $passCount + $failCount + $warningCount;

        $complianceRate = $totalAnalyzed > 0 ? round(($passCount / $totalAnalyzed) * 100, 1) : 0;

        $recentDocuments = RegulatoryDocument::with('uploader:id,name,email')
            ->latest()
            ->limit(5)
            ->get();

        $recentSubmissions = DrawingVersion::with(['drawing.floor.block.project', 'complianceResult', 'uploader:id,name,email'])
            ->latest()
            ->limit(6)
            ->get();

        $fastApiHealthy = $fastApiService->healthCheck();

        return view('admin.dashboard', compact(
            'totalProjects',
            'totalDocuments',
            'totalChunks',
            'totalVersions',
            'passCount',
            'failCount',
            'warningCount',
            'complianceRate',
            'recentDocuments',
            'recentSubmissions',
            'fastApiHealthy'
        ));
    }
}
