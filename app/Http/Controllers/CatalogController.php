<?php

namespace App\Http\Controllers;

use App\Models\Committee;
use App\Models\Paper;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Public, read-only catalog of published papers (multi-disciplinary discovery). */
class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $sectorSlugs = array_filter((array) $request->query('sectors', []));

        $papers = Paper::query()
            ->published()
            ->with(['author.committee', 'sectors'])
            ->search($request->query('q'))
            ->inSectors($sectorSlugs)
            ->inCommittee($request->query('committee'))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('catalog.index', [
            'papers' => $papers,
            'sectors' => Sector::orderBy('name')->get(),
            'committees' => Committee::orderBy('name')->get(),
            'selectedSectors' => $sectorSlugs,
        ]);
    }

    public function show(Paper $paper): View
    {
        abort_unless($paper->isPublished(), 404);

        $paper->load(['author.committee', 'sectors']);
        $sectorIds = $paper->sectors->pluck('id');

        // Papers sharing the most sectors with this one come first.
        $related = Paper::query()
            ->published()
            ->where('papers.id', '!=', $paper->id)
            ->whereHas('sectors', fn ($q) => $q->whereIn('sectors.id', $sectorIds))
            ->withCount(['sectors as shared_sectors_count' => fn ($q) => $q->whereIn('sectors.id', $sectorIds)])
            ->with('sectors')
            ->orderByDesc('shared_sectors_count')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return view('catalog.show', ['paper' => $paper, 'related' => $related]);
    }
}
