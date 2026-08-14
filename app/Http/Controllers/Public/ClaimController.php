<?php

namespace App\Http\Controllers\Public;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Clinic;
use App\Notifications\VerifyClaimEmail;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ClaimController extends Controller
{
    /**
     * GET /clinica/{clinic}/revendica — form to request ownership (auth only).
     */
    public function create(Request $request, Clinic $clinic): View
    {
        Gate::authorize('claim', $clinic);

        $seo = SeoMeta::make("Revendică {$clinic->name}")->noindex();

        return view('public.claim', [
            'seo' => $seo,
            'clinic' => $clinic,
        ]);
    }

    /**
     * POST /clinica/{clinic}/revendica — create a pending claim and email a
     * verification link to the address the claimant says they control.
     */
    public function store(Request $request, Clinic $clinic): RedirectResponse
    {
        Gate::authorize('claim', $clinic);

        $validated = $request->validate([
            'claimant_email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $claim = Claim::create([
            'clinic_id' => $clinic->id,
            'user_id' => $request->user()->id,
            'claimant_email' => $validated['claimant_email'],
            'message' => $validated['message'] ?? null,
            'status' => ClaimStatus::Pending,
            'verification_token' => Str::random(64),
        ]);

        Notification::route('mail', $claim->claimant_email)->notify(new VerifyClaimEmail($claim));

        return redirect()
            ->route('clinic.show', $clinic)
            ->with('status', 'Ți-am trimis un email de confirmare. Verifică adresa pentru a continua revendicarea.');
    }

    /**
     * GET /revendicare/{token}/confirma — the email link. Marks the claim
     * verified; a moderator approves it afterwards.
     */
    public function verify(string $token): RedirectResponse
    {
        $claim = Claim::where('verification_token', $token)
            ->where('status', ClaimStatus::Pending)
            ->firstOrFail();

        $claim->markVerified();

        return redirect()
            ->route('clinic.show', $claim->clinic)
            ->with('status', 'Adresa a fost confirmată. Solicitarea ta va fi analizată de echipa noastră.');
    }
}
