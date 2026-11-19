<?php

namespace Database\Seeders;

use App\Models\Committee;
use App\Models\EditorialNote;
use App\Models\Paper;
use App\Models\PaperStatusHistory;
use App\Models\Review;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Generates well over 1,000 rows using model factories and relationship states.
 * Demo logins (password "password"): executive@, editor@, reviewer@, author@, subscriber@ example.com
 */
class DatabaseSeeder extends Seeder
{
    private const COMMITTEES = [
        'Structural Engineering', 'Electrical & Power Systems', 'Mechanical Systems',
        'Process & Chemical Safety', 'Information Security', 'Environmental Compliance',
        'Telecommunications', 'Materials & Testing',
    ];

    private const SECTORS = [
        'Civil Infrastructure', 'Energy & Utilities', 'Manufacturing', 'Aerospace', 'Automotive',
        'Healthcare Devices', 'Oil & Gas', 'Construction', 'Renewable Energy', 'Cybersecurity',
        'Water & Wastewater', 'Transportation', 'Robotics & Automation', 'Semiconductors', 'Marine & Offshore',
    ];

    public function run(): void
    {
        $this->ensureSamplePdf();

        $committees = collect(self::COMMITTEES)
            ->map(fn (string $name) => Committee::factory()->create(['name' => $name, 'slug' => null]));

        collect(self::SECTORS)->each(fn (string $name) => Sector::factory()->create(['name' => $name, 'slug' => null]));

        $committeeId = fn () => $committees->random()->id;

        /* ---- Demo accounts ---- */
        User::factory()->executive()->create(['name' => 'Eleanor Executive', 'email' => 'executive@example.com', 'committee_id' => $committees->first()->id]);
        User::factory()->editor()->create(['name' => 'Marcus Managing-Editor', 'email' => 'editor@example.com']);
        User::factory()->reviewer()->create(['name' => 'Dr. Rita Reviewer', 'email' => 'reviewer@example.com', 'committee_id' => $committees->first()->id]);
        User::factory()->author()->create(['name' => 'Arthur Author', 'email' => 'author@example.com', 'committee_id' => $committees->first()->id]);
        User::factory()->subscriber()->create(['name' => 'Sasha Subscriber', 'email' => 'subscriber@example.com']);

        /* ---- Bulk users ---- */
        User::factory()->count(3)->executive()->state(fn () => ['committee_id' => $committeeId()])->create();
        User::factory()->count(5)->editor()->create();
        User::factory()->count(40)->reviewer()->state(fn () => ['committee_id' => $committeeId()])->create();
        User::factory()->count(80)->author()->state(fn () => ['committee_id' => $committeeId()])->create();
        User::factory()->count(25)->subscriber()->create();

        $authorIds = User::where('role', 'author')->pluck('id');
        $pickAuthor = fn () => ['user_id' => $authorIds->random()];

        /* ---- Papers in every workflow state, with sectors, reviews, notes and audit trail ---- */
        Paper::factory()->count(60)->draft()->state($pickAuthor)->withSectors()->withReviewHistory()->create();
        Paper::factory()->count(70)->underReview()->state($pickAuthor)->withSectors()->withReviewHistory()->create();
        Paper::factory()->count(40)->approved()->state($pickAuthor)->withSectors()->withReviewHistory()->create();
        Paper::factory()->count(150)->published()->state($pickAuthor)->withSectors()->withReviewHistory()->create();

        $total = $this->totalRecords();
        $this->command?->info("Seeded {$total} records.");
    }

    /** Creates the placeholder PDF that seeded papers point to. */
    private function ensureSamplePdf(): void
    {
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\n"
            ."trailer<</Root 1 0 R>>\n%%EOF\n";

        Storage::disk(config('standardspress.disk', 'local'))->put(Paper::SEED_SAMPLE_PATH, $pdf);
    }

    private function totalRecords(): int
    {
        return User::count() + Committee::count() + Sector::count() + Paper::count()
            + Review::count() + EditorialNote::count() + PaperStatusHistory::count()
            + DB::table('paper_sector')->count();
    }
}
