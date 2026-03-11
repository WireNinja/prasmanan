<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Override;
use WireNinja\Prasmanan\Models\BaseUser;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    #[Override]
    public function boot(): void
    {
        parent::boot();

        $mailTo = config('prasmanan.horizon.mail_notification_to', '');

        if (! empty($mailTo)) {
            Horizon::routeMailNotificationsTo($mailTo);
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    #[Override]
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (?BaseUser $user = null): bool => $user?->isSuperAdmin() ?? false);
    }
}
