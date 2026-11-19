<?php

namespace Tests\Feature;

use App\Enums\PaperStatus;
use App\Models\Paper;
use App\Models\Review;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaperWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function draftFor(User $author, array $attributes = []): Paper
    {
        $paper = Paper::factory()->for($author, 'author')->create($attributes);
        $paper->syncSectors([Sector::factory()->create()->id]);

        return $paper;
    }

    public function test_author_can_submit_a_draft_for_review(): void
    {
        $author = User::factory()->author()->create();
        $paper = $this->draftFor($author);

        $this->actingAs($author)->post(route('papers.submit', $paper))->assertSessionHasNoErrors();

        $paper->refresh();
        $this->assertSame(PaperStatus::UnderReview, $paper->status);
        $this->assertNotNull($paper->submitted_at);
        $this->assertDatabaseHas('paper_status_histories', [
            'paper_id' => $paper->id,
            'from_status' => 'draft',
            'to_status' => 'under_review',
            'user_id' => $author->id,
        ]);
    }

    public function test_submission_requires_a_manuscript(): void
    {
        $author = User::factory()->author()->create();
        $paper = $this->draftFor($author, ['file_path' => null]);

        $this->actingAs($author)->post(route('papers.submit', $paper))->assertSessionHasErrors('workflow');

        $this->assertSame(PaperStatus::Draft, $paper->fresh()->status);
    }

    public function test_submission_requires_at_least_one_sector(): void
    {
        $author = User::factory()->author()->create();
        $paper = Paper::factory()->for($author, 'author')->create();

        $this->actingAs($author)->post(route('papers.submit', $paper))->assertSessionHasErrors('workflow');
        $this->assertSame(PaperStatus::Draft, $paper->fresh()->status);
    }

    public function test_other_authors_cannot_submit_someone_elses_draft(): void
    {
        $paper = $this->draftFor(User::factory()->author()->create());

        $this->actingAs(User::factory()->author()->create())
            ->post(route('papers.submit', $paper))
            ->assertForbidden();
    }

    public function test_authors_cannot_drive_editorial_transitions(): void
    {
        $author = User::factory()->author()->create();
        $paper = Paper::factory()->underReview()->for($author, 'author')->create();

        $this->actingAs($author)
            ->post(route('papers.transition', $paper), ['to' => 'approved'])
            ->assertForbidden();
    }

    public function test_cannot_approve_without_completed_reviews(): void
    {
        $editor = User::factory()->editor()->create();
        $paper = Paper::factory()->underReview()->create();

        // no reviewers at all
        $this->actingAs($editor)->post(route('papers.transition', $paper), ['to' => 'approved'])
            ->assertSessionHasErrors('workflow');

        // a pending review blocks approval too
        Review::factory()->create(['paper_id' => $paper->id]);
        $this->actingAs($editor)->post(route('papers.transition', $paper), ['to' => 'approved'])
            ->assertSessionHasErrors('workflow');

        $this->assertSame(PaperStatus::UnderReview, $paper->fresh()->status);
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $editor = User::factory()->editor()->create();
        $paper = Paper::factory()->underReview()->create();

        $this->actingAs($editor)->post(route('papers.transition', $paper), ['to' => 'published'])
            ->assertSessionHasErrors('workflow');

        $this->assertSame(PaperStatus::UnderReview, $paper->fresh()->status);
    }

    public function test_editors_cannot_transition_drafts(): void
    {
        $editor = User::factory()->editor()->create();
        $paper = Paper::factory()->create();

        $this->actingAs($editor)->post(route('papers.transition', $paper), ['to' => 'under_review'])
            ->assertForbidden();
    }

    public function test_returning_a_paper_to_the_author_bumps_the_version(): void
    {
        $editor = User::factory()->editor()->create();
        $paper = Paper::factory()->underReview()->create();

        $this->actingAs($editor)->post(route('papers.transition', $paper), ['to' => 'draft', 'note' => 'Needs more data'])
            ->assertSessionHasNoErrors();

        $paper->refresh();
        $this->assertSame(PaperStatus::Draft, $paper->status);
        $this->assertSame(2, $paper->version);
        $this->assertNull($paper->submitted_at);
        $this->assertDatabaseHas('paper_status_histories', ['paper_id' => $paper->id, 'note' => 'Needs more data']);
    }

    public function test_full_editorial_lifecycle(): void
    {
        $author = User::factory()->author()->create();
        $editor = User::factory()->editor()->create();
        $reviewer = User::factory()->reviewer()->create();
        $paper = $this->draftFor($author);

        // 1. Author submits
        $this->actingAs($author)->post(route('papers.submit', $paper));
        $this->assertSame(PaperStatus::UnderReview, $paper->fresh()->status);

        // 2. Editor assigns a reviewer in-platform
        $this->actingAs($editor)->post(route('papers.reviewers.store', $paper), ['reviewer_id' => $reviewer->id])
            ->assertSessionHasNoErrors();
        $review = Review::where('paper_id', $paper->id)->firstOrFail();
        $this->assertTrue($review->isPending());

        // 3. Reviewer submits the review
        $this->actingAs($reviewer)->put(route('reviews.update', $review), [
            'recommendation' => 'accept',
            'score' => 5,
            'comments_to_author' => 'Excellent and rigorous work.',
            'confidential_notes' => 'Publish quickly.',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($review->fresh()->isCompleted());

        // 4. Editor approves, then publishes
        $this->actingAs($editor)->post(route('papers.transition', $paper), ['to' => 'approved'])->assertSessionHasNoErrors();
        $this->actingAs($editor)->post(route('papers.transition', $paper), ['to' => 'published'])->assertSessionHasNoErrors();

        $paper->refresh();
        $this->assertSame(PaperStatus::Published, $paper->status);
        $this->assertNotNull($paper->published_at);
        $this->assertMatchesRegularExpression('/^STD-\d{4}-\d{4}$/', $paper->reference);
        $this->assertCount(3, $paper->histories);
    }

    public function test_published_papers_are_final(): void
    {
        $editor = User::factory()->editor()->create();
        $paper = Paper::factory()->published()->create();

        $this->actingAs($editor)->post(route('papers.transition', $paper), ['to' => 'draft'])
            ->assertSessionHasErrors('workflow');
        $this->assertSame(PaperStatus::Published, $paper->fresh()->status);
    }

    public function test_reviewer_cannot_complete_someone_elses_review_or_a_review_twice(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $intruder = User::factory()->reviewer()->create();
        $paper = Paper::factory()->underReview()->create();
        $review = Review::factory()->create(['paper_id' => $paper->id, 'reviewer_id' => $reviewer->id]);

        $payload = ['recommendation' => 'accept', 'score' => 4, 'comments_to_author' => 'Looks solid to me.'];

        $this->actingAs($intruder)->put(route('reviews.update', $review), $payload)->assertForbidden();
        $this->actingAs($reviewer)->put(route('reviews.update', $review), $payload)->assertSessionHasNoErrors();
        $this->actingAs($reviewer)->put(route('reviews.update', $review), $payload)->assertForbidden();
    }

    public function test_only_editors_can_assign_reviewers(): void
    {
        $paper = Paper::factory()->underReview()->create();
        $reviewer = User::factory()->reviewer()->create();

        $this->actingAs(User::factory()->author()->create())
            ->post(route('papers.reviewers.store', $paper), ['reviewer_id' => $reviewer->id])
            ->assertForbidden();
    }

    public function test_editor_can_reindex_a_paper_under_multiple_sectors(): void
    {
        $editor = User::factory()->editor()->create();
        $paper = Paper::factory()->underReview()->create();
        $sectors = Sector::factory()->count(3)->create();

        $this->actingAs($editor)->put(route('papers.sectors.update', $paper), [
            'sectors' => $sectors->pluck('id')->all(),
        ])->assertSessionHasNoErrors();

        $this->assertCount(3, $paper->fresh()->sectors);
        $this->assertSame(1, $paper->sectors()->wherePivot('is_primary', true)->count());
    }
}
