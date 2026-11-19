<?php

namespace Tests\Feature;

use App\Models\Committee;
use App\Models\Paper;
use App\Models\Review;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_many_to_many_between_papers_and_sectors_with_pivot_data(): void
    {
        $paper = Paper::factory()->create();
        $sectors = Sector::factory()->count(3)->create();

        $paper->syncSectors($sectors->pluck('id')->all());

        $this->assertCount(3, $paper->sectors);
        $this->assertTrue((bool) $paper->sectors()->first()->pivot->is_primary);
        $this->assertSame(1, $paper->sectors()->wherePivot('is_primary', true)->count());
        $this->assertCount(1, $sectors->first()->papers);
        $this->assertDatabaseCount('paper_sector', 3);

        // re-indexing replaces the set
        $paper->syncSectors([$sectors->last()->id]);
        $this->assertCount(1, $paper->fresh()->sectors);
    }

    public function test_has_many_through_committee_users_papers(): void
    {
        $committee = Committee::factory()->create();
        $other = Committee::factory()->create();

        $a = User::factory()->author()->create(['committee_id' => $committee->id]);
        $b = User::factory()->author()->create(['committee_id' => $committee->id]);
        $outsider = User::factory()->author()->create(['committee_id' => $other->id]);

        Paper::factory()->count(2)->published()->for($a, 'author')->create();
        Paper::factory()->for($b, 'author')->create();                 // draft
        Paper::factory()->published()->for($outsider, 'author')->create();

        $this->assertCount(3, $committee->papers);
        $this->assertCount(2, $committee->publishedPapers);
        $this->assertCount(1, $other->publishedPapers);

        $counted = Committee::withCount(['papers', 'publishedPapers'])->find($committee->id);
        $this->assertSame(3, $counted->papers_count);
        $this->assertSame(2, $counted->published_papers_count);
    }

    public function test_reviewers_relationship_goes_through_the_reviews_table(): void
    {
        $paper = Paper::factory()->underReview()->create();
        $reviewers = User::factory()->count(2)->reviewer()->create();

        foreach ($reviewers as $reviewer) {
            Review::factory()->create(['paper_id' => $paper->id, 'reviewer_id' => $reviewer->id]);
        }

        $this->assertCount(2, $paper->reviewers);
        $this->assertCount(1, $reviewers->first()->reviewingPapers);
        $this->assertSame('pending', $paper->reviewers->first()->pivot->status);
    }

    public function test_a_reviewer_can_only_be_assigned_once_per_paper(): void
    {
        $paper = Paper::factory()->underReview()->create();
        $reviewer = User::factory()->reviewer()->create();

        Review::factory()->create(['paper_id' => $paper->id, 'reviewer_id' => $reviewer->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Review::factory()->create(['paper_id' => $paper->id, 'reviewer_id' => $reviewer->id]);
    }
}
