<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property int $user_id
 * @property string $claimant_email
 * @property string|null $message
 * @property ClaimStatus $status
 * @property string|null $verification_token
 * @property Carbon|null $verified_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ?Clinic $clinic
 * @property-read ?User $user
 * @property-read ?User $reviewer
 */
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

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
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
