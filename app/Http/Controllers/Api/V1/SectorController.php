<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectorResource;
use App\Models\Sector;

class SectorController extends Controller
{
    /** GET /api/v1/sectors : taxonomy used for multi-disciplinary indexing. */
    public function index()
    {
        return SectorResource::collection(
            Sector::withCount(['papers' => fn ($q) => $q->where('papers.status', 'published')])
                ->orderBy('name')
                ->get()
        );
    }
}
