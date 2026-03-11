@island('better-sidebar')
    {{-- @persist('sidebar-' . filament()->getId()) --}}
    {{-- @endpersist --}}
    <div>
        @php
            $navigation = filament()->getNavigation();
            $isRtl = __('filament-panels::layout.direction') === 'rtl';
            $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
            $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
            $hasNavigation = filament()->hasNavigation();
            $hasTopbar = filament()->hasTopbar();
        @endphp

        {{-- Dual Pane Sidebar --}}
        <aside x-data="{}"
            @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop) x-cloak
        @else
            x-cloak="-lg" @endif
            x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }" class="fi-sidebar fi-main-sidebar flex flex-row">

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_START) }}

            {{-- Left Pane: Panel Switcher (Icon Only) --}}
            <div class="w-16 bg-white border-r border-gray-200 flex flex-col shrink-0">
                @php
                    $panelEnumClass = config('prasmanan.panel_enum');
                    $currentPanelId = filament()->getId();
                    $allPanels = (class_exists($panelEnumClass) && is_subclass_of($panelEnumClass, \BackedEnum::class)) ? $panelEnumClass::cases() : [];
                    $homeUrl = filament()->getHomeUrl();
                @endphp

                {{-- Logo Icon --}}
                <div class="h-16 flex items-center justify-center border-b border-gray-200">
                    {{-- Toggler --}}
                    <x-filament::icon-button color="gray" icon="lucide-panel-left-close" icon-size="lg" :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                        x-cloak x-data="{}" x-on:click="$store.sidebar.close()" x-show="$store.sidebar.isOpen"
                        class="fi-sidebar-close-collapse-sidebar-btn" />

                    {{-- Toggler for open --}}
                    <x-filament::icon-button color="gray" icon="lucide-panel-left-open" icon-size="lg" :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                        x-cloak x-data="{}" x-on:click="$store.sidebar.open()" x-show="!$store.sidebar.isOpen"
                        class="fi-sidebar-open-collapse-sidebar-btn" />
                </div>

                {{-- Panel Switcher Icons --}}
                <nav class="flex-1 py-4 space-y-2 overflow-y-auto">
                    @foreach ($allPanels as $panel)
                        @php
                            $panelUrl = '/' . $panel->path();
                        @endphp

                        <a href="{{ $panelUrl }}" wire:navigate wire:current.relative="bg-zinc-800 text-white"
                            @class([
                                'size-9.5 mx-auto flex items-center justify-center rounded-lg transition-all',
                                'text-gray-600 hover:bg-gray-100 hover:text-gray-900', // Class default (non-active)
                            ]) title="{{ $panel->getLabel() }}">
                            <x-filament::icon :icon="$panel->getIcon()" class="size-5" />
                        </a>
                    @endforeach
                </nav>

                {{-- Bottom Icons: Notifications & User Menu --}}
                @php
                    $isAuthenticated = filament()->auth()->check();
                    $hasDatabaseNotifications = filament()->hasDatabaseNotifications();
                    $hasUserMenu = filament()->hasUserMenu();
                @endphp

                @if ($isAuthenticated && ($hasDatabaseNotifications || $hasUserMenu))
                    <div class="px-2 py-4 border-t border-gray-200 flex flex-col items-center gap-4">
                        @if ($hasDatabaseNotifications)
                            @livewire('prasmanan-side-notifications')
                        @endif

                        @if ($hasUserMenu)
                            <x-filament-panels::user-menu position="left-rail" />
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right Pane: Full Navigation --}}
            <div class="flex-1 flex flex-col bg-white border-r border-gray-200"
                @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop) x-show="$store.sidebar.isOpen" @endif>

                {{-- Header --}}
                <div class="fi-sidebar-header-ctn">
                    <header class="fi-sidebar-header border-b border-gray-200">
                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_BEFORE) }}

                        <div x-show="$store.sidebar.isOpen" class="fi-sidebar-header-logo-ctn">
                            @if ($homeUrl = filament()->getHomeUrl())
                                <a {{ \Filament\Support\generate_href_html($homeUrl) }}
                                    class="flex items-center justify-center">
                                    <x-filament-panels::logo class="ml-0" />
                                </a>
                            @else
                                <x-filament-panels::logo />
                            @endif
                        </div>

                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_AFTER) }}
                    </header>
                </div>

                {{-- Search --}}
                @if (filament()->isGlobalSearchEnabled() &&
                        filament()->getGlobalSearchPosition() === \Filament\Enums\GlobalSearchPosition::Sidebar)
                    <div @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop) x-show="$store.sidebar.isOpen" @endif>
                        @livewire(Filament\Livewire\GlobalSearch::class)
                    </div>
                @endif

                {{-- Navigation Items --}}
                <nav class="fi-sidebar-nav flex-1 overflow-y-auto pl-5 pr-6 pt-2.5" wire:navigate:scroll>
                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_START) }}

                    <ul class="fi-sidebar-nav-groups gap-2">
                        @foreach ($navigation as $group)
                            @php
                                $isGroupActive = $group->isActive();
                                $isGroupCollapsible = $group->isCollapsible();
                                $groupIcon = $group->getIcon();
                                $groupItems = $group->getItems();
                                $groupLabel = $group->getLabel();
                                $groupExtraSidebarAttributeBag = $group->getExtraSidebarAttributeBag();
                            @endphp

                            <li>
                                <x-prasmanan::components.filament-panels.sidebar.group :active="$isGroupActive" :collapsible="$isGroupCollapsible" :icon="$groupIcon"
                                    :items="$groupItems" :label="$groupLabel" :attributes="\Filament\Support\prepare_inherited_attributes(
                                        $groupExtraSidebarAttributeBag,
                                    )" />
                            </li>
                        @endforeach
                    </ul>

                    <script>
                        var collapsedGroups = JSON.parse(
                            localStorage.getItem('collapsedGroups'),
                        )

                        if (collapsedGroups === null || collapsedGroups === 'null') {
                            localStorage.setItem(
                                'collapsedGroups',
                                JSON.stringify(@js(collect($navigation)->filter(fn(\Filament\Navigation\NavigationGroup $group): bool => $group->isCollapsed())->map(fn(\Filament\Navigation\NavigationGroup $group): string => $group->getLabel())->values()->all())),
                            )
                        }

                        collapsedGroups = JSON.parse(
                            localStorage.getItem('collapsedGroups'),
                        )

                        document
                            .querySelectorAll('.fi-sidebar-group')
                            .forEach((group) => {
                                if (
                                    !collapsedGroups.includes(group.dataset.groupLabel)
                                ) {
                                    return
                                }

                                group.querySelector(
                                    '.fi-sidebar-group-items',
                                ).style.display = 'none'
                                group.classList.add('fi-collapsed')
                            })
                    </script>

                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_END) }}
                </nav>

            </div>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER) }}
        </aside>

        <x-filament-actions::modals />
    </div>
@endisland
