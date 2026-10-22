<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CafeDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'owner', 'manager', 'cashier'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $cafe = Cafe::updateOrCreate(
            ['slug' => 'jaya-cafe'],
            [
                'name' => 'Jaya Cafe',
                'theme_color' => '#d7263d',
                'tax_percent' => 11,
                'service_percent' => 5,
                'rounding' => 100,
                'receipt_footer' => 'Terima kasih, sampai jumpa lagi!',
            ]
        );

        $outlet = Outlet::updateOrCreate(
            ['cafe_id' => $cafe->id, 'name' => 'Outlet Utama'],
            ['address' => 'Jl. Contoh No. 1']
        );

        // Akun demo: owner (PIN 1234) dan kasir (PIN 1111)
        $owner = User::firstOrCreate(
            ['email' => 'owner@jaya.test'],
            ['name' => 'Pak Jaya', 'password' => 'password', 'pin' => '1234',
             'cafe_id' => $cafe->id, 'outlet_id' => $outlet->id, 'is_active' => true]
        );
        $owner->syncRoles(['owner']);

        $kasir = User::firstOrCreate(
            ['email' => 'kasir@jaya.test'],
            ['name' => 'Rina', 'password' => 'password', 'pin' => '1111',
             'cafe_id' => $cafe->id, 'outlet_id' => $outlet->id, 'is_active' => true]
        );
        $kasir->syncRoles(['cashier']);

        // Kategori
        $cats = [];
        foreach (['Kopi', 'Non-Kopi', 'Makanan', 'Snack'] as $i => $name) {
            $cats[$name] = Category::updateOrCreate(
                ['cafe_id' => $cafe->id, 'name' => $name],
                ['sort_order' => $i]
            );
        }

        // Grup opsi
        $groups = [];
        $groupData = [
            'Gula'   => [false, [['Normal', 0], ['Less Sugar', 0], ['No Sugar', 0]]],
            'Es'     => [false, [['Normal', 0], ['Less Ice', 0], ['No Ice', 0]]],
            'Ukuran' => [false, [['Regular', 0], ['Large', 4000]]],
            'Topping' => [true,  [['Extra Shot', 5000], ['Boba', 6000], ['Whipped Cream', 4000]]],
        ];

        foreach ($groupData as $name => [$multiple, $options]) {
            $group = OptionGroup::updateOrCreate(
                ['cafe_id' => $cafe->id, 'name' => $name],
                ['is_multiple' => $multiple]
            );
            foreach ($options as $i => [$optName, $price]) {
                Option::updateOrCreate(
                    ['option_group_id' => $group->id, 'name' => $optName],
                    ['extra_price' => $price, 'sort_order' => $i]
                );
            }
            $groups[$name] = $group;
        }

        // Menu: [nama, kategori, harga, grup opsi]
        $menus = [
            ['Es Kopi Susu Jaya', 'Kopi', 22000, ['Gula', 'Es', 'Ukuran', 'Topping']],
            ['Americano', 'Kopi', 18000, ['Gula', 'Es', 'Ukuran', 'Topping']],
            ['Matcha Latte', 'Non-Kopi', 26000, ['Gula', 'Es', 'Ukuran']],
            ['Teh Tarik Dingin', 'Non-Kopi', 16000, ['Gula', 'Es']],
            ['Nasi Goreng Jaya', 'Makanan', 28000, []],
            ['Kentang Goreng', 'Snack', 17000, []],
        ];

        foreach ($menus as $i => [$name, $cat, $price, $groupNames]) {
            $product = Product::updateOrCreate(
                ['cafe_id' => $cafe->id, 'name' => $name],
                ['category_id' => $cats[$cat]->id, 'price' => $price,
                 'is_available' => true, 'sort_order' => $i]
            );
            $product->optionGroups()->sync(
                collect($groupNames)->map(fn ($g) => $groups[$g]->id)->all()
            );
        }

        // Meja
        foreach (['Meja 1', 'Meja 2', 'Meja 3', 'Meja 4'] as $name) {
            DiningTable::updateOrCreate(
                ['cafe_id' => $cafe->id, 'name' => $name],
                ['outlet_id' => $outlet->id, 'capacity' => 4]
            );
        }
    }
}