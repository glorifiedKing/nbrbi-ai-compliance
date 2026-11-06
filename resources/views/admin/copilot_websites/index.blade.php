@extends('layouts.admin')

@section('title', 'External Copilot Websites & Embed Scripts')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto">
    {{-- Page Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-[#046938] transition-colors">Admin</a>
                <span>/</span>
                <a href="{{ route('admin.copilot.index') }}" class="hover:text-[#046938] transition-colors">Statutory Copilot</a>
                <span>/</span>
                <span class="text-[#046938]">External Websites Embed</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#03522c] to-[#088045] flex items-center justify-center text-white shadow-md shadow-[#046938]/20 shrink-0">
                    <svg class="w-5 h-5 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span>External Copilot Websites & Embed Scripts</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200">
                            JS Widget Manager
                        </span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Register external websites by slug or ID, copy their embeddable JavaScript tag, and enable or disable the floating AI Copilot remotely.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.copilot.index') }}"
                class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold border border-slate-200 shadow-2xs transition-all flex items-center gap-2">
                <span>Open Copilot Workspace</span>
            </a>
            <button type="button" onclick="document.getElementById('add-website-modal').classList.remove('hidden')"
                class="px-4 py-2.5 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-xs sm:text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add External Website</span>
            </button>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm space-y-1">
            <div class="font-bold">Please fix the following errors:</div>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- KPI Summary Strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Registered Websites</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total'] }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Configured embed targets</p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-emerald-200 p-4 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700">Enabled (Active)</p>
                <p class="text-2xl font-black text-[#046938] mt-1">{{ $stats['active'] }}</p>
                <p class="text-[11px] text-emerald-700/80 mt-0.5">Chatbot loads on external site</p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-[#046938] flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-amber-200 p-4 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-wider text-amber-700">Disabled (Blocked)</p>
                <p class="text-2xl font-black text-amber-700 mt-1">{{ $stats['disabled'] }}</p>
                <p class="text-[11px] text-amber-700/80 mt-0.5">Chatbot suppressed on site</p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">External Widget Queries</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total_requests']) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Total questions answered</p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-[#DEA74F]/15 text-[#966718] flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Websites List --}}
    @if($websites->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center space-y-4 shadow-2xs">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-200 text-[#046938] flex items-center justify-center mx-auto">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
            </div>
            <div class="max-w-lg mx-auto space-y-1">
                <h2 class="text-lg font-extrabold text-slate-900">No External Websites Registered Yet</h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Add an external website (with a custom slug or ID) to generate a single-line JavaScript snippet that embeds the NBRB Statutory AI Copilot floating chat onto that website.
                </p>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('add-website-modal').classList.remove('hidden')"
                    class="px-5 py-2.5 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-xs sm:text-sm font-bold shadow-sm transition-all inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Register First External Website</span>
                </button>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach($websites as $website)
                @php
                    $scriptUrl = route('copilot.embed.script', ['site' => $website->slug]);
                    $embedSnippet = '<script src="' . $scriptUrl . '" defer></script>';
                    $dataAttrSnippet = '<script src="' . route('copilot.embed.loader') . '" data-site="' . $website->slug . '" defer></script>';
                @endphp
                <div class="bg-white rounded-2xl border {{ $website->is_active ? 'border-slate-200' : 'border-amber-300 bg-amber-50/20' }} p-5 shadow-2xs space-y-4 transition-all">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h3 class="text-base sm:text-lg font-black text-slate-900">{{ $website->name }}</h3>

                                @if($website->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-100 text-[#046938] text-[11px] font-extrabold border border-emerald-200">
                                        <span class="w-2 h-2 rounded-full bg-[#046938] animate-pulse"></span>
                                        ENABLED • ACTIVE
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-extrabold border border-amber-300">
                                        <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                                        DISABLED • CHATBOT BLOCKED
                                    </span>
                                @endif

                                <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 border border-slate-200 font-mono text-xs font-bold text-slate-700">
                                    Slug: {{ $website->slug }}
                                </span>

                                <span class="px-2 py-0.5 rounded-lg bg-slate-50 border border-slate-200 font-mono text-[11px] text-slate-500">
                                    ID: {{ $website->id }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                                @if($website->domain)
                                    <span class="flex items-center gap-1 font-medium text-slate-700">
                                        <svg class="w-3.5 h-3.5 text-[#046938]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                        <span>{{ $website->domain }}</span>
                                    </span>
                                @endif
                                <span>Queries: <strong class="text-slate-800">{{ number_format($website->requests_count) }}</strong></span>
                                <span>Last Active: <strong class="text-slate-800">{{ $website->last_active_at ? $website->last_active_at->diffForHumans() : 'Never' }}</strong></span>
                            </div>

                            @if($website->welcome_message)
                                <p class="text-xs text-slate-600 italic bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200/80 inline-block">
                                    Welcome message: "{{ $website->welcome_message }}"
                                </p>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            {{-- Enable / Disable Form --}}
                            <form method="POST" action="{{ route('admin.copilot-websites.toggle-active', $website) }}">
                                @csrf
                                <button type="submit"
                                    class="px-3.5 py-2 rounded-xl text-xs font-extrabold border transition-all flex items-center gap-1.5 {{ $website->is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-300' : 'bg-[#046938] hover:bg-[#03522c] text-white border-[#046938]' }}">
                                    @if($website->is_active)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        <span>Disable Website</span>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Enable Website</span>
                                    @endif
                                </button>
                            </form>

                            {{-- Test / Live Preview --}}
                            <a href="{{ route('admin.copilot-websites.preview', $website) }}" target="_blank"
                                class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold border border-slate-200 transition-all flex items-center gap-1.5"
                                title="Open a test page with this website's embed script">
                                <svg class="w-4 h-4 text-[#046938]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Test Embed</span>
                            </a>

                            {{-- Edit Button --}}
                            <button type="button"
                                onclick="openEditWebsiteModal({{ Js::from($website) }})"
                                class="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold border border-slate-200 transition-all flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit</span>
                            </button>

                            {{-- Delete Form --}}
                            <form method="POST" action="{{ route('admin.copilot-websites.destroy', $website) }}"
                                onsubmit="return confirm('Delete external website {{ addslashes($website->name) }}? Its embed script will stop loading immediately.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="p-2 rounded-xl bg-white hover:bg-red-50 text-slate-500 hover:text-red-600 border border-slate-200 hover:border-red-200 transition-all"
                                    title="Delete Website">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Copyable Embed Script Box --}}
                    <div class="p-3.5 rounded-xl bg-slate-900 text-slate-100 space-y-2.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded bg-[#046938] text-white">
                                    Embed Script (Slug: {{ $website->slug }})
                                </span>
                                <span class="text-xs text-slate-400">
                                    Paste this before the closing <code class="text-amber-300 font-mono">&lt;/body&gt;</code> tag on the external website:
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                    onclick="copyEmbedCode('script-slug-{{ $website->id }}', this)"
                                    class="px-3 py-1.5 rounded-lg bg-[#DEA74F] hover:bg-[#c8923a] text-slate-950 text-xs font-extrabold transition-all flex items-center gap-1.5 shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Copy Script</span>
                                </button>
                            </div>
                        </div>

                        <pre id="script-slug-{{ $website->id }}" class="p-2.5 rounded-lg bg-slate-950 border border-slate-800 font-mono text-xs text-emerald-300 overflow-x-auto select-all">{{ $embedSnippet }}</pre>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1 border-t border-slate-800 text-[11px] text-slate-400">
                            <span>Alternative (with <code class="text-slate-300 font-mono">data-site</code> attribute): <code id="script-attr-{{ $website->id }}" class="text-slate-300 font-mono select-all">{{ $dataAttrSnippet }}</code></span>
                            <button type="button"
                                onclick="copyEmbedCode('script-attr-{{ $website->id }}', this)"
                                class="text-[#DEA74F] hover:underline font-bold text-[11px] shrink-0 self-start sm:self-auto">
                                Copy data-site tag
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Add External Website Modal --}}
<div id="add-website-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden">
        <div class="bg-gradient-to-r from-[#03522c] to-[#046938] px-6 py-4 text-white flex items-center justify-between">
            <div>
                <h3 class="font-black text-base">Register External Website</h3>
                <p class="text-xs text-emerald-100/80">Generate a shareable Copilot floating chat script</p>
            </div>
            <button type="button" onclick="document.getElementById('add-website-modal').classList.add('hidden')"
                class="p-1.5 rounded-xl hover:bg-white/15 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.copilot-websites.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                    Website / Portal Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" required placeholder="e.g. NBRB Public Website or KCCA Permit Portal"
                    value="{{ old('name') }}"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#046938]">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                    Website Slug / Identifier (Optional)
                </label>
                <input type="text" name="slug" placeholder="e.g. nbrb-public-site (auto-generated if blank)"
                    value="{{ old('slug') }}"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#046938]">
                <p class="text-[11px] text-slate-400 mt-1">Used in the script URL: <code class="font-mono">/copilot/embed/{slug}.js</code></p>
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                    Website Domain / URL (Optional)
                </label>
                <input type="text" name="domain" placeholder="e.g. https://nbrb.go.ug"
                    value="{{ old('domain') }}"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#046938]">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                    Custom Welcome Message (Optional)
                </label>
                <textarea name="welcome_message" rows="2" placeholder="Leave blank to use default persona greeting..."
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#046938]">{{ old('welcome_message') }}</textarea>
            </div>

            <div class="flex items-center gap-2.5 pt-1">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="create_is_active" name="is_active" value="1" checked
                    class="w-4 h-4 rounded text-[#046938] focus:ring-[#046938]">
                <label for="create_is_active" class="text-xs sm:text-sm font-bold text-slate-700">
                    Enable widget immediately (allow chatbot to load on external site)
                </label>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2.5">
                <button type="button" onclick="document.getElementById('add-website-modal').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-xs font-extrabold shadow-sm">
                    Register & Generate Script
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Edit External Website Modal --}}
<div id="edit-website-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden">
        <div class="bg-gradient-to-r from-[#03522c] to-[#046938] px-6 py-4 text-white flex items-center justify-between">
            <div>
                <h3 class="font-black text-base">Edit External Website</h3>
                <p class="text-xs text-emerald-100/80">Update slug, domain, welcome message, or active state</p>
            </div>
            <button type="button" onclick="document.getElementById('edit-website-modal').classList.add('hidden')"
                class="p-1.5 rounded-xl hover:bg-white/15 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-website-form" method="POST" action="" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                    Website / Portal Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="edit_name" name="name" required
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#046938]">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                    Website Slug <span class="text-red-500">*</span>
                </label>
                <input type="text" id="edit_slug" name="slug" required
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#046938]">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                    Website Domain / URL
                </label>
                <input type="text" id="edit_domain" name="domain"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#046938]">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                    Custom Welcome Message
                </label>
                <textarea id="edit_welcome_message" name="welcome_message" rows="2"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#046938]"></textarea>
            </div>

            <div class="flex items-center gap-2.5 pt-1">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1"
                    class="w-4 h-4 rounded text-[#046938] focus:ring-[#046938]">
                <label for="edit_is_active" class="text-xs sm:text-sm font-bold text-slate-700">
                    Website Enabled (load Copilot chatbot on external site)
                </label>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2.5">
                <button type="button" onclick="document.getElementById('edit-website-modal').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-xs font-extrabold shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function copyEmbedCode(elementId, btn) {
        const el = document.getElementById(elementId);
        if (!el) return;
        const text = el.textContent.trim();
        navigator.clipboard.writeText(text).then(function () {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span>✓ Copied!</span>';
            setTimeout(function () {
                btn.innerHTML = originalHtml;
            }, 1800);
        });
    }

    function openEditWebsiteModal(website) {
        const form = document.getElementById('edit-website-form');
        form.action = '{{ url("/admin/copilot-websites") }}/' + website.id;
        document.getElementById('edit_name').value = website.name || '';
        document.getElementById('edit_slug').value = website.slug || '';
        document.getElementById('edit_domain').value = website.domain || '';
        document.getElementById('edit_welcome_message').value = website.welcome_message || '';
        document.getElementById('edit_is_active').checked = !!website.is_active;
        document.getElementById('edit-website-modal').classList.remove('hidden');
    }
</script>
@endsection

