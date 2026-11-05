@extends('layouts.admin')

@section('title', 'Consolidated Compliance Report - ' . $project->name)

@section('content')
    @if($hasActiveJobs)
        <meta http-equiv="refresh" content="3">
    @endif

    <div class="space-y-6 pt-2">
        <!-- Header -->
        <div>
            <a href="{{ route('admin.projects.show', $project) }}"
                class="text-xs font-semibold text-[#046938] hover:underline inline-flex items-center gap-1 mb-2">
                ← Back to {{ $project->name }} Workspace
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        {{ $project->name }}</h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Ref: <span class="font-mono font-bold">{{ $project->reference_number }}</span> •
                        {{ \App\Enums\BuildingClass::tryNormalize($project->occupancy_class)?->value ?? $project->occupancy_class }} •
                        {{ $project->blocks->count() }} {{ Str::plural('Block', $project->blocks->count()) }} ({{ $processedDrawings }}/{{ $totalDrawings }} Drawings Audited)
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    @if($overallProjectStatus === 'COMPLIANT' && $totalWarning === 0)
                        <div
                            class="px-5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-extrabold text-sm tracking-wider flex items-center gap-2 shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            OVERALL: COMPLIANT (PASS)
                        </div>
                    @elseif($overallProjectStatus === 'COMPLIANT' && $totalWarning > 0)
                        <div
                            class="px-5 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 font-extrabold text-sm tracking-wider flex items-center gap-2 shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-600"></span>
                            CONDITIONAL COMPLIANCE (WARNING)
                        </div>
                    @elseif($overallProjectStatus === 'NON_COMPLIANT')
                        <div
                            class="px-5 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 font-extrabold text-sm tracking-wider flex items-center gap-2 shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>
                            NON-COMPLIANT (FAIL)
                        </div>
                    @elseif($overallProjectStatus === 'IN_PROGRESS' || $hasActiveJobs)
                        <div
                            class="px-5 py-2.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 font-bold text-sm tracking-wider flex items-center gap-2 animate-pulse shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            AI COMPLIANCE ASSESSMENT IN PROGRESS...
                        </div>
                    @else
                        <div
                            class="px-5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-bold text-sm tracking-wider flex items-center gap-2 shadow-sm">
                            NO SUBMISSIONS YET
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($hasActiveJobs)
            <div class="p-8 rounded-2xl glass-panel text-center space-y-4">
                <div
                    class="w-12 h-12 mx-auto rounded-full bg-[#046938]/10 text-[#046938] flex items-center justify-center animate-spin border border-[#046938]/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Analyzing Project Blueprint Drawings</h3>
                    <p class="text-xs text-slate-500 mt-1">Executing PyMuPDF vector measurement + RAG statutory search + Gemini
                        multimodal audit... (Auto-refreshing)</p>
                </div>
            </div>
        @endif

        @php
            $passItemsCount = 0;
            $failItemsCount = 0;
            $warningItemsCount = 0;
            $openingsCount = 0;
            $lastScale = null;
            $lastScaleFactor = null;
            $allPreflightValid = $processedDrawings > 0;
            $latestSource = 'Multimodal Floor Detection';
            $latestConfidence = null;
            $totalNia = 0.0;
            $hasNia = false;

            foreach ($project->blocks as $block) {
                foreach ($block->drawings as $drawing) {
                    $cv = $drawing->currentVersion ?? $drawing->versions->first();
                    $cr = $cv?->complianceResult;
                    if ($cr) {
                        $passItemsCount += (int) ($cr->summary_metrics['pass_count'] ?? 0);
                        $failItemsCount += (int) ($cr->summary_metrics['fail_count'] ?? 0);
                        $warningItemsCount += (int) ($cr->summary_metrics['warning_count'] ?? 0);
                        $openingsCount += (int) ($cr->extracted_geometry['openings_detected_count'] ?? 0);

                        if (!empty($cr->preflight_metrics['scale'])) {
                            $lastScale = $cr->preflight_metrics['scale'];
                        }
                        if (!empty($cr->preflight_metrics['scale_factor'])) {
                            $lastScaleFactor = $cr->preflight_metrics['scale_factor'];
                        }
                        if (!($cr->preflight_metrics['is_valid'] ?? true)) {
                            $allPreflightValid = false;
                        }
                        if (!empty($cr->floor_area_metrics['source'])) {
                            $latestSource = $cr->floor_area_metrics['source'];
                        }
                        if (isset($cr->floor_area_metrics['confidence_score'])) {
                            $latestConfidence = $cr->floor_area_metrics['confidence_score'];
                        }
                    }
                }
            }

            $totalGfa = (float) ($project->total_floor_area_sqm ?? 0);
            $statutoryRate = $feeRate ?? \App\Services\FeeCalculationService::getRatePerSqm($project->occupancy_class);
            $buildingClassEnum = \App\Enums\BuildingClass::tryNormalize($project->occupancy_class);
            $displayClassLabel = $buildingClassEnum ? $buildingClassEnum->label() : ($project->occupancy_class ?? 'Class B: Standard Residential and Commercial Buildings');

            $floorsList = [];
            foreach ($project->blocks as $block) {
                $rawFloors = is_array($block->floors) ? $block->floors : [];
                foreach ($rawFloors as $index => $f) {
                    if (!is_array($f)) {
                        continue;
                    }
                    $fGfa = isset($f['gross_floor_area_sqm']) ? (float) $f['gross_floor_area_sqm'] : 0.0;
                    $fNia = isset($f['net_internal_area_sqm']) ? (float) $f['net_internal_area_sqm'] : null;
                    if ($fNia !== null && $fNia > 0) {
                        $totalNia += $fNia;
                        $hasNia = true;
                    }
                    $floorRooms = is_array($f['room_breakdown'] ?? null) ? $f['room_breakdown'] : [];
                    $floorName = $f['floor_name'] ?? $f['name'] ?? ('Floor ' . ($f['floor_number'] ?? ($index + 1)));

                    $floorsList[] = [
                        'block_name' => $block->name,
                        'name' => $block->name . ' — ' . $floorName,
                        'floor_number' => $f['floor_number'] ?? $index,
                        'page' => $f['page'] ?? ($index + 1),
                        'gfa' => $fGfa,
                        'nia' => $fNia,
                        'fee' => \App\Services\FeeCalculationService::calculateFee($fGfa, $project->occupancy_class),
                        'pct' => ($totalGfa > 0 && $fGfa > 0) ? round(($fGfa / $totalGfa) * 100, 1) : null,
                        'rooms' => $floorRooms,
                    ];
                }
            }
        @endphp

        <!-- Metrics Grid (Same layout as Quick Audit Report) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="p-5 rounded-2xl glass-panel">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pass Items</span>
                <div class="mt-2 text-3xl font-extrabold text-[#046938]">
                    {{ $passItemsCount > 0 ? $passItemsCount : $totalPass }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Dimensional & safety checks passed ({{ $totalPass }}/{{ $totalDrawings }} drawings passed)</p>
            </div>

            <div class="p-5 rounded-2xl glass-panel">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Violations / Errors</span>
                <div class="mt-2 text-3xl font-extrabold text-rose-600">
                    {{ $failItemsCount > 0 ? $failItemsCount : $totalFail }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Non-compliant code discrepancies ({{ $totalFail }} drawings failed)</p>
            </div>

            <div class="p-5 rounded-2xl glass-panel">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Warnings</span>
                <div class="mt-2 text-3xl font-extrabold text-[#DEA74F]">
                    {{ $warningItemsCount > 0 ? $warningItemsCount : $totalWarning }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Observations requiring professional review</p>
            </div>
        </div>

        @if($totalGfa > 0 || !empty($floorsList))
            <!-- Floor Area & Fee Card (Matches Quick Audit Report) -->
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
                                {{ number_format($totalGfa, 2) }} <span class="text-[#046938] text-sm font-bold">m² (Gross Floor
                                    Area)</span>
                            </h3>
                            @if(count($floorsList) > 0)
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100/80 text-emerald-800 text-[11px] font-bold">
                                    {{ count($floorsList) }} {{ count($floorsList) === 1 ? 'Floor Level' : 'Floor Levels' }} Across {{ $project->blocks->count() }} {{ Str::plural('Block', $project->blocks->count()) }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-600">
                            Classified as <span class="text-slate-900 font-semibold"
                                title="{{ $buildingClassEnum?->scope() }}">{{ $displayClassLabel }}</span> •
                            Statutory rate: <span class="font-mono font-semibold text-[#046938]">UGX
                                {{ number_format($statutoryRate, 0) }}
                                / m²</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-between lg:justify-end">
                        <div class="px-5 py-3 rounded-xl bg-white border border-[#046938]/20 shadow-xs text-right">
                            <span class="text-[11px] uppercase tracking-wider text-[#046938] font-bold block">Estimated
                                Statutory
                                Plan Review Fee</span>
                            <div class="text-2xl font-black text-slate-900 mt-0.5">
                                <span class="text-[#DEA74F] text-base font-extrabold">UGX</span>
                                {{ number_format((float) $project->estimated_statutory_fee_ugx, 0) }}
                            </div>
                        </div>

                        @if(!empty($floorsList))
                            <button type="button" id="openFloorAreaModalBtn" onclick="openFloorAreaModal()"
                                class="px-4 py-3 rounded-xl bg-[#046938] hover:bg-[#03522c] active:bg-[#023c20] text-white text-xs font-bold shadow-sm hover:shadow transition-all flex items-center gap-2 group cursor-pointer">
                                <svg class="w-4 h-4 text-[#DEA74F] group-hover:scale-110 transition-transform" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <span>View Floor Breakdown</span>
                                <span class="px-2 py-0.5 rounded-full bg-white/20 text-[10px] font-mono font-semibold">
                                    {{ count($floorsList) }} {{ count($floorsList) === 1 ? 'floor' : 'floors' }}
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Discipline Breakdown -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($disciplineSummaries as $disc => $summary)
                <div class="p-4 rounded-2xl glass-panel space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-700">{{ ucfirst($disc) }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold
                            {{ $summary['status'] === 'COMPLIANT' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' :
                               ($summary['status'] === 'NON_COMPLIANT' ? 'bg-rose-50 text-rose-800 border border-rose-200' :
                               ($summary['status'] === 'IN_REVIEW' ? 'bg-blue-50 text-blue-800 border border-blue-200' : 'bg-slate-100 text-slate-600')) }}">
                            {{ $summary['status'] }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-600">
                        Total: <strong>{{ $summary['total'] }}</strong> •
                        Passed: <strong class="text-emerald-700">{{ $summary['passed'] }}</strong> •
                        Failed: <strong class="text-rose-700">{{ $summary['failed'] }}</strong>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pre-flight & Geometric Measurements (Matches Quick Audit Report) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Preflight Check -->
            <div class="p-6 rounded-2xl glass-panel space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Phase 1: Pre-Flight Verification</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Scale Notation:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $lastScale ?? '1:100 / Standard' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Document Readiness:</span>
                        <span class="font-bold {{ $allPreflightValid ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $allPreflightValid ? 'Verified Clean Vector PDF' : 'Flagged / Needs Attention' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-500 font-medium">Scale Factor:</span>
                        <span class="font-mono text-slate-800 font-semibold">{{ $lastScaleFactor ?? 'N/A' }} m/pt</span>
                    </div>
                </div>
            </div>

            <!-- Geometric Openings -->
            <div class="p-6 rounded-2xl glass-panel space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Phase 2: Vector CAD Geometry Extracted</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Openings & Clearances Detected:</span>
                        <span class="font-mono font-bold text-[#046938]">{{ $openingsCount }} elements</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-500 font-medium">Engine Strategy:</span>
                        <span class="font-mono text-slate-700 font-semibold">PyMuPDF Deterministic Vector Lines + Gemini 2.5</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discrepancies Table (Tabular format matching Quick Audit Report + Block, Discipline & Version) -->
        @php
            $discrepancyCountsByDiscipline = [
                'architectural' => 0,
                'structural' => 0,
                'mechanical' => 0,
                'electrical' => 0,
            ];
            foreach ($allDiscrepancies as $dItem) {
                $dDisc = strtolower((string) ($dItem['discipline'] ?? ''));
                if (isset($discrepancyCountsByDiscipline[$dDisc])) {
                    $discrepancyCountsByDiscipline[$dDisc]++;
                }
            }
        @endphp

        <div class="p-6 rounded-2xl glass-panel space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h3 class="text-base font-bold text-slate-900">Statutory Discrepancies & Compliance Audit Log</h3>
                        <span id="discrepancyVisibleBadge"
                            class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ count($allDiscrepancies) }} {{ Str::plural('item', count($allDiscrepancies)) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Filter discrepancies by engineering discipline (click one or multiple disciplines to filter)</p>
                </div>

                <!-- Interactive Discipline Filter: Toggle Buttons + Multi-Select Dropdown -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- "All" Default Toggle Button -->
                    <button type="button" id="discFilterAllBtn" onclick="resetDisciplineFilter()"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border bg-[#046938] text-white border-[#046938] shadow-xs">
                        All Disciplines ({{ count($allDiscrepancies) }})
                    </button>

                    <!-- Per-Discipline Multi-Select Toggle Buttons -->
                    @foreach(['architectural' => 'Architectural', 'structural' => 'Structural', 'mechanical' => 'Mechanical', 'electrical' => 'Electrical'] as $dKey => $dLabel)
                        <button type="button"
                            data-discipline-btn="{{ $dKey }}"
                            onclick="toggleDisciplineFilter('{{ $dKey }}')"
                            class="disc-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border bg-white text-slate-600 border-slate-200 hover:border-[#046938]/50 hover:text-[#046938] inline-flex items-center gap-1.5">
                            <span>{{ $dLabel }}</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-slate-100 text-slate-600">
                                {{ $discrepancyCountsByDiscipline[$dKey] }}
                            </span>
                        </button>
                    @endforeach

                    <!-- Multi-Select Dropdown Menu -->
                    <div class="relative" id="discDropdownContainer">
                        <button type="button" onclick="toggleDiscDropdown()"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 inline-flex items-center gap-1.5 cursor-pointer transition-colors">
                            <svg class="w-3.5 h-3.5 text-[#046938]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span id="discDropdownLabel">Multi-Select</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="discDropdownMenu"
                            class="hidden absolute right-0 mt-2 w-56 rounded-2xl bg-white border border-slate-200 shadow-xl z-30 p-2 space-y-1">
                            <div class="px-2.5 py-1.5 border-b border-slate-100 flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Select Disciplines</span>
                                <button type="button" onclick="resetDisciplineFilter()"
                                    class="text-[11px] font-bold text-[#046938] hover:underline cursor-pointer">
                                    Show All
                                </button>
                            </div>
                            @foreach(['architectural' => 'Architectural', 'structural' => 'Structural', 'mechanical' => 'Mechanical', 'electrical' => 'Electrical'] as $dKey => $dLabel)
                                <label class="flex items-center justify-between px-2.5 py-2 rounded-xl hover:bg-slate-50 cursor-pointer text-xs font-semibold text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" value="{{ $dKey }}"
                                            data-discipline-checkbox="{{ $dKey }}"
                                            onchange="toggleDisciplineFilter('{{ $dKey }}')"
                                            class="rounded border-slate-300 text-[#046938] focus:ring-[#046938]">
                                        <span>{{ $dLabel }}</span>
                                    </div>
                                    <span class="font-mono text-[11px] text-slate-400">{{ $discrepancyCountsByDiscipline[$dKey] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700" id="discrepanciesTable">
                    <thead
                        class="bg-slate-50 text-xs uppercase tracking-wider text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Block</th>
                            <th class="px-4 py-3">Discipline & Version</th>
                            <th class="px-4 py-3">Element / Location</th>
                            <th class="px-4 py-3">Uganda Statutory Citation</th>
                            <th class="px-4 py-3">Required Value</th>
                            <th class="px-4 py-3">Observed Value</th>
                            <th class="px-4 py-3">Severity</th>
                            <th class="px-4 py-3">Recommendation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($allDiscrepancies as $item)
                            @php
                                $rowDisc = strtolower((string) ($item['discipline'] ?? 'general'));
                                $sev = strtoupper((string) ($item['severity'] ?? 'INFO'));
                                $sevTextColor = match ($sev) {
                                    'ERROR', 'FAIL', 'CRITICAL' => 'text-rose-700',
                                    'WARNING' => 'text-amber-700',
                                    default => 'text-blue-700',
                                };
                            @endphp
                            <tr class="discrepancy-row hover:bg-slate-50/80 transition-colors" data-discipline="{{ $rowDisc }}">
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    {{ $item['block'] ?? 'Main Block' }}
                                    @if(!empty($item['floor']))
                                        <div class="text-[11px] text-slate-500 font-normal">{{ $item['floor'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs font-mono uppercase font-semibold border border-slate-200">
                                        {{ $item['discipline'] ?? 'general' }}
                                    </span>
                                    <span class="ml-1 text-xs font-mono font-bold text-slate-500">
                                        {{ $item['version'] ?? 'v1' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    {{ $item['element_id'] ?? $item['element'] ?? 'Element' }}
                                    <div class="text-[11px] text-slate-500 font-normal">{{ $item['room_or_grid'] ?? $item['location'] ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-[#046938] font-mono font-bold">
                                    {{ $item['clause_reference'] ?? $item['rule_id'] ?? 'Uganda Building Code' }}
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-700">
                                    {{ $item['required_value'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3.5 text-xs {{ $sevTextColor }} font-bold font-mono">
                                    {{ $item['observed_value'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3.5">
                                    @if(in_array($sev, ['ERROR', 'FAIL', 'CRITICAL']))
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200">{{ $sev }}</span>
                                    @elseif($sev === 'WARNING')
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200">WARNING</span>
                                    @else
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200">{{ $sev }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600">
                                    {{ $item['recommendation'] ?? $item['description'] ?? $item['message'] ?? '' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500 text-xs">
                                    @if($overallProjectStatus === 'COMPLIANT')
                                        ✨ Perfect compliance! No statutory code violations or discrepancies identified across all audited project plans.
                                    @else
                                        No discrepancy line items recorded.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                        <tr id="discrepancyFilterEmptyRow" class="hidden">
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500 text-xs">
                                No discrepancies recorded for the selected engineering discipline(s).
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if(!empty($floorsList))
            <!-- Floor Area Metrics Detail Popup Modal (Same as Quick Audit Report) -->
            <div id="floorAreaModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-floor-area-title"
                role="dialog" aria-modal="true">
                <!-- Backdrop overlay with blur -->
                <div id="floorAreaBackdrop"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 opacity-0"
                    onclick="closeFloorAreaModal()"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
                    <div id="floorAreaModalPanel"
                        class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all duration-200 opacity-0 scale-95 w-full max-w-3xl border border-slate-200">
                        <!-- Modal Header -->
                        <div
                            class="px-6 py-5 bg-gradient-to-r from-slate-900 via-[#03522c] to-[#046938] text-white flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-[#DEA74F] border border-white/10 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold tracking-tight text-white" id="modal-floor-area-title">
                                        Floor Area Metrics Breakdown
                                    </h3>
                                    <p class="text-xs text-white/70">
                                        Calculated area per block & floor level (Uganda Building Control Standards)
                                    </p>
                                </div>
                            </div>
                            <button type="button" id="closeFloorAreaModalXBtn" onclick="closeFloorAreaModal()"
                                class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 active:bg-white/30 text-white flex items-center justify-center transition-colors cursor-pointer"
                                aria-label="Close modal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                            <!-- Overall Metrics Summary Cards -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 shadow-xs">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Total
                                        Gross Area (GFA)</span>
                                    <div class="text-lg font-black text-slate-900 mt-1">
                                        {{ number_format($totalGfa, 2) }} <span
                                            class="text-xs text-[#046938] font-bold">m²</span>
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 shadow-xs">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Total Net
                                        Internal (NIA)</span>
                                    <div class="text-lg font-black text-slate-900 mt-1">
                                        @if($hasNia)
                                            {{ number_format($totalNia, 2) }} <span
                                                class="text-xs text-[#046938] font-bold">m²</span>
                                        @else
                                            <span class="text-xs text-slate-400 font-medium">N/A</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 shadow-xs">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Calculated
                                        Floors</span>
                                    <div class="text-lg font-black text-slate-900 mt-1">
                                        {{ count($floorsList) }} <span
                                            class="text-xs text-slate-500 font-semibold">{{ count($floorsList) === 1 ? 'level' : 'levels' }}</span>
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 shadow-xs">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Statutory
                                        Review Fee</span>
                                    <div class="text-lg font-black text-slate-900 font-mono mt-1">
                                        <span class="text-xs text-[#DEA74F] font-bold">UGX</span>
                                        {{ number_format((float) $project->estimated_statutory_fee_ugx, 0) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Source and Confidence Banner -->
                            <div
                                class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-xs text-emerald-950">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#046938]"></span>
                                    <span class="font-medium">Calculation Engine / Source:</span>
                                    <span class="font-bold font-mono text-[#046938]">
                                        {{ ucwords(str_replace('_', ' ', $latestSource)) }}
                                    </span>
                                </div>
                                @if(!is_null($latestConfidence))
                                    <div
                                        class="flex items-center gap-1.5 font-semibold text-[#046938] bg-white px-2.5 py-1 rounded-full border border-emerald-200">
                                        <span>Confidence:</span>
                                        <span class="font-mono font-bold">{{ round($latestConfidence * 100) }}%</span>
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
                                        Uganda Building Class: <strong class="text-slate-800"
                                            title="{{ $buildingClassEnum?->scope() }}">{{ $displayClassLabel }}</strong> (UGX
                                        {{ number_format($statutoryRate, 0) }}/m²)
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    @foreach($floorsList as $floorItem)
                                        <div
                                            class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200 hover:border-[#046938]/40 transition-colors space-y-3">
                                            <!-- Floor Title Row -->
                                            <div
                                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200/80">
                                                <div class="flex items-center gap-3">
                                                    <span
                                                        class="w-8 h-8 rounded-xl bg-[#046938] text-white font-black text-xs flex items-center justify-center font-mono shadow-xs shrink-0">
                                                        {{ $floorItem['floor_number'] ?? $loop->iteration }}
                                                    </span>
                                                    <div>
                                                        <h5 class="text-sm font-extrabold text-slate-900">{{ $floorItem['name'] }}
                                                        </h5>
                                                        <span class="text-[11px] text-slate-500 font-medium">
                                                            Block: {{ $floorItem['block_name'] }} • Floor #{{ $floorItem['floor_number'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if(!is_null($floorItem['pct']))
                                                        <span
                                                            class="px-2.5 py-1 rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-bold font-mono">
                                                            {{ $floorItem['pct'] }}% of Total GFA
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Key metrics row for this floor -->
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                                <div class="p-3 rounded-xl bg-white border border-slate-200/70">
                                                    <span
                                                        class="text-slate-500 font-semibold block text-[11px] uppercase tracking-wider">Gross
                                                        Floor Area (GFA)</span>
                                                    <span class="font-mono font-bold text-slate-900 text-sm mt-0.5 block">
                                                        {{ number_format($floorItem['gfa'], 2) }} <span
                                                            class="text-[#046938]">m²</span>
                                                    </span>
                                                </div>
                                                <div class="p-3 rounded-xl bg-white border border-slate-200/70">
                                                    <span
                                                        class="text-slate-500 font-semibold block text-[11px] uppercase tracking-wider">Net
                                                        Internal Area (NIA)</span>
                                                    <span class="font-mono font-semibold text-slate-800 text-sm mt-0.5 block">
                                                        @if($floorItem['nia'])
                                                            {{ number_format($floorItem['nia'], 2) }} <span
                                                                class="text-slate-500">m²</span>
                                                        @else
                                                            <span class="text-slate-400 font-normal text-xs">—</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="p-3 rounded-xl bg-white border border-slate-200/70">
                                                    <span
                                                        class="text-slate-500 font-semibold block text-[11px] uppercase tracking-wider">Floor
                                                        Statutory Fee</span>
                                                    <span class="font-mono font-bold text-slate-900 text-sm mt-0.5 block">
                                                        <span class="text-[#DEA74F]">UGX</span>
                                                        {{ number_format($floorItem['fee'], 0) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Room breakdown if available -->
                                            @if(!empty($floorItem['rooms']))
                                                <div class="pt-2">
                                                    <span
                                                        class="text-[11px] font-bold uppercase tracking-wider text-slate-600 block mb-2">
                                                        Detected Rooms & Compartments ({{ count($floorItem['rooms']) }})
                                                    </span>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        @foreach($floorItem['rooms'] as $room)
                                                            <div
                                                                class="px-3 py-2 rounded-xl bg-white border border-slate-200 flex items-center justify-between text-xs">
                                                                <span
                                                                    class="text-slate-700 truncate pr-2 font-medium">{{ $room['name'] ?? 'Room' }}</span>
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
                        <div
                            class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <svg class="w-4 h-4 text-[#046938]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span>Verified by NBRB Automated Statutory Area Measurement</span>
                            </div>
                            <button type="button" id="closeFloorAreaModalBtn" onclick="closeFloorAreaModal()"
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

        <!-- Discrepancies Discipline Filter Controller -->
        <script>
            const selectedDisciplines = new Set();

            function toggleDisciplineFilter(discipline) {
                if (selectedDisciplines.has(discipline)) {
                    selectedDisciplines.delete(discipline);
                } else {
                    selectedDisciplines.add(discipline);
                }
                applyDisciplineFilter();
            }

            function resetDisciplineFilter() {
                selectedDisciplines.clear();
                applyDisciplineFilter();
            }

            function toggleDiscDropdown() {
                const menu = document.getElementById('discDropdownMenu');
                if (menu) {
                    menu.classList.toggle('hidden');
                }
            }

            document.addEventListener('click', function (e) {
                const container = document.getElementById('discDropdownContainer');
                const menu = document.getElementById('discDropdownMenu');
                if (container && menu && !container.contains(e.target)) {
                    menu.classList.add('hidden');
                }
            });

            function applyDisciplineFilter() {
                const isAll = selectedDisciplines.size === 0;
                const allBtn = document.getElementById('discFilterAllBtn');
                const filterBtns = document.querySelectorAll('.disc-filter-btn');
                const checkboxes = document.querySelectorAll('[data-discipline-checkbox]');
                const rows = document.querySelectorAll('.discrepancy-row');
                const emptyFilterRow = document.getElementById('discrepancyFilterEmptyRow');
                const visibleBadge = document.getElementById('discrepancyVisibleBadge');
                const dropdownLabel = document.getElementById('discDropdownLabel');

                // Update "All Disciplines" button state
                if (allBtn) {
                    if (isAll) {
                        allBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border bg-[#046938] text-white border-[#046938] shadow-xs';
                    } else {
                        allBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border bg-white text-slate-600 border-slate-200 hover:border-[#046938]/50 hover:text-[#046938]';
                    }
                }

                // Update per-discipline toggle buttons
                filterBtns.forEach(btn => {
                    const disc = btn.getAttribute('data-discipline-btn');
                    const active = selectedDisciplines.has(disc);
                    if (active) {
                        btn.className = 'disc-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border bg-[#046938] text-white border-[#046938] shadow-xs inline-flex items-center gap-1.5';
                    } else {
                        btn.className = 'disc-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border bg-white text-slate-600 border-slate-200 hover:border-[#046938]/50 hover:text-[#046938] inline-flex items-center gap-1.5';
                    }
                });

                // Sync multi-select dropdown checkboxes
                checkboxes.forEach(cb => {
                    const disc = cb.getAttribute('data-discipline-checkbox');
                    cb.checked = selectedDisciplines.has(disc);
                });

                if (dropdownLabel) {
                    dropdownLabel.textContent = isAll ? 'Multi-Select' : `${selectedDisciplines.size} Selected`;
                }

                // Filter table rows
                let visibleCount = 0;
                rows.forEach(row => {
                    const rowDisc = row.getAttribute('data-discipline');
                    const show = isAll || selectedDisciplines.has(rowDisc);
                    row.classList.toggle('hidden', !show);
                    if (show) {
                        visibleCount++;
                    }
                });

                if (emptyFilterRow) {
                    emptyFilterRow.classList.toggle('hidden', rows.length === 0 || visibleCount > 0);
                }

                if (visibleBadge) {
                    visibleBadge.textContent = isAll
                        ? `${rows.length} ${rows.length === 1 ? 'item' : 'items'}`
                        : `Showing ${visibleCount} of ${rows.length}`;
                }
            }
        </script>
    </div>
@endsection
