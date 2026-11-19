<?php

namespace Tests\Feature;

use App\Models\Paper;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaperUploadTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Seismic Design Guidelines for Bridges',
            'abstract' => 'A comprehensive guideline covering seismic design of long-span bridges.',
            'sectors' => [Sector::factory()->create()->id],
        ], $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_author_can_upload_a_pdf_manuscript(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)->post(route('papers.store'), $this->payload([
            'manuscript' => UploadedFile::fake()->create('guideline.pdf', 512, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        $paper = Paper::firstOrFail();
        $this->assertSame($author->id, $paper->user_id);
        $this->assertSame('guideline.pdf', $paper->file_original_name);
        $this->assertSame('application/pdf', $paper->file_mime);
        Storage::disk('local')->assertExists($paper->file_path);
    }

    public function test_non_pdf_files_are_rejected(): void
    {
        $this->actingAs(User::factory()->author()->create())->post(route('papers.store'), $this->payload([
            'manuscript' => UploadedFile::fake()->create('malware.exe', 10),
        ]))->assertSessionHasErrors('manuscript');

        $this->assertSame(0, Paper::count());
    }

    public function test_oversized_files_are_rejected(): void
    {
        $this->actingAs(User::factory()->author()->create())->post(route('papers.store'), $this->payload([
            'manuscript' => UploadedFile::fake()->create('huge.pdf', 25_000, 'application/pdf'),
        ]))->assertSessionHasErrors('manuscript');

        $this->assertSame(0, Paper::count());
    }

    public function test_title_abstract_and_sectors_are_required(): void
    {
        $this->actingAs(User::factory()->author()->create())
            ->post(route('papers.store'), [])
            ->assertSessionHasErrors(['title', 'abstract', 'sectors']);
    }

    public function test_non_authors_cannot_upload(): void
    {
        $this->actingAs(User::factory()->subscriber()->create())->post(route('papers.store'), $this->payload([
            'manuscript' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ]))->assertForbidden();
    }

    public function test_replacing_the_manuscript_removes_the_old_file(): void
    {
        $author = User::factory()->author()->create();
        Storage::disk('local')->put('papers/old.pdf', 'old');
        $paper = Paper::factory()->for($author, 'author')->create(['file_path' => 'papers/old.pdf']);

        $this->actingAs($author)->put(route('papers.update', $paper), $this->payload([
            'manuscript' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        $paper->refresh();
        Storage::disk('local')->assertMissing('papers/old.pdf');
        Storage::disk('local')->assertExists($paper->file_path);
        $this->assertSame('new.pdf', $paper->file_original_name);
    }

    public function test_deleting_a_draft_removes_its_file(): void
    {
        $author = User::factory()->author()->create();
        Storage::disk('local')->put('papers/x.pdf', 'x');
        $paper = Paper::factory()->for($author, 'author')->create(['file_path' => 'papers/x.pdf']);

        $this->actingAs($author)->delete(route('papers.destroy', $paper))->assertRedirect(route('papers.index'));

        Storage::disk('local')->assertMissing('papers/x.pdf');
        $this->assertSame(0, Paper::count());
    }

    public function test_the_seed_placeholder_pdf_is_never_deleted(): void
    {
        $author = User::factory()->author()->create();
        Storage::disk('local')->put(Paper::SEED_SAMPLE_PATH, 'sample');
        $paper = Paper::factory()->for($author, 'author')->create();

        $this->actingAs($author)->delete(route('papers.destroy', $paper));

        Storage::disk('local')->assertExists(Paper::SEED_SAMPLE_PATH);
    }

    /* ------------------------------- Downloads ------------------------------- */

    public function test_author_can_download_their_own_manuscript(): void
    {
        $author = User::factory()->author()->create();
        Storage::disk('local')->put('papers/mine.pdf', '%PDF-1.4 test');
        $paper = Paper::factory()->for($author, 'author')->create(['file_path' => 'papers/mine.pdf']);

        $this->actingAs($author)->get(route('papers.download', $paper))
            ->assertOk()
            ->assertDownload();
    }

    public function test_unrelated_users_cannot_download_a_draft(): void
    {
        Storage::disk('local')->put('papers/secret.pdf', 'secret');
        $paper = Paper::factory()->create(['file_path' => 'papers/secret.pdf']);

        $this->actingAs(User::factory()->author()->create())->get(route('papers.download', $paper))->assertForbidden();
        $this->actingAs(User::factory()->subscriber()->create())->get(route('papers.download', $paper))->assertForbidden();
    }

    public function test_guests_cannot_download(): void
    {
        $paper = Paper::factory()->published()->create();

        $this->get(route('papers.download', $paper))->assertRedirect('/login');
    }

    public function test_subscribers_can_download_published_papers(): void
    {
        Storage::disk('local')->put('papers/pub.pdf', '%PDF-1.4 published');
        $paper = Paper::factory()->published()->create(['file_path' => 'papers/pub.pdf']);

        $this->actingAs(User::factory()->subscriber()->create())->get(route('papers.download', $paper))
            ->assertOk()
            ->assertDownload();
    }

    public function test_assigned_reviewers_can_download_the_manuscript(): void
    {
        Storage::disk('local')->put('papers/review.pdf', 'review me');
        $reviewer = User::factory()->reviewer()->create();
        $paper = Paper::factory()->underReview()->create(['file_path' => 'papers/review.pdf']);
        \App\Models\Review::factory()->create(['paper_id' => $paper->id, 'reviewer_id' => $reviewer->id]);

        $this->actingAs($reviewer)->get(route('papers.download', $paper))->assertOk();
        $this->actingAs(User::factory()->reviewer()->create())->get(route('papers.download', $paper))->assertForbidden();
    }

    public function test_missing_files_return_404(): void
    {
        $paper = Paper::factory()->published()->create(['file_path' => 'papers/gone.pdf']);

        $this->actingAs(User::factory()->subscriber()->create())->get(route('papers.download', $paper))->assertNotFound();
    }
}
