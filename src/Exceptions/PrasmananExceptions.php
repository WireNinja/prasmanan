<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class PrasmananExceptions
{
    /**
     * Configure the application to not report exceptions for guest users.
     */
    public static function dontReportForGuestUser(Exceptions $exceptions): void
    {
        $exceptions->dontReportWhen(function (Throwable $e): bool {
            return Auth::guest();
        });
    }
}
