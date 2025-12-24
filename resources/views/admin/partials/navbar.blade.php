<header class="h-16 bg-white border-b flex items-center justify-between px-6">

    <!-- TÍTULO -->
    <h1 class="text-lg font-semibold">
        @yield('header', 'Dashboard')
    </h1>

    <!-- ACCIONES -->
    <div class="flex items-center gap-4">

        <span class="text-sm text-gray-600">
            {{ auth()->user()->name ?? 'Administrador' }}
        </span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-red-500 hover:text-red-700 transition">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </form>

    </div>
</header>
