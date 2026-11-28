<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mawra POS')</title>
    <meta name="description" content="@yield('description', 'Aplikasi kasir cafe yang mudah: pesan, bayar, cetak struk, stok otomatis, dan laporan. Tetap jalan saat internet putus.')">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Mawra POS')">
    <meta property="og:description" content="@yield('description', 'Aplikasi kasir cafe yang mudah: pesan, bayar, cetak struk, stok otomatis, dan laporan. Tetap jalan saat internet putus.')">
    <meta name="theme-color" content="#d7263d">
    <link rel="icon" type="image/png" href="/images/brand/favicon.png">
    <link rel="apple-touch-icon" href="/images/brand/apple-touch-icon.png">
    <meta property="og:image" content="{{ url('/images/brand/mawra-icon.png') }}">
    <style>
        :root {
            color-scheme: light;
            --red: #d7263d; --red-d: #b91c32;
            --bg: #f3f4f6; --card: #ffffff; --text: #111827; --muted: #6b7280; --line: #e5e7eb; --field: #f9fafb;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height: 1.5; }
        a { color: var(--red); }
        .top { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; max-width: 1040px; margin: 0 auto; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 17px; text-decoration: none; color: var(--text); }
        .logo { width: 32px; height: 32px; border-radius: 9px; display: block; }
        .company { display: block; margin: 0 auto 14px; width: min(300px, 70%); height: auto; }
        .top nav a { font-weight: 700; text-decoration: none; margin-left: 16px; }
        main { max-width: 1040px; margin: 0 auto; padding: 8px 20px 40px; }
        footer { max-width: 1040px; margin: 0 auto; padding: 20px; color: var(--muted); font-size: 13px; text-align: center; }
        footer a { color: var(--muted); margin: 0 8px; }
        .btn { display: inline-block; padding: 13px 22px; border-radius: 12px; font-weight: 700; font-size: 15px; text-decoration: none; border: 1.5px solid var(--red); cursor: pointer; font-family: inherit; }
        .btn.primary { background: var(--red); color: #fff; }
        .btn.primary:hover { background: var(--red-d); }
        .btn.ghost { background: transparent; color: var(--red); }
    </style>
    @yield('head')
</head>
<body>
    <header class="top">
        <a class="brand" href="/"><img class="logo" src="/images/brand/mawra-icon.png" alt="" width="32" height="32"> Mawra POS</a>
        <nav><a href="/admin">Masuk</a></nav>
    </header>

    <main>@yield('content')</main>

    <footer>
        <img class="company" src="/images/brand/mawra-logo.png" alt="PT Mawra Teknologi Indonesia" loading="lazy">
        <a href="/syarat">Syarat layanan</a>·<a href="/privasi">Kebijakan privasi</a>
        @php($contact = config('jaya.contact_email'))
        @if ($contact)·<a href="mailto:{{ $contact }}">{{ $contact }}</a>@endif
        <div style="margin-top:8px">© {{ date('Y') }} PT Mawra Teknologi Indonesia</div>
    </footer>
</body>
</html>
