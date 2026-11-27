<?php

namespace App\Http\Controllers;

use App\Models\Cafe;
use App\Models\Outlet;
use App\Models\User;
use App\Services\CafeTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

// Pendaftaran mandiri: owner membuat cafe dan akunnya sendiri, tanpa menunggu super admin.
class RegisterCafeController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/admin');
        }

        return view('auth.daftar', ['templates' => CafeTemplates::TEMPLATES]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Jebakan bot: kolom "website" tersembunyi untuk manusia. Bila terisi, pura-pura berhasil.
        if ($request->filled('website')) {
            return redirect('/');
        }

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'cafe_name' => ['required', 'string', 'min:2', 'max:60'],
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'pin' => ['required', 'digits:4'],
            'template' => ['required', 'in:' . implode(',', array_keys(CafeTemplates::TEMPLATES))],
            'terms' => ['accepted'],
        ], [
            'required' => ':attribute wajib diisi.',
            'min' => ':attribute terlalu pendek (minimal :min karakter).',
            'max' => ':attribute terlalu panjang.',
            'email' => 'Format email belum benar.',
            'unique' => ':attribute ini sudah terdaftar. Silakan masuk, atau pakai email lain.',
            'confirmed' => 'Konfirmasi password tidak sama.',
            'digits' => ':attribute harus :digits angka.',
            'in' => 'Pilihan :attribute tidak dikenal.',
            'accepted' => 'Centang persetujuan syarat dan kebijakan privasi dulu.',
        ], [
            'cafe_name' => 'Nama cafe',
            'name' => 'Nama pemilik',
            'email' => 'Email',
            'password' => 'Password',
            'pin' => 'PIN',
            'template' => 'Jenis usaha',
            'terms' => 'Persetujuan',
        ]);

        $user = DB::transaction(function () use ($data) {
            // Kode cafe dipakai di tablet untuk aktivasi, jadi dibuat otomatis dan unik
            $cafe = Cafe::create([
                'name' => trim($data['cafe_name']),
                'slug' => $this->makeSlug($data['cafe_name']),
                'plan' => 'free',
                'registered_via' => 'self',
                'receipt_footer' => 'Terima kasih sudah berkunjung',
            ]);

            $outlet = Outlet::create(['cafe_id' => $cafe->id, 'name' => 'Outlet Utama']);

            foreach (['owner', 'manager', 'cashier'] as $role) {
                Role::findOrCreate($role, 'web');
            }

            $owner = User::create([
                'name' => trim($data['name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'cafe_id' => $cafe->id,
                'outlet_id' => $outlet->id,
                'pin' => $data['pin'],
                'is_active' => true,
            ]);

            $owner->assignRole('owner');

            CafeTemplates::apply($cafe, $data['template']);

            return $owner;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/admin/mulai');
    }

    /** "Kopi Senja" -> "kopi-senja-x7k2" */
    private function makeSlug(string $name): string
    {
        $base = Str::slug(Str::limit($name, 30, ''));
        $base = $base !== '' ? $base : 'cafe';

        do {
            // Tanpa huruf dan angka yang mirip (0, o, 1, l, i) supaya mudah diketik di tablet
            $suffix = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 4);
            $slug = $base . '-' . $suffix;
        } while (Cafe::where('slug', $slug)->exists());

        return $slug;
    }
}
