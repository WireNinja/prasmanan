<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Contracts;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * ProperFilamentEnum interface combines all standard Filament enum contracts.
 *
 * This contract ensures that the enum provides all necessary information
 * for Filament tables, forms, and infolists (Label, Color, Icon, Description).
 */
interface ProperFilamentEnum extends HasColor, HasDescription, HasIcon, HasLabel
{
    //
}
