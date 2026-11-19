<?php

namespace Tests\Feature;

use App\Models\Committee;
use App\Models\Paper;
use App\Models\Review;
use App\Models\User;
use App\Services\OversightReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OversightReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_empty_state_without_errors(): void
    {
        $report = app(OversightReport::class)->build();

        $this->assertSame(0, $report['total_papers']);
        $this->assertNull($report['avg_review_days']);
        $this->assertNull($report['avg_publication_days']);
        $this->assertCount(12, $report['published_by_month']);
    }

    public function test_average_review_turnaround_and_publication_time(): void
    {
        Review::factory()->completed()->create([
            'assigned_at' => now()->subDays(30),
            'completed_at' => now()->subDays(20),   // 10 days
        ]);
        Review::factory()->completed()->create([
            'assigned_at' => now()->subDays(40),
            'completed_at' => now()->subDays(20),   // 20 days
        ]);
        Review::factory()->create(); // pending: ignored for turnaround

        Paper::factory()->published()->create([
            'submitted_at' => now()->subDays(60),
            'published_at' => now()->subDays(30),   // 30 days
        ]);

        $report = app(OversightReport::class)->build();

        $this->assertEqualsWithDelta(15.0, $report['avg_review_days'], 0.1);
        $this->assertEqualsWithDelta(30.0, $report['avg_publication_days'], 0.1);
    }

    public function test_overdue_reviews_and_stalled_papers_are_counted(): void
    {
        Review::factory()->create(['assigned_at' => now()->subDays(20)]);  // overdue (>14d)
        Review::factory()->create(['assigned_at' => now()->subDays(3)]);   // fine
        Paper::factory()->underReview()->create(['submitted_at' => now()->subDays(45)]); // stalled (>30d)

        $report = app(OversightReport::class)->build();

        $this->assertSame(1, $report['overdue_reviews']);
        $this->assertGreaterThanOrEqual(1, $report['stalled_papers']);
    }

    public function test_committee_footprint_ranks_by_published_output(): void
    {
        $big = Committee::factory()->create(['name' => 'Big Output Committee']);
        $small = Committee::factory()->create(['name' => 'Small Output Committee']);

        $bigAuthor = User::factory()->author()->create(['committee_id' => $big->id]);
        $smallAuthor = User::factory()->author()->create(['committee_id' => $small->id]);

        Paper::factory()->count(3)->published()->for($bigAuthor, 'author')->create();
        Paper::factory()->published()->for($smallAuthor, 'author')->create();

        $report = app(OversightReport::class)->build();

        $this->assertSame('Big Output Committee', $report['committees']->first()->name);
        $this->assertSame(3, $report['committees']->first()->published_papers_count);
        $this->assertSame(4, $report['by_status']['published']);
    }

    public function test_oversight_page_renders_for_an_executive(): void
    {
        Paper::factory()->count(3)->published()->withSectors()->withReviewHistory()->create();

        $this->actingAs(User::factory()->executive()->create())
            ->get('/oversight')
            ->assertOk()
            ->assertSee('Institutional publishing footprint');
    }
}
