<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Schedules;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;

final class CleanupBackupSchedule
{
    /**
     * Create a new schedule event for cleaning up old backups.
     */
    public static function make(): Event
    {
        return Schedule::command('backup:clean');
    }
}
