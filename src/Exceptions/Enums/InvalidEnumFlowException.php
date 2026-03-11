<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Enums;

use BackedEnum;
use Exception;
use WireNinja\Prasmanan\Contracts\ProperFilamentEnum;

/**
 * Exception thrown when an invalid enum state transition is attempted.
 */
class InvalidEnumFlowException extends Exception
{
    /**
     * Create a new invalid enum flow exception.
     *
     * @param  BackedEnum&ProperFilamentEnum  $from  The current state
     * @param  BackedEnum&ProperFilamentEnum  $to  The attempted target state
     */
    public function __construct(
        public readonly mixed $from,
        public readonly mixed $to,
    ) {
        // Use labels for better error messages
        // @phpstan-ignore-next-line
        $fromLabel = method_exists($from, 'getLabel') ? $from->getLabel() : $from->name;
        // @phpstan-ignore-next-line
        $toLabel = method_exists($to, 'getLabel') ? $to->getLabel() : $to->name;

        parent::__construct(
            sprintf(
                'Transisi status tidak valid: Tidak dapat mengubah status dari "%s" ke "%s". Transisi ini tidak diizinkan oleh alur yang telah ditentukan.',
                $fromLabel,
                $toLabel
            )
        );
    }

    /**
     * Get the current state.
     */
    public function getFrom(): mixed
    {
        return $this->from;
    }

    /**
     * Get the attempted target state.
     */
    public function getTo(): mixed
    {
        return $this->to;
    }
}
