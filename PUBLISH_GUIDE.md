# Panduan Publish Library Prasmanan

Dokumen ini menjelaskan langkah-langkah untuk memisahkan library `wireninja/prasmanan` dari folder local project dan mempublikasikannya agar bisa diinstal via Composer di project lain.

---

## 1. Persiapan Repositori Git Terpisah

Karena saat ini library berada di dalam folder `packages/prasmanan`, kamu harus memindahkannya ke repositori Git tersendiri agar Packagist bisa membacanya.

### Langkah-langkah:
1.  Buat repositori baru di GitHub/GitLab (misal: `github.com/wireninja/prasmanan`).
2.  Buka terminal di folder library:
    ```bash
    cd packages/prasmanan
    ```
3.  Inisialisasi Git dan push ke repo baru:
    ```bash
    git init
    git add .
    git commit -m "initial release: prasmanan super booster"
    git branch -M main
    git remote add origin https://github.com/wireninja/prasmanan.git
    git push -u origin main
    ```

---

## 2. Pendaftaran ke Packagist (Public)

Jika ingin library ini bisa diinstal langsung dengan `composer require wireninja/prasmanan`:

1.  Buka [Packagist.org](https://packagist.org/).
2.  Login dengan akun GitHub-mu.
3.  Klik tombol **Submit**.
4.  Masukkan URL repositori GitHub kamu.
5.  Klik **Check** dan kemudian **Submit**.
6.  *Sangat Disarankan*: Setup **GitHub Service Hook** agar Packagist otomatis melakukan update setiap kali kamu melakukan push ke GitHub.

---

## 3. Versioning (Penting!)

Composer menggunakan Git Tags untuk menentukan versi library. Jangan lupa berikan tag setiap kali ada update:

```bash
git tag v1.0.0
git push origin v1.0.0
```

---

## 4. Cara Penggunaan di Project Lain

### A. Melalui Packagist (Public)
Cukup jalankan:
```bash
composer require wireninja/prasmanan
```

### B. Melalui Repositori Private (Tanpa Packagist)
Jika kamu ingin menyimpannya sebagai repo private di GitHub, tambahkan ini di `composer.json` project tujuan:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/wireninja/prasmanan.git"
    }
],
"require": {
    "wireninja/prasmanan": "dev-main"
}
```

### C. Melalui Path (Local Development)
Jika ingin mencoba di project local lain tanpa push ke internet:

```json
"repositories": [
    {
        "type": "path",
        "url": "../path/to/final-system/packages/prasmanan"
    }
],
"require": {
    "wireninja/prasmanan": "*"
}
```

---

## 5. Checklist Sebelum Publish
- [x] Pastikan `name` di `composer.json` adalah `wireninja/prasmanan`.
- [x] Pastikan namespace `autoload` sudah benar (`WireNinja\Prasmanan\`).
- [x] Pastikan Service Provider terdaftar di bagian `extra.laravel.providers`.
- [x] Pastikan semua file stub (Vite, CSS, Enums) sudah masuk ke dalam folder `stubs/`.

---

## 6. Penggunaan dengan GitHub Private

Jika library ini adalah hak milik intelektual yang berharga, menyimpannya di GitHub Private adalah pilihan yang tepat.

**Cara Akses:**
1.  **SSH Key**: Pastikan server/PC yang menjalankan composer punya akses SSH ke GitHub kamu.
2.  **SAT (Service Auth Token)**: Kamu bisa memasukkan token di `auth.json` jika menggunakan CI/CD.

**Konfigurasi Composer Project land:**
```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:wireninja/prasmanan.git"
    }
]
```

---

## 7. Pertimbangan Lisensi (License-Based)

### A. Tanpa Sistem Lisensi (Private Only)
Jika kamu hanya menggunakannya untuk internal atau klien terpilih, cukup simpan di **Private Repo**. Ini adalah cara paling simpel dan aman.

### B. Dengan Sistem Lisensi (Commercial)
Jika kamu berniat menjual package ini seperti *Nova* atau *Spark*, pendekatannya berbeda:
1.  **Satis / Private Packagist**: Kamu butuh server repositori composer sendiri (seperti [Private Packagist](https://packagist.com/)).
2.  **Licensing Guard**: Di dalam code library, kamu bisa menambahkan pengecekan `LicenseKey` di Service Provider yang memvalidasi ke API server kamu.

**Saran Antigravity**: Untuk tahap awal, gunakan **GitHub Private Repo**. Jauh lebih hemat waktu dan biaya. Gunakan sistem lisensi hanya jika kamu sudah siap untuk memasarkannya secara publik/komersial luas.

---

_Diproses secara otomatis oleh Antigravity System._
