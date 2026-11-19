<?php

namespace App\Services;

use App\Enums\PaperStatus;
use App\Exceptions\InvalidPaperTransition;
use App\Models\Paper;
use App\Models\User;
use App\Support\ReferenceNumber;
use Illuminate\Support\Facades\DB;

/**
 * The single place where a paper's status may change.
 * Enforces the state machine, the business guards for each step, and writes the audit trail.
 */
class PaperWorkflow
{
    public function transition(Paper $paper, PaperStatus $to, ?User $actor = null, ?string $note = null): Paper
    {
        $from = $paper->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidPaperTransition::notAllowed($from, $to);
        }

        $this->guard($paper, $to);

        return DB::transaction(function () use ($paper, $from, $to, $actor, $note) {
            $attributes = match ($to) {
                PaperStatus::Draft => [
                    'status' => $to,
                    'submitted_at' => null,
                    'version' => $paper->version + 1,
                ],
                PaperStatus::UnderReview => ['status' => $to, 'submitted_at' => now()],
                PaperStatus::Approved => ['status' => $to, 'approved_at' => now()],
                PaperStatus::Published => [
                    'status' => $to,
                    'published_at' => now(),
                    'reference' => $paper->reference ?? $this->nextReference(),
                ],
            };

            $paper->update($attributes);

            $paper->histories()->create([
                'user_id' => $actor?->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
            ]);

            return $paper->refresh();
        });
    }

    private function guard(Paper $paper, PaperStatus $to): void
    {
        if ($to === PaperStatus::UnderReview) {
            if (! $paper->hasFile()) {
                throw InvalidPaperTransition::because('Upload the manuscript PDF before submitting for review.');
            }
            if ($paper->sectors()->count() === 0) {
                throw InvalidPaperTransition::because('Assign at least one technical sector before submitting.');
            }
        }

        if ($to === PaperStatus::Approved) {
            if ($paper->reviews()->count() === 0) {
                throw InvalidPaperTransition::because('At least one reviewer must be assigned and complete a review.');
            }
            if ($paper->reviews()->pending()->exists()) {
                throw InvalidPaperTransition::because('All assigned reviews must be completed before approval.');
            }
        }
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $sequence = Paper::whereYear('published_at', $year)->count();
        $prefix = config('standardspress.reference_prefix', 'STD');

        do {
            $sequence++;
            $reference = ReferenceNumber::format($year, $sequence, $prefix);
        } while (Paper::where('reference', $reference)->exists());

        return $reference;
    }
}
