<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EditorialNoteController extends Controller
{
    public function store(Request $request, Paper $paper): RedirectResponse
    {
        Gate::authorize('addNote', $paper);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $paper->notes()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        return back()->with('status', 'Internal note added.');
    }
}
