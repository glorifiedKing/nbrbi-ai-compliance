@extends('layouts.admin')

@section('title', 'Statutory AI Copilot Workspace')

@section('content')
<div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-[1600px] mx-auto">
    {{-- Page Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-[#046938] transition-colors">Admin</a>
                <span>/</span>
                <span class="text-[#046938]">AI Workspace</span>
                <span>/</span>
                <span class="text-slate-700">Statutory Copilot</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#03522c] to-[#088045] flex items-center justify-center text-white shadow-md shadow-[#046938]/20 shrink-0">
                    <svg class="w-5 h-5 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Statutory AI Copilot Workspace</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200">
                            Dedicated Full View
                        </span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Consult Ugandan building control officers, architects, and structural engineers grounded in statutory regulations.
                    </p>
                </div>
            </div>
        </div>

        {{-- Top Stats & Actions --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white border border-slate-200 text-slate-700 font-medium shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>RAG Knowledge: <strong class="font-bold text-slate-900">{{ $totalDocuments }} Active Codes</strong> ({{ number_format($totalChunks) }} Chunks)</span>
                </span>
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white border border-slate-200 text-slate-700 font-medium shadow-2xs">
                    <span class="text-[#DEA74F] font-bold">⚡</span>
                    <span>Model: <strong class="font-bold text-slate-900">Gemini 3 Flash</strong></span>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.copilot-websites.index') }}"
                    class="px-3.5 py-2 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-xs font-bold shadow-2xs transition-all flex items-center gap-2"
                    title="Manage external websites & copy embeddable Copilot JS script">
                    <svg class="w-4 h-4 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    <span>Embed on External Sites</span>
                </a>

                <button type="button" id="workspace-export-btn"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold border border-slate-200 shadow-2xs hover:shadow-xs transition-all flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#046938]"
                    title="Export conversation as statutory memorandum">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print / Export</span>
                </button>

                <button type="button" id="workspace-clear-btn"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-red-50 text-slate-700 hover:text-red-700 text-xs font-bold border border-slate-200 hover:border-red-200 shadow-2xs hover:shadow-xs transition-all flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-red-400"
                    title="Clear consultation history">
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Clear Chat</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Main Workspace Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Left Sidebar: Personas & Regulatory Library (4 cols on lg, 3 on xl) --}}
        <div class="lg:col-span-4 xl:col-span-3 space-y-5 order-2 lg:order-1">
            {{-- Persona Cards --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-5 space-y-3.5">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#046938]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Select Statutory Persona</span>
                    </h2>
                    <span class="text-[10px] text-slate-400 font-medium">3 Specialized Agents</span>
                </div>

                <div class="space-y-2.5">
                    @foreach($personas as $pKey => $p)
                        <button type="button" data-persona="{{ $pKey }}"
                            class="workspace-persona-card w-full text-left p-3.5 rounded-xl border transition-all relative overflow-hidden group {{ $loop->first ? 'active bg-emerald-50/70 border-[#046938] ring-2 ring-[#046938]/20' : 'bg-slate-50/70 hover:bg-white border-slate-200 hover:border-slate-300' }}">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-lg shadow-2xs shrink-0 group-hover:scale-105 transition-transform">
                                    {{ $p['emoji'] }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-1">
                                        <h3 class="font-bold text-sm text-slate-900 truncate">{{ $p['title'] }}</h3>
                                        <span class="workspace-active-badge text-[10px] font-extrabold px-1.5 py-0.5 rounded-md bg-[#046938] text-white shrink-0 {{ $loop->first ? '' : 'hidden' }}">
                                            Active
                                        </span>
                                    </div>
                                    <p class="text-[11px] font-semibold text-[#046938] truncate">{{ $p['role'] }}</p>
                                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed line-clamp-2">{{ $p['summary'] }}</p>
                                </div>
                            </div>

                            <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex flex-wrap gap-1">
                                @foreach($p['highlights'] as $highlight)
                                    <span class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-white text-slate-600 border border-slate-200">
                                        {{ $highlight }}
                                    </span>
                                @endforeach
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Suggested Topic Library --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-5 space-y-3">
                <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <span>Quick Statutory Queries</span>
                </h2>

                <div id="workspace-sample-queries-list" class="space-y-1.5">
                    {{-- Dynamically populated based on active persona --}}
                </div>
            </div>

            {{-- Statutory Reference Shelf --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Active Regulations</span>
                    </h2>
                    <a href="{{ route('admin.regulatory.index') }}" class="text-[11px] font-bold text-[#046938] hover:underline">
                        View All RAG →
                    </a>
                </div>

                <div class="space-y-2">
                    @forelse($recentDocuments as $doc)
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start justify-between gap-2 text-xs">
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 truncate">{{ $doc->title }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $doc->document_code ?? 'NBRB-STATUTORY' }} • {{ $doc->chunks_count }} clauses indexed</div>
                            </div>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold shrink-0">
                                Live
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 italic">No statutory documents uploaded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right / Center: Full-Screen Chat Canvas (8 cols on lg, 9 on xl) --}}
        <div class="lg:col-span-8 xl:col-span-9 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col h-[calc(100vh-14rem)] min-h-[640px] overflow-hidden order-1 lg:order-2">
            {{-- Top Active Persona Status Bar --}}
            <div class="p-3.5 sm:p-4 bg-gradient-to-r from-[#03522c] via-[#046938] to-[#077941] text-white flex items-center justify-between shrink-0 shadow-xs border-b border-emerald-600/30">
                <div class="flex items-center gap-3 min-w-0">
                    <div id="workspace-persona-avatar"
                        class="w-10 h-10 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-xl shrink-0 shadow-inner">
                        🏛️
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 id="workspace-persona-title" class="font-black text-sm sm:text-base text-white tracking-tight leading-tight truncate">
                                Inspector Okello
                            </h2>
                            <span id="workspace-persona-role-badge"
                                class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-500/30 border border-emerald-400/30 text-[10px] text-emerald-100 font-semibold truncate">
                                Chief Building Control Officer
                            </span>
                        </div>
                        <p id="workspace-persona-subtitle" class="text-xs text-emerald-100/80 font-medium truncate">
                            Statutory rulings, Building Control Act & Regulations 2020 enforcement
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/10 border border-white/20 text-[11px] font-mono font-medium text-emerald-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        Full Page Mode
                    </span>
                </div>
            </div>

            {{-- Quick Suggestion Chips Bar --}}
            <div class="px-4 py-2 bg-slate-50 border-b border-slate-200 flex items-center gap-2 overflow-x-auto no-scrollbar shrink-0">
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider shrink-0 flex items-center gap-1">
                    <span>💡</span>
                    <span>Quick:</span>
                </span>
                <button type="button"
                    data-query="What is the required window ventilation percentage for habitable rooms under the Building Control Regulations?"
                    class="workspace-chip px-3 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-xs font-medium whitespace-nowrap transition-all shadow-2xs">
                    💨 Room Ventilation %
                </button>
                <button type="button" data-query="What are the fire escape stairway width and exit door requirements?"
                    class="workspace-chip px-3 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-xs font-medium whitespace-nowrap transition-all shadow-2xs">
                    🚪 Fire Escape Width
                </button>
                <button type="button"
                    data-query="What are the minimum wheelchair ramp slope standards for PWD accessibility?"
                    class="workspace-chip px-3 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-xs font-medium whitespace-nowrap transition-all shadow-2xs">
                    ♿ PWD Ramp Slope
                </button>
                <button type="button"
                    data-query="What are the minimum plot boundary setback distances for residential buildings?"
                    class="workspace-chip px-3 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-xs font-medium whitespace-nowrap transition-all shadow-2xs">
                    📐 Boundary Setbacks
                </button>
                <button type="button"
                    data-query="What concrete grade is required for reinforced structural foundations and columns?"
                    class="workspace-chip px-3 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-xs font-medium whitespace-nowrap transition-all shadow-2xs">
                    🏗️ Concrete Grades
                </button>
            </div>

            {{-- Messages Stream Container --}}
            <div id="workspace-messages-container"
                class="flex-1 p-4 sm:p-6 space-y-5 overflow-y-auto bg-slate-50/60 scroll-smooth">
                {{-- Dynamically rendered from shared localStorage --}}
            </div>

            {{-- Typing Indicator --}}
            <div id="workspace-typing-indicator"
                class="hidden px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center gap-3 text-xs text-slate-500 shrink-0">
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-[#046938] animate-bounce" style="animation-delay: 0ms;"></span>
                    <span class="w-2 h-2 rounded-full bg-[#046938] animate-bounce" style="animation-delay: 150ms;"></span>
                    <span class="w-2 h-2 rounded-full bg-[#046938] animate-bounce" style="animation-delay: 300ms;"></span>
                </div>
                <span id="workspace-typing-text" class="font-medium text-xs text-slate-700">
                    Consulting Inspector Okello & referencing statutory clauses...
                </span>
            </div>

            {{-- Input Form Area --}}
            <form id="workspace-form" class="p-3 sm:p-4 bg-white border-t border-slate-200 shrink-0 space-y-2.5">
                <div class="relative flex items-end gap-3">
                    <textarea id="workspace-input" rows="2"
                        placeholder="Ask about Uganda building codes, statutory clauses, requirements..."
                        class="w-full resize-none rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#046938] focus:border-transparent transition-all max-h-36 leading-relaxed"
                        maxlength="2000"></textarea>

                    <button type="submit" id="workspace-submit-btn"
                        class="px-5 py-3.5 rounded-2xl bg-[#046938] hover:bg-[#03522c] active:scale-95 text-white shadow-md shadow-[#046938]/20 disabled:opacity-40 disabled:cursor-not-allowed transition-all shrink-0 flex items-center justify-center gap-2 font-bold text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#046938]"
                        title="Send query (Enter)">
                        <span>Send</span>
                        <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                        </svg>
                    </button>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-400 px-1 font-medium">
                    <span class="flex items-center gap-1.5 truncate">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>National Building Review Board • Statutory Compliance Copilot</span>
                    </span>
                    <span class="hidden sm:inline text-slate-400 font-mono">
                        Press <strong>Enter ↵</strong> to send • <strong>Shift+Enter</strong> for newline
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Dedicated Full-Page Workspace Engine Script --}}
<script>
    (function () {
        const STORAGE_KEY = 'nbrb_copilot_history_v2';
        const PERSONA_KEY = 'nbrb_copilot_persona_v2';

        const personaCards = document.querySelectorAll('.workspace-persona-card');
        const personaAvatar = document.getElementById('workspace-persona-avatar');
        const personaTitle = document.getElementById('workspace-persona-title');
        const personaRoleBadge = document.getElementById('workspace-persona-role-badge');
        const personaSubtitle = document.getElementById('workspace-persona-subtitle');
        const sampleQueriesList = document.getElementById('workspace-sample-queries-list');
        const messagesContainer = document.getElementById('workspace-messages-container');
        const typingIndicator = document.getElementById('workspace-typing-indicator');
        const typingText = document.getElementById('workspace-typing-text');
        const form = document.getElementById('workspace-form');
        const input = document.getElementById('workspace-input');
        const submitBtn = document.getElementById('workspace-submit-btn');
        const clearBtn = document.getElementById('workspace-clear-btn');
        const exportBtn = document.getElementById('workspace-export-btn');
        const chips = document.querySelectorAll('.workspace-chip');

        const personas = @json($personas);
        const personaKeys = Object.keys(personas);
        const defaultPersona = personaKeys[0] || 'inspector_okello';

        let currentPersona = localStorage.getItem(PERSONA_KEY);
        if (!currentPersona || !personas[currentPersona]) {
            currentPersona = defaultPersona;
        }
        let messages = [];
        let isFetching = false;

        // Auto-expand textarea
        if (input) {
            input.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!isFetching && input.value.trim().length > 0) {
                        form.dispatchEvent(new Event('submit', { cancelable: true }));
                    }
                }
            });
        }

        // Set Persona
        function setPersona(personaId) {
            if (!personas[personaId]) {
                personaId = defaultPersona;
            }
            if (!personas[personaId]) return;
            currentPersona = personaId;
            localStorage.setItem(PERSONA_KEY, personaId);

            const meta = personas[personaId];

            if (personaAvatar) personaAvatar.textContent = meta.emoji || '🏛️';
            if (personaTitle) personaTitle.textContent = meta.title || '';
            if (personaRoleBadge) personaRoleBadge.textContent = meta.role || '';
            if (personaSubtitle) personaSubtitle.textContent = meta.subtitle || meta.summary || '';
            if (typingText) typingText.textContent = `Consulting ${meta.title} (${meta.role})...`;

            // Update Persona cards UI
            personaCards.forEach(card => {
                const pid = card.getAttribute('data-persona');
                const badge = card.querySelector('.workspace-active-badge');
                if (pid === personaId) {
                    card.classList.add('active', 'bg-emerald-50/70', 'border-[#046938]', 'ring-2', 'ring-[#046938]/20');
                    card.classList.remove('bg-slate-50/70', 'border-slate-200');
                    if (badge) badge.classList.remove('hidden');
                } else {
                    card.classList.remove('active', 'bg-emerald-50/70', 'border-[#046938]', 'ring-2', 'ring-[#046938]/20');
                    card.classList.add('bg-slate-50/70', 'border-slate-200');
                    if (badge) badge.classList.add('hidden');
                }
            });

            // Populate sample queries for this persona
            const sampleQueries = meta.sampleQueries || meta.sample_queries || [];
            if (sampleQueriesList) {
                sampleQueriesList.innerHTML = sampleQueries.map(q => `
                    <button type="button" data-query="${q.replace(/"/g, '&quot;')}"
                        class="workspace-sample-query-btn w-full text-left p-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50/70 border border-slate-200 hover:border-emerald-300 text-xs font-medium text-slate-700 hover:text-[#046938] transition-all flex items-start gap-2 group">
                        <span class="text-[#046938] font-bold group-hover:translate-x-0.5 transition-transform">→</span>
                        <span class="leading-snug">${q}</span>
                    </button>
                `).join('');

                sampleQueriesList.querySelectorAll('.workspace-sample-query-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const query = this.getAttribute('data-query');
                        if (query && input) {
                            input.value = query;
                            input.style.height = 'auto';
                            input.style.height = (input.scrollHeight) + 'px';
                            input.focus();
                        }
                    });
                });
            }

            if (messages.length === 0) {
                renderMessages();
            }
        }

        personaCards.forEach(card => {
            card.addEventListener('click', function () {
                const pid = this.getAttribute('data-persona');
                setPersona(pid);
            });
        });

        // Quick Suggestion Chips
        chips.forEach(chip => {
            chip.addEventListener('click', function () {
                const query = this.getAttribute('data-query');
                if (query && input) {
                    input.value = query;
                    input.style.height = 'auto';
                    input.style.height = (input.scrollHeight) + 'px';
                    input.focus();
                }
            });
        });

        // Clear Chat
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (confirm('Clear entire consultation history?')) {
                    messages = [];
                    localStorage.removeItem(STORAGE_KEY);
                    renderMessages();
                }
            });
        }

        // Export / Print Conversation
        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                window.print();
            });
        }

        // Markdown Formatter
        function formatMarkdown(text) {
            if (!text) return '';
            let safe = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            safe = safe.replace(/^### (.*$)/gim, '<h4 class="font-bold text-sm sm:text-base text-slate-900 mt-3 mb-1.5">$1</h4>');
            safe = safe.replace(/^## (.*$)/gim, '<h3 class="font-extrabold text-base text-slate-900 mt-3.5 mb-2">$1</h3>');
            safe = safe.replace(/^\> (.*$)/gim, '<blockquote class="border-l-4 border-[#046938] bg-emerald-50/70 pl-3 py-1.5 my-2 text-xs sm:text-sm text-slate-700 italic rounded-r-lg">$1</blockquote>');
            safe = safe.replace(/\*\*(.*?)\*\*/gim, '<strong class="font-bold text-slate-900">$1</strong>');
            safe = safe.replace(/\*(.*?)\*/gim, '<em class="italic text-slate-700">$1</em>');
            safe = safe.replace(/\[([^\]]+)\]/g, '<span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-800 border border-slate-200 text-xs font-mono font-semibold my-0.5">$1</span>');
            safe = safe.replace(/^\s*[-•]\s+(.*$)/gim, '<li class="ml-5 list-disc text-xs sm:text-sm text-slate-700 my-1">$1</li>');
            safe = safe.replace(/^\s*(\d+)\.\s+(.*$)/gim, '<li class="ml-5 list-decimal text-xs sm:text-sm text-slate-700 my-1"><span class="font-bold text-slate-900">$1.</span> $2</li>');
            safe = safe.replace(/\n\n/g, '<div class="h-2.5"></div>');
            safe = safe.replace(/\n/g, '<br>');
            return safe;
        }

        function scrollToBottom() {
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        // Render Messages
        function renderMessages() {
            if (!messagesContainer) return;
            messagesContainer.innerHTML = '';

            if (messages.length === 0) {
                const meta = personas[currentPersona] || personas[defaultPersona];
                if (!meta) return;
                const sampleQueries = meta.sampleQueries || meta.sample_queries || [];
                const welcomeDiv = document.createElement('div');
                welcomeDiv.className = 'p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4 max-w-4xl mx-auto';
                welcomeDiv.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-2xl shadow-xs">
                            ${meta.emoji || '🏛️'}
                        </div>
                        <div>
                            <div class="font-black text-base text-slate-900 leading-tight">${meta.title || ''}</div>
                            <div class="text-xs text-[#046938] font-bold">${meta.role || ''} • Uganda National Building Review Board</div>
                        </div>
                    </div>
                    <div class="text-xs sm:text-sm text-slate-700 leading-relaxed">
                        ${formatMarkdown(meta.welcome)}
                    </div>
                    ${sampleQueries.length > 0 ? `
                        <div class="pt-3 border-t border-slate-100">
                            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Recommended Inquiries:</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                ${sampleQueries.map(sq => `
                                    <button type="button" data-query="${sq.replace(/"/g, '&quot;')}"
                                        class="workspace-welcome-query-btn text-left p-3 rounded-xl bg-slate-50 hover:bg-emerald-50/70 border border-slate-200 hover:border-emerald-300 text-xs text-slate-700 hover:text-[#046938] transition-all font-medium flex items-start gap-2">
                                        <span class="text-[#046938] font-bold">💬</span>
                                        <span>${sq}</span>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                `;
                messagesContainer.appendChild(welcomeDiv);

                welcomeDiv.querySelectorAll('.workspace-welcome-query-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const query = this.getAttribute('data-query');
                        if (query && input) {
                            input.value = query;
                            input.style.height = 'auto';
                            input.style.height = (input.scrollHeight) + 'px';
                            input.focus();
                        }
                    });
                });
                return;
            }

            messages.forEach((msg, idx) => {
                const item = document.createElement('div');

                if (msg.role === 'user') {
                    item.className = 'flex flex-col items-end max-w-4xl mx-auto';
                    item.innerHTML = `
                        <div class="bg-[#046938] text-white rounded-2xl rounded-tr-xs px-4 py-3 shadow-sm text-xs sm:text-sm max-w-[85%] sm:max-w-[75%] leading-relaxed font-medium">
                            ${msg.text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 font-mono pr-1">${msg.timestamp || ''}</span>
                    `;
                } else {
                    const meta = personas[msg.persona] || personas[currentPersona] || personas[defaultPersona] || {};
                    const roleBadgeClass = meta.badgeClass || meta.badge_class || 'bg-emerald-100 text-emerald-800 border-emerald-200';
                    const avatarSymbol = meta.emoji || (meta.badge ? meta.badge.split(' ')[0] : '🏛️');
                    item.className = 'flex items-start gap-3 sm:gap-4 max-w-4xl mx-auto';

                    let clausesHtml = '';
                    if (msg.clauses && msg.clauses.length > 0) {
                        clausesHtml = `
                            <div class="mt-3.5 pt-3 border-t border-slate-100 space-y-2">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 block flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-[#046938]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Referenced Statutory Clauses (${msg.clauses.length}):</span>
                                </span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    ${msg.clauses.map(c => `
                                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs leading-relaxed hover:border-emerald-300 hover:bg-emerald-50/20 transition-all shadow-2xs">
                                            <div class="font-bold text-[#046938] flex items-center justify-between gap-2 pb-1 border-b border-slate-200/60">
                                                <span class="truncate">${c.clause_number || ''} ${c.clause_title ? '— ' + c.clause_title : ''}</span>
                                                <span class="text-[10px] font-mono text-slate-500 shrink-0">${c.document || 'Uganda Building Code'}</span>
                                            </div>
                                            ${c.snippet ? `<p class="text-slate-600 mt-1.5 text-xs line-clamp-3 leading-relaxed">${c.snippet}</p>` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                    item.innerHTML = `
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-lg sm:text-xl shrink-0 mt-0.5 shadow-2xs">
                            ${avatarSymbol}
                        </div>
                        <div class="flex-1 bg-white border border-slate-200/90 rounded-2xl rounded-tl-xs p-4 sm:p-5 shadow-xs text-xs sm:text-sm text-slate-800 leading-relaxed">
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-xs sm:text-sm text-slate-900">${meta.title || ''}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-md ${roleBadgeClass} font-bold">${meta.role || ''}</span>
                                </div>
                                <span class="text-[10px] font-mono text-slate-400">${msg.timestamp || ''}</span>
                            </div>

                            <div class="prose-copilot text-xs sm:text-sm leading-relaxed text-slate-700">
                                ${formatMarkdown(msg.text)}
                            </div>

                            ${clausesHtml}

                            <div class="mt-3 pt-2 flex items-center justify-end">
                                <button type="button" class="workspace-copy-btn px-2.5 py-1 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 text-xs font-semibold flex items-center gap-1.5 transition-colors" data-index="${idx}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Copy Text</span>
                                </button>
                            </div>
                        </div>
                    `;
                }

                messagesContainer.appendChild(item);
            });

            // Attach Copy Listeners
            document.querySelectorAll('.workspace-copy-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const idx = this.getAttribute('data-index');
                    const msg = messages[idx];
                    if (msg && msg.text) {
                        navigator.clipboard.writeText(msg.text).then(() => {
                            const original = this.innerHTML;
                            this.innerHTML = '<span class="text-emerald-600 font-bold">✓ Copied!</span>';
                            setTimeout(() => { this.innerHTML = original; }, 1800);
                        });
                    }
                });
            });

            scrollToBottom();
        }

        // Send Query
        async function sendMessage(text) {
            if (!text || isFetching) return;
            const trimmed = text.trim();
            if (trimmed.length === 0) return;

            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            messages.push({
                role: 'user',
                text: trimmed,
                persona: currentPersona,
                timestamp: timeStr
            });

            renderMessages();

            if (input) {
                input.value = '';
                input.style.height = 'auto';
            }

            isFetching = true;
            if (submitBtn) submitBtn.disabled = true;
            if (typingIndicator) typingIndicator.classList.remove('hidden');
            scrollToBottom();

            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';

            const endpoints = ['{{ route("admin.copilot") }}', '{{ route("copilot") }}', '/admin/copilot/ask', '/copilot/ask'];
            let resolvedData = null;
            let lastError = null;

            for (const endpoint of endpoints) {
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            query: trimmed,
                            persona: currentPersona
                        })
                    });

                    if (response.ok) {
                        const json = await response.json();
                        if (json && (json.status === 'success' || json.data)) {
                            resolvedData = json.data || json;
                            break;
                        }
                    } else if (response.status === 422) {
                        const errJson = await response.json();
                        lastError = errJson.message || 'Validation error.';
                        break;
                    }
                } catch (err) {
                    lastError = err.message;
                }
            }

            isFetching = false;
            if (submitBtn) submitBtn.disabled = false;
            if (typingIndicator) typingIndicator.classList.add('hidden');

            const botTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            if (resolvedData) {
                messages.push({
                    role: 'assistant',
                    text: resolvedData.response || 'No response details received.',
                    persona: resolvedData.persona || currentPersona,
                    timestamp: botTime,
                    clauses: resolvedData.referenced_clauses || []
                });
            } else {
                messages.push({
                    role: 'assistant',
                    text: `⚠️ **Unable to fetch copilot response.**\n\n${lastError || 'Please verify your network connection or administrative session and try again.'}`,
                    persona: currentPersona,
                    timestamp: botTime,
                    clauses: []
                });
            }

            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(messages.slice(-30)));
            } catch (e) {
                // Ignore storage quota
            }

            renderMessages();
            if (input) input.focus();
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (input && !isFetching) {
                    sendMessage(input.value);
                }
            });
        }

        // Initialize from localStorage
        try {
            const savedPersona = localStorage.getItem(PERSONA_KEY);
            if (savedPersona && personas[savedPersona]) {
                setPersona(savedPersona);
            } else {
                setPersona(defaultPersona);
            }

            const savedMessages = localStorage.getItem(STORAGE_KEY);
            if (savedMessages) {
                const parsed = JSON.parse(savedMessages);
                if (Array.isArray(parsed)) {
                    messages = parsed;
                }
            }
        } catch (e) {
            messages = [];
        }

        renderMessages();
    })();
</script>
@endsection
