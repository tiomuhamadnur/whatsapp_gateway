<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        return view('cms.tokens.index', [
            'tokens' => $request->user()->tokens()->latest()->get(),
            'plainTextToken' => session('plain_text_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $token = $request->user()->createToken($validated['name'])->plainTextToken;

        return redirect()
            ->route('cms.tokens.index')
            ->with('plain_text_token', $token)
            ->with('status', 'Token API berhasil dibuat. Simpan token ini karena hanya ditampilkan sekali.');
    }

    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        $request->user()->tokens()->whereKey($tokenId)->delete();

        return back()->with('status', 'Token API dihapus.');
    }
}
