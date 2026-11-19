<?php

namespace App\Policies;

use App\Enums\PaperStatus;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isReviewer();
    }

    public function view(User $user, Review $review): bool
    {
        return $review->reviewer_id === $user->id || $user->isEditor();
    }

    /** A reviewer can complete only their own pending review on a paper that is still under review. */
    public function update(User $user, Review $review): bool
    {
        return $review->reviewer_id === $user->id
            && $review->isPending()
            && $review->paper->status === PaperStatus::UnderReview;
    }
}
