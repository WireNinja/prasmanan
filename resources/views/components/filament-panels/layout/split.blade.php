@php
    use Filament\Support\Enums\Width;

    $livewire ??= null;
    $renderHookScopes = $livewire?->getRenderHookScopes();

    // Kita tetap ambil config width, tapi nanti di-apply ke container kanan
    $maxContentWidth ??= filament()->getSimplePageMaxContentWidth() ?? Width::Large;

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }

    // Mapping width enum ke tailwind class manual karena kita lepas dari class filament
    $maxWidthClass = match ($maxContentWidth) {
        Width::ExtraSmall => 'max-w-xs',
        Width::Small => 'max-w-sm',
        Width::Medium => 'max-w-md',
        Width::Large => 'max-w-lg',
        Width::ExtraLarge => 'max-w-xl',
        Width::TwoExtraLarge => 'max-w-2xl',
        Width::ThreeExtraLarge => 'max-w-3xl',
        Width::FourExtraLarge => 'max-w-4xl',
        Width::FiveExtraLarge => 'max-w-5xl',
        Width::SixExtraLarge => 'max-w-6xl',
        Width::SevenExtraLarge => 'max-w-7xl',
        Width::Full => 'max-w-full',
        default => 'max-w-lg',
    };
@endphp

<x-filament-panels.layout.base-auth :livewire="$livewire">
    <div class="flex min-h-screen w-full bg-gray-50">

        {{-- KIRI: Bagian Kosong / Dekoratif --}}
        {{-- Hidden di mobile, 50% width di desktop --}}
        <div class="hidden lg:flex w-1/2 relative bg-gray-50 items-center justify-center">

            {{-- Hook start ditaruh sini jika ada plugin yang inject script diawal --}}
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

            {{-- Image Implementation --}}
            <img src="{{ asset('images/split/1.webp') }}" alt="Background Decor"
                class="absolute inset-0 h-full w-full object-cover transition-opacity duration-500 opacity-0"
                onload="this.classList.remove('opacity-0')" decoding="async" fetchpriority="high">

            {{-- Overlay gradient (Opsional: untuk transisi halus jika gambar belum load) --}}
            {{-- <div class="absolute inset-0 bg-gradient-to-tr from-gray-50 to-transparent dark:from-gray-950"></div> --}}

            {{-- Original Overlay (Tetap dipertahankan untuk dark mode dimming) --}}
            <div class="absolute inset-0 bg-gray-100/10 dark:bg-gray-900/50 mix-blend-multiply"></div>

        </div>

        {{-- KANAN: Main Slot --}}
        <div class="flex w-full lg:w-1/2 flex-col justify-center px-4 py-12 sm:px-6 lg:px-20 xl:px-24">

            {{-- Header Kanan: User Menu & Notif (Tetap dimunculkan di pojok kanan atas) --}}
            @if (($hasTopbar ?? true) && filament()->auth()->check())
                <div class="absolute top-4 right-4 flex items-center gap-x-4">
                    @if (filament()->hasDatabaseNotifications())
                        @livewire(Filament\Livewire\DatabaseNotifications::class, [
                            'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                            'position' => \Filament\Enums\DatabaseNotificationsPosition::Topbar,
                        ])
                    @endif

                    @if (filament()->hasUserMenu())
                        @livewire(Filament\Livewire\SimpleUserMenu::class)
                    @endif
                </div>
            @endif

            {{-- Wrapper Konten Utama --}}
            <div class="mx-auto w-full {{ $maxWidthClass }}">
                <main>
                    {{ $slot }}
                </main>

                {{-- Footer Hooks --}}
                <div class="mt-10">
                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}
                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
                </div>
            </div>

        </div>
    </div>
</x-filament-panels.layout.base-auth>
