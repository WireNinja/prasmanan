<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string|null $activityLogName
 */
trait MagicActivityLog
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->getActivityLogName());

        if (isset($this->activityLogAttributes) && is_array($this->activityLogAttributes)) {
            $options->logOnly($this->activityLogAttributes);
        } else {
            $options->logUnguarded();
        }

        return $options;
    }

    protected function getActivityLogName(): string
    {
        return $this->activityLogName ?? class_basename($this);
    }
}
