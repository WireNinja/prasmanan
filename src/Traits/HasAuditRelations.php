<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Traits;

use Filament\Resources\RelationManagers\RelationGroup;
use WireNinja\Prasmanan\RelationManagers\ActivityLogRelationManager;

trait HasAuditRelations
{
    /**
     * @return array<int, RelationGroup>
     */
    public static function getAuditRelations(): array
    {
        return [
            RelationGroup::make('Audit', [
                ActivityLogRelationManager::class,
            ])->icon('lucide-clock'),
        ];
    }
}
