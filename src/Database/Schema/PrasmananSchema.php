<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Database\Schema;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laragear\WebAuthn\Models\WebAuthnCredential;

final class PrasmananSchema
{
    /**
     * Build Core Identity Tables (users, authentications, login attempts)
     */
    public static function createIdentityTables(?Closure $additionalUserColumns = null): void
    {
        Schema::create('users', function (Blueprint $table) use ($additionalUserColumns): void {
            $table->id();
            $table->string('name');

            // Login
            $table->string('email')->unique();
            $table->string('phone_number')->nullable()->unique();
            $table->string('username')->nullable()->unique();

            $table->string('avatar')->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('phone_number_verified_at')->nullable();
            $table->string('phone_number_verified_via')->nullable(); // telegram, whatsapp, sms, etc

            $table->string('password')->nullable();
            $table->rememberToken();

            $table->boolean('is_suspended')->default(false);
            $table->timestampTz('suspended_until')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestampTz('last_position_updated_at')->nullable();

            $table->boolean('is_online')->default(false);
            $table->timestampTz('last_online_at')->nullable();

            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();

            $table->timestampsTz();

            $table->index(['is_suspended', 'suspended_until']);

            if ($additionalUserColumns) {
                // Allows user-land injection of custom columns
                $additionalUserColumns($table);
            }
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('user_social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // google, github, etc.
            $table->string('provider_id');
            $table->string('provider_email')->nullable();
            $table->string('provider_avatar')->nullable();
            $table->text('provider_token')->nullable();
            $table->text('provider_refresh_token')->nullable();
            $table->timestampTz('token_expires_at')->nullable();
            $table->timestampsTz();

            $table->unique(['provider', 'provider_id']);
            $table->index(['user_id', 'provider']);
        });

        if (class_exists(WebAuthnCredential::class)) {
            $migration = WebAuthnCredential::migration();
            if (method_exists($migration, 'with')) {
                $migration->with(function () {});
            }
            $migration->up();
        }
    }

    /**
     * Build Security Roles & Permissions Tables (Spatie Shield)
     */
    public static function createSecurityTables(): void
    {
        $tableNames = config('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);
        $columnNames = config('permission.column_names', [
            'role_pivot_key' => 'role_id',
            'permission_pivot_key' => 'permission_id',
            'model_morph_key' => 'model_id',
            'team_foreign_key' => 'team_id',
        ]);
        $teams = config('permission.teams', false);
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestampsTz();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->bigIncrements('id');
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestampsTz();
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);
            $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->onDelete('cascade');
            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        rescue(fn() => resolve('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key')));
    }

    /**
     * Build System Notifications, Activity Logs & Push Notifications
     */
    public static function createSystemLogsAndNotifications(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->jsonb('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->morphs('subscribable');
            $table->string('endpoint', 500)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });

        Schema::create(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->jsonb('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('event')->nullable();
            $table->timestampsTz();
            $table->index('log_name');
        });
    }

    public static function createSystemSettingTable(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();

            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');

            $table->timestamps();

            $table->unique(['group', 'name']);
        });
    }

    /**
     * Build Filament Plugin Dependencies (Media, Imports, Exports)
     */
    public static function createFilamentSystemTables(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->morphs('model');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->jsonb('manipulations');
            $table->jsonb('custom_properties');
            $table->jsonb('generated_conversions');
            $table->jsonb('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->nullableTimestampsTz();
        });

        Schema::create('imports', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('importer');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestampsTz();
        });

        Schema::create('exports', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_name');
            $table->string('file_disk');
            $table->string('exporter');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestampsTz();
        });

        Schema::create('failed_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->json('data');
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->text('validation_error')->nullable();
            $table->timestampsTz();
        });
    }

    // ===================================
    // Reverse/Drop methods
    // ===================================

    public static function dropIdentityTables(): void
    {
        if (class_exists(WebAuthnCredential::class)) {
            $migration = WebAuthnCredential::migration();
            $migration->down();
        }
        Schema::dropIfExists('user_social_accounts');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }

    public static function dropSecurityTables(): void
    {
        $tableNames = config('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);
        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }

    public static function dropSystemLogsAndNotifications(): void
    {
        Schema::dropIfExists(config('activitylog.table_name', 'activity_log'));
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('notifications');
    }

    public static function dropFilamentSystemTables(): void
    {
        Schema::dropIfExists('failed_import_rows');
        Schema::dropIfExists('exports');
        Schema::dropIfExists('imports');
        Schema::dropIfExists('media');
    }

    public static function truncateSystemSettingTable(): void
    {
        DB::table('settings')->truncate();
    }
}
