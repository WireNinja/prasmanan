<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Schedules;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;

final class BackupDatabaseSchedule
{
    /**
     * Create a new schedule event for backing up the database only.
     */
    public static function make(): Event
    {
        return Schedule::command('backup:run --only-db');
    }
}
