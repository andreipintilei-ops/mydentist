<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * GET /admin/utilizatori — users and their roles.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->withCount('ownedClinics')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'owned_clinics' => $user->owned_clinics_count,
            ]);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => ['q' => $search],
            'roles' => collect(UserRole::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
            'canAssign' => $request->user()->isAdmin(),
        ]);
    }

    /**
     * PATCH /admin/utilizatori/{user}/rol — assign a role (admins only).
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', array_column(UserRole::cases(), 'value'))],
        ]);

        // Don't let an admin strip their own admin role and lock themselves out.
        abort_if($user->id === $request->user()->id && $validated['role'] !== UserRole::Admin->value, 422, 'Nu îți poți schimba propriul rol de administrator.');

        $user->forceFill(['role' => $validated['role']])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rolul a fost actualizat.']);

        return back();
    }
}
