<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Schedules;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;

final class BackupFullSchedule
{
    /**
     * Create a new schedule event for a full backup (database + files).
     */
    public static function make(): Event
    {
        return Schedule::command('backup:run');
    }
}
