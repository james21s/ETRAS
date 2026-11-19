<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommitteeResource;
use App\Models\Committee;

class CommitteeController extends Controller
{
    /** GET /api/v1/committees : committees with their published output (institutional footprint). */
    public function index()
    {
        return CommitteeResource::collection(
            Committee::withCount('publishedPapers')->orderBy('name')->get()
        );
    }
}
