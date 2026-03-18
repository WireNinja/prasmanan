# Laravel 13 Upgrade Checklist (Prasmanan Library) - [STATUS: ON HOLD]

## 🚨 High Impact Changes
- [ ] **Dependencies**: Update `composer.json` for `laravel/framework:^13.0`.
    *   *Audit*: **READY**. (Current: ^12.0)
    *   *Filament ^5.0*: ✅ L13 support officially merged (19h ago).
    *   *WebAuthn ^4.0*: ✅ Compatible with L11+ (L13 included).
    *   *Spatie Settings ^3.0*: ⚠️ Flex-support likely, requires verified build.
- [ ] **CSRF Protection**: Rename `VerifyCsrfToken` references to `PreventRequestForgery`.
    *   *Target Filename*: `packages/prasmanan/src/Builders/PanelBuilder.php` (Line 107)
    *   *Status*: **ON HOLD** (Reverted to `VerifyCsrfToken`).

## ⚠️ Medium Impact Changes
- [ ] **Cache Serialization**: Hardened unserialization.
    *   *Audit*: No direct object caching detected in library level. We use `app_settings` as classes, but they are resolved via `app()`, not cached as objects.

## ℹ️ Low Impact Changes
- [x] **Model Booting**: Verify initialization cycle.
    *   *Audit Result*: **PASS**. No `static function boot()` found in library level.
- [x] **Queue Events**: `JobAttempted` & `QueueBusy` updates.
    *   *Audit Result*: **PASS**. No queue event listeners found in package code.
- [x] **Domain Route Precedence**: Priority for explicit domains.
    *   *Audit Result*: **PASS**. No routing conflicts identified.
- [x] **Request Forgery API**: Middleware configuration API updates.
    *   *Status*: **Updated** in `PanelBuilder`.

## ✅ Verification & Build
- [ ] **Local Upgrade**: Successfully running `composer update`.
- [ ] **Test Suite**: Pest 4 passes all existing tests.
- [ ] **System Refresh**: `php artisan prasmanan:system-refresh` runs without errors.
- [ ] **Git Checkpoint**: Final commit before finalize.
