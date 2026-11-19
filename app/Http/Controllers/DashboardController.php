<?php

namespace App\Http\Controllers;

use App\Enums\PaperStatus;
use App\Models\Paper;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $data = ['role' => $user->role->value];

        if ($user->isAuthor()) {
            $counts = $user->papers()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
            $data['counts'] = collect(PaperStatus::cases())->mapWithKeys(fn ($s) => [$s->value => (int) ($counts[$s->value] ?? 0)]);
            $data['recent'] = $user->papers()->latest('updated_at')->limit(6)->get();
        } elseif ($user->isReviewer()) {
            $data['pending'] = Review::pending()->where('reviewer_id', $user->id)->with('paper')->orderBy('assigned_at')->get();
            $data['completedCount'] = Review::completed()->where('reviewer_id', $user->id)->count();
        } elseif ($user->isEditor()) {
            $underReview = fn () => Paper::where('status', PaperStatus::UnderReview->value);

            $data['needsReviewers'] = $underReview()->doesntHave('reviews')->with('author')->limit(8)->get();
            $data['awaitingReviews'] = $underReview()->whereHas('reviews', fn ($q) => $q->pending())->with('author')->limit(8)->get();
            $data['readyToApprove'] = $underReview()->whereHas('reviews')->whereDoesntHave('reviews', fn ($q) => $q->pending())->with('author')->limit(8)->get();
            $data['readyToPublish'] = Paper::where('status', PaperStatus::Approved->value)->with('author')->limit(8)->get();
        } elseif ($user->isExecutive()) {
            $data['published'] = Paper::published()->count();
            $data['inPipeline'] = Paper::whereIn('status', [PaperStatus::UnderReview->value, PaperStatus::Approved->value])->count();
        } else {
            $data['latest'] = Paper::published()->with(['author.committee', 'sectors'])->orderByDesc('published_at')->limit(6)->get();
        }

        return view('dashboard', $data);
    }
}
