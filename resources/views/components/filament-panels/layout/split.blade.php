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

<x-prasmanan::filament-panels.layout.base-auth :livewire="$livewire">
    <div class="flex min-h-screen w-full bg-gray-50">

        {{-- KIRI: Bagian Kosong / Dekoratif --}}
        {{-- Hidden di mobile, 50% width di desktop --}}
        @php
            $settingsClass = config('prasmanan.filament.auth_setting');
            $settings = $settingsClass ? app($settingsClass) : null;
            $images = $settings?->login_split_images ?? [
                ['image_path' => 'https://picsum.photos/1080/1920?random=1'],
                ['image_path' => 'https://picsum.photos/1080/1920?random=2'],
            ];
            $isSlider = $settings?->login_split_slider_enabled && count($images) > 1;
            $interval = $settings?->login_split_slider_interval ?? 5000;
        @endphp

        <div class="hidden lg:flex w-1/2 relative bg-gray-50 items-center justify-center p-4">

            {{-- Hook start ditaruh sini jika ada plugin yang inject script diawal --}}
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

            <div class="relative h-full w-full overflow-hidden rounded-3xl shadow-2xl ring-1 ring-gray-950/5 dark:ring-white/10"
                @if($isSlider)
                    x-data="{
                        current: 0,
                        images: {{ json_encode($images) }},
                        init() {
                            setInterval(() => {
                                this.current = (this.current + 1) % this.images.length;
                            }, {{ $interval }});
                        }
                    }"
                @endif
            >
                @foreach($images as $index => $imageData)
                    @php
                        $imagePath = is_array($imageData) ? $imageData['image_path'] : $imageData;
                    @endphp
                    <div
                        class="absolute inset-0 transition-opacity duration-1000"
                        @if($isSlider)
                            x-show="current === {{ $index }}"
                            x-transition:enter="transition ease-out duration-1000"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-1000"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                        @else
                            style="{{ $index === 0 ? 'opacity: 1' : 'opacity: 0; pointer-events: none;' }}"
                        @endif
                    >
                        {{-- Image Implementation --}}
                        <div class="absolute inset-0">
                            @php
                                $finalPath = str_starts_with($imagePath, 'http') 
                                    ? $imagePath 
                                    : asset('storage/' . $imagePath);
                            @endphp
                            <img src="{{ $finalPath }}" alt="Background Decor"
                                class="h-full w-full object-cover rounded-2xl transition-opacity duration-500"
                                decoding="async" fetchpriority="high">
                        </div>

                        {{-- Overlay --}}
                        <div class="absolute inset-0">
                            <div class="h-full w-full rounded-2xl bg-gray-100/10 dark:bg-gray-900/50 mix-blend-multiply"></div>
                        </div>
                    </div>
                @endforeach
            </div>

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
</x-prasmanan::filament-panels.layout.base-auth>
