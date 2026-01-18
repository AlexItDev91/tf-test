<nav class="-mx-3 flex flex-1 justify-end">
    @auth
        <a
            href="{{ route('home') }}"
            class="flex flex-row items-center gap-1 min-w-[64px]
           rounded-md px-3 py-2
           text-black ring-1 ring-transparent transition
           hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]
           dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white
           whitespace-nowrap"
        >
            <flux:icon.products variant="mini" />
            <span class="text-sm leading-tight">Products</span>
        </a>

        <a
            href="{{ route('shop.cart') }}"
            class="flex flex-row items-center gap-1 min-w-[64px]
           rounded-md px-3 py-2
           text-black ring-1 ring-transparent transition
           hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]
           dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white
           whitespace-nowrap"
        >
            <flux:icon.cart variant="mini" />
            <span class="text-sm leading-tight">Cart</span>
        </a>
        <a
            href="{{ route('shop.sales') }}"
            class="flex flex-row items-center gap-1 rounded-md px-3 py-2
           text-black ring-1 ring-transparent transition
           hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]
           dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white
           whitespace-nowrap"
        >
            <flux:icon.sales variant="mini" />
            <span class="text-sm leading-tight">Sales</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf

            <button
                type="submit"
                class="flex flex-row items-center gap-2 min-h-[40px]
               rounded-md px-3 py-2
               text-black ring-1 ring-transparent transition
               hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]
               dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white
               whitespace-nowrap"
            >
        <span class="flex w-5 h-5 items-center justify-center">
            <flux:icon.logout variant="mini" class="block" />
        </span>
                <span class="text-sm leading-none">Log out</span>
            </button>
        </form>
    @else
        <a
            href="{{ route('login') }}"
            class="flex flex-row items-center gap-2 min-h-[40px]
           rounded-md px-3 py-2
           text-black ring-1 ring-transparent transition
           hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]
           dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white
           whitespace-nowrap"
        >
    <span class="flex w-5 h-5 items-center justify-center">
        <flux:icon.login variant="mini" class="block" />
    </span>
            <span class="text-sm leading-none">Log in</span>
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                class="flex flex-row items-center gap-2 min-h-[40px]
               rounded-md px-3 py-2
               text-black ring-1 ring-transparent transition
               hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]
               dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white
               whitespace-nowrap"
            >
        <span class="flex w-5 h-5 items-center justify-center">
            {{-- empty slot to keep alignment --}}
        </span>
                <span class="text-sm leading-none">Register</span>
            </a>
        @endif
    @endauth
</nav>
