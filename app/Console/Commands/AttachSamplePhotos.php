<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Pasang foto contoh ke menu yang sudah ada (cafe yang dibuat sebelum fitur foto contoh).
 * Tanpa --force hanya menu yang belum punya foto. Dengan --force foto diganti (hati-hati: foto upload owner ikut tertimpa).
 */
class AttachSamplePhotos extends Command
{
    protected $signature = 'jaya:attach-sample-photos {--all : Termasuk menu non-contoh yang namanya cocok}
        {--force : Ganti foto yang sudah ada dengan file terbaru di resources/sample-images}';

    protected $description = 'Pasang foto contoh ke menu yang belum punya foto';

    public function handle(): int
    {
        $query = Product::withoutGlobalScopes();

        if (! $this->option('all')) {
            $query->where('is_sample', true);
        }

        $done = 0;

        foreach ($query->get() as $product) {
            if (! $this->option('force') && $product->hasMedia('photo')) {
                continue;
            }

            $path = resource_path('sample-images/'.Str::slug($product->name).'.jpg');

            if (! is_file($path)) {
                $this->line("Lewati {$product->name} (tidak ada gambar {$path})");

                continue;
            }

            try {
                $product->addMedia($path)->preservingOriginal()->toMediaCollection('photo');
                $this->info("OK: {$product->name}");
                $done++;
            } catch (\Throwable $e) {
                $this->error("Gagal {$product->name}: {$e->getMessage()}");
            }
        }

        $this->info("Selesai. {$done} menu diberi foto.");

        return self::SUCCESS;
    }
}