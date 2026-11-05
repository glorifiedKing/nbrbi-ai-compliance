@extends('layouts.admin')

@section('title', 'Projects & Blocks Management')

@section('content')
    <div class="space-y-8 pt-2">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Multi-Block Projects & Compliance Workspace</h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">Create building projects, configure blocks and floor schedules, upload engineering drawings by discipline, and generate consolidated statutory compliance reports.</p>
            </div>
        </div>

        <!-- Create New Project Card -->
        <div class="p-4 sm:p-6 lg:p-8 rounded-2xl glass-panel relative overflow-hidden">
            <div class="absolute -right-12 -top-12 w-64 h-64 bg-[#046938]/5 rounded-full blur-3xl pointer-events-none"></div>

            <div class="mb-5">
                <h2 class="text-base sm:text-lg font-extrabold text-slate-900">Register New Building Project</h2>
                <p class="text-xs text-slate-500 mt-0.5">A default "Main Block" with Ground Floor details will be created automatically so you can immediately upload drawings or add more blocks.</p>
            </div>

            <form method="POST" action="{{ route('admin.projects.store') }}" class="space-y-5 relative z-10">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                    <!-- Project Name -->
                    <div>
                        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Project Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            placeholder="e.g., Kampala Heights Commercial Tower"
                            class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-[#046938] focus:ring-1 focus:ring-[#046938] shadow-sm">
                        @error('name')
                            <p class="text-xs text-red-500 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reference Number -->
                    <div>
                        <label for="reference_number" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Reference Number <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number') }}"
                            placeholder="Auto-generated if left blank"
                            class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-800 font-mono focus:outline-none focus:border-[#046938] focus:ring-1 focus:ring-[#046938] shadow-sm">
                        @error('reference_number')
                            <p class="text-xs text-red-500 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Occupancy Class -->
                    <div>
                        <label for="occupancy_class" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Building Class (Uganda Building Control Act)
                        </label>
                        <select id="occupancy_class" name="occupancy_class"
                            class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-[#046938] focus:ring-1 focus:ring-[#046938] shadow-sm">
                            <option value="Class A (Complex & Public Buildings)">
                                Class A: Complex & Public Buildings (UGX 4,000/m²)
                            </option>
                            <option value="Class B (Residential & Commercial)" selected>
                                Class B: Standard Residential & Commercial (UGX 2,500/m²)
                            </option>
                            <option value="Class C (Minor & Temporary Buildings)">
                                Class C: Minor & Temporary Buildings (UGX 1,500/m²)
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-1">
                    <button type="submit"
                        class="px-6 py-3 rounded-xl bg-gradient-to-r from-[#046938] to-[#03522c] hover:from-[#03522c] hover:to-[#023b20] text-white text-sm font-bold transition-all shadow-md shadow-[#046938]/20 inline-flex items-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Create Project & Configure Blocks</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Registered Projects List -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900">Registered Building Projects</h3>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                    Total: {{ $projects->total() }}
                </span>
            </div>

            <div class="rounded-2xl glass-panel overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4">Project & Reference</th>
                                <th class="px-6 py-4">Building Class</th>
                                <th class="px-6 py-4">Blocks & Drawings</th>
                                <th class="px-6 py-4">Total Floor Area & Fee</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Created</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($projects as $project)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('admin.projects.show', $project) }}" class="font-bold text-slate-900 hover:text-[#046938] transition-colors">
                                            {{ $project->name }}
                                        </a>
                                        <div class="text-xs text-slate-500 font-mono mt-0.5">
                                            Ref: {{ $project->reference_number }} • Owner: {{ $project->owner?->name ?? 'Admin' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-medium text-slate-700">
                                        {{ $project->occupancy_class }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 font-bold border border-blue-200">
                                                {{ $project->blocks_count }} {{ Str::plural('Block', $project->blocks_count) }}
                                            </span>
                                            <span class="px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 font-bold border border-purple-200">
                                                {{ $project->drawings_count }} {{ Str::plural('Drawing', $project->drawings_count) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($project->total_floor_area_sqm > 0)
                                            <div class="font-bold text-[#046938] text-xs">
                                                {{ number_format($project->total_floor_area_sqm, 2) }} m²
                                            </div>
                                            <div class="text-[11px] text-slate-500 font-mono">
                                                UGX {{ number_format($project->estimated_statutory_fee_ugx, 0) }}
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">Pending Arch Audit</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @php $st = strtolower((string) $project->status); @endphp
                                        @if($st === 'compliant')
                                            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">COMPLIANT</span>
                                        @elseif($st === 'non_compliant')
                                            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-800 border border-rose-200">NON-COMPLIANT</span>
                                        @elseif($st === 'in_progress')
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">IN PROGRESS</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">{{ strtoupper($st ?: 'DRAFT') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500">
                                        {{ $project->created_at?->diffForHumans() }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.projects.show', $project) }}"
                                                class="px-3 py-1.5 rounded-lg bg-[#046938] hover:bg-[#03522c] text-white text-xs font-bold transition-colors">
                                                Blocks & Drawings
                                            </a>
                                            <a href="{{ route('admin.projects.report', $project) }}"
                                                class="px-3 py-1.5 rounded-lg bg-[#DEA74F]/15 hover:bg-[#DEA74F]/25 text-[#9a6a1e] border border-[#DEA74F]/30 text-xs font-bold transition-colors">
                                                Audit Report →
                                            </a>
                                            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}"
                                                onsubmit="return confirm('Delete project {{ addslashes($project->name) }} and all its blocks and drawings?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-1.5 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition-colors border border-slate-200 cursor-pointer"
                                                    title="Delete Project">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                        No building projects registered yet. Use the form above to create a project and manage its blocks and drawings.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($projects->hasPages())
                    <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                        {{ $projects->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

