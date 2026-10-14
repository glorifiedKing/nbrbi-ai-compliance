@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="space-y-8">
        <!-- Header banner -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pt-2">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">AI Compliance & Regulatory Control</h1>
                <p class="text-sm text-slate-500 mt-1 font-medium">National Building Review Board • Automated Plan Checking
                    Engine</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.regulatory.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-sm font-bold transition-all shadow-sm hover:shadow">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Upload Statutory Standard</span>
                </a>
            </div>
        </div>

        <!-- Microservice Status Banner -->
        <div
            class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex items-center justify-between border-l-4 {{ $fastApiHealthy ? 'border-l-[#046938]' : 'border-l-[#DEA74F]' }}">
            <div class="flex items-center gap-3.5">
                <div class="w-3 h-3 rounded-full {{ $fastApiHealthy ? 'bg-[#046938] animate-ping' : 'bg-[#DEA74F]' }}">
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-800">
                        Python AI Microservice:
                        <span class="{{ $fastApiHealthy ? 'text-[#046938]' : 'text-[#DEA74F] font-black' }}">
                            {{ $fastApiHealthy ? 'Connected & Ready (FastAPI + Celery)' : 'Microservice Offline / Standby' }}
                        </span>
                    </h4>
                    <p class="text-xs text-slate-500">vector extraction + Gemini multimodal auditor</p>
                </div>
            </div>
            <span
                class="text-xs font-mono font-medium text-slate-600 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                {{ config('services.fastapi.base_url') }}
            </span>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Card 1 -->
            <div
                class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Statutory Standards</span>
                    <span class="p-2.5 rounded-xl bg-[#046938]/10 text-[#046938] border border-[#046938]/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-black text-slate-900 tracking-tight">{{ $totalDocuments }}</div>
                    <p class="text-xs text-slate-500 mt-1 font-medium"><span
                            class="text-[#046938] font-bold">{{ $totalChunks }}</span> vectorized clauses in pgvector</p>
                </div>
            </div>

            <!-- Card 2 -->
            <div
                class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Projects Registered</span>
                    <span class="p-2.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-black text-slate-900 tracking-tight">{{ $totalProjects }}</div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Multi-block Ugandan estate submissions</p>
                </div>
            </div>

            <!-- Card 3 -->
            <div
                class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Drawings Audited</span>
                    <span class="p-2.5 rounded-xl bg-purple-50 text-purple-700 border border-purple-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-black text-slate-900 tracking-tight">{{ $totalVersions }}</div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Arch, Struct, Mech & Elec versions</p>
                </div>
            </div>

            <!-- Card 4 -->
            <div
                class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Compliance Pass Rate</span>
                    <span class="p-2.5 rounded-xl bg-[#DEA74F]/15 text-[#9a6a1e] border border-[#DEA74F]/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-black text-slate-900 tracking-tight">{{ $complianceRate }}%</div>
                    <p class="text-xs text-slate-500 mt-1 font-medium"><span
                            class="text-[#046938] font-bold">{{ $passCount }} Pass</span> • <span
                            class="text-red-600 font-bold">{{ $failCount }} Non-compliant</span></p>
                </div>
            </div>
        </div>

        <!-- Architectural Floor Area & Statutory Fee Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div
                class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm border-l-4 border-l-[#046938] flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#046938] flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        Total Audited Gross Floor Area (GFA)
                    </span>
                    <div class="text-3xl font-black text-slate-900 tracking-tight mt-2">
                        {{ number_format($totalAuditedFloorAreaSqm, 2) }} <span
                            class="text-lg font-bold text-[#046938]">m²</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Extracted from architectural floor plans across projects & quick
                        audits</p>
                </div>
                <div
                    class="w-12 h-12 rounded-2xl bg-[#046938]/10 border border-[#046938]/20 flex items-center justify-center text-[#046938]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>

            <div
                class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm border-l-4 border-l-[#DEA74F] flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#9a6a1e] flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Total Statutory Plan Examination Fees
                    </span>
                    <div class="text-3xl font-black text-slate-900 tracking-tight mt-2">
                        <span class="text-lg font-bold text-[#DEA74F]">UGX</span>
                        {{ number_format($totalEstimatedFeesUgx, 0) }}
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Computed per m² under Uganda Building Control Regulations fee
                        schedules</p>
                </div>
                <div
                    class="w-12 h-12 rounded-2xl bg-[#DEA74F]/15 border border-[#DEA74F]/30 flex items-center justify-center text-[#9a6a1e]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Dual column content -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left: Regulatory Standards Corpus -->
            <div class="p-6 rounded-2xl bg-white border border-slate-200/90 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Official Statutory Corpus (RAG)</h3>
                        <p class="text-xs text-slate-500">Uganda National Building Code & Regulations</p>
                    </div>
                    <a href="{{ route('admin.regulatory.index') }}"
                        class="text-xs text-[#046938] hover:underline font-bold">
                        View All ({{ $totalDocuments }}) →
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($recentDocuments as $doc)
                        <div class="py-3.5 flex items-center justify-between">
                            <div class="min-w-0 pr-4">
                                <h4 class="text-sm font-bold text-slate-800 truncate">{{ $doc->title }}</h4>
                                <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                                    <span
                                        class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[10px] uppercase font-bold">{{ $doc->category }}</span>
                                    <span>Ed. {{ $doc->edition_year }}</span>
                                    <span>•</span>
                                    <span>{{ $doc->chunks_count ?? $doc->total_chunks }} clauses</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if($doc->status === 'indexed')
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#046938] border border-emerald-200">Indexed</span>
                                @elseif($doc->status === 'processing')
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 animate-pulse">Vectorizing</span>
                                @elseif($doc->status === 'failed')
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">Failed</span>
                                @else
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">Pending</span>
                                @endif

                                <a href="{{ route('admin.regulatory.chunks', $doc) }}"
                                    class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs transition-colors"
                                    title="View Chunks">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-sm">
                            No statutory standards uploaded yet. Click "Upload Statutory Standard" to index Uganda Building
                            Codes.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right: Recent Drawing Submissions -->
            <div class="p-6 rounded-2xl bg-white border border-slate-200/90 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Recent Drawing Audits</h3>
                        <p class="text-xs text-slate-500">Mobile engineering submissions & AI reviews</p>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($recentSubmissions as $sub)
                        <div class="py-3.5 flex items-center justify-between">
                            <div class="min-w-0 pr-4">
                                <h4 class="text-sm font-bold text-slate-800 truncate">
                                    {{ $sub->drawing?->floor?->block?->project?->name ?? 'Project Submission' }}
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ ucfirst($sub->drawing?->discipline ?? 'General') }} •
                                    {{ $sub->drawing?->floor?->name ?? 'Floor' }} (v{{ $sub->version_number }})
                                </p>
                            </div>
                            <div class="shrink-0">
                                @php
                                    $status = $sub->complianceResult?->overall_status ?? $sub->status;
                                @endphp
                                @if($status === 'PASS')
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-[#046938] border border-emerald-200">PASS</span>
                                @elseif($status === 'WARNING')
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-50 text-[#9a6a1e] border border-amber-200">WARNING</span>
                                @elseif(in_array($status, ['FAIL', 'REJECTED_PREFLIGHT']))
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-black bg-red-50 text-red-700 border border-red-200">FAIL</span>
                                @else
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 animate-pulse">ANALYZING</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-sm">
                            No drawing submissions recorded yet. Submissions from the mobile Flutter client will appear here in
                            real-time.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection