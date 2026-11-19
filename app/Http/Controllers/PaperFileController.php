<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use App\Services\PaperFileStorage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaperFileController extends Controller
{
    public function download(Paper $paper, PaperFileStorage $files): StreamedResponse
    {
        Gate::authorize('download', $paper);

        return $files->download($paper);
    }
}
