<?php

namespace App\Policies;

use App\Enums\PaperStatus;
use App\Models\Paper;
use App\Models\User;

class PaperPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // listings are scoped per role with Paper::visibleTo()
    }

    public function view(User $user, Paper $paper): bool
    {
        if ($paper->status === PaperStatus::Published) {
            return true;
        }

        if ($user->isEditor() || $user->isExecutive() || $paper->user_id === $user->id) {
            return true;
        }

        // Reviewers only ever see papers they have been assigned.
        return $user->isReviewer()
            && $paper->reviews()->where('reviewer_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAuthor();
    }

    public function update(User $user, Paper $paper): bool
    {
        return $paper->user_id === $user->id && $paper->status === PaperStatus::Draft;
    }

    public function delete(User $user, Paper $paper): bool
    {
        if ($paper->status === PaperStatus::Published) {
            return false;
        }

        return $user->isEditor()
            || ($paper->user_id === $user->id && $paper->status === PaperStatus::Draft);
    }

    /** Author sends a draft into review. */
    public function submit(User $user, Paper $paper): bool
    {
        return $paper->user_id === $user->id && $paper->status === PaperStatus::Draft;
    }

    /** Download the manuscript (same visibility as the paper itself). */
    public function download(User $user, Paper $paper): bool
    {
        return $this->view($user, $paper);
    }

    /** Editor-driven transitions (approve, return, publish). */
    public function transition(User $user, Paper $paper): bool
    {
        return $user->isEditor() && $paper->status !== PaperStatus::Draft;
    }

    public function assignReviewers(User $user, Paper $paper): bool
    {
        return $user->isEditor() && $paper->status === PaperStatus::UnderReview;
    }

    public function classify(User $user, Paper $paper): bool
    {
        return $user->isEditor();
    }

    public function addNote(User $user, Paper $paper): bool
    {
        return $user->isEditor();
    }

    /** Reviewer identities, confidential notes and internal editorial notes. */
    public function viewConfidential(User $user, Paper $paper): bool
    {
        return $user->isEditor();
    }

    /** Anonymised reviewer feedback shown to the author once the paper has left Draft. */
    public function viewFeedback(User $user, Paper $paper): bool
    {
        return $paper->user_id === $user->id;
    }
}
