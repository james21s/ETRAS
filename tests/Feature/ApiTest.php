<?php

namespace Tests\Feature;

use App\Models\Committee;
use App\Models\Paper;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $user = User::factory()->subscriber()->create();
        Sanctum::actingAs($user, ['papers:read']);

        return $user;
    }

    public function test_api_requires_a_token(): void
    {
        $this->getJson('/api/v1/papers')->assertUnauthorized();
        $this->getJson('/api/v1/sectors')->assertUnauthorized();
        $this->getJson('/api/v1/committees')->assertUnauthorized();
    }

    public function test_a_real_bearer_token_grants_access(): void
    {
        $token = User::factory()->subscriber()->create()->createToken('aggregator', ['papers:read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/papers')->assertOk();
        $this->withToken('invalid-token')->getJson('/api/v1/papers')->assertUnauthorized();
    }

    public function test_only_published_papers_are_listed_with_a_stable_structure(): void
    {
        Paper::factory()->count(3)->published()->withSectors()->create();
        Paper::factory()->create();               // draft
        Paper::factory()->underReview()->create();
        Paper::factory()->approved()->create();
        $this->signIn();

        $this->getJson('/api/v1/papers')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id', 'reference', 'title', 'abstract', 'status', 'version', 'published_at',
                    'authors' => [['name', 'organization']],
                    'committee',
                    'sectors' => [['id', 'name', 'slug']],
                    'file' => ['size_bytes', 'mime', 'download_url'],
                    'links' => ['self'],
                ]],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.status', 'published');
    }

    public function test_author_email_addresses_are_not_exposed(): void
    {
        $author = User::factory()->author()->create(['email' => 'private.author@example.org']);
        Paper::factory()->published()->for($author, 'author')->create();
        $this->signIn();

        $this->getJson('/api/v1/papers')->assertOk()->assertDontSee('private.author@example.org');
    }

    public function test_filter_by_sector_matches_any_selected_sector(): void
    {
        $civil = Sector::factory()->create(['name' => 'Civil', 'slug' => 'civil']);
        $energy = Sector::factory()->create(['name' => 'Energy', 'slug' => 'energy']);
        $marine = Sector::factory()->create(['name' => 'Marine', 'slug' => 'marine']);

        Paper::factory()->published()->create()->syncSectors([$civil->id, $energy->id]); // multi-disciplinary
        Paper::factory()->published()->create()->syncSectors([$energy->id]);
        Paper::factory()->published()->create()->syncSectors([$marine->id]);
        $this->signIn();

        $this->getJson('/api/v1/papers?sector[]=civil')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/papers?sector[]=energy')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/papers?sector[]=civil&sector[]=marine')->assertJsonCount(2, 'data');
    }

    public function test_filter_by_committee(): void
    {
        $committee = Committee::factory()->create(['slug' => 'structures']);
        $member = User::factory()->author()->create(['committee_id' => $committee->id]);

        Paper::factory()->published()->for($member, 'author')->create();
        Paper::factory()->published()->create();
        $this->signIn();

        $this->getJson('/api/v1/papers?committee=structures')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.committee.slug', 'structures');
    }

    public function test_free_text_search(): void
    {
        Paper::factory()->published()->create(['title' => 'Offshore wind foundation standard']);
        Paper::factory()->published()->create(['title' => 'Boiler inspection manual']);
        $this->signIn();

        $this->getJson('/api/v1/papers?q=offshore')->assertJsonCount(1, 'data');
    }

    public function test_date_range_filter(): void
    {
        Paper::factory()->published()->create(['published_at' => '2024-01-15 10:00:00']);
        Paper::factory()->published()->create(['published_at' => '2025-06-15 10:00:00']);
        $this->signIn();

        $this->getJson('/api/v1/papers?published_after=2025-01-01')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/papers?published_before=2024-12-31')->assertJsonCount(1, 'data');
    }

    public function test_pagination_and_per_page_validation(): void
    {
        Paper::factory()->count(5)->published()->create();
        $this->signIn();

        $this->getJson('/api/v1/papers?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5);

        $this->getJson('/api/v1/papers?per_page=500')->assertStatus(422);
    }

    public function test_show_returns_published_and_hides_unpublished(): void
    {
        $published = Paper::factory()->published()->create();
        $draft = Paper::factory()->create();
        $this->signIn();

        $this->getJson("/api/v1/papers/{$published->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $published->id)
            ->assertJsonPath('data.reference', $published->reference);

        $this->getJson("/api/v1/papers/{$draft->id}")->assertNotFound();
    }

    public function test_download_streams_the_published_pdf(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('papers/open.pdf', '%PDF-1.4 open access');
        $paper = Paper::factory()->published()->create(['file_path' => 'papers/open.pdf']);
        $this->signIn();

        $this->get("/api/v1/papers/{$paper->id}/download")
            ->assertOk()
            ->assertDownload()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_unpublished_files_cannot_be_downloaded_through_the_api(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('papers/draft.pdf', 'draft');
        $paper = Paper::factory()->underReview()->create(['file_path' => 'papers/draft.pdf']);
        $this->signIn();

        $this->getJson("/api/v1/papers/{$paper->id}/download")->assertNotFound();
    }

    public function test_sector_and_committee_endpoints(): void
    {
        $sector = Sector::factory()->create();
        $committee = Committee::factory()->create();
        $member = User::factory()->author()->create(['committee_id' => $committee->id]);
        Paper::factory()->published()->for($member, 'author')->create()->syncSectors([$sector->id]);
        $this->signIn();

        $this->getJson('/api/v1/sectors')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'published_papers_count']]])
            ->assertJsonPath('data.0.published_papers_count', 1);

        $this->getJson('/api/v1/committees')
            ->assertOk()
            ->assertJsonPath('data.0.published_papers_count', 1);
    }

    public function test_tokens_can_be_created_and_revoked_through_the_ui(): void
    {
        $user = User::factory()->subscriber()->create();

        $this->actingAs($user)->post(route('tokens.store'), ['name' => 'Scholar Aggregator'])
            ->assertSessionHas('new_token');
        $this->assertSame(1, $user->tokens()->count());

        $this->actingAs($user)->delete(route('tokens.destroy', $user->tokens()->first()->id));
        $this->assertSame(0, $user->tokens()->count());
    }
}
