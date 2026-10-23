<?php

namespace App\Filament\Pages;

use App\Models\Cafe;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use UnitEnum;

class CafeSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Pengelolaan';

    protected static ?string $navigationLabel = 'Pengaturan Cafe';

    protected static ?string $title = 'Pengaturan Cafe';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'pengaturan-cafe';

    protected string $view = 'filament.pages.cafe-settings';

    public ?array $data = [];

    private const FIELDS = [
        'name', 'receipt_footer', 'tax_percent', 'service_percent', 'rounding', 'theme_color',
    ];

    // Hanya owner cafe. Super admin mengatur cafe lewat menu "Cafe Pelanggan".
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->cafe_id !== null && $user->hasRole('owner');
    }

    public function getSubheading(): ?string
    {
        return 'Semua perubahan langsung terlihat di pratinjau struk.';
    }

    public function mount(): void
    {
        $this->form->fill(Arr::only($this->cafe()->toArray(), self::FIELDS));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama cafe')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 400)
                            ->columnSpanFull(),
                        TextInput::make('receipt_footer')
                            ->label('Pesan di bawah struk')
                            ->maxLength(255)
                            ->live(debounce: 400)
                            ->columnSpanFull(),
                        TextInput::make('tax_percent')
                            ->label('Pajak / PPN (%)')
                            ->numeric()->minValue(0)->maxValue(100)->required()
                            ->live(debounce: 400),
                        TextInput::make('service_percent')
                            ->label('Service charge (%)')
                            ->numeric()->minValue(0)->maxValue(100)->required()
                            ->live(debounce: 400),
                        Select::make('rounding')
                            ->label('Pembulatan')
                            ->options([
                                0 => 'Tanpa pembulatan',
                                100 => 'Ke 100 terdekat',
                                500 => 'Ke 500 terdekat',
                            ])
                            ->required()
                            ->live(),
                        ColorPicker::make('theme_color')
                            ->label('Warna tema'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $this->cafe()->update(Arr::only($state, self::FIELDS));

        Notification::make()->title('Pengaturan disimpan')->success()->send();
    }

    public function cafe(): Cafe
    {
        return Cafe::findOrFail(auth()->user()->cafe_id);
    }

    /** Data untuk pratinjau struk (contoh pesanan tetap, seperti mockup). */
    public function receipt(): array
    {
        $sub = 61000;
        $service = $sub * (float) ($this->data['service_percent'] ?? 0) / 100;
        $tax = ($sub + $service) * (float) ($this->data['tax_percent'] ?? 0) / 100;
        $raw = $sub + $service + $tax;
        $step = (int) ($this->data['rounding'] ?? 0);
        $total = $step ? round($raw / $step) * $step : $raw;

        return [
            'name' => $this->data['name'] ?? '',
            'address' => $this->cafe()->outlets()->value('address') ?: 'Jl. Contoh No. 1',
            'footer' => $this->data['receipt_footer'] ?? '',
            'sub' => $sub,
            'service' => $service,
            'tax' => $tax,
            'rounding' => $total - $raw,
            'total' => $total,
        ];
    }

    public function rp(float|int $n): string
    {
        return ($n < 0 ? '-' : '') . number_format(abs(round($n)), 0, ',', '.');
    }
}
