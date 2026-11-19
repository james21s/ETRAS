<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Review::class);

        $reviews = Review::where('reviewer_id', $request->user()->id)
            ->with('paper.sectors')
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderBy('assigned_at')
            ->paginate(15);

        return view('reviews.index', compact('reviews'));
    }

    public function edit(Review $review): View
    {
        Gate::authorize('view', $review);
        Gate::authorize('update', $review);

        $review->load('paper.sectors', 'paper.author');

        return view('reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('update', $review);

        $data = $request->validate([
            'recommendation' => ['required', Rule::in(array_keys(Review::RECOMMENDATIONS))],
            'score' => ['required', 'integer', 'between:1,5'],
            'comments_to_author' => ['required', 'string', 'min:10', 'max:10000'],
            'confidential_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $review->update($data + [
            'status' => Review::COMPLETED,
            'completed_at' => now(),
        ]);

        return redirect()->route('reviews.index')->with('status', 'Review submitted. Thank you.');
    }
}
