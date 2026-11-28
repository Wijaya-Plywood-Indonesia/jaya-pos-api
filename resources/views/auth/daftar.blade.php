@extends('layouts.public')

@section('title', 'Daftar gratis · Mawra POS')

@section('head')
    <style>
        .wrap { max-width: 520px; margin: 18px auto 0; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 26px; }
        h1 { font-size: 24px; margin: 0 0 4px; }
        .sub { color: var(--muted); margin: 0 0 20px; font-size: 14px; }
        label.l { display: block; font-weight: 700; font-size: 13px; margin: 14px 0 6px; }
        input[type=text], input[type=email], input[type=password], input[inputmode=numeric] {
            width: 100%; padding: 12px 14px; border-radius: 11px; border: 1.5px solid var(--line);
            background: var(--field); color: var(--text); font-size: 15px; font-family: inherit;
        }
        input:focus { outline: none; border-color: var(--red); }
        .hint { color: var(--muted); font-size: 12.5px; margin-top: 5px; }
        .err { color: #dc2626; font-size: 13px; margin-top: 5px; font-weight: 600; }
        .tpl { display: grid; gap: 10px; }
        .tpl label { display: flex; gap: 12px; align-items: flex-start; border: 1.5px solid var(--line); border-radius: 13px; padding: 13px; cursor: pointer; background: var(--field); }
        .tpl label:has(input:checked) { border-color: var(--red); background: rgba(215,38,61,.07); }
        .tpl input { margin-top: 4px; accent-color: var(--red); }
        .tpl b { display: block; font-size: 14.5px; }
        .tpl span { color: var(--muted); font-size: 13px; }
        .terms { display: flex; gap: 10px; align-items: flex-start; margin-top: 18px; font-size: 13.5px; }
        .terms input { margin-top: 3px; accent-color: var(--red); }
        .submit { width: 100%; margin-top: 22px; }
        .hp { position: absolute; left: -9999px; height: 0; overflow: hidden; }
        .login { text-align: center; margin-top: 16px; font-size: 14px; color: var(--muted); }
    </style>
@endsection

@section('content')
    <div class="wrap">
        <div class="card">
            <h1>Daftar gratis</h1>
            <p class="sub">Buat cafe dan akun owner-mu. Sekitar 2 menit, tanpa kartu kredit.</p>

            <form method="POST" action="/daftar" novalidate>
                @csrf

                {{-- Jebakan bot, jangan diisi --}}
                <div class="hp" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <label class="l" for="cafe_name">Nama cafe</label>
                <input type="text" id="cafe_name" name="cafe_name" value="{{ old('cafe_name') }}" placeholder="Contoh: Kopi Senja" maxlength="60" required>
                @error('cafe_name')<div class="err">{{ $message }}</div>@enderror

                <label class="l" for="name">Nama kamu (pemilik)</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Nama lengkap" maxlength="80" required>
                @error('name')<div class="err">{{ $message }}</div>@enderror

                <label class="l" for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required>
                @error('email')<div class="err">{{ $message }}</div>@enderror

                <label class="l" for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
                <div class="hint">Minimal 8 karakter. Dipakai untuk masuk ke panel web.</div>
                @error('password')<div class="err">{{ $message }}</div>@enderror

                <label class="l" for="password_confirmation">Ulangi password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>

                <label class="l" for="pin">PIN owner (4 angka)</label>
                <input type="password" id="pin" name="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off" required>
                <div class="hint">Dipakai untuk masuk di aplikasi kasir dan menyetujui diskon manual. Jangan sama dengan PIN kasir.</div>
                @error('pin')<div class="err">{{ $message }}</div>@enderror

                <label class="l">Jenis usaha</label>
                <div class="tpl">
                    @foreach ($templates as $key => $tpl)
                        <label>
                            <input type="radio" name="template" value="{{ $key }}" @checked(old('template', 'kafe-kopi') === $key)>
                            <div><b>{{ $tpl['name'] }}</b><span>{{ $tpl['description'] }}</span></div>
                        </label>
                    @endforeach
                </div>
                @error('template')<div class="err">{{ $message }}</div>@enderror

                <label class="terms">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms'))>
                    <span>Saya setuju dengan <a href="/syarat" target="_blank">Syarat layanan</a> dan <a href="/privasi" target="_blank">Kebijakan privasi</a>.</span>
                </label>
                @error('terms')<div class="err">{{ $message }}</div>@enderror

                <button class="btn primary submit" type="submit">Buat cafe saya</button>
            </form>
        </div>

        <p class="login">Sudah punya akun? <a href="/admin">Masuk</a></p>
    </div>
@endsection
