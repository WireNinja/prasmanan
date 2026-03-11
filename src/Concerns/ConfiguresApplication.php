<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Livewire\Livewire;
use WireNinja\Prasmanan\Exceptions\Monitoring\DiscardedAttributeViolationException;
use WireNinja\Prasmanan\Exceptions\Monitoring\LazyLoadingViolationException;
use WireNinja\Prasmanan\Exceptions\Monitoring\MessageSendingSignalException;
use WireNinja\Prasmanan\Exceptions\Monitoring\MessageSentSignalException;
use WireNinja\Prasmanan\Exceptions\Monitoring\MissingAttributeViolationException;
use WireNinja\Prasmanan\Exceptions\Monitoring\NotificationFailedException;
use WireNinja\Prasmanan\Exceptions\Monitoring\QueueJobFailedSignalException;
use WireNinja\Prasmanan\Exceptions\Monitoring\SlowJobException;
use WireNinja\Prasmanan\Exceptions\Monitoring\SlowQueryException;
use WireNinja\Prasmanan\Livewire\Synthesizers\BigDecimalSynth;

/**
 * @property Application $app
 */
trait ConfiguresApplication
{
    /*
    |--------------------------------------------------------------------------
    | Entry Point — Orkestrasi Konfigurasi Aplikasi
    |--------------------------------------------------------------------------
    |
    | Method ini adalah satu-satunya pintu masuk untuk seluruh konfigurasi.
    | Urutan pemanggilan PENTING: Security & Database harus selalu pertama
    | sebelum layer lain (Eloquent, Cache, Queue) yang bergantung pada koneksi
    | yang sudah aman dan terkonfigurasi dengan benar.
    |
    */
    protected function configureApplication(): void
    {
        $this->configureSecurity();
        $this->configureDatabase();
        $this->configureDate();
        $this->configureEloquent();
        $this->configureQueue();
        $this->configureNetwork();
        $this->configureCommunications();
        $this->configureRateLimiting();
        $this->configureVite();
        $this->configureLivewire();
        $this->configureFilament();
    }

