# Laravel 13 Upgrade Checklist (Prasmanan Library)

## 🚨 High Impact Changes
- [ ] **Dependencies**: Update `composer.json` for `laravel/framework:^13.0` and `laravel/tinker:^3.0`.
    *   *Audit*: Root already uses Pest 4 (`^4.3`).
- [ ] **CSRF Protection**: Rename `VerifyCsrfToken` references to `PreventRequestForgery`.
    *   *Target Filename*: `packages/prasmanan/src/Builders/PanelBuilder.php` (Line 107)
    *   *Audit*: Confirmed usage. Must be updated to the new class name.

## ⚠️ Medium Impact Changes
- [ ] **Cache Serialization**: Hardened unserialization.
    *   *Audit*: No direct object caching detected in library level. We use `app_settings` as classes, but they are resolved via `app()`, not cached as objects.

## ℹ️ Low Impact Changes
- [ ] **Model Booting**: Verify initialization cycle.
    *   *Audit Result*: **PASS**. No `static function boot()` or `function booted()` found in `packages/prasmanan/src/`.
- [ ] **Queue Events**: `JobAttempted` & `QueueBusy` updates.
    *   *Audit Result*: **PASS**. No queue event listeners found in package code.
- [ ] **Domain Route Precedence**: Priority for explicit domains.
    *   *Audit Result*: **PASS**. No explicit domain routing used in core package routes.
- [ ] **Request Forgery API**: Update any middleware configuration calls.
    *   *Audit Result*: No direct `preventRequestForgery()` calls used yet.

## ✅ Verification & Build
- [ ] **Local Upgrade**: Successfully running `composer update`.
- [ ] **Test Suite**: Pest 4 passes all existing tests.
- [ ] **System Refresh**: `php artisan prasmanan:system-refresh` runs without errors.
- [ ] **Git Checkpoint**: Final commit before finalize.
