<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use Override;
use WireNinja\Prasmanan\Concerns\MagicActivityLog;
use WireNinja\Prasmanan\Concerns\MagicGetterSetter;

/**
 * Base User Social Account Model for Prasmanan Core.
 */
abstract class BaseUserSocialAccount extends Model
{
    use MagicActivityLog;
    use MagicGetterSetter;
    use Searchable;

    protected $table = 'user_social_accounts';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \WireNinja\Prasmanan\Models\BaseUser::class));
    }
}
