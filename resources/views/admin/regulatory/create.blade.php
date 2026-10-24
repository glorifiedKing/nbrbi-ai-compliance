@extends('layouts.admin')

@section('title', 'Upload Statutory Standard')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6 pt-4">
        <!-- Breadcrumb & Title -->
        <div>
            <a href="{{ route('admin.regulatory.index') }}"
                class="text-xs font-semibold text-[#046938] hover:underline inline-flex items-center gap-1 mb-2">
                ← Back to Statutory Standards
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Upload Building Standard PDF</h1>
            <p class="text-sm text-slate-600 mt-1">Upload official Uganda National Building Code volumes, statutory
                instruments, or circulars for AI vector indexing.</p>
        </div>

        <!-- Upload Form Card -->
        <div class="p-8 rounded-2xl glass-panel space-y-6">
            <form method="POST" action="{{ route('admin.regulatory.store') }}" enctype="multipart/form-data"
                class="space-y-6">
                @csrf

                <!-- Document Title -->
                <div>
                    <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Standard / Act Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required
                        placeholder="e.g. The Building Control Regulations, 2020"
                        class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#046938] focus:ring-1 focus:ring-[#046938] @error('title') border-red-500 @enderror">
                    @error('title')
                        <p class="text-xs text-red-500 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Grid: Category & Edition Year -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="category" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Engineering Discipline <span class="text-red-500">*</span>
                        </label>
                        <select id="category" name="category" required
                            class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-[#046938] focus:ring-1 focus:ring-[#046938]">
                            <option value="architectural" {{ old('category') === 'architectural' ? 'selected' : '' }}>
                                Architectural (Light, Egress, Room Sizes)</option>
                            <option value="structural" {{ old('category') === 'structural' ? 'selected' : '' }}>Structural
                                (Columns, Loads, Beams)</option>
                            <option value="mechanical" {{ old('category') === 'mechanical' ? 'selected' : '' }}>Mechanical &
                                Plumbing (HVAC,
                                Drainage)</option>
                            <option value="electrical" {{ old('category') === 'electrical' ? 'selected' : '' }}>Electrical
                            </option>
                            <option value="general_building_control" {{ old('category') === 'general_building_control' ? 'selected' : '' }}>General Building Control / Statutory Act</option>
                        </select>
                    </div>

                    <div>
                        <label for="edition_year"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Edition / Enactment Year <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="edition_year" name="edition_year" value="{{ old('edition_year', '2020') }}"
                            maxlength="4" required placeholder="2020"
                            class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-800 font-mono focus:outline-none focus:border-[#046938] focus:ring-1 focus:ring-[#046938] @error('edition_year') border-red-500 @enderror">
                        @error('edition_year')
                            <p class="text-xs text-red-500 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Classification applicability -->
                <div>
                    <label for="classification"
                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Occupancy Scope / Classification (Optional)
                    </label>
                    <input type="text" id="classification" name="classification" value="{{ old('classification') }}"
                        placeholder="e.g. Category A (Residential) & Category B (Commercial)"
                        class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#046938] focus:ring-1 focus:ring-[#046938]">
                    <p class="text-[11px] text-slate-500 mt-1">Leave blank if this code applies universally to all building
                        categories.</p>
                </div>

                <!-- PDF File Upload Box -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Statutory PDF Document <span class="text-red-500">*</span>
                    </label>
                    <div
                        class="border-2 border-dashed border-slate-300 hover:border-[#046938] rounded-2xl p-8 text-center bg-slate-50/70 hover:bg-[#046938]/5 transition-all cursor-pointer relative group">
                        <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="updateFileName(this)">
                        <div class="space-y-3 pointer-events-none">
                            <div
                                class="w-12 h-12 mx-auto rounded-full bg-[#046938]/10 text-[#046938] flex items-center justify-center border border-[#046938]/20 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800" id="file-label-title">Click to upload or
                                    drag and drop statutory PDF</p>
                                <p class="text-xs text-slate-500 mt-0.5">Maximum file size: 100MB • Text & Vector PDF
                                    supported</p>
                            </div>
                        </div>
                    </div>
                    @error('pdf_file')
                        <p class="text-xs text-red-500 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <a href="{{ route('admin.regulatory.index') }}"
                        class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold border border-slate-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-[#046938] to-[#03522c] hover:from-[#03522c] hover:to-[#023b20] text-white text-sm font-bold transition-all shadow-md shadow-[#046938]/20">
                        Upload & Ingest Standard
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('file-label-title').textContent = input.files[0].name + ' (' + (input.files[0].size / (1024 * 1024)).toFixed(2) + ' MB)';
            }
        }
    </script>
@endsection