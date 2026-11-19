<?php

namespace App\Services;

use App\Enums\PaperStatus;
use App\Models\Committee;
use App\Models\Paper;
use App\Models\Review;
use App\Models\Sector;
use App\Support\Duration;
use Illuminate\Support\Facades\DB;

/** Aggregations for the executive oversight dashboard. */
class OversightReport
{
    /** @return array<string, mixed> */
    public function build(): array
    {
        $statusCounts = Paper::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = [];
        foreach (PaperStatus::cases() as $status) {
            $byStatus[$status->value] = (int) ($statusCounts[$status->value] ?? 0);
        }

        $reviewDays = Review::completed()
            ->get(['assigned_at', 'completed_at'])
            ->map(fn ($r) => Duration::days($r->assigned_at, $r->completed_at));

        $publicationDays = Paper::published()
            ->get(['submitted_at', 'published_at'])
            ->map(fn ($p) => Duration::days($p->submitted_at, $p->published_at));

        $monthly = Paper::published()
            ->where('published_at', '>=', now()->subMonths(11)->startOfMonth())
            ->pluck('published_at')
            ->countBy(fn ($d) => $d->format('Y-m'));

        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $months[$key] = (int) ($monthly[$key] ?? 0);
        }

        return [
            'total_papers' => array_sum($byStatus),
            'by_status' => $byStatus,
            'avg_review_days' => Duration::average($reviewDays),
            'avg_publication_days' => Duration::average($publicationDays),
            'overdue_reviews' => Review::pending()
                ->where('assigned_at', '<', now()->subDays(config('standardspress.review_overdue_days', 14)))
                ->count(),
            'stalled_papers' => Paper::where('status', PaperStatus::UnderReview->value)
                ->where('submitted_at', '<', now()->subDays(config('standardspress.paper_overdue_days', 30)))
                ->count(),
            'published_by_month' => $months,
            'committees' => Committee::withCount(['papers', 'publishedPapers'])
                ->orderByDesc('published_papers_count')
                ->orderBy('name')
                ->get(),
            'sectors' => Sector::withCount(['papers as published_papers_count' => fn ($q) => $q->where('papers.status', PaperStatus::Published->value)])
                ->orderByDesc('published_papers_count')
                ->orderBy('name')
                ->get(),
        ];
    }
}
