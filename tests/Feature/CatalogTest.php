<?php

namespace Tests\Feature;

use App\Models\Paper;
use App\Models\Sector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_published_papers(): void
    {
        Paper::factory()->published()->create(['title' => 'Visible published standard']);
        Paper::factory()->create(['title' => 'Hidden draft manuscript']);
        Paper::factory()->underReview()->create(['title' => 'Hidden review manuscript']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Visible published standard')
            ->assertDontSee('Hidden draft manuscript')
            ->assertDontSee('Hidden review manuscript');
    }

    public function test_catalog_filters_by_sector(): void
    {
        $civil = Sector::factory()->create(['name' => 'Civil Works', 'slug' => 'civil-works']);
        $marine = Sector::factory()->create(['name' => 'Marine Works', 'slug' => 'marine-works']);
        Paper::factory()->published()->create(['title' => 'Bridge paper'])->syncSectors([$civil->id]);
        Paper::factory()->published()->create(['title' => 'Harbour paper'])->syncSectors([$marine->id]);

        $this->get('/?sectors[]=civil-works')
            ->assertSee('Bridge paper')
            ->assertDontSee('Harbour paper');
    }

    public function test_related_papers_are_ranked_by_shared_sectors(): void
    {
        [$a, $b, $c] = Sector::factory()->count(3)->create();

        $main = Paper::factory()->published()->create(['title' => 'Main paper']);
        $main->syncSectors([$a->id, $b->id, $c->id]);

        $strong = Paper::factory()->published()->create(['title' => 'Strongly related']);
        $strong->syncSectors([$a->id, $b->id]);

        $weak = Paper::factory()->published()->create(['title' => 'Weakly related']);
        $weak->syncSectors([$c->id]);

        $unrelated = Paper::factory()->published()->create(['title' => 'Totally unrelated']);
        $unrelated->syncSectors([Sector::factory()->create()->id]);

        $html = $this->get(route('catalog.show', $main))->assertOk()->getContent();

        $this->assertStringContainsString('Strongly related', $html);
        $this->assertStringContainsString('Weakly related', $html);
        $this->assertStringNotContainsString('Totally unrelated', $html);
        $this->assertLessThan(strpos($html, 'Weakly related'), strpos($html, 'Strongly related'));
    }

    public function test_unpublished_papers_have_no_public_page(): void
    {
        $draft = Paper::factory()->create();

        $this->get(route('catalog.show', $draft))->assertNotFound();
    }
}
