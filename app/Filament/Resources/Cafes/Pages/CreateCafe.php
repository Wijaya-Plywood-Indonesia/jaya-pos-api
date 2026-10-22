<?php

namespace App\Filament\Resources\Cafes\Pages;

use App\Filament\Resources\Cafes\CafeResource;
use App\Models\Cafe;
use App\Models\Outlet;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateCafe extends CreateRecord
{
    protected static string $resource = CafeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Pisahkan data akun owner dari data cafe
        $owner = [
            'name' => $data['owner_name'],
            'email' => strtolower($data['owner_email']),
            'password' => $data['owner_password'],
            'pin' => $data['owner_pin'],
        ];

        unset(
            $data['owner_name'],
            $data['owner_email'],
            $data['owner_password'],
            $data['owner_pin'],
        );

        return DB::transaction(function () use ($data, $owner) {
            Role::findOrCreate('owner', 'web');

            $cafe = Cafe::create($data);

            $outlet = Outlet::create([
                'cafe_id' => $cafe->id,
                'name' => 'Outlet Utama',
            ]);

            User::create($owner + [
                'cafe_id' => $cafe->id,
                'outlet_id' => $outlet->id,
                'is_active' => true,
            ])->assignRole('owner');

            return $cafe;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}