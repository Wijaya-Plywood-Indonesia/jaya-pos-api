<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Jaya POS')</title>
    <meta name="description" content="@yield('description', 'Aplikasi kasir cafe yang mudah: pesan, bayar, cetak struk, stok otomatis, dan laporan. Tetap jalan saat internet putus.')">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Jaya POS')">
    <meta property="og:description" content="@yield('description', 'Aplikasi kasir cafe yang mudah: pesan, bayar, cetak struk, stok otomatis, dan laporan. Tetap jalan saat internet putus.')">
    <meta name="theme-color" content="#d7263d">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%23d7263d'/%3E%3Ctext x='32' y='45' font-size='38' font-weight='800' text-anchor='middle' fill='white' font-family='Arial'%3EJ%3C/text%3E%3C/svg%3E">
    <style>
        :root {
            --red: #d7263d; --red-d: #b91c32;
            --bg: #f3f4f6; --card: #ffffff; --text: #111827; --muted: #6b7280; --line: #e5e7eb; --field: #f9fafb;
        }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0f1115; --card: #1b1e25; --text: #f3f4f6; --muted: #9ca3af; --line: #2d323c; --field: #14171c; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height: 1.5; }
        a { color: var(--red); }
        .top { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; max-width: 1040px; margin: 0 auto; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 17px; text-decoration: none; color: var(--text); }
        .logo { width: 32px; height: 32px; border-radius: 10px; background: var(--red); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; }
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
        <a class="brand" href="/"><span class="logo">J</span> Jaya POS</a>
        <nav><a href="/admin">Masuk</a></nav>
    </header>

    <main>@yield('content')</main>

    <footer>
        <a href="/syarat">Syarat layanan</a>·<a href="/privasi">Kebijakan privasi</a>
        @php($contact = config('jaya.contact_email'))
        @if ($contact)·<a href="mailto:{{ $contact }}">{{ $contact }}</a>@endif
        <div style="margin-top:8px">© {{ date('Y') }} Jaya POS</div>
    </footer>
</body>
</html>
