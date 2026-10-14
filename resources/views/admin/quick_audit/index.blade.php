@extends('layouts.admin')

@section('title', 'Quick Compliance Audit')

@section('content')
<div class="space-y-8 pt-2">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Instant Single Drawing Compliance Audit</h1>
            <p class="text-sm text-slate-600 mt-1">Quickly benchmark a single 2D/vector blueprint against Uganda Building Regulations without creating a full project hierarchy.</p>
        </div>
    </div>

    <!-- Quick Upload Form Card -->
    <div class="p-8 rounded-2xl glass-panel relative overflow-hidden">
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-[#046938]/5 rounded-full blur-3xl pointer-events-none"></div>

        <form method="POST" action="{{ route('admin.quick-audit.store') }}" enctype="multipart/form-data" class="space-y-6 relative z-10">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Engineering Discipline -->
                <div>
                    <label for="discipline" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Engineering Discipline <span class="text-red-500">*</span>
                    </label>
                    <select id="discipline" name="discipline" required
                            class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-[#046938] focus:ring-1 focus:ring-[#046938] shadow-sm">
                        <option value="architectural">Architectural (Floor Plans, Egress, Ventilation, Room Sizes)</option>
                        <option value="structural">Structural (Columns, Beams, Reinforcement Schedules)</option>
                        <option value="mechanical">Mechanical & Plumbing (HVAC, Drainage, Pipe Sizing)</option>
                        <option value="electrical">Electrical (Power Distribution, Emergency Lighting)</option>
                    </select>
                </div>

                <!-- Building Occupancy Class -->
                <div>
                    <label for="occupancy_class" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Building Occupancy Class
                    </label>
                    <select id="occupancy_class" name="occupancy_class"
                            class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-[#046938] focus:ring-1 focus:ring-[#046938] shadow-sm">
                        <option value="Class A (Residential)">Class A (Residential Dwellings & Apartments)</option>
                        <option value="Class B (Commercial & Offices)">Class B (Commercial, Retail, Offices & Banks)</option>
                        <option value="Class C (Institutional & Public)">Class C (Schools, Hospitals, Public Assemblies)</option>
                        <option value="Class D (Industrial & Storage)">Class D (Factories, Warehouses, Workshops)</option>
                    </select>
                </div>
            </div>

            <!-- PDF Upload Box -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    Upload Drawing PDF Document <span class="text-red-500">*</span>
                </label>
                <div class="border-2 border-dashed border-slate-300 hover:border-[#046938] rounded-2xl p-8 text-center bg-slate-50/70 hover:bg-[#046938]/5 transition-all cursor-pointer relative group">
                    <input type="file" id="drawing_file" name="drawing_file" accept=".pdf" required
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                           onchange="updateQuickFileName(this)">
                    <div class="space-y-3 pointer-events-none">
                        <div class="w-12 h-12 mx-auto rounded-full bg-[#046938]/10 text-[#046938] flex items-center justify-center border border-[#046938]/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800" id="quick-file-title">Drop vector/CAD drawing PDF here or click to browse</p>
                            <p class="text-xs text-slate-500 mt-0.5">Supports 2D Architectural, Structural, Mechanical or Electrical blueprints up to 50MB</p>
                        </div>
                    </div>
                </div>
                @error('drawing_file')
                    <p class="text-xs text-red-500 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end pt-2">
                <button type="submit" 
                        class="px-7 py-3 rounded-xl bg-gradient-to-r from-[#046938] to-[#03522c] hover:from-[#03522c] hover:to-[#023b20] text-white text-sm font-bold transition-all shadow-md shadow-[#046938]/20 inline-flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Run Quick Compliance Audit</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Quick Audits List -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Recent Quick Audits</h3>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200">Total: {{ $audits->total() }}</span>
        </div>

        <div class="rounded-2xl glass-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Drawing File</th>
                            <th class="px-6 py-4">Discipline</th>
                            <th class="px-6 py-4">Occupancy</th>
                            <th class="px-6 py-4">Floor Area & Fee</th>
                            <th class="px-6 py-4">Audit Status</th>
                            <th class="px-6 py-4">Compliance Verdict</th>
                            <th class="px-6 py-4">Submitted At</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($audits as $audit)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900">{{ $audit->original_name ?? basename($audit->file_name) }}</div>
                                    <div class="text-xs text-slate-500 font-mono">ID: {{ substr($audit->id, 0, 8) }}...</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-mono uppercase font-semibold border border-slate-200">
                                        {{ $audit->discipline }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-medium text-slate-700">
                                    {{ $audit->occupancy_class }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($audit->gross_floor_area_sqm)
                                        <div class="font-bold text-[#046938] text-xs">{{ number_format($audit->gross_floor_area_sqm, 1) }} m²</div>
                                        <div class="text-[11px] text-slate-500 font-mono">UGX {{ number_format($audit->estimated_statutory_fee_ugx, 0) }}</div>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($audit->status === 'completed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            Completed
                                        </span>
                                    @elseif($audit->status === 'analyzing')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200 animate-pulse">
                                            Analyzing
                                        </span>
                                    @elseif($audit->status === 'failed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                                            Failed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php $verdict = $audit->overall_status; @endphp
                                    @if($verdict === 'PASS')
                                        <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">PASS</span>
                                    @elseif($verdict === 'WARNING')
                                        <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-800 border border-amber-200">WARNING</span>
                                    @elseif(in_array($verdict, ['FAIL', 'REJECTED_PREFLIGHT']))
                                        <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-800 border border-rose-200">{{ $verdict }}</span>
                                    @else
                                        <span class="text-xs text-slate-500 font-mono">In Review</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $audit->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.quick-audit.show', $audit) }}" 
                                           class="px-3 py-1.5 rounded-lg bg-[#046938]/10 hover:bg-[#046938]/20 text-[#046938] border border-[#046938]/20 text-xs font-bold transition-colors">
                                            View Report →
                                        </a>

                                        <form method="POST" action="{{ route('admin.quick-audit.destroy', $audit) }}" onsubmit="return confirm('Delete this quick audit record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition-colors border border-slate-200" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                    No standalone audits run yet. Upload an architectural or structural drawing above for instant verification.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($audits->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                    {{ $audits->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function updateQuickFileName(input) {
        if (input.files && input.files[0]) {
            document.getElementById('quick-file-title').textContent = input.files[0].name + ' (' + (input.files[0].size / (1024 * 1024)).toFixed(2) + ' MB)';
        }
    }
</script>
@endsection
