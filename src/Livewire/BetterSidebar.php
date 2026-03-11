<?php

namespace WireNinja\Prasmanan\Livewire;

use Filament\Livewire\Sidebar;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Isolate;
use Override;

#[Isolate]
class BetterSidebar extends Sidebar
{
    #[Override]
    public function render(): View
    {
        return view('prasmanan::livewire.better-sidebar');
    }
}
