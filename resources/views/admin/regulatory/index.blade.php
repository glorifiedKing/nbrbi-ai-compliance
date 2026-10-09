@extends('layouts.admin')

@section('title', 'Statutory Documents')

@section('content')
<div class="space-y-6 pt-2">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-100 tracking-tight">Uganda Building Standards & Regulations</h1>
            <p class="text-sm text-slate-400 mt-1">Manage official statutory PDFs, vector embeddings, and RAG knowledge base.</p>
        </div>
        <a href="{{ route('admin.regulatory.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-semibold hover:from-emerald-500 hover:to-teal-500 transition-all shadow-lg shadow-emerald-900/40">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Upload New Standard</span>
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="p-4 rounded-2xl glass-panel flex flex-col md:flex-row gap-4 justify-between items-center">
        <form method="GET" action="{{ route('admin.regulatory.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative min-w-[240px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or clause..." 
                       class="w-full bg-slate-900/90 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-emerald-500">
            </div>

            <select name="category" onchange="this.form.submit()" 
                    class="bg-slate-900/90 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-300 focus:outline-none focus:border-emerald-500">
                <option value="">All Disciplines</option>
                <option value="architectural" {{ request('category') === 'architectural' ? 'selected' : '' }}>Architectural</option>
                <option value="structural" {{ request('category') === 'structural' ? 'selected' : '' }}>Structural</option>
                <option value="mep" {{ request('category') === 'mep' ? 'selected' : '' }}>MEP</option>
                <option value="fire_safety" {{ request('category') === 'fire_safety' ? 'selected' : '' }}>Fire Safety</option>
                <option value="general_building_control" {{ request('category') === 'general_building_control' ? 'selected' : '' }}>General Building Control</option>
            </select>

            <select name="status" onchange="this.form.submit()" 
                    class="bg-slate-900/90 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-300 focus:outline-none focus:border-emerald-500">
                <option value="">All Statuses</option>
                <option value="indexed" {{ request('status') === 'indexed' ? 'selected' : '' }}>Indexed</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Vectorizing</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>

            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium transition-colors">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'category', 'status']))
                <a href="{{ route('admin.regulatory.index') }}" class="text-xs text-slate-400 hover:text-slate-200">Reset</a>
            @endif
        </form>
    </div>

    <!-- Documents Table -->
    <div class="rounded-2xl glass-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/90 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Title & Classification</th>
                        <th class="px-6 py-4">Discipline</th>
                        <th class="px-6 py-4">Edition</th>
                        <th class="px-6 py-4">Vector Status</th>
                        <th class="px-6 py-4">Clauses</th>
                        <th class="px-6 py-4">Active in RAG</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-100">{{ $doc->title }}</div>
                                @if($doc->classification)
                                    <div class="text-xs text-slate-400 font-mono mt-0.5">Applies to: {{ $doc->classification }}</div>
                                @endif
                                @if($doc->error_message)
                                    <div class="text-xs text-red-400 mt-1 truncate max-w-xs" title="{{ $doc->error_message }}">
                                        Error: {{ $doc->error_message }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-md bg-slate-800 text-slate-300 text-xs font-medium font-mono uppercase">
                                    {{ $doc->category }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-300">
                                {{ $doc->edition_year }}
                            </td>
                            <td class="px-6 py-4">
                                @if($doc->status === 'indexed')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Indexed
                                    </span>
                                @elseif($doc->status === 'processing')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                        Vectorizing
                                    </span>
                                @elseif($doc->status === 'failed')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                        Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-400">
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-slate-200">
                                {{ $doc->chunks_count ?? $doc->total_chunks }}
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.regulatory.toggle-active', $doc) }}">
                                    @csrf
                                    <button type="submit" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $doc->is_active ? 'bg-emerald-500' : 'bg-slate-700' }}">
                                        <span class="translate-x-0 pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $doc->is_active ? 'translate-x-4' : '' }}"></span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.regulatory.chunks', $doc) }}" class="p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-emerald-400 transition-colors" title="View Indexed Chunks">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>

                                    <form method="POST" action="{{ route('admin.regulatory.reingest', $doc) }}" onsubmit="return confirm('Re-trigger AI chunking and vector indexing for this statutory document?');">
                                        @csrf
                                        <button type="submit" class="p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-blue-400 transition-colors" title="Re-index Chunks">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.regulatory.destroy', $doc) }}" onsubmit="return confirm('Are you sure you want to permanently delete this statutory standard and all its vector embeddings?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-red-400 transition-colors" title="Delete Standard">
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
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No regulatory documents found matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
