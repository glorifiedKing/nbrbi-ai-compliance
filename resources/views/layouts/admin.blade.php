<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NBRB BIMS Uganda') - AI Compliance Engine</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        nbrb: {
                            primary: '#046938',
                            'primary-dark': '#03522c',
                            'primary-light': '#088045',
                            secondary: '#DEA74F',
                            'secondary-dark': '#c8923a',
                            'secondary-light': '#ebbe6e',
                        },
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            500: '#046938',
                            600: '#03522c',
                            700: '#023c20',
                            gold: '#DEA74F',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
        }

        .glow-emerald {
            box-shadow: 0 0 25px -5px rgba(4, 105, 56, 0.2);
        }

        /* Desktop collapsed styles */
        @media (min-width: 1024px) {
            #admin-sidebar.is-collapsed {
                width: 5rem;
            }
            #admin-sidebar.is-collapsed .sidebar-text {
                display: none;
            }
            #admin-sidebar.is-collapsed .sidebar-header-box {
                justify-content: center;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            #admin-sidebar.is-collapsed .sidebar-nav-link {
                justify-content: center;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            #admin-sidebar.is-collapsed .sidebar-status-full {
                display: none;
            }
            #admin-sidebar.is-collapsed .sidebar-status-mini {
                display: flex;
            }
        }
    </style>
</head>

