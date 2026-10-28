<?php

namespace Database\Seeders;

use App\Models\Build;
use App\Models\Component;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require database_path('seeders/component_catalog.php');

        DB::transaction(function () use ($catalog): void {
            Build::query()->delete();
            Component::query()->delete();

            $timestamp = now();
            $items = array_map(static fn (array $item): array => [
                'category' => $item['category'],
                'brand' => $item['brand'],
                'name' => $item['name'],
                'price' => $item['price'],
                'power_watts' => $item['power_watts'],
                'specs' => json_encode($item['specs'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $catalog);

            foreach (array_chunk($items, 100) as $chunk) {
                Component::query()->insert($chunk);
            }
        });
    }
}
