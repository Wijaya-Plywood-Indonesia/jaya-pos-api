<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderSyncTest extends TestCase
{
    use RefreshDatabase;

    // PENGAMAN: hanya jalan di SQLite memori, tidak pernah di MySQL project.
    protected function setUp(): void
    {
        $driver = $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION');
        $database = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');

        if ($driver !== 'sqlite' || $database !== ':memory:') {
            $this->markTestSkipped(
                'Dilewati demi keamanan data: aktifkan DB_CONNECTION=sqlite dan DB_DATABASE=:memory: di phpunit.xml.'
            );
        }

        parent::setUp();
    }

    private function kasir(Cafe $cafe, string $name = 'Rina'): User
    {
        Role::findOrCreate('cashier', 'web');

        $user = User::create([
            'name' => $name,
            'email' => Str::slug($name) . '@' . $cafe->slug . '.test',
            'password' => 'password-rahasia',
            'pin' => '1111',
            'cafe_id' => $cafe->id,
            'is_active' => true,
        ]);
        $user->syncRoles(['cashier']);

        return $user;
    }

    private function openShift(string $id): void
    {
        $this->postJson('/api/v1/shifts', [
            'id' => $id,
            'opened_at' => '2026-10-26T01:00:00Z',
            'opening_cash' => 200000,
        ])->assertCreated();
    }

    private function payload(?string $shiftId, array $over = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid(),
            'order_number' => '261026-AB12-001',
            'shift_id' => $shiftId,
            'type' => 'dine_in',
            'status' => 'paid',
            'subtotal' => 44000,
            'discount_total' => 0,
            'service_amount' => 0,
            'tax_amount' => 4400,
            'rounding' => 0,
            'total' => 48400,
            'items' => [[
                'product_id' => null,
                'product_name' => 'Es Kopi Susu Jaya',
                'unit_price' => 22000,
                'quantity' => 2,
                'options_total' => 0,
                'line_total' => 44000,
                'note' => null,
                'options' => [['option_id' => null, 'option_name' => 'Less Sugar', 'extra_price' => 0]],
            ]],
            'payment' => ['method' => 'cash', 'amount' => 48400, 'cash_received' => 50000, 'change_amount' => 1600],
            'ordered_at' => '2026-10-26T03:00:00Z',
            'paid_at' => '2026-10-26T03:00:00Z',
        ], $over);
    }

    public function test_transaksi_tersimpan_dan_kiriman_ulang_tidak_dobel(): void
    {
        $cafe = Cafe::create(['name' => 'Cafe A', 'slug' => 'cafe-a']);
        Sanctum::actingAs($this->kasir($cafe), ['pos']);

        $shift = (string) Str::uuid();
        $this->openShift($shift);

        $body = $this->payload($shift);

        $this->postJson('/api/v1/orders', $body)->assertCreated()->assertJsonPath('status', 'created');
        $this->postJson('/api/v1/orders', $body)->assertOk()->assertJsonPath('status', 'duplicate');

        $this->assertSame(1, Order::withoutGlobalScopes()->count());

        $order = Order::withoutGlobalScopes()->with('items.options', 'payments')->first();
        $this->assertSame(48400, $order->total);
        $this->assertSame('paid', $order->status);
        $this->assertCount(1, $order->items);
        $this->assertSame('Less Sugar', $order->items[0]->options[0]->option_name);
        $this->assertSame('cash', $order->payments[0]->method);
        $this->assertNotNull($order->synced_at);
    }

    public function test_shift_belum_ada_ditolak(): void
    {
        $cafe = Cafe::create(['name' => 'Cafe A', 'slug' => 'cafe-a']);
        Sanctum::actingAs($this->kasir($cafe), ['pos']);

        $this->postJson('/api/v1/orders', $this->payload((string) Str::uuid()))
            ->assertStatus(422)
            ->assertJsonPath('code', 'shift_missing');

        $this->assertSame(0, Order::withoutGlobalScopes()->count());
    }

    public function test_nomor_struk_bentrok_diberi_akhiran(): void
    {
        $cafe = Cafe::create(['name' => 'Cafe A', 'slug' => 'cafe-a']);
        Sanctum::actingAs($this->kasir($cafe), ['pos']);

        $shift = (string) Str::uuid();
        $this->openShift($shift);

        $this->postJson('/api/v1/orders', $this->payload($shift))->assertCreated();
        $this->postJson('/api/v1/orders', $this->payload($shift))->assertCreated();

        $numbers = Order::withoutGlobalScopes()->pluck('order_number')->all();
        $this->assertCount(2, array_unique($numbers));
    }

    public function test_kasir_cafe_lain_tidak_dipakai(): void
    {
        $a = Cafe::create(['name' => 'Cafe A', 'slug' => 'cafe-a']);
        $b = Cafe::create(['name' => 'Cafe B', 'slug' => 'cafe-b']);

        $kasirA = $this->kasir($a, 'Rina');
        $kasirB = $this->kasir($b, 'Orang Lain');

        Sanctum::actingAs($kasirA, ['pos']);
        $shift = (string) Str::uuid();
        $this->openShift($shift);

        $this->postJson('/api/v1/orders', $this->payload($shift, ['cashier_id' => $kasirB->id]))->assertCreated();

        $order = Order::withoutGlobalScopes()->first();
        $this->assertSame($kasirA->id, $order->cashier_id);
        $this->assertSame($a->id, $order->cafe_id);
    }

    public function test_id_milik_cafe_lain_ditolak(): void
    {
        $a = Cafe::create(['name' => 'Cafe A', 'slug' => 'cafe-a']);
        $b = Cafe::create(['name' => 'Cafe B', 'slug' => 'cafe-b']);

        Sanctum::actingAs($this->kasir($b, 'Orang Lain'), ['pos']);
        $shiftB = (string) Str::uuid();
        $this->openShift($shiftB);
        $body = $this->payload($shiftB);
        $this->postJson('/api/v1/orders', $body)->assertCreated();

        Sanctum::actingAs($this->kasir($a), ['pos']);
        $this->postJson('/api/v1/orders', $this->payload(null, ['id' => $body['id']]))->assertStatus(409);
    }

    public function test_shift_tertutup_tidak_bisa_dibuka_lagi(): void
    {
        $cafe = Cafe::create(['name' => 'Cafe A', 'slug' => 'cafe-a']);
        Sanctum::actingAs($this->kasir($cafe), ['pos']);

        $id = (string) Str::uuid();
        $this->openShift($id);

        $this->postJson('/api/v1/shifts', [
            'id' => $id,
            'opened_at' => '2026-10-26T01:00:00Z',
            'opening_cash' => 200000,
            'closed_at' => '2026-10-26T09:00:00Z',
            'closing_cash' => 195000,
            'expected_cash' => 200000,
        ])->assertOk()->assertJsonPath('status', 'updated');

        $shift = Shift::withoutGlobalScopes()->find($id);
        $this->assertSame(-5000, $shift->cash_difference);

        // Kiriman lama (tanpa data tutup) tidak boleh membuka shift itu kembali
        $this->postJson('/api/v1/shifts', [
            'id' => $id,
            'opened_at' => '2026-10-26T01:00:00Z',
            'opening_cash' => 200000,
        ])->assertOk()->assertJsonPath('status', 'unchanged');

        $this->assertNotNull(Shift::withoutGlobalScopes()->find($id)->closed_at);
    }

    public function test_tanpa_token_ditolak(): void
    {
        $this->postJson('/api/v1/orders', [])->assertUnauthorized();
        $this->postJson('/api/v1/shifts', [])->assertUnauthorized();
    }
}