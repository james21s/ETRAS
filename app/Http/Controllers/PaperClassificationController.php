<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Multi-disciplinary indexing: editors re-index a paper under several sectors. */
class PaperClassificationController extends Controller
{
    public function update(Request $request, Paper $paper): RedirectResponse
    {
        Gate::authorize('classify', $paper);

        $data = $request->validate([
            'sectors' => ['required', 'array', 'min:1'],
            'sectors.*' => ['integer', 'distinct', 'exists:sectors,id'],
        ]);

        $paper->syncSectors($data['sectors']);

        return back()->with('status', 'Sector indexing updated.');
    }
}
