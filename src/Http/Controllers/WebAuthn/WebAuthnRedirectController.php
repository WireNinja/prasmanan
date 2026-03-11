<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Http\Controllers\WebAuthn;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class WebAuthnRedirectController extends Controller
{
    /**
     * Tampilkan antarmuka untuk login menggunakan WebAuthn (Passkey)
     * atau kita lempar ke panel/auth Filament bila sudah di-handle di sana.
     */
    public function __invoke(): RedirectResponse
    {
        // TODO: Kita dapat mengarahkan (redirect) ke Filament Login Page
        // yang mana komponen LoginOptions Livewire sudah memiliki
        // interface untuk memicu WebAuthn Script bawaan Laragear.

        // Buat jaga-jaga apabila route ini di-hit, kita kembalikan ke sistem admin Filament
        return redirect()->to(url('/admin/login?webauthn=1'));
    }
}
