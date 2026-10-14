<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login - NBRB BIMS Uganda</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-slate-50 relative overflow-hidden text-slate-800">
    <!-- Ambient subtle background decorations -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-[#046938]/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-[#DEA74F]/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md space-y-6 relative z-10">
        <!-- Logo & Header -->
        <div class="text-center space-y-3">
            <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-white shadow-md border border-slate-200/90">
                <img src="{{ asset('logo.jpg') }}" alt="NBRB Logo" class="h-16 w-auto object-contain">
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">National Building Review Board</h1>
                <p class="text-xs text-[#046938] font-bold uppercase tracking-wider mt-1">BIMS AI Automated Plan Checking Engine</p>
            </div>
        </div>

        <!-- Flash alerts -->
        @if(session('error'))
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2.5 shadow-xs">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-[#046938] text-xs flex items-center gap-2.5 shadow-xs">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Form Card -->
        <div class="p-8 rounded-3xl bg-white border border-slate-200/90 shadow-xl space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-bold text-slate-900">Administrator Sign In</h2>
                <p class="text-xs text-slate-500 mt-0.5">Enter your authorized NBRB admin credentials</p>
            </div>

            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Admin Email
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', 'admin@nbrb.go.ug') }}" required autofocus
                           placeholder="admin@nbrb.go.ug"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#046938] focus:ring-1 focus:ring-[#046938] @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Password
                    </label>
                    <input type="password" id="password" name="password" required
                           placeholder="••••••••••••"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#046938] focus:ring-1 focus:ring-[#046938] @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600 hover:text-slate-800">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#046938] focus:ring-[#046938]">
                        <span>Remember session</span>
                    </label>
                    <span class="text-slate-500 font-mono text-[11px]">Role: System Admin</span>
                </div>

                <button type="submit" 
                        class="w-full py-3.5 rounded-xl bg-[#046938] hover:bg-[#03522c] text-white text-sm font-bold transition-all shadow-md hover:shadow-lg mt-2">
                    Sign In to Portal
                </button>
            </form>
        </div>

        <div class="text-center text-xs text-slate-400">
            National Building Review Board • Uganda Building Control Act
        </div>
    </div>
</body>
</html>
