<?php

namespace Tests\Feature;

use App\Models\EditorialNote;
use App\Models\Paper;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BlindReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private User $editor;
    private Paper $paper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->author()->create();
        $this->editor = User::factory()->editor()->create();
        $reviewer = User::factory()->reviewer()->create([
            'name' => 'Dr Secret Reviewer',
            'email' => 'secret.reviewer@example.org',
            'organization' => 'Hidden Labs Inc',
        ]);

        $this->paper = Paper::factory()->underReview()->for($this->author, 'author')->create();

        Review::factory()->completed()->create([
            'paper_id' => $this->paper->id,
            'reviewer_id' => $reviewer->id,
            'comments_to_author' => 'PUBLIC-FEEDBACK-TEXT',
            'confidential_notes' => 'CONFIDENTIAL-NOTE-TEXT',
        ]);

        EditorialNote::factory()->create([
            'paper_id' => $this->paper->id,
            'user_id' => $this->editor->id,
            'body' => 'INTERNAL-EDITOR-NOTE-TEXT',
        ]);
    }

    public function test_author_sees_feedback_but_never_identities_or_internal_notes(): void
    {
        $this->actingAs($this->author)->get(route('papers.show', $this->paper))
            ->assertOk()
            ->assertSee('PUBLIC-FEEDBACK-TEXT')
            ->assertSee('Reviewer 1')
            ->assertDontSee('Dr Secret Reviewer')
            ->assertDontSee('secret.reviewer@example.org')
            ->assertDontSee('Hidden Labs Inc')
            ->assertDontSee('CONFIDENTIAL-NOTE-TEXT')
            ->assertDontSee('INTERNAL-EDITOR-NOTE-TEXT');
    }

    public function test_editor_sees_everything(): void
    {
        $this->actingAs($this->editor)->get(route('papers.show', $this->paper))
            ->assertOk()
            ->assertSee('Dr Secret Reviewer')
            ->assertSee('PUBLIC-FEEDBACK-TEXT')
            ->assertSee('CONFIDENTIAL-NOTE-TEXT')
            ->assertSee('INTERNAL-EDITOR-NOTE-TEXT');
    }

    public function test_executives_do_not_get_confidential_review_details(): void
    {
        $this->actingAs(User::factory()->executive()->create())->get(route('papers.show', $this->paper))
            ->assertOk()
            ->assertDontSee('Dr Secret Reviewer')
            ->assertDontSee('CONFIDENTIAL-NOTE-TEXT')
            ->assertDontSee('INTERNAL-EDITOR-NOTE-TEXT');
    }

    public function test_authors_do_not_see_pending_reviews(): void
    {
        $paper = Paper::factory()->underReview()->for($this->author, 'author')->create();
        Review::factory()->create(['paper_id' => $paper->id, 'comments_to_author' => 'DRAFT-NOT-SUBMITTED']);

        $this->actingAs($this->author)->get(route('papers.show', $paper))
            ->assertOk()
            ->assertDontSee('DRAFT-NOT-SUBMITTED');
    }

    public function test_the_public_api_never_leaks_reviews(): void
    {
        $this->paper->update(['status' => 'published', 'published_at' => now(), 'reference' => 'STD-2026-0001']);

        Sanctum::actingAs(User::factory()->subscriber()->create());

        $this->getJson('/api/v1/papers')
            ->assertOk()
            ->assertDontSee('Dr Secret Reviewer')
            ->assertDontSee('secret.reviewer@example.org')
            ->assertDontSee('PUBLIC-FEEDBACK-TEXT')
            ->assertDontSee('CONFIDENTIAL-NOTE-TEXT')
            ->assertDontSee('INTERNAL-EDITOR-NOTE-TEXT');
    }
}
