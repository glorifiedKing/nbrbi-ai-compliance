@extends('layouts.admin')

@section('title', 'Quick Audit Report - ' . ($quickAudit->original_name ?? 'Drawing'))

@section('content')
@if(in_array($quickAudit->status, ['pending', 'analyzing']))
    <meta http-equiv="refresh" content="3">
@endif

<div class="space-y-6 pt-2">
    <!-- Header -->
    <div>
        <a href="{{ route('admin.quick-audit.index') }}" class="text-xs font-semibold text-emerald-400 hover:underline inline-flex items-center gap-1 mb-2">
            ← Back to Quick Audits
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-100 tracking-tight">{{ $quickAudit->original_name ?? 'Single Drawing Audit' }}</h1>
                <p class="text-sm text-slate-400 mt-1">
                    {{ ucfirst($quickAudit->discipline) }} Plan • {{ $quickAudit->occupancy_class }} • Submitted {{ $quickAudit->created_at->format('M d, Y H:i') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                @php $verdict = $quickAudit->overall_status; @endphp
                @if($verdict === 'PASS')
                    <div class="px-5 py-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-extrabold text-sm tracking-wider flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                        OVERALL: COMPLIANT (PASS)
                    </div>
                @elseif($verdict === 'WARNING')
                    <div class="px-5 py-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-sm tracking-wider flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        CONDITIONAL COMPLIANCE (WARNING)
                    </div>
                @elseif(in_array($verdict, ['FAIL', 'REJECTED_PREFLIGHT']))
                    <div class="px-5 py-2.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 font-extrabold text-sm tracking-wider flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                        NON-COMPLIANT ({{ $verdict }})
                    </div>
                @else
                    <div class="px-5 py-2.5 rounded-xl bg-blue-500/10 border border-blue-500/30 text-blue-400 font-bold text-sm tracking-wider flex items-center gap-2 animate-pulse">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                        AI COMPLIANCE ASSESSMENT IN PROGRESS...
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($quickAudit->status === 'analyzing' || $quickAudit->status === 'pending')
        <div class="p-8 rounded-2xl glass-panel text-center space-y-4">
            <div class="w-12 h-12 mx-auto rounded-full bg-blue-500/10 text-blue-400 flex items-center justify-center animate-spin border border-blue-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-100">Analyzing Blueprint Drawing</h3>
                <p class="text-xs text-slate-400 mt-1">Executing PyMuPDF vector measurement + RAG statutory search + Gemini multimodal audit... (Auto-refreshing)</p>
            </div>
        </div>
    @endif

    @if($quickAudit->error_message)
        <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
            <div class="font-bold">Audit Rejection / Issue:</div>
            <div class="text-xs mt-1 text-slate-300">{{ $quickAudit->error_message }}</div>
        </div>
    @endif

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="p-5 rounded-2xl glass-panel">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pass Items</span>
            <div class="mt-2 text-3xl font-extrabold text-emerald-400">
                {{ $quickAudit->summary_metrics['pass_count'] ?? 0 }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Dimensional & safety checks passed</p>
        </div>

        <div class="p-5 rounded-2xl glass-panel">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Violations / Errors</span>
            <div class="mt-2 text-3xl font-extrabold text-red-400">
                {{ $quickAudit->summary_metrics['fail_count'] ?? 0 }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Non-compliant code discrepancies</p>
        </div>

        <div class="p-5 rounded-2xl glass-panel">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Warnings</span>
            <div class="mt-2 text-3xl font-extrabold text-amber-400">
                {{ $quickAudit->summary_metrics['warning_count'] ?? 0 }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Observations requiring professional review</p>
        </div>
    </div>

    @if($quickAudit->gross_floor_area_sqm)
        <!-- Floor Area & Fee Card -->
        <div class="p-6 rounded-2xl glass-panel border border-teal-500/30 bg-teal-950/10">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        Calculated Architectural Floor Area & Statutory Fee
                    </span>
                    <h3 class="text-xl font-extrabold text-slate-100">
                        {{ number_format($quickAudit->gross_floor_area_sqm, 2) }} <span class="text-teal-400 text-sm font-semibold">m² (Gross Floor Area)</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Classified as <span class="text-slate-200 font-semibold">{{ $quickAudit->occupancy_class }}</span> • Statutory rate: <span class="font-mono text-teal-300">UGX {{ number_format(\App\Services\FeeCalculationService::getRatePerSqm($quickAudit->occupancy_class), 0) }} / m²</span>
                    </p>
                </div>
                <div class="px-5 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-right">
                    <span class="text-[11px] uppercase tracking-wider text-emerald-400 font-bold block">Estimated Statutory Plan Review Fee</span>
                    <div class="text-2xl font-black text-slate-100 mt-0.5">
                        <span class="text-emerald-400 text-base">UGX</span> {{ number_format($quickAudit->estimated_statutory_fee_ugx, 0) }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Pre-flight & Geometric Measurements -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Preflight Check -->
        <div class="p-6 rounded-2xl glass-panel space-y-3">
            <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">Phase 1: Pre-Flight Verification</h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Scale Notation:</span>
                    <span class="font-mono font-bold text-slate-200">{{ $quickAudit->preflight_metrics['scale'] ?? 'Not Detected' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Document Readiness:</span>
                    <span class="font-semibold {{ ($quickAudit->preflight_metrics['is_valid'] ?? false) ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ ($quickAudit->preflight_metrics['is_valid'] ?? false) ? 'Verified Clean Vector PDF' : 'Flagged / Needs Attention' }}
                    </span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-slate-400">Scale Factor:</span>
                    <span class="font-mono text-slate-200">{{ $quickAudit->preflight_metrics['scale_factor'] ?? 'N/A' }} m/pt</span>
                </div>
            </div>
        </div>

        <!-- Geometric Openings -->
        <div class="p-6 rounded-2xl glass-panel space-y-3">
            <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">Phase 2: Vector CAD Geometry Extracted</h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-800">
                    <span class="text-slate-400">Openings & Clearances Detected:</span>
                    <span class="font-mono font-bold text-emerald-400">{{ $quickAudit->extracted_geometry['openings_detected_count'] ?? 0 }} elements</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-slate-400">Engine Strategy:</span>
                    <span class="font-mono text-slate-300">PyMuPDF Deterministic Vector Lines + Gemini 2.5</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Discrepancies Table -->
    <div class="p-6 rounded-2xl glass-panel space-y-4">
        <h3 class="text-base font-bold text-slate-100">Statutory Discrepancies & Compliance Audit Log</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/90 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Element / Location</th>
                        <th class="px-4 py-3">Uganda Statutory Citation</th>
                        <th class="px-4 py-3">Required Value</th>
                        <th class="px-4 py-3">Observed Value</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">Recommendation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($quickAudit->discrepancies ?? [] as $item)
                        <tr class="hover:bg-slate-900/40">
                            <td class="px-4 py-3.5 font-bold text-slate-200">
                                {{ $item['element_id'] ?? 'Element' }}
                                <div class="text-[11px] text-slate-500 font-normal">{{ $item['room_or_grid'] ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-emerald-400 font-mono">
                                {{ $item['clause_reference'] ?? 'Uganda Building Code' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-300">
                                {{ $item['required_value'] ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-red-400 font-semibold font-mono">
                                {{ $item['observed_value'] ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3.5">
                                @php $sev = $item['severity'] ?? 'INFO'; @endphp
                                @if($sev === 'ERROR')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-red-500/10 text-red-400 border border-red-500/30">ERROR</span>
                                @elseif($sev === 'WARNING')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-500/10 text-amber-400 border border-amber-500/30">WARNING</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-500/10 text-blue-400 border border-blue-500/30">INFO</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-300">
                                {{ $item['recommendation'] ?? '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-xs">
                                @if($quickAudit->overall_status === 'PASS')
                                    ✨ Perfect compliance! No statutory code violations or discrepancies identified on this plan.
                                @else
                                    No discrepancy line items recorded.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
