# Panduan Publish Library Prasmanan

Dokumen ini menjelaskan langkah-langkah untuk mempublikasikan library `wireninja/prasmanan` secara publik ke Packagist agar bisa diinstal via Composer di project manapun.

---

## 1. Pendaftaran ke Packagist (Public)

Agar library ini bisa diinstal langsung dengan `composer require wireninja/prasmanan`:

1.  Pastikan repositori GitHub kamu sudah disetel ke **Public**.
2.  Buka [Packagist.org](https://packagist.org/).
3.  Login dengan akun GitHub-mu.
4.  Klik tombol **Submit**.
5.  Masukkan URL repositori GitHub: `https://github.com/WireNinja/prasmanan`
6.  Klik **Check** dan kemudian **Submit**.
7.  **Sangat Disarankan**: Setup **GitHub Service Hook** (Webhooks) agar Packagist otomatis melakukan update setiap kali kamu melakukan push ke GitHub.

---

## 2. Versioning

Composer menggunakan Git Tags untuk menentukan versi library. Jangan lupa berikan tag setiap kali ada update besar:

```bash
git tag v1.0.0
git push origin v1.0.0
```

---

## 3. Cara Penggunaan di Project Lain

Setelah terdaftar di Packagist, cukup jalankan:

```bash
composer require wireninja/prasmanan
```

Untuk development lokal di project monorepo, kamu bisa tetap menggunakan relasi `path`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/prasmanan"
    }
]
```

---

## 4. Checklist Sebelum Update
- [x] Pastikan `name` di `composer.json` adalah `wireninja/prasmanan`.
- [x] Pastikan namespace `autoload` sudah benar (`WireNinja\Prasmanan\`).
- [x] Pastikan Service Provider terdaftar di bagian `extra.laravel.providers`.
- [x] Pastikan semua file stub (Vite, CSS, Enums) sudah masuk ke dalam folder `stubs/`.

---

_Diproses secara otomatis oleh Antigravity System._
