<div class="jaya-brand">
    @if (!empty($name))
        {{-- Pengguna cafe: huruf awal berwarna tema cafe + nama cafe --}}
        <span class="jaya-logo" style="background: {{ $color }}">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
        <span class="jaya-brand-name">{{ $name }}</span>
    @else
        {{-- Halaman masuk dan super admin: logo Mawra POS --}}
        <img src="{{ asset('images/brand/mawra-icon.png') }}" alt="" width="36" height="36" style="width:36px;height:36px;border-radius:10px;display:block">
        <span class="jaya-brand-name">Mawra POS</span>
    @endif
</div>