<body class="h-full bg-slate-50 flex overflow-hidden text-slate-800">
    <!-- Backdrop for mobile drawer -->
    <div id="sidebar-backdrop"
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-300 opacity-0 pointer-events-none lg:hidden"
        aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside id="admin-sidebar"
        class="fixed inset-y-0 left-0 z-50 flex flex-col justify-between w-72 max-w-[85vw] bg-white border-r border-slate-200 shadow-2xl lg:shadow-xs transition-all duration-300 ease-in-out -translate-x-full lg:translate-x-0 lg:static lg:z-30 shrink-0">
        <div>
            <!-- Header / Logo -->
            <div class="sidebar-header-box p-4 sm:p-5 border-b border-slate-200 flex items-center justify-between bg-white">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div
                        class="w-11 h-11 rounded-xl p-1 bg-white border border-slate-200 shadow-xs flex items-center justify-center shrink-0 overflow-hidden">
                        <img src="{{ asset('logo.jpg') }}" alt="NBRB Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="min-w-0 sidebar-text">
                        <h1 class="font-extrabold text-sm text-slate-900 tracking-tight leading-tight truncate">NBRB Uganda</h1>
                        <p class="text-[11px] text-[#046938] font-bold tracking-wide truncate">BIMS AI Compliance</p>
                    </div>
                </div>

                <!-- Close button for mobile screen -->
                <button type="button" id="sidebar-close-btn"
                    class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-[#046938]"
                    aria-label="Close sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3.5 sm:p-4 space-y-1.5">
                <a href="{{ route('admin.dashboard') }}"
                    title="Overview Dashboard"
                    class="sidebar-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-[#046938] text-white shadow-sm' : 'text-slate-600 hover:text-[#046938] hover:bg-[#046938]/5' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="sidebar-text truncate">Overview Dashboard</span>
                </a>

                <a href="{{ route('admin.quick-audit.index') }}"
                    title="Quick Drawing Audit"
                    class="sidebar-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.quick-audit.*') ? 'bg-[#046938] text-white shadow-sm' : 'text-slate-600 hover:text-[#046938] hover:bg-[#046938]/5' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="sidebar-text truncate">Quick Drawing Audit</span>
                </a>

                <a href="{{ route('admin.regulatory.index') }}"
                    title="Statutory Standards RAG"
                    class="sidebar-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.regulatory.*') && !request()->routeIs('admin.regulatory.create') ? 'bg-[#046938] text-white shadow-sm' : 'text-slate-600 hover:text-[#046938] hover:bg-[#046938]/5' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="sidebar-text truncate">Statutory Standards RAG</span>
                </a>

                <a href="{{ route('admin.regulatory.create') }}"
                    title="Upload Building Code"
                    class="sidebar-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.regulatory.create') ? 'bg-[#046938] text-white shadow-sm' : 'text-slate-600 hover:text-[#046938] hover:bg-[#046938]/5' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span class="sidebar-text truncate">Upload Building Code</span>
                </a>
            </nav>
        </div>

        <!-- Footer / System status -->
        <div class="p-3.5 sm:p-4 border-t border-slate-200">
            <div class="sidebar-status-full p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#046938] animate-pulse shrink-0"></span>
                    <span class="text-xs text-slate-800 font-mono font-bold truncate">BIMS AI v1.0</span>
                </div>
                <span class="text-[10px] text-slate-500 font-mono">Ready</span>
            </div>

            <div class="sidebar-status-mini hidden p-3 rounded-xl bg-slate-50 border border-slate-200 items-center justify-center" title="BIMS AI v1.0 - Ready">
                <span class="w-2.5 h-2.5 rounded-full bg-[#046938] animate-pulse"></span>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto bg-slate-50">
        <!-- Topbar -->
        <header
            class="h-16 border-b border-slate-200 px-4 sm:px-6 lg:px-8 flex items-center justify-between bg-white sticky top-0 z-20 shadow-xs">
            <div class="flex items-center gap-2.5 sm:gap-3">
                <!-- Mobile hamburger toggle button -->
                <button type="button" id="mobile-menu-btn"
                    class="lg:hidden p-2 -ml-1 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-[#046938] transition-colors"
                    aria-label="Toggle navigation menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Desktop collapse toggle button -->
                <button type="button" id="desktop-collapse-btn"
                    class="hidden lg:flex p-2 -ml-2 rounded-xl text-slate-500 hover:text-[#046938] hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-[#046938] transition-colors"
                    title="Toggle sidebar">
                    <svg id="desktop-collapse-icon" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>

                <div class="flex items-center gap-2 min-w-0">
                    <div class="h-2.5 w-2.5 rounded-full bg-[#DEA74F] shrink-0"></div>
                    <span class="text-xs uppercase tracking-wider font-extrabold text-slate-700 hidden md:inline truncate">
                        National Building Review Board Portal
                    </span>
                    <span class="text-xs uppercase tracking-wider font-extrabold text-slate-700 md:hidden truncate">
                        NBRB AI Portal
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2.5 sm:gap-4">
                <a href="{{ url('/api/docs') }}" target="_blank"
                    class="hidden sm:inline-block text-xs text-slate-500 hover:text-[#046938] transition-colors font-mono font-medium">
                    API Reference ↗
                </a>
                <div class="hidden sm:block h-4 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <div class="flex items-center gap-2.5 text-xs font-medium text-slate-700">
                        <div
                            class="w-8 h-8 rounded-full bg-[#046938] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                            {{ substr(auth()->user()->name ?? 'AD', 0, 2) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <div class="font-bold text-slate-900 leading-tight">{{ auth()->user()->name ?? 'Administrator' }}</div>
                            <div class="text-[10px] text-[#046938] font-mono font-semibold">Role:
                                {{ strtoupper(auth()->user()->role ?? 'ADMIN') }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                            class="p-2 sm:px-3 sm:py-1.5 rounded-lg bg-slate-100 hover:bg-red-50 text-slate-600 hover:text-red-600 text-xs font-semibold border border-slate-200 hover:border-red-200 transition-all flex items-center gap-1.5"
                            title="Log Out">
                            <svg class="w-4 h-4 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span class="hidden md:inline">Log Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Flash alerts -->
        <div class="px-4 sm:px-6 lg:px-8 pt-4 sm:pt-6">
            @if(session('success'))
                <div
                    class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-[#046938] text-sm flex items-center gap-3 shadow-xs">
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div
                    class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-3 shadow-xs">
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif
        </div>

        <!-- Page body -->
        <div class="px-4 sm:px-6 lg:px-8 pb-8 sm:pb-12">
            @yield('content')
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('admin-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const mobileBtn = document.getElementById('mobile-menu-btn');
            const closeBtn = document.getElementById('sidebar-close-btn');
            const desktopBtn = document.getElementById('desktop-collapse-btn');
            const desktopIcon = document.getElementById('desktop-collapse-icon');

            // Open mobile drawer
            function openMobileSidebar() {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                backdrop.classList.add('opacity-100', 'pointer-events-auto');
            }

            // Close mobile drawer
            function closeMobileSidebar() {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.remove('opacity-100', 'pointer-events-auto');
                backdrop.classList.add('opacity-0', 'pointer-events-none');
            }

            if (mobileBtn) mobileBtn.addEventListener('click', openMobileSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeMobileSidebar);
            if (backdrop) backdrop.addEventListener('click', closeMobileSidebar);

            // Close on nav link click in mobile view
            const navLinks = sidebar.querySelectorAll('nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 1024) {
                        closeMobileSidebar();
                    }
                });
            });

            // Close on Escape key press
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && window.innerWidth < 1024) {
                    closeMobileSidebar();
                }
            });

            // Desktop collapse/expand functionality
            const STORAGE_KEY = 'nbrb_admin_sidebar_collapsed';
            function setDesktopCollapsed(collapsed) {
                if (collapsed) {
                    sidebar.classList.add('is-collapsed');
                    if (desktopIcon) desktopIcon.classList.add('rotate-180');
                } else {
                    sidebar.classList.remove('is-collapsed');
                    if (desktopIcon) desktopIcon.classList.remove('rotate-180');
                }
            }

            // Restore desktop state from localStorage
            const savedState = localStorage.getItem(STORAGE_KEY);
            if (savedState === 'true' && window.innerWidth >= 1024) {
                setDesktopCollapsed(true);
            }

            if (desktopBtn) {
                desktopBtn.addEventListener('click', function () {
                    const isCurrentlyCollapsed = sidebar.classList.contains('is-collapsed');
                    const newState = !isCurrentlyCollapsed;
                    setDesktopCollapsed(newState);
                    localStorage.setItem(STORAGE_KEY, newState);
                });
            }
        });
    </script>

    {{-- Floating AI Copilot for Administrative Portal --}}
    @include('admin.partials.copilot-chatbot')
</body>

</html>