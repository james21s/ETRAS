<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lets aggregator/subscriber accounts mint Sanctum tokens for the REST feed. */
class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        return view('tokens.index', ['tokens' => $request->user()->tokens()->latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);

        $token = $request->user()->createToken($data['name'], ['papers:read']);

        return back()->with('status', 'Token created. Copy it now; it will not be shown again.')
            ->with('new_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back()->with('status', 'Token revoked.');
    }
}
