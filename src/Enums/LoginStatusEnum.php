<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Enums;

use WireNinja\Prasmanan\Concerns\InteractsWithEnums;
use WireNinja\Prasmanan\Contracts\ProperFilamentEnum;

/**
 * @method bool isSuccess()
 * @method bool isNotSuccess()
 * @method bool isFailure()
 * @method bool isNotFailure()
 * @method bool isBanned()
 * @method bool isNotBanned()
 */
enum LoginStatusEnum: string implements ProperFilamentEnum
{
    use InteractsWithEnums;

    case Success = 'success';
    case Failure = 'failure';
    case Banned = 'banned';

    public function getLabel(): string
    {
        return match ($this) {
            self::Success => 'Berhasil',
            self::Failure => 'Gagal',
            self::Banned => 'Dicekal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Success => 'success',
            self::Failure => 'danger',
            self::Banned => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Success => 'lucide-check-circle',
            self::Failure => 'lucide-x-circle',
            self::Banned => 'lucide-ban',
        };
    }

    public function getDescription(): ?string
    {
        return null;
    }
}
