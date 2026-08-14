<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClinicStatus;
use App\Http\Controllers\Controller;
use App\Models\CityServiceStat;
use App\Models\Clinic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class SeoToolsController extends Controller
{
    /**
     * GET /admin/seo — maintenance actions + a thin-content report.
     */
    public function index(): Response
    {
        $threshold = (int) config('directory.thin_content_threshold');

        // city × service pairs that exist but sit below the publish threshold —
        // these are the pages that 301 to the city page instead of rendering.
        $thin = CityServiceStat::query()
            ->with(['city:id,name', 'service:id,name'])
            ->whereBetween('clinic_count', [1, $threshold - 1])
            ->orderByDesc('clinic_count')
            ->limit(50)
            ->get()
            ->map(fn (CityServiceStat $stat) => [
                'city' => $stat->city?->name,
                'service' => $stat->service?->name,
                'count' => $stat->clinic_count,
            ]);

        return Inertia::render('admin/seo/Index', [
            'threshold' => $threshold,
            'stats' => [
                'published' => Clinic::withoutGlobalScope('excludeDemo')->where('status', ClinicStatus::Published->value)->count(),
                'demo' => Clinic::withoutGlobalScope('excludeDemo')->where('is_demo', true)->count(),
                'stat_pairs' => CityServiceStat::count(),
            ],
            'thin' => $thin,
        ]);
    }

    public function rebuildStats(): RedirectResponse
    {
        Artisan::call('stats:rebuild-city-service');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Statisticile city × service au fost recalculate.']);

        return back();
    }

    public function purgeDemo(): RedirectResponse
    {
        Artisan::call('clinics:purge-demo', ['--force' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clinicile demo au fost șterse.']);

        return back();
    }
}
