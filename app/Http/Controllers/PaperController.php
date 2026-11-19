<?php

namespace App\Http\Controllers;

use App\Enums\PaperStatus;
use App\Http\Requests\StorePaperRequest;
use App\Http\Requests\UpdatePaperRequest;
use App\Models\Paper;
use App\Models\Sector;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\PaperFileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaperController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Paper::class);

        $status = PaperStatus::tryFrom((string) $request->query('status'));

        $papers = Paper::query()
            ->with(['author.committee', 'sectors'])
            ->visibleTo($request->user())
            ->when($status, fn ($q) => $q->where('papers.status', $status->value))
            ->search($request->query('q'))
            ->latest('papers.updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('papers.index', compact('papers', 'status'));
    }

    public function create(): View
    {
        Gate::authorize('create', Paper::class);

        return view('papers.create', ['sectors' => Sector::orderBy('name')->get()]);
    }

    public function store(StorePaperRequest $request, PaperFileStorage $files): RedirectResponse
    {
        $paper = DB::transaction(function () use ($request, $files) {
            $paper = $request->user()->papers()->create([
                'title' => $request->validated('title'),
                'abstract' => $request->validated('abstract'),
                'status' => PaperStatus::Draft,
            ]);

            if ($request->hasFile('manuscript')) {
                $paper->update($files->store($request->file('manuscript')));
            }

            $paper->syncSectors($request->validated('sectors'));

            $paper->histories()->create([
                'user_id' => $request->user()->id,
                'from_status' => null,
                'to_status' => PaperStatus::Draft,
                'note' => 'Draft created',
            ]);

            return $paper;
        });

        return redirect()->route('papers.show', $paper)->with('status', 'Draft saved.');
    }

    public function show(Request $request, Paper $paper): View
    {
        Gate::authorize('view', $paper);

        $paper->load(['author.committee', 'sectors', 'histories.user']);

        $feedback = null;
        $availableReviewers = collect();
        $allSectors = collect();

        if (Gate::allows('viewConfidential', $paper)) {
            // Editors: full picture, including reviewer identities and internal notes.
            $paper->load(['reviews.reviewer', 'notes.user']);
            $availableReviewers = User::where('role', UserRole::Reviewer->value)
                ->whereNotIn('id', $paper->reviews->pluck('reviewer_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'organization']);
            $allSectors = Sector::orderBy('name')->get();
        } elseif ($paper->status !== PaperStatus::Draft && Gate::allows('viewFeedback', $paper)) {
            // Authors: anonymised feedback only. reviewer_id / confidential_notes are never loaded.
            $feedback = $paper->reviews()
                ->completed()
                ->orderBy('completed_at')
                ->get(['id', 'recommendation', 'score', 'comments_to_author', 'completed_at'])
                ->values()
                ->map(fn ($review, $i) => [
                    'label' => 'Reviewer '.($i + 1),
                    'recommendation' => $review->recommendation,
                    'score' => $review->score,
                    'comments' => $review->comments_to_author,
                    'completed_at' => $review->completed_at,
                ]);
        }

        return view('papers.show', [
            'paper' => $paper,
            'feedback' => $feedback,
            'availableReviewers' => $availableReviewers,
            'allSectors' => $allSectors,
        ]);
    }

    public function edit(Paper $paper): View
    {
        Gate::authorize('update', $paper);

        $paper->load('sectors');

        return view('papers.edit', ['paper' => $paper, 'sectors' => Sector::orderBy('name')->get()]);
    }

    public function update(UpdatePaperRequest $request, Paper $paper, PaperFileStorage $files): RedirectResponse
    {
        DB::transaction(function () use ($request, $paper, $files) {
            $paper->update([
                'title' => $request->validated('title'),
                'abstract' => $request->validated('abstract'),
            ]);

            if ($request->hasFile('manuscript')) {
                $old = $paper->file_path;
                $paper->update($files->store($request->file('manuscript')));
                $files->delete($old);
            }

            $paper->syncSectors($request->validated('sectors'));
        });

        return redirect()->route('papers.show', $paper)->with('status', 'Draft updated.');
    }

    public function destroy(Paper $paper, PaperFileStorage $files): RedirectResponse
    {
        Gate::authorize('delete', $paper);

        $path = $paper->file_path;
        $paper->delete();
        $files->delete($path);

        return redirect()->route('papers.index')->with('status', 'Paper deleted.');
    }
}
