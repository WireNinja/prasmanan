<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Concerns;

use Illuminate\Support\Str;

trait MagicGetterSetter
{
    public function __call($method, $parameters)
    {
        // 1. Biarkan method yang memang ada di parent class/trait lain lewat dulu
        if (method_exists(get_parent_class($this), $method)) {
            return parent::__call($method, $parameters);
        }

        // 2. Handle Magic Getter
        if (str_starts_with($method, 'get') && strlen($method) > 3) {
            $key = Str::snake(substr($method, 3));

            // Gunakan nama helper yang unik: 'isActualAttribute'
            if ($this->isActualAttribute($key)) {
                return $this->getAttribute($key);
            }
        }

        // 3. Handle Magic Setter
        if (str_starts_with($method, 'set') && strlen($method) > 3) {
            $key = Str::snake(substr($method, 3));
            $this->setAttribute($key, $parameters[0] ?? null);

            return $this;
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Nama method diganti agar tidak bentrok dengan internal Laravel
     */
    protected function isActualAttribute($key): bool
    {
        if (array_key_exists($key, $this->getAttributes())) {
            return true;
        }
        if ($this->hasGetMutator($key)) {
            return true;
        }

        return (bool) $this->hasAttributeMutator($key);
    }
}
