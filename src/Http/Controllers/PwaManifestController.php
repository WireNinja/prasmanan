<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

final class PwaManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // Secara umum vite-plugin-pwa menaruh hasil build di public/build/manifest.webmanifest
        // ataupun manifest.json. Kita cek bila ini ada.
        $webManifestPath = public_path('build/manifest.webmanifest');
        $jsonManifestPath = public_path('manifest.json');

        if (File::exists($webManifestPath)) {
            $content = json_decode(File::get($webManifestPath), true);

            return response()->json($content);
        }

        if (File::exists($jsonManifestPath)) {
            $content = json_decode(File::get($jsonManifestPath), true);

            return response()->json($content);
        }

        return response()->json([
            'name' => config('app.name', 'Prasmanan App'),
            'short_name' => config('app.name', 'Prasmanan'),
            'theme_color' => '#ffffff',
            'background_color' => '#ffffff',
            'display' => 'standalone',
            'scope' => '/',
            'start_url' => '/',
        ]);
    }
}
