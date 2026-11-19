<?php

namespace Tests\Feature;

use App\Enums\PaperStatus;
use App\Enums\UserRole;
use App\Models\Committee;
use App\Models\EditorialNote;
use App\Models\Paper;
use App\Models\PaperStatusHistory;
use App\Models\Review;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_generates_at_least_one_thousand_records(): void
    {
        Storage::fake('local');

        $this->seed(DatabaseSeeder::class);

        $total = User::count() + Committee::count() + Sector::count() + Paper::count()
            + Review::count() + EditorialNote::count() + PaperStatusHistory::count()
            + DB::table('paper_sector')->count();

        $this->assertGreaterThanOrEqual(1000, $total, "Seeder produced only {$total} records");
    }

    public function test_the_seeded_data_is_realistic_and_consistent(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        // every role is represented, including the demo logins
        foreach (UserRole::cases() as $role) {
            $this->assertGreaterThan(0, User::where('role', $role->value)->count(), "No {$role->value} users");
        }
        $this->assertDatabaseHas('users', ['email' => 'editor@example.com', 'role' => 'editor']);

        // every workflow state has papers
        foreach (PaperStatus::cases() as $status) {
            $this->assertGreaterThan(0, Paper::where('status', $status->value)->count());
        }

        // published papers have references, dates and multiple-sector indexing
        $published = Paper::published()->with('sectors')->get();
        $this->assertTrue($published->every(fn ($p) => $p->reference && $p->published_at));
        $this->assertTrue($published->contains(fn ($p) => $p->sectors->count() > 1), 'Expected multi-sector papers');

        // approved/published papers have only completed reviews
        $this->assertSame(0, Review::pending()->whereHas('paper', fn ($q) => $q->whereIn('status', ['approved', 'published']))->count());

        // the shared placeholder PDF exists so downloads work
        Storage::disk('local')->assertExists(Paper::SEED_SAMPLE_PATH);
    }
}
