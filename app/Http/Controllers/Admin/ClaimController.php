<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClaimController extends Controller
{
    /**
     * GET /admin/revendicari — verified claims awaiting an ownership decision.
     */
    public function index(): Response
    {
        $claims = Claim::whereIn('status', [ClaimStatus::Verified->value, ClaimStatus::Pending->value])
            ->with(['clinic:id,name,slug', 'user:id,name,email'])
            ->latest()
            ->paginate(15)
            ->through(fn (Claim $claim) => [
                'id' => $claim->id,
                'status' => $claim->status->value,
                'status_label' => $claim->status->label(),
                'clinic' => $claim->clinic?->name,
                'user' => $claim->user?->name,
                'user_email' => $claim->user?->email,
                'claimant_email' => $claim->claimant_email,
                'message' => $claim->message,
                'created_at' => $claim->created_at->format('d.m.Y'),
            ]);

        return Inertia::render('admin/claims/Index', ['claims' => $claims]);
    }

    public function approve(Request $request, Claim $claim): RedirectResponse
    {
        $claim->approve($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Revendicare aprobată. Proprietarul a fost asignat.']);

        return back();
    }

    public function reject(Request $request, Claim $claim): RedirectResponse
    {
        $claim->reject($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Revendicare respinsă.']);

        return back();
    }
}
