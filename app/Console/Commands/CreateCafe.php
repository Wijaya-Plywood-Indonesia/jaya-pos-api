<?php

namespace App\Console\Commands;

use App\Models\Cafe;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateCafe extends Command
{
    protected $signature = 'cafe:create
        {name : Nama cafe}
        {slug : Kode cafe untuk aplikasi (huruf kecil, angka, tanda minus)}
        {owner : Nama owner}
        {email : Email owner untuk login web}';

    protected $description = 'Mendaftarkan cafe pelanggan baru beserta outlet utama dan akun owner';

    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = strtolower($this->argument('slug'));
        $ownerName = $this->argument('owner');
        $email = strtolower($this->argument('email'));

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->error('Kode cafe hanya boleh huruf kecil, angka, dan tanda minus, contoh: kopi-senja');

            return self::FAILURE;
        }

        if (Cafe::where('slug', $slug)->exists()) {
            $this->error("Kode cafe '{$slug}' sudah dipakai.");

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
            $this->error('Email tidak valid atau sudah terdaftar.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password login web owner (minimal 8 karakter)');
        if (strlen($password) < 8) {
            $this->error('Password minimal 8 karakter.');

            return self::FAILURE;
        }

        $pin = (string) $this->secret('PIN owner untuk aplikasi (4-6 angka)');
        if (! preg_match('/^\d{4,6}$/', $pin)) {
            $this->error('PIN harus 4 sampai 6 angka.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $slug, $ownerName, $email, $password, $pin) {
            Role::findOrCreate('owner', 'web');

            $cafe = Cafe::create([
                'name' => $name,
                'slug' => $slug,
            ]);

            $outlet = Outlet::create([
                'cafe_id' => $cafe->id,
                'name' => 'Outlet Utama',
            ]);

            $owner = User::create([
                'name' => $ownerName,
                'email' => $email,
                'password' => $password,
                'pin' => $pin,
                'cafe_id' => $cafe->id,
                'outlet_id' => $outlet->id,
                'is_active' => true,
            ]);

            $owner->assignRole('owner');
        });

        $this->info('Cafe berhasil didaftarkan.');
        $this->table(['Item', 'Nilai'], [
            ['Nama cafe', $name],
            ['Kode cafe (untuk aplikasi)', $slug],
            ['Owner', $ownerName],
            ['Email login web', $email],
            ['Panel web', url('/admin')],
        ]);

        return self::SUCCESS;
    }
}