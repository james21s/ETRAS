<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaperResource;
use App\Models\Paper;
use App\Services\PaperFileStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaperController extends Controller
{
    /** GET /api/v1/papers : search published paper metadata. */
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'array'],
            'sector.*' => ['string', 'max:120'],
            'committee' => ['nullable', 'string', 'max:120'],
            'published_after' => ['nullable', 'date'],
            'published_before' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $papers = Paper::query()
            ->published()
            ->with(['author.committee', 'sectors'])
            ->search($request->query('q'))
            ->inSectors((array) $request->query('sector', []))
            ->inCommittee($request->query('committee'))
            ->when($request->query('published_after'), fn ($q, $d) => $q->whereDate('papers.published_at', '>=', $d))
            ->when($request->query('published_before'), fn ($q, $d) => $q->whereDate('papers.published_at', '<=', $d))
            ->orderByDesc('papers.published_at')
            ->paginate((int) $request->query('per_page', 20))
            ->withQueryString();

        return PaperResource::collection($papers);
    }

    /** GET /api/v1/papers/{paper} */
    public function show(Paper $paper): PaperResource
    {
        abort_unless($paper->isPublished(), 404);

        return new PaperResource($paper->load(['author.committee', 'sectors']));
    }

    /** GET /api/v1/papers/{paper}/download : stream the published PDF. */
    public function download(Paper $paper, PaperFileStorage $files): StreamedResponse
    {
        abort_unless($paper->isPublished() && $paper->hasFile(), 404);

        return $files->download($paper);
    }
}
