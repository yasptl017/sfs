<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Forest Inventory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-slate-900 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
        <section class="relative hidden overflow-hidden bg-emerald-900 lg:block">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(6,95,70,.95),rgba(20,83,45,.82)),url('https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=1600&q=80')] bg-cover bg-center"></div>
            <div class="relative flex h-full flex-col justify-between p-10 text-white">
                <div class="inline-flex w-fit items-center gap-3 rounded-lg bg-white/10 px-4 py-3 backdrop-blur">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-white text-emerald-800">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3v18M6 8c2.5 0 4.5-1.5 6-5 1.5 3.5 3.5 5 6 5-1 4-3 6-6 6s-5-2-6-6Z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold">Forest Department</p>
                        <p class="text-xs text-emerald-100">Inventory Management System</p>
                    </div>
                </div>
                <div class="max-w-xl pb-8">
                    <h1 class="text-5xl font-semibold leading-tight">Secure stock records for divisions and ranges.</h1>
                    <p class="mt-4 text-lg text-emerald-50">A professional light admin workspace for managing range access, inventory records, and operational reporting.</p>
                </div>
            </div>
        </section>

        <section class="flex items-center justify-center px-5 py-10">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <div class="mb-4 grid h-12 w-12 place-items-center rounded-lg bg-emerald-700 text-white">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M6 8c2.5 0 4.5-1.5 6-5 1.5 3.5 3.5 5 6 5-1 4-3 6-6 6s-5-2-6-6Z"/></svg>
                    </div>
                    <h1 class="text-2xl font-semibold">Forest Inventory</h1>
                </div>

                <div class="rounded-lg border border-emerald-100 bg-white p-6 shadow-sm">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-slate-950">Sign in</h2>
                        <p class="mt-1 text-sm text-slate-500">Use your administrator, division, or range login credentials.</p>
                    </div>

                    <form class="space-y-4" method="POST" action="{{ route('login.store') }}">
                        @csrf
                        <div>
                            <label class="form-label" for="username">Username</label>
                            <input id="username" class="form-input" name="username" value="{{ old('username') }}" autocomplete="username" autofocus>
                            @error('username') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label" for="password">Password</label>
                            <input id="password" class="form-input" type="password" name="password" autocomplete="current-password">
                            @error('password') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input class="h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" type="checkbox" name="remember">
                            Remember me
                        </label>

                        <button class="primary-button w-full" type="submit">Login</button>
                    </form>

                    <p class="mt-5 rounded-lg bg-stone-50 px-3 py-2 text-sm text-slate-600">
                        Default administrator login: <span class="font-semibold">admin</span> / <span class="font-semibold">admin</span>
                    </p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
