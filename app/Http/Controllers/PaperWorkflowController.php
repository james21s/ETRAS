<?php

namespace App\Http\Controllers;

use App\Enums\PaperStatus;
use App\Exceptions\InvalidPaperTransition;
use App\Models\Paper;
use App\Services\PaperWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PaperWorkflowController extends Controller
{
    /** Author: Draft -> Under Review. */
    public function submit(Request $request, Paper $paper, PaperWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('submit', $paper);

        try {
            $workflow->transition($paper, PaperStatus::UnderReview, $request->user(), 'Submitted for review');
        } catch (InvalidPaperTransition $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('papers.show', $paper)->with('status', 'Paper submitted for review.');
    }

    /** Editor: Under Review -> Approved | Draft, Approved -> Published. */
    public function transition(Request $request, Paper $paper, PaperWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('transition', $paper);

        $data = $request->validate([
            'to' => ['required', Rule::enum(PaperStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $workflow->transition($paper, PaperStatus::from($data['to']), $request->user(), $data['note'] ?? null);
        } catch (InvalidPaperTransition $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('papers.show', $paper)
            ->with('status', 'Paper is now "'.$paper->fresh()->status->label().'".');
    }
}
