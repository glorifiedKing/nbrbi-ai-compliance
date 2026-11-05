@extends('layouts.admin')

@section('title', $project->name . ' - Blocks & Drawings Workspace')

@section('content')
    @if($hasActiveJobs)
        <meta http-equiv="refresh" content="4">
    @endif

    <div class="space-y-8 pt-2">
        <!-- Breadcrumb & Header -->
        <div>
            <a href="{{ route('admin.projects.index') }}"
                class="text-xs font-semibold text-[#046938] hover:underline inline-flex items-center gap-1 mb-2">
                ← Back to Projects List
            </a>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">{{ $project->name }}</h1>
                        <span class="px-2.5 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-xs font-mono font-bold text-slate-700">
                            {{ $project->reference_number }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1">
                        {{ $project->occupancy_class }} •
                        Total Area: <span class="font-bold text-[#046938]">{{ number_format((float) $project->total_floor_area_sqm, 2) }} m²</span> •
                        Estimated Fee: <span class="font-bold text-[#9a6a1e]">UGX {{ number_format((float) $project->estimated_statutory_fee_ugx, 0) }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.projects.report', $project) }}"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#046938] to-[#03522c] hover:from-[#03522c] hover:to-[#023b20] text-white text-xs sm:text-sm font-bold transition-all shadow-md shadow-[#046938]/20 inline-flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>View Project Compliance Report →</span>
                    </a>
                </div>
            </div>
        </div>

        @if($hasActiveJobs)
            <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 flex items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center animate-spin shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold">AI Compliance Analysis in Progress</h4>
                        <p class="text-xs text-blue-700">One or more uploaded block drawings are currently being analyzed by FastAPI + Gemini. This page auto-refreshes.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Top Grid: Edit Project Settings & Add New Block -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Edit Project Details -->
            <div class="p-5 sm:p-6 rounded-2xl glass-panel space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Project Settings</h3>
                    <p class="text-xs text-slate-500">Update project name, building occupancy classification, or review status.</p>
                </div>

                <form method="POST" action="{{ route('admin.projects.update', $project) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Project Name</label>
                        <input type="text" name="name" value="{{ old('name', $project->name) }}" required
                            class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-[#046938]">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Building Class</label>
                            <select name="occupancy_class"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-[#046938]">
                                @foreach(['Class A (Complex & Public Buildings)', 'Class B (Residential & Commercial)', 'Class C (Minor & Temporary Buildings)'] as $cls)
                                    <option value="{{ $cls }}" {{ $project->occupancy_class === $cls ? 'selected' : '' }}>
                                        {{ $cls }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Project Status</label>
                            <select name="status"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-[#046938]">
                                @foreach(['draft' => 'Draft', 'in_progress' => 'In Progress', 'compliant' => 'Compliant', 'non_compliant' => 'Non-Compliant'] as $val => $label)
                                    <option value="{{ $val }}" {{ $project->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit"
                            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors cursor-pointer">
                            Save Project Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Add New Block -->
            <div class="p-5 sm:p-6 rounded-2xl glass-panel space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Add Building Block</h3>
                    <p class="text-xs text-slate-500">Add a block (e.g., Block B, Annex Wing, Parking Structure) with initial floor details stored in JSONB.</p>
                </div>

                <form method="POST" action="{{ route('admin.projects.blocks.store', $project) }}" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Block Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required placeholder="e.g., Block B / East Wing"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-[#046938]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Description</label>
                            <input type="text" name="description" placeholder="e.g., 3-Storey Commercial Wing"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-[#046938]">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Initial Floor Details (JSONB)</label>
                            <button type="button" onclick="addFloorRow('new-block-floors')"
                                class="text-xs font-bold text-[#046938] hover:underline cursor-pointer">
                                + Add Floor Row
                            </button>
                        </div>
                        <div id="new-block-floors" class="space-y-2">
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <input type="number" name="floors[0][floor_number]" value="0" placeholder="No."
                                    class="col-span-3 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
                                <input type="text" name="floors[0][floor_name]" value="Ground Floor" placeholder="Floor Name"
                                    class="col-span-5 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
                                <input type="number" step="0.01" name="floors[0][gross_floor_area_sqm]" value="0" placeholder="Area (m²)"
                                    class="col-span-4 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400">Note: Uploading an Architectural PDF will automatically extract and update floor details from the drawing sheets.</p>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit"
                            class="px-4 py-2 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-xs font-bold transition-colors cursor-pointer">
                            + Add Block to Project
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Blocks & Engineering Drawings Section -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Project Blocks & Engineering Drawings ({{ $project->blocks->count() }})</h2>
                    <p class="text-xs text-slate-500">Upload Architectural, Structural, Mechanical, or Electrical PDFs per block to trigger automated compliance audits.</p>
                </div>
            </div>

            @forelse($project->blocks as $block)
                <div class="rounded-2xl glass-panel overflow-hidden border border-slate-200/90">
                    <!-- Block Header -->
                    <div class="p-4 sm:p-6 bg-slate-50/80 border-b border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="px-2.5 py-1 rounded-lg bg-[#046938]/10 text-[#046938] border border-[#046938]/20 text-xs font-extrabold uppercase">
                                    Block
                                </span>
                                <h3 class="text-base sm:text-lg font-extrabold text-slate-900">{{ $block->name }}</h3>
                                @if($block->description)
                                    <span class="text-xs text-slate-500">• {{ $block->description }}</span>
                                @endif
                            </div>

                            <!-- Floor JSONB Pills -->
                            <div class="flex flex-wrap items-center gap-2 mt-2.5">
                                <span class="text-xs font-bold text-slate-600">
                                    Block GFA: <span class="text-[#046938]">{{ number_format((float) $block->total_floor_area_sqm, 2) }} m²</span>
                                </span>
                                <span class="text-slate-300">|</span>
                                @php $blockFloors = is_array($block->floors) ? $block->floors : []; @endphp
                                @forelse($blockFloors as $fl)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white border border-slate-200 text-slate-700">
                                        <span>{{ $fl['floor_name'] ?? $fl['name'] ?? ('Floor ' . ($fl['floor_number'] ?? 0)) }}</span>
                                        @if(!empty($fl['gross_floor_area_sqm']) && (float) $fl['gross_floor_area_sqm'] > 0)
                                            <span class="text-[#046938] font-mono font-bold">({{ number_format((float) $fl['gross_floor_area_sqm'], 1) }} m²)</span>
                                        @endif
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400 italic">No floors recorded yet</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" onclick="toggleBlockEdit('edit-block-{{ $block->id }}')"
                                class="px-3 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold transition-colors cursor-pointer">
                                Edit Block / Floors
                            </button>
                            @if($project->blocks->count() > 1)
                                <form method="POST" action="{{ route('admin.projects.blocks.destroy', [$project, $block]) }}"
                                    onsubmit="return confirm('Delete block {{ addslashes($block->name) }} and its drawings?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-3 py-1.5 rounded-lg bg-white hover:bg-rose-50 text-slate-500 hover:text-rose-600 border border-slate-200 text-xs font-bold transition-colors cursor-pointer">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Collapsible Edit Block & Floors JSONB Form -->
                    <div id="edit-block-{{ $block->id }}" class="hidden p-4 sm:p-6 bg-amber-50/30 border-b border-slate-200">
                        <form method="POST" action="{{ route('admin.projects.blocks.update', [$project, $block]) }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Block Name</label>
                                    <input type="text" name="name" value="{{ $block->name }}" required
                                        class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-800">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Description</label>
                                    <input type="text" name="description" value="{{ $block->description }}"
                                        class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-800">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Floor Schedule Details (JSONB)</label>
                                    <button type="button" onclick="addFloorRow('block-floors-{{ $block->id }}')"
                                        class="text-xs font-bold text-[#046938] hover:underline cursor-pointer">
                                        + Add Floor Row
                                    </button>
                                </div>

                                <div id="block-floors-{{ $block->id }}" class="space-y-2">
                                    @php $editFloors = !empty($blockFloors) ? array_values($blockFloors) : [['floor_number' => 0, 'floor_name' => 'Ground Floor', 'gross_floor_area_sqm' => 0]]; @endphp
                                    @foreach($editFloors as $idx => $fl)
                                        <div class="grid grid-cols-12 gap-2 items-center">
                                            <input type="number" name="floors[{{ $idx }}][floor_number]" value="{{ $fl['floor_number'] ?? $idx }}" placeholder="Floor #"
                                                class="col-span-3 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
                                            <input type="text" name="floors[{{ $idx }}][floor_name]" value="{{ $fl['floor_name'] ?? $fl['name'] ?? '' }}" placeholder="Floor Name"
                                                class="col-span-5 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
                                            <input type="number" step="0.01" name="floors[{{ $idx }}][gross_floor_area_sqm]" value="{{ $fl['gross_floor_area_sqm'] ?? 0 }}" placeholder="Area (m²)"
                                                class="col-span-4 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <button type="button" onclick="toggleBlockEdit('edit-block-{{ $block->id }}')"
                                    class="px-3.5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-xs font-bold cursor-pointer">
                                    Update Block & Recalculate Fees
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Block Body: Upload Drawing + Discipline Cards -->
                    <div class="p-4 sm:p-6 space-y-6">
                        <!-- Upload Engineering Drawing Form for this Block -->
                        <form method="POST" action="{{ route('admin.projects.drawings.upload', [$project, $block]) }}"
                            enctype="multipart/form-data"
                            class="p-4 rounded-xl bg-slate-50 border border-slate-200/90 flex flex-col md:flex-row items-stretch md:items-end gap-4">
                            @csrf
                            <div class="w-full md:w-64 shrink-0">
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Discipline <span class="text-red-500">*</span>
                                </label>
                                <select name="discipline" required
                                    class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-[#046938]">
                                    <option value="architectural">Architectural (All Floors PDF)</option>
                                    <option value="structural">Structural Engineering</option>
                                    <option value="mechanical">Mechanical & Plumbing</option>
                                    <option value="electrical">Electrical Installation</option>
                                </select>
                            </div>

                            <div class="flex-1 min-w-0">
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Engineering Drawing PDF (Max 50MB) <span class="text-red-500">*</span>
                                </label>
                                <input type="file" name="drawing_file" accept=".pdf" required
                                    class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs sm:text-sm text-slate-700 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#046938]/10 file:text-[#046938] hover:file:bg-[#046938]/20">
                            </div>

                            <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-xs sm:text-sm font-bold transition-all shadow-sm inline-flex items-center justify-center gap-2 shrink-0 cursor-pointer">
                                <svg class="w-4 h-4 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span>Upload & Run Audit</span>
                            </button>
                        </form>

                        <!-- 4 Discipline Cards -->
                        @php
                            $drawingsByDiscipline = $block->drawings->keyBy(fn ($d) => strtolower($d->discipline));
                            $disciplines = [
                                'architectural' => 'Architectural Plans',
                                'structural' => 'Structural Plans',
                                'mechanical' => 'Mechanical & HVAC',
                                'electrical' => 'Electrical Systems',
                            ];
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            @foreach($disciplines as $discKey => $discLabel)
                                @php
                                    $drawing = $drawingsByDiscipline->get($discKey);
                                    $latestVersion = $drawing?->currentVersion ?? $drawing?->versions?->first();
                                    $result = $latestVersion?->complianceResult;
                                    $verdict = $result?->overall_status;
                                @endphp
                                <div class="p-4 rounded-xl bg-white border border-slate-200 flex flex-col justify-between gap-3">
                                    <div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-800">{{ $discLabel }}</span>
                                            @if($latestVersion)
                                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[11px] font-bold">
                                                    v{{ $latestVersion->version_number }}
                                                </span>
                                            @endif
                                        </div>

                                        @if($latestVersion)
                                            <div class="mt-3 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[11px] text-slate-500">Status:</span>
                                                    @if(in_array($latestVersion->status, ['pending', 'analyzing']))
                                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200 animate-pulse">
                                                            {{ ucfirst($latestVersion->status) }}...
                                                        </span>
                                                    @elseif($verdict === 'PASS')
                                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                            PASS
                                                        </span>
                                                    @elseif($verdict === 'WARNING')
                                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-50 text-amber-800 border border-amber-200">
                                                            WARNING
                                                        </span>
                                                    @elseif(in_array($verdict, ['FAIL', 'REJECTED_PREFLIGHT']) || $latestVersion->status === 'failed')
                                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-50 text-rose-800 border border-rose-200">
                                                            {{ $verdict ?? 'FAILED' }}
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                                            {{ strtoupper($latestVersion->status) }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if($result)
                                                    <div class="flex items-center gap-2 text-[11px]">
                                                        <span class="text-emerald-700 font-bold">{{ $result->summary_metrics['pass_count'] ?? 0 }} Pass</span>
                                                        <span>•</span>
                                                        <span class="text-amber-700 font-bold">{{ $result->summary_metrics['warning_count'] ?? 0 }} Warn</span>
                                                        <span>•</span>
                                                        <span class="text-rose-700 font-bold">{{ $result->summary_metrics['fail_count'] ?? 0 }} Fail</span>
                                                    </div>
                                                @endif

                                                @if($latestVersion->error_message)
                                                    <p class="text-[11px] text-rose-600 bg-rose-50 p-2 rounded-lg border border-rose-100">
                                                        {{ $latestVersion->error_message }}
                                                    </p>
                                                @endif
                                            </div>
                                        @else
                                            <p class="text-xs text-slate-400 mt-3 italic">No {{ $discKey }} drawing uploaded yet.</p>
                                        @endif
                                    </div>

                                    @if($latestVersion)
                                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                            <span>{{ $latestVersion->created_at?->diffForHumans() }}</span>
                                            <a href="{{ route('admin.projects.report', $project) }}" class="font-bold text-[#046938] hover:underline">
                                                Details →
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 rounded-2xl glass-panel text-center text-slate-500">
                    No blocks found for this project. Use the "Add Building Block" form above to add one.
                </div>
            @endforelse
        </div>
    </div>

    <script>
        function toggleBlockEdit(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.toggle('hidden');
            }
        }

        function addFloorRow(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const index = container.children.length;
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-2 items-center';
            row.innerHTML = `
                <input type="number" name="floors[${index}][floor_number]" value="${index}" placeholder="Floor #"
                    class="col-span-3 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
                <input type="text" name="floors[${index}][floor_name]" value="Floor ${index}" placeholder="Floor Name"
                    class="col-span-5 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
                <input type="number" step="0.01" name="floors[${index}][gross_floor_area_sqm]" value="0" placeholder="Area (m²)"
                    class="col-span-4 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
            `;
            container.appendChild(row);
        }
    </script>
@endsection

