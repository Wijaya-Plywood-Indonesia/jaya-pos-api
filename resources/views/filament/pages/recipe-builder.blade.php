<x-filament-panels::page>
    @php($s = $this->summary())

    <div class="jaya-card" style="max-width: 360px">
        <label class="jaya-mu" style="display:block; margin-bottom:6px; font-weight:600">Pilih menu</label>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="productId">
                @foreach ($this->products() as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    @if ($s)
        <div class="jaya-stats">
            <div class="jaya-card"><span class="jaya-mu">Harga jual</span><b>Rp {{ $this->rp($s['product']->price) }}</b></div>
            <div class="jaya-card"><span class="jaya-mu">HPP</span><b>Rp {{ $this->rp($s['hpp']) }}</b></div>
            <div class="jaya-card">
                <span class="jaya-mu">Margin</span>
                <b style="color: {{ $s['margin'] < 50 ? '#d7263d' : '#14b8a6' }}">{{ number_format($s['margin'], 0) }}%</b>
            </div>
        </div>

        <div class="jaya-card">
            <div class="jaya-tw">
                <table class="jaya-table">
                    <thead>
                        <tr><th>Bahan</th><th>Takaran</th><th>Harga/satuan</th><th>Biaya</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($s['rows'] as $row)
                            @php($cost = $row->ingredient?->cost_per_unit ?? 0)
                            @php($unit = $row->ingredient?->unit)
                            <tr wire:key="recipe-{{ $row->id }}">
                                <td class="m">{{ $row->ingredient?->name }}</td>
                                <td>{{ (float) $row->quantity }} {{ $unit }}</td>
                                <td>Rp {{ $this->rp($cost) }}/{{ $unit }}</td>
                                <td>Rp {{ $this->rp($row->quantity * $cost) }}</td>
                                <td style="text-align:right">
                                    <x-filament::link tag="button" color="danger" wire:click="removeRow({{ $row->id }})"
                                        wire:confirm="Hapus bahan ini dari resep?">Hapus</x-filament::link>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="jaya-mu">Belum ada resep untuk menu ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form wire:submit="addRow" class="jaya-addrow">
                <div>
                    <x-filament::input.wrapper :valid="! $errors->has('ingredientId')">
                        <x-filament::input.select wire:model="ingredientId">
                            <option value="">Pilih bahan...</option>
                            @foreach ($this->ingredients() as $i)
                                <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit }})</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    @error('ingredientId') <p class="jaya-mu" style="color:#d7263d">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-filament::input.wrapper :valid="! $errors->has('quantity')">
                        <x-filament::input type="number" step="any" min="0" placeholder="Takaran" wire:model="quantity" />
                    </x-filament::input.wrapper>
                    @error('quantity') <p class="jaya-mu" style="color:#d7263d">{{ $message }}</p> @enderror
                </div>
                <x-filament::button type="submit" color="gray">+ Bahan</x-filament::button>
            </form>
        </div>
    @else
        <div class="jaya-card jaya-mu">Belum ada menu. Tambahkan menu dulu di Katalog, lalu atur resepnya di sini.</div>
    @endif
</x-filament-panels::page>