    /*
    |--------------------------------------------------------------------------
    | Security — Lapisan Pertahanan Pertama
    |--------------------------------------------------------------------------
    |
    | Di production, kita matikan semua perintah destruktif pada database
    | (DROP, TRUNCATE, dll) untuk mencegah kecelakaan fatal lewat Artisan.
    | Ini adalah safety net terakhir sebelum data hilang permanen.
    |
    */
    protected function configureSecurity(): void
    {
        if ($this->app->isProduction()) {
            DB::prohibitDestructiveCommands();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Database — Kontrol Query & Koneksi
    |--------------------------------------------------------------------------
    |
    | Di production, query log dimatikan untuk menghemat memori. Query log
    | menampung seluruh query dalam array di memori selama satu request —
    | berbahaya jika ada request berat seperti ekspor laporan inventory besar.
    |
    | Slow query monitor (> 1 detik) dikomen sebagai opt-in, karena closure
    | DB::listen() dipanggil PER QUERY dan bisa menambah overhead jika
    | query per request sangat banyak.
    |
    */
    protected function configureDatabase(): void
    {
        if ($this->app->isProduction()) {
            DB::disableQueryLog();

            // Threshold bisa disesuaikan per kebutuhan project melalui config prasmanan.php
            DB::listen(function ($query): void {
                if ($query->time > config('prasmanan.monitoring.slow_query_threshold', 1000)) {
                    rescue(fn () => throw SlowQueryException::create($query->sql, $query->time, $query->connectionName));
                }
            });

            return;
        }

        // Di local/staging, query log boleh aktif untuk debugging via Telescope
        // atau Debugbar. Tapi tetap opt-in, bukan default aktif.
        // DB::enableQueryLog();
    }

    /*
    |--------------------------------------------------------------------------
    | Date — Immutability sebagai Standar
    |--------------------------------------------------------------------------
    |
    | CarbonImmutable dipakai agar setiap operasi tanggal menghasilkan
    | instance BARU, bukan memodifikasi instance yang sama. Ini mencegah
    | bug tersembunyi dimana satu method mengubah tanggal dan efeknya
    | muncul di tempat lain yang tidak terduga (side effect).
    |
    | Contoh bug yang dicegah:
    |   $start = now();
    |   $end = $start->addDays(7); // Dengan Carbon biasa, $start ikut berubah!
    |
    */
    protected function configureDate(): void
    {
        Date::use(CarbonImmutable::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Eloquent — Mode Ketat untuk Mencegah Bug Tersembunyi
    |--------------------------------------------------------------------------
    |
    | Model::unguard()      → Matikan mass-assignment protection secara global.
    |                         Kita percayakan validasi ke Form Request / DTO,
    |                         bukan ke $fillable yang sering lupa diupdate.
    |
    | Model::shouldBeStrict() → Aktifkan 3 proteksi sekaligus:
    |   1. LazyLoading akan throw Exception (dev) / log error (production)
    |   2. Akses atribut yang tidak ada akan throw Exception
    |   3. Assignment ke atribut yang di-discard akan throw Exception
    |
    | Di production, kita downgrade dari Exception menjadi Log::error()
    | supaya user tidak melihat error page, tapi tim dev tetap tahu ada masalah.
    |
    */
    protected function configureEloquent(): void
    {
        Model::unguard();
        Model::shouldBeStrict();

        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
                rescue(fn () => throw LazyLoadingViolationException::create($model::class, $relation));
            });

            Model::handleDiscardedAttributeViolationUsing(function (Model $model, array $keys): void {
                rescue(fn () => throw DiscardedAttributeViolationException::create($model::class, $keys));
            });

            Model::handleMissingAttributeViolationUsing(function (Model $model, string $key): void {
                rescue(fn () => throw MissingAttributeViolationException::create($model::class, $key));
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Queue — Monitoring Job & Strategi Kegagalan
    |--------------------------------------------------------------------------
    |
    | Queue adalah tulang punggung fitur async: kirim email massal (portal berita),
    | update stok (inventory), ekspor laporan (arsip), dll.
    |
    | Kita monitor 3 skenario kritis:
    |   1. Job MULAI diproses → catat waktu mulai untuk kalkulasi durasi
    |   2. Job SELESAI       → deteksi job lambat (> 10 detik) dan log peringatan
    |   3. Job GAGAL         → log error lengkap dengan exception message
    |
    | Kenapa tidak pakai Queue::before/after? Karena event JobProcessing dan
    | JobProcessed lebih eksplisit dan tidak bergantung pada magic property
    | injection ke object $job yang bisa menyebabkan memory leak.
    |
    */
    protected function configureQueue(): void
    {
        if (! $this->app->isProduction()) {
            return;
        }

        // Simpan waktu mulai di luar closure menggunakan WeakMap atau array statik
        // untuk menghindari memory leak akibat closure menangkap $startTimes terus tumbuh.
        $startTimes = [];

        Event::listen(JobProcessing::class, function (JobProcessing $event) use (&$startTimes): void {
            $jobId = $event->job->getJobId();

            if ($jobId !== null) {
                $startTimes[$jobId] = microtime(true);
            }
        });

        Event::listen(JobProcessed::class, function (JobProcessed $event) use (&$startTimes): void {
            $jobId = $event->job->getJobId();

            if ($jobId !== null && isset($startTimes[$jobId])) {
                $duration = (microtime(true) - $startTimes[$jobId]) * 1000;
                unset($startTimes[$jobId]); // Bersihkan setelah selesai untuk mencegah memory leak

                if ($duration > config('prasmanan.monitoring.slow_job_threshold', 10000)) {
                    rescue(fn () => throw SlowJobException::create($event->job->resolveName(), $duration, $event->job->getQueue(), $event->connectionName));
                }
            }
        });

        Event::listen(JobFailed::class, function (JobFailed $event): void {
            rescue(fn () => throw QueueJobFailedSignalException::create(
                $event->job->resolveName(),
                $event->job->getQueue(),
                $event->connectionName,
                $event->exception->getMessage()
            ));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Network — HTTPS Enforcement & HTTP Client Safety
    |--------------------------------------------------------------------------
    |
    | URL::forceHttps()          → Semua URL yang digenerate (route(), asset(), dll)
    |                               otomatis menggunakan HTTPS di production.
    |                               Penting saat app di-proxy oleh Nginx/Cloudflare.
    |
    | Http::preventStrayRequests() → Di local/staging, HTTP request ke URL yang
    |                               tidak di-mock/fake akan throw Exception.
    |                               Ini mencegah test yang tidak sengaja hit API
    |                               eksternal sungguhan (billing, SMS, dll).
    |
    */
    protected function configureNetwork(): void
    {
        URL::forceHttps($this->app->isProduction());
        Http::preventStrayRequests(! $this->app->isProduction());
    }

    /*
    |--------------------------------------------------------------------------
    | Communications — Audit Trail Email & Notifikasi
    |--------------------------------------------------------------------------
    |
    | Di production, kita catat semua aktivitas pengiriman komunikasi.
    | Tujuannya bukan untuk debug, tapi untuk AUDIT: "apakah email invoice
    | sudah terkirim ke pelanggan X pada tanggal Y?" bisa dijawab dari log.
    |
    | Hanya aktif di production karena di local kita pakai Mailtrap/Log driver
    | yang sudah punya mekanisme inspeksi sendiri.
    |
    */
    protected function configureCommunications(): void
    {
        if (! $this->app->isProduction()) {
            return;
        }

        Event::listen(MessageSending::class, function (MessageSending $event): void {
            rescue(fn () => throw MessageSendingSignalException::create(
                array_keys($event->message->getTo() ?? []),
                (string) $event->message->getSubject(),
                (string) config('mail.default')
            ));
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            rescue(fn () => throw MessageSentSignalException::create(
                array_keys($event->message->getTo() ?? []),
                (string) $event->message->getSubject()
            ));
        });

        Event::listen(NotificationFailed::class, function (NotificationFailed $event): void {
            rescue(fn () => throw NotificationFailedException::create(
                $event->notification::class,
                $event->channel,
                get_class($event->notifiable),
                method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : null
            ));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting — Proteksi dari Abuse & Brute Force
    |--------------------------------------------------------------------------
    |
    | Definisikan rate limiter yang dapat dipakai ulang di routes/api.php
    | atau middleware. Dua limiter standar yang hampir selalu dibutuhkan:
    |
    |   'api'    → Batasi request API per user/IP. Default 60 req/menit.
    |               Sesuaikan per project: inventory mungkin butuh lebih
    |               tinggi, portal berita mungkin butuh lebih rendah.
    |
    |   'login'  → Proteksi brute force pada endpoint login. 5 percobaan
    |               per menit per IP adalah standar yang aman.
    |
    | Limiter ini tidak otomatis aktif. Gunakan di route:
    |   Route::middleware('throttle:api')->group(...)
    |
    */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) config('prasmanan.monitoring.rate_limit.api', 60))
                ->by($request->user()?->id ?? $request->ip());
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute((int) config('prasmanan.monitoring.rate_limit.login', 5))
                ->by($request->ip());
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Vite — Optimasi Asset Loading
    |--------------------------------------------------------------------------
    |
    | Aggressive prefetching menggunakan <link rel="prefetch"> untuk semua
    | asset Vite saat halaman pertama dimuat. Ini mempercepat navigasi
    | halaman berikutnya karena asset sudah ada di browser cache.
    |
    | Cocok untuk aplikasi dengan banyak halaman (admin panel, portal berita)
    | tapi perlu dipertimbangkan ulang jika bandwidth pengguna terbatas.
    |
    */
    protected function configureVite(): void
    {
        Vite::useAggressivePrefetching();
    }

    /*
    |--------------------------------------------------------------------------
    | Livewire — Custom Synthesizer & Behavior Global
    |--------------------------------------------------------------------------
    |
    | BigDecimalSynth memungkinkan property Livewire bertipe BigDecimal
    | (dari library brick/money atau sejenis) untuk di-serialize dan
    | di-deserialize dengan benar saat wire:model digunakan.
    |
    | Tanpa synthesizer ini, nilai desimal presisi tinggi (harga, stok desimal,
    | nilai kurs) akan kehilangan presisi saat melalui JSON round-trip Livewire.
    | Kritis untuk project inventory dan keuangan.
    |
    */
    protected function configureLivewire(): void
    {
        Livewire::propertySynthesizer(BigDecimalSynth::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Filament — Konfigurasi Panel Admin
    |--------------------------------------------------------------------------
    |
    | Implementasi ada di AppServiceProvider atau FilamentServiceProvider
    | yang menggunakan trait ini. Dipisah agar konfigurasi panel (tema,
    | plugin, auth guard) tidak bercampur dengan konfigurasi framework.
    |
    */
    abstract protected function configureFilament(): void;
}
