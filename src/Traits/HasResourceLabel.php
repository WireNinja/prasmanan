<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Traits;

use BackedEnum;

trait HasResourceLabel
{
    private static function _getResourceEnum(): ?BackedEnum
    {
        $enumClass = config('prasmanan.resource_enum');

        if (! class_exists($enumClass)) {
            return null;
        }

        /** @var class-string<BackedEnum> $enumClass */
        return $enumClass::tryFrom(static::class);
    }

    public static function getModelLabel(): string
    {
        $enum = self::_getResourceEnum();

        if (method_exists($enum, 'getLabel')) {
            return $enum->getLabel();
        }

        return parent::getModelLabel();
    }

    public static function getNavigationLabel(): string
    {
        $enum = self::_getResourceEnum();

        if (method_exists($enum, 'getLabel')) {
            return $enum->getLabel();
        }

        return parent::getNavigationLabel();
    }

    public static function getPluralModelLabel(): string
    {
        $enum = self::_getResourceEnum();

        if (method_exists($enum, 'getLabel')) {
            return $enum->getLabel();
        }

        return parent::getPluralModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        $enum = self::_getResourceEnum();

        if (method_exists($enum, 'getNavigationGroup')) {
            return $enum->getNavigationGroup();
        }

        $parentGroup = parent::getNavigationGroup();

        return is_string($parentGroup) ? $parentGroup : null;
    }

    public static function getNavigationIcon(): ?string
    {
        $enum = self::_getResourceEnum();

        if (method_exists($enum, 'getIcon')) {
            return $enum->getIcon();
        }

        return parent::getNavigationIcon();
    }
}
