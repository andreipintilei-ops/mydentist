<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    /**
     * GET /admin/recenzii — reviews awaiting moderation.
     */
    public function index(): Response
    {
        $reviews = Review::where('status', 'pending')
            ->with('clinic:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn (Review $review) => [
                'id' => $review->id,
                'clinic' => $review->clinic?->name,
                'author_name' => $review->author_name,
                'rating' => $review->rating,
                'body' => $review->body,
                'created_at' => $review->created_at->format('d.m.Y'),
            ]);

        return Inertia::render('admin/reviews/Index', ['reviews' => $reviews]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['status' => 'published', 'published_at' => now()]);
        $review->clinic->recalculateRating();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recenzie publicată.']);

        return back();
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->update(['status' => 'rejected']);
        $review->clinic->recalculateRating();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recenzie ascunsă.']);

        return back();
    }
}
