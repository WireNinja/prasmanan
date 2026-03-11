<button type="button" aria-label="{{ __('filament-panels::layout.actions.open_database_notifications.label') }}"
    class="relative size-9.5 flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-all">
    <x-filament::icon icon="lucide-bell" class="size-5" />

    @if ($unreadNotificationsCount)
        <span
            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-danger-600 text-[10px] font-bold text-white">
            {{ $unreadNotificationsCount }}
        </span>
    @endif
</button>
