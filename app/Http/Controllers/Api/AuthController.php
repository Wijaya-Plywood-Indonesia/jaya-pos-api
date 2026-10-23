<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cafe;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Daftar staf aktif untuk layar login (hanya nama dan role, tanpa data sensitif)
    public function staff(string $slug): JsonResponse
    {
        $cafe = Cafe::where('slug', $slug)->where('is_active', true)->first();

        if (! $cafe) {
            return response()->json(['message' => 'Kode cafe tidak ditemukan.'], 404);
        }

        $staff = User::with('roles')
            ->where('cafe_id', $cafe->id)
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role' => $u->getRoleNames()->first(),
            ]);

        return response()->json([
            'cafe' => $this->cafePayload($cafe),
            'staff' => $staff,
        ]);
    }

    public function pinLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cafe' => ['required', 'string'],
            'user_id' => ['required', 'integer'],
            'pin' => ['required', 'string', 'digits_between:4,6'],
            'device_name' => ['required', 'string', 'max:60'],
        ]);

        $cafe = Cafe::where('slug', $data['cafe'])->where('is_active', true)->first();

        $user = $cafe
            ? User::with('roles')
                ->where('id', $data['user_id'])
                ->where('cafe_id', $cafe->id)
                ->where('is_active', true)
                ->first()
            : null;

        // Pesan sengaja dibuat sama agar tidak membocorkan mana yang salah
        if (! $user || ! $user->pin || ! Hash::check($data['pin'], $user->pin)) {
            return response()->json(['message' => 'PIN salah.'], 422);
        }

        $token = $user->createToken($data['device_name'], ['pos'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
            'cafe' => $this->cafePayload($cafe),
        ]);
    }

    // Data untuk login offline di tablet: daftar staf + hash PIN KASIR saja.
    // PIN owner dan manajer tidak pernah dikirim keluar dari server.
    public function offlineStaff(Request $request): JsonResponse
    {
        $me = $request->user()->load('cafe');

        abort_unless($me->cafe_id && $me->cafe?->is_active, 403, 'Akun ini tidak terhubung ke cafe aktif.');

        $users = User::with('roles')
            ->where('cafe_id', $me->cafe_id)
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->orderBy('name')
            ->get();

        $staff = $users->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'role' => $u->getRoleNames()->first(),
            'outlet_id' => $u->outlet_id,
        ])->values();

        $pins = [];
        foreach ($users as $u) {
            // Hanya akun yang murni kasir
            if (! $u->hasRole('cashier') || $u->hasAnyRole(['owner', 'manager', 'super_admin'])) {
                continue;
            }

            $hash = $u->pin;

            // Hanya hash bcrypt yang valid
            if (is_string($hash) && preg_match('/^\$2[aby]\$/', $hash)) {
                $pins[$u->id] = $hash;
            }
        }

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'staff' => $staff,
            'pins' => (object) $pins,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles', 'cafe');

        return response()->json([
            'user' => $this->userPayload($user),
            'cafe' => $user->cafe ? $this->cafePayload($user->cafe) : null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->getRoleNames()->first(),
            'outlet_id' => $user->outlet_id,
        ];
    }

    // Pengaturan cafe yang dibutuhkan aplikasi (pajak, struk, tema)
    private function cafePayload(Cafe $cafe): array
    {
        return [
            'id' => $cafe->id,
            'name' => $cafe->name,
            'slug' => $cafe->slug,
            'theme_color' => $cafe->theme_color,
            'tax_percent' => (float) $cafe->tax_percent,
            'service_percent' => (float) $cafe->service_percent,
            'rounding' => (int) $cafe->rounding,
            'receipt_footer' => $cafe->receipt_footer,
        ];
    }
}