<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Paper;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Editors route papers to reviewers inside the platform (replaces email attachments). */
class PaperReviewerController extends Controller
{
    public function store(Request $request, Paper $paper): RedirectResponse
    {
        Gate::authorize('assignReviewers', $paper);

        $data = $request->validate([
            'reviewer_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Reviewer->value),
            ],
        ]);

        Review::firstOrCreate(
            ['paper_id' => $paper->id, 'reviewer_id' => $data['reviewer_id']],
            ['status' => Review::PENDING, 'assigned_at' => now()],
        );

        return back()->with('status', 'Reviewer assigned.');
    }

    public function destroy(Paper $paper, Review $review): RedirectResponse
    {
        Gate::authorize('assignReviewers', $paper);
        abort_unless($review->paper_id === $paper->id, 404);

        if ($review->isCompleted()) {
            return back()->withErrors(['workflow' => 'A completed review cannot be removed.']);
        }

        $review->delete();

        return back()->with('status', 'Reviewer removed.');
    }
}
