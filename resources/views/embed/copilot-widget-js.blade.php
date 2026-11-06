(function () {
    'use strict';

    if (window.__NBRB_EXT_COPILOT_LOADED__) {
        return;
    }
    window.__NBRB_EXT_COPILOT_LOADED__ = true;

    const SITE_CONFIG = {
        id: @json($website->id),
        slug: @json($website->slug),
        name: @json($website->name),
        welcomeMessage: @json($website->welcome_message),
        askUrl: @json($askUrl),
        baseUrl: @json($baseUrl),
        personas: @json($personas)
    };

    const STORAGE_KEY = 'nbrb_ext_copilot_history_' + SITE_CONFIG.slug;
    const PERSONA_KEY = 'nbrb_ext_copilot_persona_' + SITE_CONFIG.slug;
    const OPEN_KEY = 'nbrb_ext_copilot_open_' + SITE_CONFIG.slug;
    const EXPANDED_KEY = 'nbrb_ext_copilot_expanded_' + SITE_CONFIG.slug;

    function mountWidget() {
        if (document.getElementById('nbrb-ext-copilot-host')) {
            return;
        }

        const styleEl = document.createElement('style');
        styleEl.id = 'nbrb-ext-copilot-styles';
        styleEl.textContent = `
            #nbrb-ext-copilot-host, #nbrb-ext-copilot-host * {
                box-sizing: border-box;
                font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            #nbrb-ext-copilot-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(2, 6, 23, 0.45);
                backdrop-filter: blur(2px);
                z-index: 2147483640;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.25s ease;
            }
            #nbrb-ext-copilot-backdrop.is-visible {
                opacity: 1;
                pointer-events: auto;
            }
            #nbrb-ext-copilot-fab {
                position: fixed;
                bottom: 22px;
                right: 22px;
                z-index: 2147483645;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 12px 18px;
                border-radius: 9999px;
                background: linear-gradient(90deg, #03522c 0%, #046938 50%, #088045 100%);
                color: #ffffff;
                border: 2px solid rgba(255, 255, 255, 0.22);
                box-shadow: 0 20px 35px -10px rgba(4, 105, 56, 0.45), 0 8px 16px -6px rgba(0, 0, 0, 0.2);
                cursor: pointer;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                outline: none;
            }
            #nbrb-ext-copilot-fab:hover {
                transform: scale(1.04);
                box-shadow: 0 24px 40px -10px rgba(4, 105, 56, 0.6);
            }
            #nbrb-ext-copilot-fab:active {
                transform: scale(0.97);
            }
            #nbrb-ext-copilot-fab .nbrb-fab-label {
                font-weight: 800;
                font-size: 13px;
                letter-spacing: -0.01em;
                color: #ffffff;
                user-select: none;
            }
            #nbrb-ext-copilot-fab .nbrb-fab-dot {
                width: 8px;
                height: 8px;
                border-radius: 9999px;
                background: #6ee7b7;
                box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.3);
            }
            #nbrb-ext-copilot-fab .nbrb-fab-badge {
                padding: 2px 7px;
                border-radius: 9999px;
                background: #DEA74F;
                color: #020617;
                font-size: 10px;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            #nbrb-ext-copilot-window {
                position: fixed;
                bottom: 86px;
                right: 22px;
                z-index: 2147483646;
                display: flex;
                flex-direction: column;
                width: min(470px, calc(100vw - 28px));
                height: min(610px, calc(100vh - 110px));
                background: #ffffff;
                border-radius: 24px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.32);
                overflow: hidden;
                opacity: 0;
                transform: translateY(16px) scale(0.96);
                pointer-events: none;
                transform-origin: bottom right;
                transition: opacity 0.25s ease, transform 0.25s ease, inset 0.25s ease, width 0.25s ease, height 0.25s ease;
            }
            #nbrb-ext-copilot-window.is-open {
                opacity: 1;
                transform: translateY(0) scale(1);
                pointer-events: auto;
            }
            #nbrb-ext-copilot-window.is-expanded {
                inset: 20px;
                width: auto;
                height: auto;
                transform-origin: center;
            }
            .nbrb-ext-header {
                background: linear-gradient(90deg, #03522c 0%, #046938 55%, #077941 100%);
                padding: 14px 16px;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                border-bottom: 1px solid rgba(16, 185, 129, 0.3);
                user-select: none;
            }
            .nbrb-ext-header-left {
                display: flex;
                align-items: center;
                gap: 11px;
                min-width: 0;
            }
            .nbrb-ext-logo-box {
                width: 38px;
                height: 38px;
                border-radius: 13px;
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.22);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                color: #DEA74F;
            }
            .nbrb-ext-title {
                margin: 0;
                font-size: 14px;
                font-weight: 800;
                color: #ffffff;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .nbrb-ext-subtitle {
                margin: 2px 0 0 0;
                font-size: 11px;
                color: rgba(209, 250, 229, 0.85);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .nbrb-ext-actions {
                display: flex;
                align-items: center;
                gap: 4px;
            }
            .nbrb-ext-icon-btn {
                background: transparent;
                border: none;
                color: rgba(209, 250, 229, 0.88);
                padding: 6px;
                border-radius: 10px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: background 0.15s ease, color 0.15s ease;
            }
            .nbrb-ext-icon-btn:hover {
                background: rgba(255, 255, 255, 0.16);
                color: #ffffff;
            }
            .nbrb-ext-persona-bar {
                padding: 8px 12px;
                background: #f1f5f9;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                overflow-x: auto;
            }
            .nbrb-ext-persona-label {
                font-size: 10px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: #64748b;
                flex-shrink: 0;
            }
            .nbrb-ext-persona-list {
                display: flex;
                align-items: center;
                gap: 6px;
                overflow-x: auto;
            }
            .nbrb-ext-persona-btn {
                border: 1px solid transparent;
                background: transparent;
                color: #475569;
                font-size: 11px;
                font-weight: 600;
                padding: 5px 10px;
                border-radius: 10px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                white-space: nowrap;
                transition: all 0.15s ease;
            }
            .nbrb-ext-persona-btn:hover {
                background: rgba(255, 255, 255, 0.8);
                color: #0f172a;
            }
            .nbrb-ext-persona-btn.is-active {
                background: #ffffff;
                color: #046938;
                border-color: #cbd5e1;
                font-weight: 800;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            }
            .nbrb-ext-chips-bar {
                padding: 7px 12px;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                gap: 6px;
                overflow-x: auto;
            }
            .nbrb-ext-chip {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                color: #334155;
                font-size: 11px;
                font-weight: 600;
                padding: 4px 10px;
                border-radius: 8px;
                cursor: pointer;
                white-space: nowrap;
                transition: all 0.15s ease;
            }
            .nbrb-ext-chip:hover {
                background: #ecfdf5;
                border-color: #6ee7b7;
                color: #046938;
            }
            .nbrb-ext-messages {
                flex: 1;
                padding: 16px;
                overflow-y: auto;
                background: #f8fafc;
                display: flex;
                flex-direction: column;
                gap: 14px;
            }
            .nbrb-ext-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 14px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            }
            .nbrb-ext-msg-user {
                align-self: flex-end;
                background: #046938;
                color: #ffffff;
                border-radius: 16px 16px 3px 16px;
                padding: 10px 14px;
                font-size: 13px;
                line-height: 1.5;
                max-width: 84%;
                word-break: break-word;
            }
            .nbrb-ext-msg-bot-wrap {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                width: 100%;
            }
            .nbrb-ext-avatar {
                width: 30px;
                height: 30px;
                border-radius: 10px;
                background: #ecfdf5;
                border: 1px solid #a7f3d0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                flex-shrink: 0;
            }
            .nbrb-ext-msg-bot {
                flex: 1;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px 16px 16px 3px;
                padding: 12px 14px;
                font-size: 13px;
                color: #1e293b;
                line-height: 1.55;
                min-width: 0;
            }
            .nbrb-ext-typing {
                display: none;
                padding: 8px 16px;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                font-size: 11px;
                color: #475569;
                align-items: center;
                gap: 8px;
            }
            .nbrb-ext-typing.is-visible {
                display: flex;
            }
            .nbrb-ext-form {
                padding: 12px;
                background: #ffffff;
                border-top: 1px solid #e2e8f0;
            }
            .nbrb-ext-input-row {
                display: flex;
                align-items: flex-end;
                gap: 8px;
            }
            .nbrb-ext-textarea {
                flex: 1;
                resize: none;
                border-radius: 14px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                padding: 9px 13px;
                font-size: 13px;
                color: #0f172a;
                max-height: 100px;
                outline: none;
            }
            .nbrb-ext-textarea:focus {
                background: #ffffff;
                border-color: #046938;
                box-shadow: 0 0 0 2px rgba(4, 105, 56, 0.18);
            }
            .nbrb-ext-send-btn {
                padding: 10px 14px;
                border-radius: 14px;
                background: #046938;
                color: #ffffff;
                border: none;
                font-weight: 700;
                font-size: 12px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: background 0.15s ease;
            }
            .nbrb-ext-send-btn:hover {
                background: #03522c;
            }
            .nbrb-ext-send-btn:disabled {
                opacity: 0.45;
                cursor: not-allowed;
            }
            .nbrb-ext-footer-note {
                margin-top: 6px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 10px;
                color: #94a3b8;
            }
            .nbrb-ext-clause-box {
                margin-top: 8px;
                padding: 8px 10px;
                border-radius: 10px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                font-size: 11px;
            }
        `;
        document.head.appendChild(styleEl);

        const personas = SITE_CONFIG.personas || {};
        const personaKeys = Object.keys(personas);
        const defaultPersona = personaKeys[0] || 'inspector_okello';

        let currentPersona = localStorage.getItem(PERSONA_KEY);
        if (!currentPersona || !personas[currentPersona]) {
            currentPersona = defaultPersona;
        }
        let messages = [];
        let isOpen = false;
        let isFetching = false;
        let isExpanded = localStorage.getItem(EXPANDED_KEY) === 'true';

        const host = document.createElement('div');
        host.id = 'nbrb-ext-copilot-host';

        const personaButtonsHtml = personaKeys.map(function (key) {
            const p = personas[key];
            return `<button type="button" class="nbrb-ext-persona-btn" data-persona="${key}" title="${p.title || ''} (${p.role || ''})">
                <span>${p.emoji || '🏛️'}</span>
                <span>${p.short_name || p.title || key}</span>
            </button>`;
        }).join('');

        host.innerHTML = `
            <div id="nbrb-ext-copilot-backdrop" aria-hidden="true"></div>

            <button type="button" id="nbrb-ext-copilot-fab" aria-label="Open NBRB Statutory AI Copilot" title="NBRB Building Code AI Copilot (Ctrl + /)">
                <svg id="nbrb-ext-icon-closed" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
                <svg id="nbrb-ext-icon-open" width="22" height="22" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span class="nbrb-fab-label">AI Copilot</span>
                <span class="nbrb-fab-dot"></span>
                <span class="nbrb-fab-badge">NBRB</span>
            </button>

            <div id="nbrb-ext-copilot-window" role="dialog" aria-modal="true" aria-label="NBRB Statutory Copilot">
                <div class="nbrb-ext-header" id="nbrb-ext-header">
                    <div class="nbrb-ext-header-left">
                        <div class="nbrb-ext-logo-box">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div style="min-width:0;">
                            <h3 class="nbrb-ext-title">NBRB Statutory Copilot</h3>
                            <p class="nbrb-ext-subtitle">${SITE_CONFIG.name || 'Uganda National Building Code & Regulations'}</p>
                        </div>
                    </div>
                    <div class="nbrb-ext-actions">
                        <button type="button" class="nbrb-ext-icon-btn" id="nbrb-ext-expand-btn" title="Toggle Fullscreen">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                            </svg>
                        </button>
                        <button type="button" class="nbrb-ext-icon-btn" id="nbrb-ext-clear-btn" title="Clear History">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                        <button type="button" class="nbrb-ext-icon-btn" id="nbrb-ext-close-btn" title="Close">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="nbrb-ext-persona-bar">
                    <span class="nbrb-ext-persona-label">Persona:</span>
                    <div class="nbrb-ext-persona-list">
                        ${personaButtonsHtml}
                    </div>
                </div>

                <div class="nbrb-ext-chips-bar">
                    <button type="button" class="nbrb-ext-chip" data-query="What is the required window ventilation percentage for habitable rooms under the Building Control Regulations?">💨 Room Ventilation %</button>
                    <button type="button" class="nbrb-ext-chip" data-query="What are the fire escape stairway width and exit door requirements?">🚪 Fire Escape Width</button>
                    <button type="button" class="nbrb-ext-chip" data-query="What are the minimum wheelchair ramp slope standards for PWD accessibility?">♿ PWD Ramp Slope</button>
                    <button type="button" class="nbrb-ext-chip" data-query="What are the minimum plot boundary setback distances for residential buildings?">📐 Boundary Setbacks</button>
                </div>

                <div class="nbrb-ext-messages" id="nbrb-ext-messages"></div>

                <div class="nbrb-ext-typing" id="nbrb-ext-typing">
                    <span id="nbrb-ext-typing-text">Searching Uganda Building Regulations & Statutory Clauses...</span>
                </div>

                <form class="nbrb-ext-form" id="nbrb-ext-form">
                    <div class="nbrb-ext-input-row">
                        <textarea class="nbrb-ext-textarea" id="nbrb-ext-input" rows="1" maxlength="2000" placeholder="Ask about Uganda building codes, clauses, standards..."></textarea>
                        <button type="submit" class="nbrb-ext-send-btn" id="nbrb-ext-submit">Send</button>
                    </div>
                    <div class="nbrb-ext-footer-note">
                        <span>National Building Review Board • Uganda</span>
                        <span>Enter ↵ to send</span>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(host);

        const fabBtn = document.getElementById('nbrb-ext-copilot-fab');
        const windowEl = document.getElementById('nbrb-ext-copilot-window');
        const backdropEl = document.getElementById('nbrb-ext-copilot-backdrop');
        const iconClosed = document.getElementById('nbrb-ext-icon-closed');
        const iconOpen = document.getElementById('nbrb-ext-icon-open');
        const expandBtn = document.getElementById('nbrb-ext-expand-btn');
        const clearBtn = document.getElementById('nbrb-ext-clear-btn');
        const closeBtn = document.getElementById('nbrb-ext-close-btn');
        const messagesEl = document.getElementById('nbrb-ext-messages');
        const typingEl = document.getElementById('nbrb-ext-typing');
        const typingTextEl = document.getElementById('nbrb-ext-typing-text');
        const formEl = document.getElementById('nbrb-ext-form');
        const inputEl = document.getElementById('nbrb-ext-input');
        const submitBtn = document.getElementById('nbrb-ext-submit');
        const personaBtns = host.querySelectorAll('.nbrb-ext-persona-btn');
        const chipBtns = host.querySelectorAll('.nbrb-ext-chip');

        function formatMarkdown(text) {
            if (!text) return '';
            let safe = String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            safe = safe.replace(/^### (.*$)/gim, '<strong style="display:block;margin-top:8px;margin-bottom:4px;color:#0f172a;">$1</strong>');
            safe = safe.replace(/^## (.*$)/gim, '<strong style="display:block;font-size:14px;margin-top:10px;margin-bottom:4px;color:#0f172a;">$1</strong>');
            safe = safe.replace(/^\> (.*$)/gim, '<blockquote style="border-left:3px solid #046938;background:#ecfdf5;padding:4px 8px;margin:6px 0;font-style:italic;">$1</blockquote>');
            safe = safe.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');
            safe = safe.replace(/\*(.*?)\*/gim, '<em>$1</em>');
            safe = safe.replace(/\n\n/g, '<br><br>');
            safe = safe.replace(/\n/g, '<br>');
            return safe;
        }

        function setOpen(open) {
            isOpen = open;
            if (isOpen) {
                windowEl.classList.add('is-open');
                iconClosed.style.display = 'none';
                iconOpen.style.display = 'block';
                if (isExpanded) {
                    backdropEl.classList.add('is-visible');
                }
                localStorage.setItem(OPEN_KEY, 'true');
                setTimeout(function () { inputEl.focus(); }, 120);
            } else {
                windowEl.classList.remove('is-open');
                iconClosed.style.display = 'block';
                iconOpen.style.display = 'none';
                backdropEl.classList.remove('is-visible');
                localStorage.setItem(OPEN_KEY, 'false');
            }
        }

        function setExpanded(expanded) {
            isExpanded = expanded;
            localStorage.setItem(EXPANDED_KEY, isExpanded ? 'true' : 'false');
            if (isExpanded) {
                windowEl.classList.add('is-expanded');
                if (isOpen) backdropEl.classList.add('is-visible');
            } else {
                windowEl.classList.remove('is-expanded');
                backdropEl.classList.remove('is-visible');
            }
        }

        function setPersona(pid) {
            if (!personas[pid]) pid = defaultPersona;
            currentPersona = pid;
            localStorage.setItem(PERSONA_KEY, pid);
            personaBtns.forEach(function (btn) {
                if (btn.getAttribute('data-persona') === pid) {
                    btn.classList.add('is-active');
                } else {
                    btn.classList.remove('is-active');
                }
            });
            const meta = personas[pid] || {};
            typingTextEl.textContent = 'Consulting ' + (meta.title || 'Copilot') + '...';
            if (messages.length === 0) renderMessages();
        }

        function renderMessages() {
            messagesEl.innerHTML = '';
            if (messages.length === 0) {
                const meta = personas[currentPersona] || personas[defaultPersona] || {};
                const welcomeText = SITE_CONFIG.welcomeMessage || meta.welcome || 'Welcome! Ask any question regarding the Uganda National Building Code and Building Control Regulations.';
                const card = document.createElement('div');
                card.className = 'nbrb-ext-card';
                card.innerHTML = `
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <div class="nbrb-ext-avatar">${meta.emoji || '🏛️'}</div>
                        <div>
                            <div style="font-weight:800;font-size:12px;color:#0f172a;">${meta.title || 'Inspector Okello'}</div>
                            <div style="font-size:10px;font-weight:700;color:#046938;">${meta.role || 'Building Control Officer'}</div>
                        </div>
                    </div>
                    <div style="font-size:12px;color:#475569;line-height:1.5;">
                        ${formatMarkdown(welcomeText)}
                    </div>
                `;
                messagesEl.appendChild(card);
                return;
            }

            messages.forEach(function (msg) {
                if (msg.role === 'user') {
                    const userDiv = document.createElement('div');
                    userDiv.className = 'nbrb-ext-msg-user';
                    userDiv.textContent = msg.text;
                    messagesEl.appendChild(userDiv);
                } else {
                    const meta = personas[msg.persona] || personas[currentPersona] || {};
                    const wrap = document.createElement('div');
                    wrap.className = 'nbrb-ext-msg-bot-wrap';

                    let clausesHtml = '';
                    if (Array.isArray(msg.clauses) && msg.clauses.length > 0) {
                        clausesHtml = msg.clauses.map(function (c) {
                            return `<div class="nbrb-ext-clause-box">
                                <strong style="color:#046938;">${c.clause_number || ''} ${c.clause_title ? '— ' + c.clause_title : ''}</strong>
                                <div style="font-size:10px;color:#64748b;margin-top:2px;">${c.document || 'Uganda Building Code'}</div>
                            </div>`;
                        }).join('');
                    }

                    wrap.innerHTML = `
                        <div class="nbrb-ext-avatar">${meta.emoji || '🏛️'}</div>
                        <div class="nbrb-ext-msg-bot">
                            <div style="font-weight:800;font-size:11px;color:#046938;margin-bottom:4px;">
                                ${meta.title || 'NBRB Copilot'} • <span style="font-weight:600;color:#64748b;">${meta.role || ''}</span>
                            </div>
                            <div>${formatMarkdown(msg.text)}</div>
                            ${clausesHtml}
                        </div>
                    `;
                    messagesEl.appendChild(wrap);
                }
            });

            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        async function sendMessage(text) {
            const trimmed = (text || '').trim();
            if (!trimmed || isFetching) return;

            messages.push({
                role: 'user',
                text: trimmed,
                persona: currentPersona
            });
            renderMessages();
            inputEl.value = '';

            isFetching = true;
            submitBtn.disabled = true;
            typingEl.classList.add('is-visible');

            try {
                const res = await fetch(SITE_CONFIG.askUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        query: trimmed,
                        persona: currentPersona
                    })
                });

                if (res.status === 403) {
                    host.remove();
                    return;
                }

                const json = await res.json();
                const data = json.data || json;

                messages.push({
                    role: 'assistant',
                    text: data.response || 'No response received.',
                    persona: data.persona || currentPersona,
                    clauses: data.referenced_clauses || []
                });
            } catch (err) {
                messages.push({
                    role: 'assistant',
                    text: '⚠️ Unable to reach the NBRB Statutory AI Copilot service right now. Please try again shortly.',
                    persona: currentPersona,
                    clauses: []
                });
            } finally {
                isFetching = false;
                submitBtn.disabled = false;
                typingEl.classList.remove('is-visible');
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(messages.slice(-25)));
                } catch (_) {}
                renderMessages();
            }
        }

        fabBtn.addEventListener('click', function () {
            setOpen(!isOpen);
        });
        closeBtn.addEventListener('click', function () {
            setOpen(false);
        });
        expandBtn.addEventListener('click', function () {
            if (!isOpen) setOpen(true);
            setExpanded(!isExpanded);
        });
        backdropEl.addEventListener('click', function () {
            setExpanded(false);
        });
        clearBtn.addEventListener('click', function () {
            messages = [];
            localStorage.removeItem(STORAGE_KEY);
            renderMessages();
        });
        personaBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setPersona(this.getAttribute('data-persona'));
            });
        });
        chipBtns.forEach(function (chip) {
            chip.addEventListener('click', function () {
                const q = this.getAttribute('data-query');
                if (q) {
                    inputEl.value = q;
                    if (!isOpen) setOpen(true);
                    inputEl.focus();
                }
            });
        });
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage(inputEl.value);
            }
        });
        formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            sendMessage(inputEl.value);
        });

        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed)) messages = parsed;
            }
        } catch (_) {}

        setPersona(currentPersona);
        if (isExpanded) setExpanded(true);
        if (localStorage.getItem(OPEN_KEY) === 'true') setOpen(true);
        renderMessages();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountWidget);
    } else {
        mountWidget();
    }
})();

