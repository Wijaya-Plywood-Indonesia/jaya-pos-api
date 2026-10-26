<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OfflineStaffTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PENGAMAN: RefreshDatabase menghapus seluruh isi database yang dipakai.
     * Tes ini hanya boleh jalan di SQLite memori, tidak pernah di MySQL project.
     * Pemeriksaan dilakukan sebelum aplikasi dibuat, jadi tidak ada tabel yang tersentuh.
     */
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

    private function staff(Cafe $cafe, string $name, array $roles, string $pin): User
    {
        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        $user = User::create([
            'name' => $name,
            'email' => Str::slug($name) . '@' . $cafe->slug . '.test',
            'password' => 'password-rahasia',
            'pin' => $pin,
            'cafe_id' => $cafe->id,
            'is_active' => true,
        ]);

        $user->syncRoles($roles);

        return $user;
    }

    public function test_hash_hanya_untuk_kasir_murni_dan_hanya_satu_cafe(): void
    {
        $a = Cafe::create(['name' => 'Cafe A', 'slug' => 'cafe-a']);
        $b = Cafe::create(['name' => 'Cafe B', 'slug' => 'cafe-b']);

        $owner = $this->staff($a, 'Budi', ['owner'], '1234');
        $kasir = $this->staff($a, 'Rina', ['cashier'], '1111');
        $merangkap = $this->staff($a, 'Sari', ['cashier', 'manager'], '2222');
        $this->staff($b, 'Orang Lain', ['cashier'], '3333');

        Sanctum::actingAs($kasir, ['pos']);

        $response = $this->getJson('/api/v1/auth/offline-staff')->assertOk();

        // Hanya staf cafe A
        $names = collect($response->json('staff'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Budi', 'Rina', 'Sari'], $names);

        // Hash hanya untuk kasir murni
        $pins = $response->json('pins');
        $this->assertArrayHasKey((string) $kasir->id, $pins);
        $this->assertArrayNotHasKey((string) $owner->id, $pins);
        $this->assertArrayNotHasKey((string) $merangkap->id, $pins);

        // Hash cocok dengan PIN yang benar saja
        $this->assertTrue(password_verify('1111', $pins[(string) $kasir->id]));
        $this->assertFalse(password_verify('9999', $pins[(string) $kasir->id]));
    }

    public function test_cafe_nonaktif_ditolak(): void
    {
        $cafe = Cafe::create(['name' => 'Cafe Mati', 'slug' => 'cafe-mati', 'is_active' => false]);
        $kasir = $this->staff($cafe, 'Rina', ['cashier'], '1111');

        Sanctum::actingAs($kasir, ['pos']);

        $this->getJson('/api/v1/auth/offline-staff')->assertForbidden();
    }

    public function test_tanpa_token_ditolak(): void
    {
        $this->getJson('/api/v1/auth/offline-staff')->assertUnauthorized();
    }
}