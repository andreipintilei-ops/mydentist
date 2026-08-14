<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Enums\ClinicStatus;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Clinic;
use App\Models\Review;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // Admin sees everything, including demo rows (which are purged before
        // launch anyway); withoutGlobalScope is a no-op locally, correct in prod.
        $byStatus = Clinic::withoutGlobalScope('excludeDemo')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('admin/Dashboard', [
            'kpis' => [
                'published' => (int) ($byStatus[ClinicStatus::Published->value] ?? 0),
                'pending' => (int) ($byStatus[ClinicStatus::Pending->value] ?? 0),
                'draft' => (int) ($byStatus[ClinicStatus::Draft->value] ?? 0),
                'rejected' => (int) ($byStatus[ClinicStatus::Rejected->value] ?? 0),
                'open_claims' => Claim::where('status', ClaimStatus::Verified->value)->count(),
                'pending_reviews' => Review::where('status', 'pending')->count(),
            ],
            'registrations' => $this->registrationsSeries(),
        ]);
    }

    /**
     * New clinics per week for the last 8 weeks — the overview chart series.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function registrationsSeries(): array
    {
        $start = Carbon::now()->startOfWeek()->subWeeks(7);

        $counts = Clinic::withoutGlobalScope('excludeDemo')
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn (Clinic $clinic) => $clinic->created_at->startOfWeek()->format('Y-m-d'))
            ->map->count();

        return collect(range(0, 7))->map(function (int $i) use ($start, $counts) {
            $week = $start->copy()->addWeeks($i);

            return [
                'label' => $week->format('d M'),
                'count' => (int) ($counts[$week->format('Y-m-d')] ?? 0),
            ];
        })->all();
    }
}
