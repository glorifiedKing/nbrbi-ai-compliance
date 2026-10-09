@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pt-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-100 tracking-tight">AI Compliance & Regulatory Control</h1>
            <p class="text-sm text-slate-400 mt-1">National Building Review Board • Automated Plan Checking Engine</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.regulatory.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-semibold hover:from-emerald-500 hover:to-teal-500 transition-all shadow-lg shadow-emerald-900/40">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Upload Statutory Standard</span>
            </a>
        </div>
    </div>

    <!-- Microservice Status Banner -->
    <div class="p-4 rounded-2xl glass-panel flex items-center justify-between border-l-4 {{ $fastApiHealthy ? 'border-l-emerald-500' : 'border-l-amber-500' }}">
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full {{ $fastApiHealthy ? 'bg-emerald-400 animate-ping' : 'bg-amber-400' }}"></div>
            <div>
                <h4 class="text-sm font-semibold text-slate-200">
                    Python AI Microservice: 
                    <span class="{{ $fastApiHealthy ? 'text-emerald-400' : 'text-amber-400 font-bold' }}">
                        {{ $fastApiHealthy ? 'Connected & Ready (FastAPI + Celery)' : 'Microservice Offline / Standby' }}
                    </span>
                </h4>
                <p class="text-xs text-slate-400">Deterministic PyMuPDF vector extraction + Gemini 2.5 multimodal auditor + pgvector</p>
            </div>
        </div>
        <span class="text-xs font-mono text-slate-500 bg-slate-900/80 px-3 py-1.5 rounded-lg border border-slate-800">
            {{ config('services.fastapi.base_url') }}
        </span>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1 -->
        <div class="p-5 rounded-2xl glass-panel relative overflow-hidden group hover:border-emerald-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Statutory Standards</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-100 tracking-tight">{{ $totalDocuments }}</div>
                <p class="text-xs text-slate-400 mt-1"><span class="text-emerald-400 font-semibold">{{ $totalChunks }}</span> vectorized clauses in pgvector</p>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="p-5 rounded-2xl glass-panel relative overflow-hidden group hover:border-blue-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Projects Registered</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-100 tracking-tight">{{ $totalProjects }}</div>
                <p class="text-xs text-slate-400 mt-1">Multi-block Ugandan estate submissions</p>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="p-5 rounded-2xl glass-panel relative overflow-hidden group hover:border-purple-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Drawings Audited</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-100 tracking-tight">{{ $totalVersions }}</div>
                <p class="text-xs text-slate-400 mt-1">Arch, Struct, Mech & Elec versions</p>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="p-5 rounded-2xl glass-panel relative overflow-hidden group hover:border-amber-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Compliance Pass Rate</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-100 tracking-tight">{{ $complianceRate }}%</div>
                <p class="text-xs text-slate-400 mt-1"><span class="text-emerald-400">{{ $passCount }} Pass</span> • <span class="text-red-400">{{ $failCount }} Non-compliant</span></p>
            </div>
        </div>
    </div>

    <!-- Dual column content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left: Regulatory Standards Corpus -->
        <div class="p-6 rounded-2xl glass-panel space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-200">Official Statutory Corpus (RAG)</h3>
                    <p class="text-xs text-slate-400">Uganda National Building Code & Regulations</p>
                </div>
                <a href="{{ route('admin.regulatory.index') }}" class="text-xs text-emerald-400 hover:underline font-medium">
                    View All ({{ $totalDocuments }}) →
                </a>
            </div>

            <div class="divide-y divide-slate-800/60">
                @forelse($recentDocuments as $doc)
                    <div class="py-3.5 flex items-center justify-between">
                        <div class="min-w-0 pr-4">
                            <h4 class="text-sm font-semibold text-slate-200 truncate">{{ $doc->title }}</h4>
                            <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[10px] uppercase">{{ $doc->category }}</span>
                                <span>Ed. {{ $doc->edition_year }}</span>
                                <span>•</span>
                                <span>{{ $doc->chunks_count ?? $doc->total_chunks }} clauses</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($doc->status === 'indexed')
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Indexed</span>
                            @elseif($doc->status === 'processing')
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse">Vectorizing</span>
                            @elseif($doc->status === 'failed')
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">Failed</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-400">Pending</span>
                            @endif

                            <a href="{{ route('admin.regulatory.chunks', $doc) }}" class="p-1.5 rounded-lg bg-slate-800/60 hover:bg-slate-700 text-slate-300 text-xs transition-colors" title="View Chunks">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-500 text-sm">
                        No statutory standards uploaded yet. Click "Upload Statutory Standard" to index Uganda Building Codes.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Recent Drawing Submissions -->
        <div class="p-6 rounded-2xl glass-panel space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-200">Recent Drawing Audits</h3>
                    <p class="text-xs text-slate-400">Mobile engineering submissions & AI reviews</p>
                </div>
            </div>

            <div class="divide-y divide-slate-800/60">
                @forelse($recentSubmissions as $sub)
                    <div class="py-3.5 flex items-center justify-between">
                        <div class="min-w-0 pr-4">
                            <h4 class="text-sm font-semibold text-slate-200 truncate">
                                {{ $sub->drawing?->floor?->block?->project?->name ?? 'Project Submission' }}
                            </h4>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ ucfirst($sub->drawing?->discipline ?? 'General') }} • {{ $sub->drawing?->floor?->name ?? 'Floor' }} (v{{ $sub->version_number }})
                            </p>
                        </div>
                        <div class="shrink-0">
                            @php
                                $status = $sub->complianceResult?->overall_status ?? $sub->status;
                            @endphp
                            @if($status === 'PASS')
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">PASS</span>
                            @elseif($status === 'WARNING')
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">WARNING</span>
                            @elseif(in_array($status, ['FAIL', 'REJECTED_PREFLIGHT']))
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-500/10 text-red-400 border border-red-500/30">FAIL</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse">ANALYZING</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-500 text-sm">
                        No drawing submissions recorded yet. Submissions from the mobile Flutter client will appear here in real-time.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
