<?php

namespace App\Console\Commands;

use App\Enums\ClinicStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildCityServiceStats extends Command
{
    protected $signature = 'stats:rebuild-city-service';

    protected $description = 'Recompute the city_service_stats table (published clinic counts per city × service).';

    public function handle(): int
    {
        // Count published clinics per (city, service). Demo clinics are counted
        // locally (they ARE the content in dev) but excluded in production, which
        // mirrors the Clinic model's global scope — done by hand here because the
        // query builder bypasses Eloquent scopes.
        $rows = DB::table('clinic_service as cs')
            ->join('clinics as c', 'c.id', '=', 'cs.clinic_id')
            ->where('c.status', ClinicStatus::Published->value)
            ->when(app()->environment('production'), fn ($q) => $q->where('c.is_demo', false))
            ->groupBy('c.city_id', 'cs.service_id')
            ->select('c.city_id', 'cs.service_id', DB::raw('COUNT(*) as clinic_count'))
            ->get();

        $now = now();

        $inserts = $rows->map(fn ($r) => [
            'city_id' => $r->city_id,
            'service_id' => $r->service_id,
            'clinic_count' => $r->clinic_count,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::transaction(function () use ($inserts) {
            // Full rebuild: simplest way to also drop pairs that fell to zero.
            DB::table('city_service_stats')->delete();

            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('city_service_stats')->insert($chunk);
            }
        });

        $this->info('Rebuilt city_service_stats: '.count($inserts).' city × service pairs with published clinics.');

        return self::SUCCESS;
    }
}
