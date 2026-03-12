# Prasmanan Library

"Super Booster" library for Filament PHP by **WireNinja**.

---

## Onboarding

1. **Install Package**

    ```bash
    composer require wireninja/prasmanan -W
    ```

2. **Initialize Project** (Interactive)

    ```bash
    php artisan prasmanan:init
    ```

    _Syncs .env, .env.example, package.json, Vite, CSS, Enums, and Core Migrations._

3. **Publish Configuration** (Optional)
    ```bash
    php artisan vendor:publish --tag=prasmanan-config
    ```
    _Allows overriding default behaviors in `config/prasmanan.php`._

4. **Verify Health**
    ```bash
    php artisan prasmanan:audit
    ```
    _Run with `--production` before deployment._

---

## Core Commands

| Command                    | Description                                      |
| -------------------------- | ------------------------------------------------ |
| `prasmanan:audit`          | Audit project health & readiness (Tabular View). |
| `prasmanan:init`           | Setup/Reset project to Prasmanan standards.      |
| `prasmanan:env-sync`       | Force sync `.env.example` with defaults.         |
| `prasmanan:system-format`  | Format code using opinionated Pint preset.       |
| `prasmanan:system-refresh` | Pure database reset (`migrate:fresh --seed`).    |

---

## Development Workflow

1. **Start**: Run `prasmanan:init` on fresh projects.
2. **Coding**: Use standard Filament & Prasmanan utility classes.
3. **Commit**: Run `prasmanan:system-format --dirty` before pushing.
4. **Deploy**: Run `prasmanan:audit --production` for pre-flight check.

## Blade Directives
For non-Filament pages (e.g. landing pages or custom layouts), you can use these directives to inject Prasmanan features:

- **PWA Assets**: 
    ```blade
    @prasmananPwa
    ```
    _Place this inside your `<head>` tag. It injects manifest, meta tags, and icons._

- **PWA Service Worker Registration**: 
    ```blade
    @prasmananPwaScript
    ```
    _Place this before `</body>`. It registers `/sw.js`. **Note**: Skip this if you already register it via `@vite` (e.g. `import './pwa'`)._

- **Real-time Broadcasting**:
    ```blade
    @prasmananBroadcasting
    ```
    _Place this before `</body>`. It injects Echo listeners and notification handlers._

---

_Developed with Passion by WireNinja._
