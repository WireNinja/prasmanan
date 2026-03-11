<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Traits;

trait BaseEntity
{
    public function toArray($stripNull = true): array
    {
        $array = (array) $this;

        if ($stripNull) {
            return array_filter($array, fn ($value): bool => $value !== null);
        }

        return $array;
    }

    public function toJson(int $options = 0, $stripNull = true): string
    {
        return json_encode($this->toArray($stripNull), $options);
    }
}
