<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Routes are protected by the `manage-sectors` gate (see routes/web.php). */
class SectorController extends Controller
{
    public function index(): View
    {
        return view('sectors.index', ['sectors' => Sector::withCount('papers')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:sectors,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Sector::create($data + ['slug' => Str::slug($data['name'])]);

        return back()->with('status', 'Sector created.');
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        if ($sector->papers()->exists()) {
            return back()->withErrors(['sector' => 'Re-index the papers in this sector before deleting it.']);
        }

        $sector->delete();

        return back()->with('status', 'Sector deleted.');
    }
}
