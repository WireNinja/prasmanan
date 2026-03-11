<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Concerns;

use WireNinja\Prasmanan\Exceptions\Enums\InvalidEnumFlowException;

/**
 * Trait for managing enum state transitions (State Machine).
 *
 * This trait allows you to define a roadmap of valid transitions
 * and ensures that transitions only happen through authorized paths.
 *
 * This trait complements InteractsWithEnums to provide logic for state managers.
 */
trait HasFlow
{
    /**
     * Define the roadmap of allowed state transitions.
     *
     * Keys: Current state (string value).
     * Values: Array of Enum cases that can be reached from this state.
     *
     * @return array<string, array<int, static>>
     */
    protected function defineFlow(): array
    {
        return [];
    }

    /**
     * Get all possible transitions (reached Enum cases) from current state.
     *
     * @return array<int, static>
     */
    public function getAllowedTransitions(): array
    {
        // @phpstan-ignore-next-line
        $flow = $this->defineFlow();

        return $flow[$this->value] ?? [];
    }

    /**
     * Check if a transition to the target state is allowed by flow roadmap.
     */
    public function canTransitionTo(self $to): bool
    {
        $allowedTransitions = $this->getAllowedTransitions();

        foreach ($allowedTransitions as $allowedState) {
            if ($allowedState === $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * Move to the target state if allowed, or throw an exception.
     *
     * @throws InvalidEnumFlowException
     */
    public function transitionTo(self $to): static
    {
        if (! $this->canTransitionTo($to)) {
            // @phpstan-ignore-next-line
            throw new InvalidEnumFlowException($this, $to);
        }

        return $to;
    }

    /**
     * Check if the transition violates the roadmap.
     */
    public function violatesFlowTo(self $to): bool
    {
        return ! $this->canTransitionTo($to);
    }

    /**
     * Get a human-readable description of states you can go to from here.
     */
    public function getAllowedTransitionsDescription(): string
    {
        $allowedTransitions = $this->getAllowedTransitions();

        if (empty($allowedTransitions)) {
            return 'Tidak ada transisi yang diizinkan dari status ini.';
        }

        $labels = array_map(
            // Ensure getLabel() exists or fallback to the name
            fn (self $state) => method_exists($state, 'getLabel') ? $state->getLabel() : $state->name,
            $allowedTransitions
        );

        return sprintf(
            'Dapat diubah ke: %s',
            implode(', ', $labels)
        );
    }
}
