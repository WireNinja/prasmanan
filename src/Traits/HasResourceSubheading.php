<?php

namespace WireNinja\Prasmanan\Traits;

use Filament\Resources\Resource;

trait HasResourceSubheading
{
    public function getSubheading(): ?string
    {
        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        $enumClass = config('prasmanan.resource_enum');
        if (class_exists($enumClass)) {
            $enum = $enumClass::tryFrom($resource);

            if (method_exists($enum, 'getDescription')) {
                return $enum->getDescription();
            }
        }

        return null;
    }
}
