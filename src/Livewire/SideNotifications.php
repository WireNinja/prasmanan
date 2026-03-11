<?php

namespace WireNinja\Prasmanan\Livewire;

use Filament\Livewire\DatabaseNotifications as BaseComponent;
use Illuminate\Contracts\View\View;
use Override;

class SideNotifications extends BaseComponent
{
    #[Override]
    public function getTrigger(): ?View
    {
        return view('prasmanan::livewire.side-notifications');
    }
}
