<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Paper;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/papers')->assertRedirect('/login');
        $this->get('/oversight')->assertRedirect('/login');
    }

    public function test_public_catalog_is_open_to_guests(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_every_role_can_open_its_dashboard(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/dashboard')->assertOk();
        }
    }

    public function test_oversight_is_limited_to_executives_and_editors(): void
    {
        foreach ([UserRole::Author, UserRole::Reviewer, UserRole::Subscriber] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/oversight')->assertForbidden();
        }

        foreach ([UserRole::Executive, UserRole::Editor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/oversight')->assertOk();
        }
    }

    public function test_only_authors_can_create_papers(): void
    {
        $this->actingAs(User::factory()->subscriber()->create())->get('/papers/create')->assertForbidden();
        $this->actingAs(User::factory()->editor()->create())->get('/papers/create')->assertForbidden();
        $this->actingAs(User::factory()->author()->create())->get('/papers/create')->assertOk();
    }

    public function test_sector_management_requires_the_editor_gate(): void
    {
        $this->actingAs(User::factory()->author()->create())->get('/sectors')->assertForbidden();
        $this->actingAs(User::factory()->executive()->create())->get('/sectors')->assertForbidden();
        $this->actingAs(User::factory()->editor()->create())->get('/sectors')->assertOk();
    }

    public function test_user_administration_is_admin_only(): void
    {
        $this->actingAs(User::factory()->editor()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->executive()->create())->get('/admin/users')->assertOk();
    }

    public function test_executive_can_change_a_role_but_not_their_own(): void
    {
        $exec = User::factory()->executive()->create();
        $target = User::factory()->author()->create();

        $this->actingAs($exec)
            ->put(route('admin.users.update', $target), ['role' => 'reviewer'])
            ->assertSessionHasNoErrors();
        $this->assertSame(UserRole::Reviewer, $target->fresh()->role);

        $this->actingAs($exec)
            ->put(route('admin.users.update', $exec), ['role' => 'author'])
            ->assertSessionHasErrors('role');
        $this->assertSame(UserRole::Executive, $exec->fresh()->role);
    }

    public function test_non_admins_cannot_change_roles(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->put(route('admin.users.update', $author), ['role' => 'editor'])
            ->assertForbidden();
    }

    public function test_api_token_page_is_hidden_from_authors_and_reviewers(): void
    {
        $this->actingAs(User::factory()->reviewer()->create())->get('/api-tokens')->assertForbidden();
        $this->actingAs(User::factory()->author()->create())->get('/api-tokens')->assertForbidden();
        $this->actingAs(User::factory()->subscriber()->create())->get('/api-tokens')->assertOk();
    }

    public function test_navigation_only_shows_links_the_role_may_use(): void
    {
        $this->actingAs(User::factory()->author()->create())->get('/dashboard')
            ->assertSee('New submission')
            ->assertDontSee('Oversight')
            ->assertDontSee('API tokens');

        $this->actingAs(User::factory()->executive()->create())->get('/dashboard')
            ->assertSee('Oversight')
            ->assertDontSee('New submission');
    }

    public function test_reviewer_cannot_open_an_unassigned_paper(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $paper = Paper::factory()->underReview()->create();

        $this->actingAs($reviewer)->get(route('papers.show', $paper))->assertForbidden();
    }

    public function test_reviewer_can_open_an_assigned_paper(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $paper = Paper::factory()->underReview()->create();
        Review::factory()->create(['paper_id' => $paper->id, 'reviewer_id' => $reviewer->id]);

        $this->actingAs($reviewer)->get(route('papers.show', $paper))->assertOk();
    }

    public function test_authors_cannot_view_or_edit_other_authors_drafts(): void
    {
        $other = User::factory()->author()->create();
        $paper = Paper::factory()->create();

        $this->actingAs($other)->get(route('papers.show', $paper))->assertForbidden();
        $this->actingAs($other)->get(route('papers.edit', $paper))->assertForbidden();
        $this->actingAs($other)->delete(route('papers.destroy', $paper))->assertForbidden();
    }

    public function test_submitted_papers_are_locked_for_editing(): void
    {
        $author = User::factory()->author()->create();
        $paper = Paper::factory()->underReview()->for($author, 'author')->create();

        $this->actingAs($author)->get(route('papers.edit', $paper))->assertForbidden();
    }

    public function test_paper_listing_is_scoped_by_role(): void
    {
        $author = User::factory()->author()->create();
        $mine = Paper::factory()->for($author, 'author')->create(['title' => 'My own draft']);
        $theirs = Paper::factory()->create(['title' => 'Someone elses draft']);
        $published = Paper::factory()->published()->create(['title' => 'Public standard']);

        $this->actingAs($author)->get('/papers')
            ->assertSee('My own draft')
            ->assertDontSee('Someone elses draft')
            ->assertDontSee('Public standard');

        $this->actingAs(User::factory()->subscriber()->create())->get('/papers')
            ->assertSee('Public standard')
            ->assertDontSee('My own draft');

        $this->actingAs(User::factory()->editor()->create())->get('/papers')
            ->assertSee('My own draft')
            ->assertSee('Someone elses draft');
    }
}
