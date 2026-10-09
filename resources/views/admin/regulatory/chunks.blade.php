@extends('layouts.admin')

@section('title', 'Vectorized Clauses - ' . $document->title)

@section('content')
<div class="space-y-6 pt-2">
    <!-- Header & Breadcrumb -->
    <div>
        <a href="{{ route('admin.regulatory.index') }}" class="text-xs font-semibold text-emerald-400 hover:underline inline-flex items-center gap-1 mb-2">
            ← Back to Statutory Standards
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-100 tracking-tight">{{ $document->title }}</h1>
                <p class="text-sm text-slate-400 mt-1">
                    {{ ucfirst($document->category) }} • Edition {{ $document->edition_year }} • 
                    <span class="text-emerald-400 font-semibold">{{ $chunks->total() }} vectorized clauses</span> in pgvector
                </p>
            </div>
            <form method="POST" action="{{ route('admin.regulatory.reingest', $document) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold inline-flex items-center gap-2 border border-slate-700 transition-colors">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Re-index Document
                </button>
            </form>
        </div>
    </div>

    <!-- Search filter -->
    <div class="p-4 rounded-2xl glass-panel">
        <form method="GET" action="{{ route('admin.regulatory.chunks', $document) }}" class="flex items-center gap-3">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search clause number, section title, or keyword..." 
                       class="w-full bg-slate-900/90 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-emerald-500">
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition-colors">
                Search Clauses
            </button>
            @if(request('search'))
                <a href="{{ route('admin.regulatory.chunks', $document) }}" class="text-xs text-slate-400 hover:text-slate-200">Reset</a>
            @endif
        </form>
    </div>

    <!-- Chunks Grid / List -->
    <div class="space-y-4">
        @forelse($chunks as $chunk)
            <div class="p-5 rounded-2xl glass-panel space-y-2.5 border-l-4 border-l-emerald-500/60 hover:border-l-emerald-400 transition-all">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-mono font-bold">
                            {{ $chunk->clause_number ?? 'Section' }}
                        </span>
                        <h3 class="text-sm font-bold text-slate-100">{{ $chunk->clause_title ?? 'General Provision' }}</h3>
                    </div>
                    <span class="text-[10px] font-mono text-slate-500">
                        768d Vector (pgvector)
                    </span>
                </div>

                <div class="text-xs text-slate-300 font-mono leading-relaxed bg-slate-900/80 p-3.5 rounded-xl border border-slate-800/80 whitespace-pre-line">
                    {{ $chunk->content }}
                </div>

                @if($chunk->metadata)
                    <div class="flex items-center gap-2 pt-1 text-[11px] text-slate-400">
                        <span class="font-semibold text-slate-400">Metadata:</span>
                        <code class="text-slate-300 font-mono">{{ json_encode($chunk->metadata) }}</code>
                    </div>
                @endif
            </div>
        @empty
            <div class="p-12 text-center rounded-2xl glass-panel text-slate-500">
                No vectorized clauses found for this statutory document yet. If processing is in progress, please check back in a moment or re-trigger indexing.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($chunks->hasPages())
        <div class="pt-4">
            {{ $chunks->links() }}
        </div>
    @endif
</div>
@endsection
