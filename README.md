# Prasmanan Library

Library "Super Booster" opiniated untuk Filament PHP, dikembangkan oleh **WireNinja**.

---

## 1. Onboarding & Instalasi

### A. Registrasi Service Provider

Pastikan library terdaftar di `bootstrap/providers.php`. Selain itu, daftarkan **Horizon Service Provider** untuk manajemen antrean:

```php
// bootstrap/providers.php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,         // Optional: Untuk dashboard Horizon
];
```

### B. Konfigurasi Initial

Jalankan perintah persiapan sistem untuk sinkronisasi konfigurasi awal:

```bash
php artisan prasmanan:system-prepare
```

### C. Integrasi Self-Healing Hook (Composer)

Sangat disarankan untuk menambahkan hook ini di `composer.json` project kamu agar folder-folder wajib (seperti `resources/svg` untuk BladeIcons) otomatis dibuat jika hilang/belum ada:

```json
"scripts": {
    "pre-update-cmd": [
        "WireNinja\\Prasmanan\\Supports\\ComposerHooks::setup"
    ],
    "pre-install-cmd": [
        "WireNinja\\Prasmanan\\Supports\\ComposerHooks::setup"
    ]
}
```

---

## 2. Command Sistem

### System Prepare

`php artisan prasmanan:system-prepare`
"Doktor Sistem" untuk audit dan setup otomatis.

- `--check`: Dry-run (hanya cek).
- `--production`: Audit kesiapan deploy (env, debug, logging).
- `--force`: Timpa config dari stub (Rector, PHPStan, Vite, CSS).

### System Format

`php artisan prasmanan:system-format`
Otomatis merapikan kode menggunakan Pint (Prasmanan Preset).

- `--dirty`: Hanya file yang berubah.

---

## 3. Utility Commands

### Environment Print

`php artisan prasmanan:env-print`
Mencetak daftar semua key dalam `.env` dengan format yang bersih untuk dokumentasi atau verifikasi.

### PWA Installation

`php artisan prasmanan:install-pwa`
Menyiapkan struktur PWA, menginstal stub konfigurasi, dan mendaftarkan script NPM yang diperlukan di `package.json`.

**Script yang tersedia setelah instalasi:**

- `bun run pwa:iconify`: Fetch icons dari Iconify.
- `bun run pwa:assets`: Generate PWA assets.
- `bun run pwa:copy`: Salin icons ke folder publik.

---

## 4. Vite & CSS Opinionated Integration

Library ini menyediakan helper dan stub untuk menjaga `vite.config.js` dan CSS tetap bersih serta konsisten.

### Vite Helpers
Gunakan helper dari library untuk auto-discovery themes dan watch exclusions.

```javascript
// vite.config.js
import {
    getFilamentThemes,
    commonWatchExclusions,
} from "./packages/prasmanan/resources/js/vite-helpers";

export default defineConfig({
    // ...
});
```

### CSS Stubs
Command `system:prepare` akan menyinkronkan file CSS berikut:
- `resources/css/app.css`: Base Tailwind + Font Configuration.
- `resources/css/sources.css`: Tailwind v4 Scan Sources (Monorepo aware).
- `resources/css/pdf.css`: Base Tailwind untuk PDF generation.

---

## 5. Alur Kerja yang Disarankan

1.  **Project Baru**: Jalankan `system:prepare` untuk sinkronisasi konfigurasi awal (Rector, PHPStan, Vite, CSS, Enums).
2.  **Sebelum Commit**: Jalankan `system:format --dirty` untuk memastikan gaya kode konsisten.
3.  **Check Berkala**: Jalankan `system:prepare --check` untuk memastikan integritas file tetap terjaga.
4.  **Sebelum Deploy**: Jalankan `system:prepare --production` untuk audit keamanan.

---

_Developed with Passion by WireNinja._
