<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Contracts;

interface ProperPanelEnum extends ProperFilamentEnum
{
    /**
     * Get the path associated with the panel enum.
     */
    public function path(): string;
}
