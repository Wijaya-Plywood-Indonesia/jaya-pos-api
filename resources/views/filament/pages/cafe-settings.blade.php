<x-filament-panels::page>
    @php($r = $this->receipt())

    <div class="jaya-grid">
        <div>
            <form wire:submit="save">
                {{ $this->form }}

                <div style="margin-top: 16px">
                    <x-filament::button type="submit">Simpan pengaturan</x-filament::button>
                </div>
            </form>
        </div>

        <div class="jaya-card">
            <b>Pratinjau struk</b>

            <div class="jaya-receipt">
                <b class="c">{{ $r['name'] }}</b>
                <span class="c">{{ $r['address'] }}</span>
                <hr>
                <div><span>2x Es Kopi Susu</span><span>44.000</span></div>
                <div><span>1x Kentang Goreng</span><span>17.000</span></div>
                <hr>
                <div><span>Subtotal</span><span>{{ $this->rp($r['sub']) }}</span></div>
                <div><span>Service</span><span>{{ $this->rp($r['service']) }}</span></div>
                <div><span>Pajak</span><span>{{ $this->rp($r['tax']) }}</span></div>
                <div><span>Pembulatan</span><span>{{ $this->rp($r['rounding']) }}</span></div>
                <hr>
                <div class="m"><span>TOTAL</span><span>Rp {{ $this->rp($r['total']) }}</span></div>
                <hr>
                <span class="c">{{ $r['footer'] }}</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
