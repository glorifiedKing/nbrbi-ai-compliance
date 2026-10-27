{{-- Administrative Floating Copilot Chatbot --}}
<div id="nbrb-copilot-root" class="relative z-50">
    {{-- Floating Action Button (FAB) --}}
    <button type="button" id="nbrb-copilot-toggle"
        class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-50 flex items-center gap-2.5 sm:gap-3 px-4 py-3 sm:px-4.5 sm:py-3.5 rounded-full bg-gradient-to-r from-[#03522c] via-[#046938] to-[#088045] text-white shadow-2xl hover:shadow-[#046938]/30 hover:scale-105 active:scale-95 transition-all duration-300 border-2 border-white/20 focus:outline-none focus:ring-4 focus:ring-[#046938]/30 group"
        aria-label="Open NBRB Statutory AI Copilot" title="NBRB Building Code AI Copilot (Ctrl + /)">
        
        {{-- Pulsing aura --}}
        <span class="absolute -inset-1 rounded-full bg-[#046938]/35 blur-md animate-pulse group-hover:bg-[#046938]/60 transition-all pointer-events-none"></span>

        {{-- Icon wrapper with toggle states --}}
        <div class="relative w-6 h-6 flex items-center justify-center shrink-0">
            {{-- Default Closed State Icon: Shield / AI Sparkles --}}
            <svg id="copilot-toggle-icon-closed" class="w-6 h-6 transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
            </svg>
            {{-- Open State Close Icon --}}
            <svg id="copilot-toggle-icon-open" class="w-6 h-6 hidden transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </div>

        {{-- Text label & status --}}
        <div class="flex items-center gap-2">
            <span class="font-extrabold text-xs sm:text-sm tracking-tight text-white select-none">AI Copilot</span>
            <span class="w-2 h-2 rounded-full bg-emerald-300 ring-4 ring-emerald-400/30 animate-pulse"></span>
        </div>

        {{-- Badge pill --}}
        <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full bg-[#DEA74F] text-slate-950 text-[10px] font-black uppercase tracking-wider shadow-xs">
            NBRB RAG
        </span>
    </button>

    {{-- Chat Window Modal Container --}}
    <div id="nbrb-copilot-window"
        class="fixed bottom-20 right-4 sm:bottom-24 sm:right-6 z-50 flex flex-col w-[calc(100vw-2rem)] sm:w-[460px] md:w-[490px] h-[610px] max-h-[calc(100vh-7.5rem)] bg-white rounded-3xl border border-slate-200/90 shadow-2xl overflow-hidden transition-all duration-300 ease-out opacity-0 scale-95 pointer-events-none translate-y-4 origin-bottom-right"
        role="dialog" aria-modal="true" aria-labelledby="copilot-title">

        {{-- Top Window Header --}}
        <div class="bg-gradient-to-r from-[#03522c] via-[#046938] to-[#077941] p-3.5 sm:p-4 text-white flex items-center justify-between shrink-0 shadow-sm select-none border-b border-emerald-600/30">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white shrink-0 shadow-inner">
                    <svg class="w-5 h-5 text-[#DEA74F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 id="copilot-title" class="font-black text-sm text-white tracking-tight leading-tight truncate">
                            NBRB Statutory Copilot
                        </h3>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-emerald-500/25 border border-emerald-400/30 text-[10px] text-emerald-200 font-mono font-bold shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>RAG Ready
                        </span>
                    </div>
                    <p class="text-[11px] text-emerald-100/80 font-medium truncate">
                        Uganda National Building Code & Regulations (2020)
                    </p>
                </div>
            </div>

            {{-- Header Actions --}}
            <div class="flex items-center gap-1 text-emerald-100">
                <button type="button" id="copilot-clear-btn"
                    class="p-1.5 sm:p-2 rounded-xl hover:bg-white/15 text-emerald-100/80 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white/30"
                    title="Clear Conversation History">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
                <button type="button" id="copilot-minimize-btn"
                    class="p-1.5 sm:p-2 rounded-xl hover:bg-white/15 text-emerald-100/80 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white/30"
                    title="Minimize Copilot">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <button type="button" id="copilot-close-btn"
                    class="p-1.5 sm:p-2 rounded-xl hover:bg-white/15 text-emerald-100/80 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white/30"
                    title="Close">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Persona Selector Bar --}}
        <div class="px-3 py-2 bg-slate-100/90 border-b border-slate-200 flex items-center justify-between gap-2 shrink-0">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider shrink-0 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-[#046938]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Persona:
            </span>

            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar w-full justify-end">
                {{-- Inspector Okello --}}
                <button type="button" data-persona="inspector_okello"
                    class="copilot-persona-btn active px-2.5 py-1 rounded-xl text-[11px] font-bold transition-all flex items-center gap-1 shrink-0 bg-white text-[#046938] shadow-xs border border-slate-200"
                    title="Chief Building Control Officer - Precise statutory citations & rulings">
                    <span>🏛️</span>
                    <span>Insp. Okello</span>
                </button>

                {{-- Arch. Namubiru --}}
                <button type="button" data-persona="architect_namubiru"
                    class="copilot-persona-btn px-2.5 py-1 rounded-xl text-[11px] font-medium transition-all flex items-center gap-1 shrink-0 text-slate-600 hover:text-slate-900 hover:bg-white/70"
                    title="Chartered Architect - Light, ventilation, headroom, spatial standards & PWD accessibility">
                    <span>📐</span>
                    <span>Arch. Namubiru</span>
                </button>

                {{-- Eng. Kigozi --}}
                <button type="button" data-persona="engineer_kigozi"
                    class="copilot-persona-btn px-2.5 py-1 rounded-xl text-[11px] font-medium transition-all flex items-center gap-1 shrink-0 text-slate-600 hover:text-slate-900 hover:bg-white/70"
                    title="Senior Structural Engineer - Structural loads, concrete strengths, safety & site engineering">
                    <span>🏗️</span>
                    <span>Eng. Kigozi</span>
                </button>
            </div>
        </div>

        {{-- Quick Query Suggestion Chips --}}
        <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-200 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider shrink-0">Suggest:</span>
            <button type="button" data-query="What is the required window ventilation percentage for habitable rooms under the Building Control Regulations?"
                class="copilot-chip px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-[11px] font-medium whitespace-nowrap transition-all shadow-2xs">
                💨 Room Ventilation %
            </button>
            <button type="button" data-query="What are the fire escape stairway width and exit door requirements?"
                class="copilot-chip px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-[11px] font-medium whitespace-nowrap transition-all shadow-2xs">
                🚪 Fire Escape Width
            </button>
            <button type="button" data-query="What are the minimum wheelchair ramp slope standards for PWD accessibility?"
                class="copilot-chip px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-[11px] font-medium whitespace-nowrap transition-all shadow-2xs">
                ♿ PWD Ramp Slope
            </button>
            <button type="button" data-query="What are the minimum plot boundary setback distances for residential buildings?"
                class="copilot-chip px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-[11px] font-medium whitespace-nowrap transition-all shadow-2xs">
                📐 Boundary Setbacks
            </button>
            <button type="button" data-query="What concrete grade is required for reinforced structural foundations and columns?"
                class="copilot-chip px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-[#046938] text-[11px] font-medium whitespace-nowrap transition-all shadow-2xs">
                🏗️ Concrete Grades
            </button>
        </div>

        {{-- Messages History Container --}}
        <div id="copilot-messages-container" class="flex-1 p-3.5 sm:p-4 space-y-4 overflow-y-auto bg-slate-50/70 scroll-smooth">
            {{-- Initial / Loaded Messages Will Render Here Dynamically --}}
        </div>

        {{-- Typing / Searching Indicator --}}
        <div id="copilot-typing-indicator" class="hidden px-4 py-2.5 bg-slate-50 border-t border-slate-100 flex items-center gap-2.5 text-xs text-slate-500 shrink-0">
            <div class="flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-[#046938] animate-bounce" style="animation-delay: 0ms;"></span>
                <span class="w-2 h-2 rounded-full bg-[#046938] animate-bounce" style="animation-delay: 150ms;"></span>
                <span class="w-2 h-2 rounded-full bg-[#046938] animate-bounce" style="animation-delay: 300ms;"></span>
            </div>
            <span id="copilot-typing-text" class="font-medium text-[11px] text-slate-600">
                Searching Uganda Building Regulations & Statutory Clauses...
            </span>
        </div>

        {{-- Input & Submit Form --}}
        <form id="copilot-form" class="p-3 bg-white border-t border-slate-200 shrink-0 space-y-2">
            <div class="relative flex items-end gap-2">
                <textarea id="copilot-input" rows="1"
                    placeholder="Ask about Uganda building codes, clauses, standards..."
                    class="w-full resize-none rounded-2xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#046938] focus:border-transparent transition-all max-h-28"
                    maxlength="2000"></textarea>

                <button type="submit" id="copilot-submit-btn"
                    class="p-2.5 rounded-2xl bg-[#046938] hover:bg-[#03522c] active:scale-95 text-white shadow-sm disabled:opacity-40 disabled:cursor-not-allowed transition-all shrink-0 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-[#046938]"
                    title="Send message (Enter)">
                    <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                </button>
            </div>

            <div class="flex items-center justify-between text-[10px] text-slate-400 px-1 font-medium">
                <span class="flex items-center gap-1 truncate">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>National Building Review Board • Uganda</span>
                </span>
                <span class="shrink-0 hidden sm:inline text-slate-400 font-mono">
                    Enter ↵ to send • Shift+Enter newline
                </span>
            </div>
        </form>
    </div>
</div>

{{-- Copilot Chatbot Engine Script --}}
<script>
    (function () {
        const STORAGE_KEY = 'nbrb_copilot_history_v2';
        const PERSONA_KEY = 'nbrb_copilot_persona_v2';
        const OPEN_KEY = 'nbrb_copilot_open_v2';

        const root = document.getElementById('nbrb-copilot-root');
        const toggleBtn = document.getElementById('nbrb-copilot-toggle');
        const windowEl = document.getElementById('nbrb-copilot-window');
        const iconClosed = document.getElementById('copilot-toggle-icon-closed');
        const iconOpen = document.getElementById('copilot-toggle-icon-open');
        const minimizeBtn = document.getElementById('copilot-minimize-btn');
        const closeBtn = document.getElementById('copilot-close-btn');
        const clearBtn = document.getElementById('copilot-clear-btn');
        const personaBtns = document.querySelectorAll('.copilot-persona-btn');
        const chips = document.querySelectorAll('.copilot-chip');
        const messagesContainer = document.getElementById('copilot-messages-container');
        const typingIndicator = document.getElementById('copilot-typing-indicator');
        const typingText = document.getElementById('copilot-typing-text');
        const form = document.getElementById('copilot-form');
        const input = document.getElementById('copilot-input');
        const submitBtn = document.getElementById('copilot-submit-btn');

        // Available persona metadata
        const personas = {
            inspector_okello: {
                id: 'inspector_okello',
                title: 'Inspector Okello',
                role: 'Chief Building Control Officer',
                badge: '🏛️ Statutory Ruling',
                badgeClass: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                welcome: 'I am **Inspector Okello**, Chief Building Control Officer at NBRB. I provide definitive statutory rulings grounded in the Uganda Building Control Act and Regulations. Ask me about compliance thresholds, statutory instruments, or permit clearance requirements.'
            },
            architect_namubiru: {
                id: 'architect_namubiru',
                title: 'Arch. Namubiru',
                role: 'Chartered Architect',
                badge: '📐 Spatial & Light',
                badgeClass: 'bg-purple-100 text-purple-800 border-purple-200',
                welcome: 'I am **Architect Namubiru**, chartered architect specializing in Ugandan spatial compliance. Ask me about natural lighting ratios, minimum room dimensions, ventilation openings, and accessibility (PWD) standards.'
            },
            engineer_kigozi: {
                id: 'engineer_kigozi',
                title: 'Eng. Kigozi',
                role: 'Senior Structural Engineer',
                badge: '🏗️ Structural & Site',
                badgeClass: 'bg-amber-100 text-amber-800 border-amber-200',
                welcome: 'I am **Eng. Kigozi**, senior structural and site engineer. Ask me about load-bearing margins, structural reinforcement, foundation safety standards, and practical on-site compliance under the building code.'
            }
        };

        // Active State
        let currentPersona = localStorage.getItem(PERSONA_KEY) || 'inspector_okello';
        let messages = [];
        let isOpen = false;
        let isFetching = false;

        // Auto-expand textarea
        if (input) {
            input.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Submit on Enter (unless Shift is held)
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!isFetching && input.value.trim().length > 0) {
                        form.dispatchEvent(new Event('submit', { cancelable: true }));
                    }
                }
            });
        }

        // Toggle Open/Close Window
        function setCopilotOpen(open) {
            isOpen = open;
            if (isOpen) {
                windowEl.classList.remove('opacity-0', 'scale-95', 'pointer-events-none', 'translate-y-4');
                windowEl.classList.add('opacity-100', 'scale-100', 'pointer-events-auto', 'translate-y-0');
                if (iconClosed) iconClosed.classList.add('hidden');
                if (iconOpen) iconOpen.classList.remove('hidden');
                localStorage.setItem(OPEN_KEY, 'true');
                scrollToBottom();
                setTimeout(() => { if (input) input.focus(); }, 150);
            } else {
                windowEl.classList.add('opacity-0', 'scale-95', 'pointer-events-none', 'translate-y-4');
                windowEl.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto', 'translate-y-0');
                if (iconClosed) iconClosed.classList.remove('hidden');
                if (iconOpen) iconOpen.classList.add('hidden');
                localStorage.setItem(OPEN_KEY, 'false');
            }
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                setCopilotOpen(!isOpen);
            });
        }

        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', function () {
                setCopilotOpen(false);
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                setCopilotOpen(false);
            });
        }

        // Keyboard Shortcut: Ctrl + / or Cmd + / to toggle, Escape to close
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                e.preventDefault();
                setCopilotOpen(!isOpen);
            } else if (e.key === 'Escape' && isOpen) {
                setCopilotOpen(false);
            }
        });

        // Set Active Persona
        function setPersona(personaId) {
            if (!personas[personaId]) return;
            currentPersona = personaId;
            localStorage.setItem(PERSONA_KEY, personaId);

            personaBtns.forEach(btn => {
                if (btn.getAttribute('data-persona') === personaId) {
                    btn.classList.add('active', 'bg-white', 'text-[#046938]', 'shadow-xs', 'border', 'border-slate-200', 'font-bold');
                    btn.classList.remove('text-slate-600', 'font-medium');
                } else {
                    btn.classList.remove('active', 'bg-white', 'text-[#046938]', 'shadow-xs', 'border', 'border-slate-200', 'font-bold');
                    btn.classList.add('text-slate-600', 'font-medium');
                }
            });

            // Update typing text
            const meta = personas[personaId];
            if (typingText) {
                typingText.textContent = `Consulting ${meta.title} (${meta.role})...`;
            }

            // If empty conversation, re-render default greeting
            if (messages.length === 0) {
                renderMessages();
            }
        }

        personaBtns.forEach(btn => {
            btn.addEventListener('click', function () {
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
                    if (!isOpen) setCopilotOpen(true);
                    input.focus();
                }
            });
        });

        // Clear Chat History
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (confirm('Clear copilot conversation history?')) {
                    messages = [];
                    localStorage.removeItem(STORAGE_KEY);
                    renderMessages();
                }
            });
        }

        // Safe Markdown Parser
        function formatMarkdown(text) {
            if (!text) return '';

            // Escape raw HTML first
            let safe = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Headings ###
            safe = safe.replace(/^### (.*$)/gim, '<h4 class="font-bold text-xs sm:text-sm text-slate-900 mt-2 mb-1">$1</h4>');
            safe = safe.replace(/^## (.*$)/gim, '<h3 class="font-extrabold text-sm text-slate-900 mt-2 mb-1.5">$1</h3>');

            // Blockquotes
            safe = safe.replace(/^\> (.*$)/gim, '<blockquote class="border-l-3 border-[#046938] bg-emerald-50/60 pl-2.5 py-1 my-1.5 text-xs text-slate-700 italic rounded-r-md">$1</blockquote>');

            // Bold & Italic
            safe = safe.replace(/\*\*(.*?)\*\*/gim, '<strong class="font-bold text-slate-900">$1</strong>');
            safe = safe.replace(/\*(.*?)\*/gim, '<em class="italic text-slate-700">$1</em>');

            // Statutory Clause citations [Reg 14.3] or (Doc - Clause)
            safe = safe.replace(/\[([^\]]+)\]/g, '<span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 text-[11px] font-mono font-semibold my-0.5">$1</span>');

            // Bullet Lists
            safe = safe.replace(/^\s*[-•]\s+(.*$)/gim, '<li class="ml-4 list-disc text-xs text-slate-700 my-0.5">$1</li>');

            // Numbered Lists
            safe = safe.replace(/^\s*(\d+)\.\s+(.*$)/gim, '<li class="ml-4 list-decimal text-xs text-slate-700 my-0.5"><span class="font-semibold text-slate-800">$1.</span> $2</li>');

            // Newlines to breaks
            safe = safe.replace(/\n\n/g, '<div class="h-2"></div>');
            safe = safe.replace(/\n/g, '<br>');

            return safe;
        }

        // Scroll Messages Container to Bottom
        function scrollToBottom() {
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        // Render All Messages
        function renderMessages() {
            if (!messagesContainer) return;

            messagesContainer.innerHTML = '';

            // Default Greeting if no conversation
            if (messages.length === 0) {
                const meta = personas[currentPersona] || personas['inspector_okello'];
                const welcomeDiv = document.createElement('div');
                welcomeDiv.className = 'p-4 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3';
                welcomeDiv.innerHTML = `
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-base">
                            ${meta.badge.split(' ')[0]}
                        </div>
                        <div>
                            <div class="font-bold text-xs text-slate-900 leading-tight">${meta.title}</div>
                            <div class="text-[10px] text-[#046938] font-semibold">${meta.role}</div>
                        </div>
                    </div>
                    <div class="text-xs text-slate-600 leading-relaxed">
                        ${formatMarkdown(meta.welcome)}
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex flex-wrap gap-1.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider w-full mb-0.5">Key Expertise:</span>
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-medium">Uganda Building Code</span>
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-medium">NBRB Regulations 2020</span>
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-medium">Statutory Standards</span>
                    </div>
                `;
                messagesContainer.appendChild(welcomeDiv);
                return;
            }

            // Render Message Items
            messages.forEach((msg, idx) => {
                const item = document.createElement('div');

                if (msg.role === 'user') {
                    item.className = 'flex flex-col items-end';
                    item.innerHTML = `
                        <div class="bg-[#046938] text-white rounded-2xl rounded-tr-xs px-3.5 py-2.5 shadow-xs text-xs sm:text-sm max-w-[85%] leading-relaxed">
                            ${msg.text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                        </div>
                        <span class="text-[9px] text-slate-400 mt-1 font-mono pr-1">${msg.timestamp || ''}</span>
                    `;
                } else {
                    const meta = personas[msg.persona] || personas['inspector_okello'];
                    item.className = 'flex items-start gap-2.5 max-w-[96%]';

                    let clausesHtml = '';
                    if (msg.clauses && msg.clauses.length > 0) {
                        clausesHtml = `
                            <div class="mt-2.5 pt-2 border-t border-slate-100 space-y-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Referenced Statutory Clauses:</span>
                                <div class="grid grid-cols-1 gap-1.5">
                                    ${msg.clauses.map(c => `
                                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-[11px] leading-snug">
                                            <div class="font-bold text-[#046938] flex items-center justify-between">
                                                <span>${c.clause_number || ''} ${c.clause_title ? '— ' + c.clause_title : ''}</span>
                                                <span class="text-[9px] font-mono text-slate-500">${c.document || 'Uganda Building Code'}</span>
                                            </div>
                                            ${c.snippet ? `<p class="text-slate-600 mt-1 text-[10px] line-clamp-2">${c.snippet}</p>` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                    item.innerHTML = `
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-center text-sm shrink-0 mt-0.5 shadow-2xs">
                            ${meta.badge.split(' ')[0]}
                        </div>
                        <div class="flex-1 bg-white border border-slate-200/90 rounded-2xl rounded-tl-xs p-3.5 shadow-xs text-xs sm:text-sm text-slate-800 leading-relaxed">
                            <div class="flex items-center justify-between pb-1.5 mb-1.5 border-b border-slate-100">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-[11px] text-slate-900">${meta.title}</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded-md ${meta.badgeClass} font-semibold">${meta.role}</span>
                                </div>
                                <span class="text-[9px] font-mono text-slate-400">${msg.timestamp || ''}</span>
                            </div>

                            <div class="prose-copilot text-xs sm:text-sm leading-relaxed text-slate-700">
                                ${formatMarkdown(msg.text)}
                            </div>

                            ${clausesHtml}

                            <div class="mt-2 pt-1.5 flex items-center justify-end">
                                <button type="button" class="copilot-copy-btn px-2 py-0.5 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 text-[10px] font-semibold flex items-center gap-1 transition-colors" data-index="${idx}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Copy</span>
                                </button>
                            </div>
                        </div>
                    `;
                }

                messagesContainer.appendChild(item);
            });

            // Attach Copy listeners
            document.querySelectorAll('.copilot-copy-btn').forEach(btn => {
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

        // Send Question to Copilot Controller
        async function sendMessage(text) {
            if (!text || isFetching) return;

            const trimmed = text.trim();
            if (trimmed.length === 0) return;

            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            // Append User Message
            messages.push({
                role: 'user',
                text: trimmed,
                persona: currentPersona,
                timestamp: timeStr
            });

            renderMessages();

            // Clear Input
            if (input) {
                input.value = '';
                input.style.height = 'auto';
            }

            // Show Typing indicator
            isFetching = true;
            if (submitBtn) submitBtn.disabled = true;
            if (typingIndicator) typingIndicator.classList.remove('hidden');
            scrollToBottom();

            // Resolve CSRF Token
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';

            // Preferred endpoints: try /admin/copilot/ask then fallback to /copilot/ask
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

            // Hide Typing indicator
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

            // Persist to local storage
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(messages.slice(-30)));
            } catch (e) {
                // Ignore storage quota errors
            }

            renderMessages();
            if (input) input.focus();
        }

        // Form Submit Handler
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (input && !isFetching) {
                    sendMessage(input.value);
                }
            });
        }

        // Initial Load from Storage
        try {
            const savedPersona = localStorage.getItem(PERSONA_KEY);
            if (savedPersona && personas[savedPersona]) {
                setPersona(savedPersona);
            } else {
                setPersona('inspector_okello');
            }

            const savedMessages = localStorage.getItem(STORAGE_KEY);
            if (savedMessages) {
                const parsed = JSON.parse(savedMessages);
                if (Array.isArray(parsed)) {
                    messages = parsed;
                }
            }

            const savedOpen = localStorage.getItem(OPEN_KEY);
            if (savedOpen === 'true') {
                setCopilotOpen(true);
            }
        } catch (e) {
            messages = [];
        }

        renderMessages();
    })();
</script>
