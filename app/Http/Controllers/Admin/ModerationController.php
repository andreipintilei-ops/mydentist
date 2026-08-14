<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClinicStatus;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Notifications\ClinicModerated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    /**
     * GET /admin/moderare — clinics awaiting a publish decision.
     */
    public function index(): Response
    {
        $clinics = Clinic::withoutGlobalScope('excludeDemo')
            ->where('status', ClinicStatus::Pending->value)
            ->with('city:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn (Clinic $clinic) => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'city' => $clinic->city?->name,
                'address' => $clinic->address,
                'email' => $clinic->email,
                'phone' => $clinic->phone,
                'created_at' => $clinic->created_at->format('d.m.Y'),
                'geocoded' => (bool) ($clinic->lat && $clinic->lng),
            ]);

        return Inertia::render('admin/moderation/Index', ['clinics' => $clinics]);
    }

    public function approve(Clinic $clinic): RedirectResponse
    {
        $clinic->update(['status' => ClinicStatus::Published, 'verified_at' => now()]);

        $this->notifySubmitter($clinic, approved: true);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clinica a fost publicată.']);

        return back();
    }

    public function reject(Request $request, Clinic $clinic): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $clinic->update(['status' => ClinicStatus::Rejected]);

        $this->notifySubmitter($clinic, approved: false, reason: $validated['reason'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clinica a fost respinsă.']);

        return back();
    }

    private function notifySubmitter(Clinic $clinic, bool $approved, ?string $reason = null): void
    {
        if ($clinic->email) {
            Notification::route('mail', $clinic->email)
                ->notify(new ClinicModerated($clinic, $approved, $reason));
        }
    }
}
