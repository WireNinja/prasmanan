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

3. **Verify Health**
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

---

_Developed with Passion by WireNinja._
