<!-- PWA Manifest -->
<link rel="manifest" href="{{ route('pwa.manifest') }}">

<!-- PWA Theme Color -->
<meta name="theme-color" content="#4B5563">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

<!-- PWA Icons -->
<link rel="apple-touch-icon" href="{{ asset('pwa/icons/apple-touch-icon-180x180.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('pwa/icons/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('pwa/icons/favicon-16x16.png') }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
