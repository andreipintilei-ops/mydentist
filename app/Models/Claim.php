<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    protected $fillable = [
        'clinic_id', 'user_id', 'claimant_email', 'message',
        'status', 'verification_token', 'verified_at', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeAwaitingReview(Builder $query): Builder
    {
        return $query->where('status', ClaimStatus::Verified->value);
    }

    /**
     * Confirm the claimant controls the email. Clears the token so the link is
     * single-use, then the claim waits on a moderator.
     */
    public function markVerified(): void
    {
        $this->update([
            'status' => ClaimStatus::Verified,
            'verified_at' => now(),
            'verification_token' => null,
        ]);
    }

    /**
     * Grant ownership: point the clinic at the claimant and promote them to
     * clinic_owner (unless they already hold a higher role).
     */
    public function approve(User $moderator): void
    {
        $this->update([
            'status' => ClaimStatus::Approved,
            'reviewed_by' => $moderator->id,
            'reviewed_at' => now(),
        ]);

        $this->clinic->update(['claimed_by' => $this->user_id]);

        if ($this->user->role === UserRole::User) {
            $this->user->forceFill(['role' => UserRole::ClinicOwner])->save();
        }
    }

    public function reject(User $moderator): void
    {
        $this->update([
            'status' => ClaimStatus::Rejected,
            'reviewed_by' => $moderator->id,
            'reviewed_at' => now(),
        ]);
    }
}
