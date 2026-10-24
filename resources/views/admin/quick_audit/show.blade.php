@extends('layouts.admin')

@section('title', 'Quick Audit Report - ' . ($quickAudit->original_name ?? 'Drawing'))

@section('content')
    @if(in_array($quickAudit->status, ['pending', 'analyzing']))
        <meta http-equiv="refresh" content="3">
    @endif

    <div class="space-y-6 pt-2">
        <!-- Header -->
        <div>
            <a href="{{ route('admin.quick-audit.index') }}"
                class="text-xs font-semibold text-[#046938] hover:underline inline-flex items-center gap-1 mb-2">
                ← Back to Quick Audits
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        {{ $quickAudit->original_name ?? 'Single Drawing Audit' }}</h1>
                    <p class="text-sm text-slate-600 mt-1">
                        {{ ucfirst($quickAudit->discipline) }} Plan • {{ \App\Enums\BuildingClass::tryNormalize($quickAudit->occupancy_class)?->value ?? $quickAudit->occupancy_class }} • Submitted
                        {{ $quickAudit->created_at->format('M d, Y H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    @php $verdict = $quickAudit->overall_status; @endphp
                    @if($verdict === 'PASS')
                        <div
                            class="px-5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-extrabold text-sm tracking-wider flex items-center gap-2 shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            OVERALL: COMPLIANT (PASS)
                        </div>
                    @elseif($verdict === 'WARNING')
                        <div
                            class="px-5 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 font-extrabold text-sm tracking-wider flex items-center gap-2 shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-600"></span>
                            CONDITIONAL COMPLIANCE (WARNING)
                        </div>
                    @elseif(in_array($verdict, ['FAIL', 'REJECTED_PREFLIGHT']))
                        <div
                            class="px-5 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 font-extrabold text-sm tracking-wider flex items-center gap-2 shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>
                            NON-COMPLIANT ({{ $verdict }})
                        </div>
                    @else
                        <div
                            class="px-5 py-2.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 font-bold text-sm tracking-wider flex items-center gap-2 animate-pulse shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            AI COMPLIANCE ASSESSMENT IN PROGRESS...
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($quickAudit->status === 'analyzing' || $quickAudit->status === 'pending')
            <div class="p-8 rounded-2xl glass-panel text-center space-y-4">
                <div
                    class="w-12 h-12 mx-auto rounded-full bg-[#046938]/10 text-[#046938] flex items-center justify-center animate-spin border border-[#046938]/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Analyzing Blueprint Drawing</h3>
                    <p class="text-xs text-slate-500 mt-1">Executing PyMuPDF vector measurement + RAG statutory search + Gemini
                        multimodal audit... (Auto-refreshing)</p>
                </div>
            </div>
        @endif

        @if($quickAudit->error_message)
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                <div class="font-bold">Audit Rejection / Issue:</div>
                <div class="text-xs mt-1 text-slate-700">{{ $quickAudit->error_message }}</div>
            </div>
        @endif

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="p-5 rounded-2xl glass-panel">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pass Items</span>
                <div class="mt-2 text-3xl font-extrabold text-[#046938]">
                    {{ $quickAudit->summary_metrics['pass_count'] ?? 0 }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Dimensional & safety checks passed</p>
            </div>

            <div class="p-5 rounded-2xl glass-panel">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Violations / Errors</span>
                <div class="mt-2 text-3xl font-extrabold text-rose-600">
                    {{ $quickAudit->summary_metrics['fail_count'] ?? 0 }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Non-compliant code discrepancies</p>
            </div>

            <div class="p-5 rounded-2xl glass-panel">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Warnings</span>
                <div class="mt-2 text-3xl font-extrabold text-[#DEA74F]">
                    {{ $quickAudit->summary_metrics['warning_count'] ?? 0 }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Observations requiring professional review</p>
            </div>
        </div>

        @php
            $metrics = $quickAudit->floor_area_metrics ?? [];
            $rawFloors = $metrics['floors'] ?? [];
            $totalGfa = (float) ($quickAudit->gross_floor_area_sqm ?? $metrics['gross_floor_area_sqm'] ?? 0);
            $totalNia = isset($metrics['net_internal_area_sqm']) ? (float) $metrics['net_internal_area_sqm'] : null;
            $statutoryRate = \App\Services\FeeCalculationService::getRatePerSqm($quickAudit->occupancy_class);
            $buildingClassEnum = \App\Enums\BuildingClass::tryNormalize($quickAudit->occupancy_class);
            $displayClassLabel = $buildingClassEnum ? $buildingClassEnum->label() : ($quickAudit->occupancy_class ?? 'Class B: Standard Residential and Commercial Buildings');

            $floorsList = [];
            $allRootRooms = is_array($metrics['room_breakdown'] ?? null) ? $metrics['room_breakdown'] : [];

            if (!empty($rawFloors) && is_array($rawFloors)) {
                foreach ($rawFloors as $index => $f) {
                    $fGfa = isset($f['gross_floor_area_sqm']) ? (float) $f['gross_floor_area_sqm'] : 0.0;
                    $fNia = isset($f['net_internal_area_sqm']) ? (float) $f['net_internal_area_sqm'] : null;
                    $floorRooms = is_array($f['room_breakdown'] ?? null) ? $f['room_breakdown'] : [];
                    $floorsList[] = [
                        'name' => $f['floor_name'] ?? ('Floor ' . ($f['page'] ?? ($index + 1))),
                        'page' => $f['page'] ?? ($index + 1),
                        'gfa' => $fGfa,
                        'nia' => $fNia,
                        'fee' => \App\Services\FeeCalculationService::calculateFee($fGfa, $quickAudit->occupancy_class),
                        'pct' => ($totalGfa > 0 && $fGfa > 0) ? round(($fGfa / $totalGfa) * 100, 1) : null,
                        'rooms' => $floorRooms,
                    ];
                }
            } elseif ($totalGfa > 0) {
                $floorsList[] = [
                    'name' => $metrics['floor_name'] ?? 'Main Level / Floor Plan',
                    'page' => 1,
                    'gfa' => $totalGfa,
                    'nia' => $totalNia,
                    'fee' => $quickAudit->estimated_statutory_fee_ugx ?? \App\Services\FeeCalculationService::calculateFee($totalGfa, $quickAudit->occupancy_class),
                    'pct' => 100.0,
                    'rooms' => $allRootRooms,
                ];
            }
        @endphp

        @if($totalGfa > 0)
            <!-- Floor Area & Fee Card -->
            <div
                class="p-6 rounded-2xl glass-panel border border-[#046938]/20 bg-gradient-to-r from-emerald-50/60 via-white to-amber-50/40">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#046938] flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                            Calculated Architectural Floor Area & Statutory Fee
                        </span>
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <h3 class="text-xl font-extrabold text-slate-900">
                                {{ number_format($totalGfa, 2) }} <span
                                    class="text-[#046938] text-sm font-bold">m² (Gross Floor Area)</span>
                            </h3>
                            @if(count($floorsList) > 1)
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100/80 text-emerald-800 text-[11px] font-bold">
                                    {{ count($floorsList) }} Floor Levels Calculated
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-600">
                            Classified as <span class="text-slate-900 font-semibold" title="{{ $buildingClassEnum?->scope() }}">{{ $displayClassLabel }}</span> •
                            Statutory rate: <span class="font-mono font-semibold text-[#046938]">UGX
                                {{ number_format($statutoryRate, 0) }}
                                / m²</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-between lg:justify-end">
                        <div class="px-5 py-3 rounded-xl bg-white border border-[#046938]/20 shadow-xs text-right">
                            <span class="text-[11px] uppercase tracking-wider text-[#046938] font-bold block">Estimated Statutory
                                Plan Review Fee</span>
                            <div class="text-2xl font-black text-slate-900 mt-0.5">
                                <span class="text-[#DEA74F] text-base font-extrabold">UGX</span>
                                {{ number_format($quickAudit->estimated_statutory_fee_ugx, 0) }}
                            </div>
                        </div>

                        <button type="button"
                            id="openFloorAreaModalBtn"
                            onclick="openFloorAreaModal()"
                            class="px-4 py-3 rounded-xl bg-[#046938] hover:bg-[#03522c] active:bg-[#023c20] text-white text-xs font-bold shadow-sm hover:shadow transition-all flex items-center gap-2 group cursor-pointer">
                            <svg class="w-4 h-4 text-[#DEA74F] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>View Floor Breakdown</span>
                            <span class="px-2 py-0.5 rounded-full bg-white/20 text-[10px] font-mono font-semibold">
                                {{ count($floorsList) }} {{ count($floorsList) === 1 ? 'floor' : 'floors' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Pre-flight & Geometric Measurements -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Preflight Check -->
            <div class="p-6 rounded-2xl glass-panel space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Phase 1: Pre-Flight Verification</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Scale Notation:</span>
                        <span
                            class="font-mono font-bold text-slate-800">{{ $quickAudit->preflight_metrics['scale'] ?? 'Not Detected' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Document Readiness:</span>
                        <span
                            class="font-bold {{ ($quickAudit->preflight_metrics['is_valid'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ ($quickAudit->preflight_metrics['is_valid'] ?? false) ? 'Verified Clean Vector PDF' : 'Flagged / Needs Attention' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-500 font-medium">Scale Factor:</span>
                        <span
                            class="font-mono text-slate-800 font-semibold">{{ $quickAudit->preflight_metrics['scale_factor'] ?? 'N/A' }}
                            m/pt</span>
                    </div>
                </div>
            </div>

            <!-- Geometric Openings -->
            <div class="p-6 rounded-2xl glass-panel space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Phase 2: Vector CAD Geometry Extracted
                </h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Openings & Clearances Detected:</span>
                        <span
                            class="font-mono font-bold text-[#046938]">{{ $quickAudit->extracted_geometry['openings_detected_count'] ?? 0 }}
                            elements</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-500 font-medium">Engine Strategy:</span>
                        <span class="font-mono text-slate-700 font-semibold">PyMuPDF Deterministic Vector Lines + Gemini
                            2.5</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discrepancies Table -->
        <div class="p-6 rounded-2xl glass-panel space-y-4">
            <h3 class="text-base font-bold text-slate-900">Statutory Discrepancies & Compliance Audit Log</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead
                        class="bg-slate-50 text-xs uppercase tracking-wider text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Element / Location</th>
                            <th class="px-4 py-3">Uganda Statutory Citation</th>
                            <th class="px-4 py-3">Required Value</th>
                            <th class="px-4 py-3">Observed Value</th>
                            <th class="px-4 py-3">Severity</th>
                            <th class="px-4 py-3">Recommendation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($quickAudit->discrepancies ?? [] as $item)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    {{ $item['element_id'] ?? 'Element' }}
                                    <div class="text-[11px] text-slate-500 font-normal">{{ $item['room_or_grid'] ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-[#046938] font-mono font-bold">
                                    {{ $item['clause_reference'] ?? 'Uganda Building Code' }}
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-700">
                                    {{ $item['required_value'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3.5 text-xs text-rose-600 font-bold font-mono">
                                    {{ $item['observed_value'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3.5">
                                    @php $sev = $item['severity'] ?? 'INFO'; @endphp
                                    @if($sev === 'ERROR')
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200">ERROR</span>
                                    @elseif($sev === 'WARNING')
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200">WARNING</span>
                                    @else
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200">INFO</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600">
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

        @if(!empty($floorsList))
            <!-- Floor Area Metrics Detail Popup Modal -->
            <div id="floorAreaModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-floor-area-title" role="dialog" aria-modal="true">
                <!-- Backdrop overlay with blur -->
                <div id="floorAreaBackdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 opacity-0" onclick="closeFloorAreaModal()"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
                    <div id="floorAreaModalPanel" class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all duration-200 opacity-0 scale-95 w-full max-w-3xl border border-slate-200">
                        <!-- Modal Header -->
                        <div class="px-6 py-5 bg-gradient-to-r from-slate-900 via-[#03522c] to-[#046938] text-white flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-[#DEA74F] border border-white/10 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold tracking-tight text-white" id="modal-floor-area-title">
                                        Floor Area Metrics Breakdown
                                    </h3>
                                    <p class="text-xs text-white/70">
                                        Calculated area per floor & statutory fee schedule (Uganda Building Control Standards)
                                    </p>
                                </div>
                            </div>
                            <button type="button"
                                id="closeFloorAreaModalXBtn"
                                onclick="closeFloorAreaModal()"
                                class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 active:bg-white/30 text-white flex items-center justify-center transition-colors cursor-pointer"
                                aria-label="Close modal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                            <!-- Overall Metrics Summary Cards -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 shadow-xs">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Total Gross Area (GFA)</span>
                                    <div class="text-lg font-black text-slate-900 mt-1">
                                        {{ number_format($totalGfa, 2) }} <span class="text-xs text-[#046938] font-bold">m²</span>
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 shadow-xs">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Total Net Internal (NIA)</span>
                                    <div class="text-lg font-black text-slate-900 mt-1">
                                        @if($totalNia)
                                            {{ number_format($totalNia, 2) }} <span class="text-xs text-[#046938] font-bold">m²</span>
                                        @else
                                            <span class="text-xs text-slate-400 font-medium">N/A</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 shadow-xs">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Calculated Floors</span>
                                    <div class="text-lg font-black text-slate-900 mt-1">
                                        {{ count($floorsList) }} <span class="text-xs text-slate-500 font-semibold">{{ count($floorsList) === 1 ? 'level' : 'levels' }}</span>
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 shadow-xs">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Statutory Review Fee</span>
                                    <div class="text-lg font-black text-slate-900 font-mono mt-1">
                                        <span class="text-xs text-[#DEA74F] font-bold">UGX</span> {{ number_format($quickAudit->estimated_statutory_fee_ugx, 0) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Source and Confidence Banner -->
                            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-xs text-emerald-950">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#046938]"></span>
                                    <span class="font-medium">Calculation Engine / Source:</span>
                                    <span class="font-bold font-mono text-[#046938]">
                                        {{ ucwords(str_replace('_', ' ', $metrics['source'] ?? 'Multimodal Floor Detection')) }}
                                    </span>
                                </div>
                                @if(isset($metrics['confidence_score']))
                                    <div class="flex items-center gap-1.5 font-semibold text-[#046938] bg-white px-2.5 py-1 rounded-full border border-emerald-200">
                                        <span>Confidence:</span>
                                        <span class="font-mono font-bold">{{ round($metrics['confidence_score'] * 100) }}%</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Per-Floor Details List -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                        Calculated Area Per Floor Level
                                    </h4>
                                    <span class="text-xs text-slate-500 font-medium">
                                        Uganda Building Class: <strong class="text-slate-800" title="{{ $buildingClassEnum?->scope() }}">{{ $displayClassLabel }}</strong> (UGX {{ number_format($statutoryRate, 0) }}/m²)
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    @foreach($floorsList as $floorItem)
                                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200 hover:border-[#046938]/40 transition-colors space-y-3">
                                            <!-- Floor Title Row -->
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200/80">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-8 h-8 rounded-xl bg-[#046938] text-white font-black text-xs flex items-center justify-center font-mono shadow-xs shrink-0">
                                                        {{ $floorItem['page'] ?? $loop->iteration }}
                                                    </span>
                                                    <div>
                                                        <h5 class="text-sm font-extrabold text-slate-900">{{ $floorItem['name'] }}</h5>
                                                        @if(!empty($floorItem['page']))
                                                            <span class="text-[11px] text-slate-500 font-medium">Blueprint Sheet Page {{ $floorItem['page'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if(!is_null($floorItem['pct']))
                                                        <span class="px-2.5 py-1 rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-bold font-mono">
                                                            {{ $floorItem['pct'] }}% of Total GFA
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Key metrics row for this floor -->
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                                <div class="p-3 rounded-xl bg-white border border-slate-200/70">
                                                    <span class="text-slate-500 font-semibold block text-[11px] uppercase tracking-wider">Gross Floor Area (GFA)</span>
                                                    <span class="font-mono font-bold text-slate-900 text-sm mt-0.5 block">
                                                        {{ number_format($floorItem['gfa'], 2) }} <span class="text-[#046938]">m²</span>
                                                    </span>
                                                </div>
                                                <div class="p-3 rounded-xl bg-white border border-slate-200/70">
                                                    <span class="text-slate-500 font-semibold block text-[11px] uppercase tracking-wider">Net Internal Area (NIA)</span>
                                                    <span class="font-mono font-semibold text-slate-800 text-sm mt-0.5 block">
                                                        @if($floorItem['nia'])
                                                            {{ number_format($floorItem['nia'], 2) }} <span class="text-slate-500">m²</span>
                                                        @else
                                                            <span class="text-slate-400 font-normal text-xs">—</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="p-3 rounded-xl bg-white border border-slate-200/70">
                                                    <span class="text-slate-500 font-semibold block text-[11px] uppercase tracking-wider">Floor Statutory Fee</span>
                                                    <span class="font-mono font-bold text-slate-900 text-sm mt-0.5 block">
                                                        <span class="text-[#DEA74F]">UGX</span> {{ number_format($floorItem['fee'], 0) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Room breakdown if available -->
                                            @if(!empty($floorItem['rooms']))
                                                <div class="pt-2">
                                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600 block mb-2">
                                                        Detected Rooms & Compartments ({{ count($floorItem['rooms']) }})
                                                    </span>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        @foreach($floorItem['rooms'] as $room)
                                                            <div class="px-3 py-2 rounded-xl bg-white border border-slate-200 flex items-center justify-between text-xs">
                                                                <span class="text-slate-700 truncate pr-2 font-medium">{{ $room['name'] ?? 'Room' }}</span>
                                                                <span class="font-mono font-bold text-[#046938] shrink-0">
                                                                    {{ isset($room['area_sqm']) ? number_format((float) $room['area_sqm'], 2) . ' m²' : (isset($room['area']) ? number_format((float) $room['area'], 2) . ' m²' : '—') }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <svg class="w-4 h-4 text-[#046938]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span>Verified by NBRB Automated Statutory Area Measurement</span>
                            </div>
                            <button type="button"
                                id="closeFloorAreaModalBtn"
                                onclick="closeFloorAreaModal()"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 active:bg-slate-400 text-slate-800 text-xs font-bold transition-colors cursor-pointer">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal JavaScript Controller -->
            <script>
                function openFloorAreaModal() {
                    const modal = document.getElementById('floorAreaModal');
                    const backdrop = document.getElementById('floorAreaBackdrop');
                    const panel = document.getElementById('floorAreaModalPanel');
                    if (!modal) return;

                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');

                    requestAnimationFrame(() => {
                        if (backdrop) {
                            backdrop.classList.remove('opacity-0');
                            backdrop.classList.add('opacity-100');
                        }
                        if (panel) {
                            panel.classList.remove('opacity-0', 'scale-95');
                            panel.classList.add('opacity-100', 'scale-100');
                        }
                    });
                }

                function closeFloorAreaModal() {
                    const modal = document.getElementById('floorAreaModal');
                    const backdrop = document.getElementById('floorAreaBackdrop');
                    const panel = document.getElementById('floorAreaModalPanel');
                    if (!modal) return;

                    if (backdrop) {
                        backdrop.classList.remove('opacity-100');
                        backdrop.classList.add('opacity-0');
                    }
                    if (panel) {
                        panel.classList.remove('opacity-100', 'scale-100');
                        panel.classList.add('opacity-0', 'scale-95');
                    }

                    setTimeout(() => {
                        modal.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    }, 200);
                }

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        const modal = document.getElementById('floorAreaModal');
                        if (modal && !modal.classList.contains('hidden')) {
                            closeFloorAreaModal();
                        }
                    }
                });
            </script>
        @endif
    </div>
@endsection