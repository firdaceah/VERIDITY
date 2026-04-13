<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>VeriDity Admin - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-slate-950 text-white font-sans flex min-h-screen">

    <aside class="w-64 bg-slate-900 border-r border-slate-800 hidden lg:flex flex-col sticky top-0 h-screen">
        <div class="p-6">
            <a href="/" class="text-2xl font-bold tracking-tighter text-blue-500">
                <i class="fa-solid fa-shield-halved"></i> VeriDity.
            </a>
        </div>

        <nav class="flex-1 px-4 space-y-2">
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-3 p-3 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <i class="fa-solid fa-chart-line w-5"></i> Dashboard
            </a>

            <a href="{{ route('admin.audit-logs') }}"
                class="flex items-center gap-3 p-3 rounded-xl transition {{ request()->routeIs('admin.audit-logs') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <i class="fa-solid fa-file w-5"></i> Audit Logs
            </a>
            <a href="{{ route('admin.blacklist') }}"
                class="flex items-center gap-3 p-3 rounded-xl transition {{ request()->routeIs('admin.blacklist') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <i class="fa-solid fa-database w-5"></i> Blacklist Data
            </a>
        </nav>

        <div class="p-6 border-t border-slate-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex items-center gap-3 text-red-400 hover:text-red-300 transition text-sm font-bold">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 p-6 md:p-10 overflow-y-auto">
        @yield('content')
    </main>

</body>

</html>
