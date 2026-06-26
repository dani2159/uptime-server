<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = \App\Models\ApiToken::orderByDesc('created_at')->get();
        return view('api-tokens.index', compact('tokens'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'abilities'  => 'in:read,write,admin',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $raw   = bin2hex(random_bytes(32));
        $token = \App\Models\ApiToken::create([
            'name'       => $data['name'],
            'token'      => hash('sha256', $raw),
            'abilities'  => $data['abilities'] ?? 'read',
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        \App\Services\AuditService::log('api_token.created', "API token \"{$token->name}\" dibuat");
        return redirect()->route('api-tokens.index')
            ->with('new_token', $raw)
            ->with('success', 'Token dibuat. Salin sekarang — tidak akan ditampilkan lagi.');
    }

    public function destroy($id)
    {
        $token = \App\Models\ApiToken::findOrFail($id);
        \App\Services\AuditService::log('api_token.deleted', "API token \"{$token->name}\" dihapus");
        $token->delete();
        return redirect()->route('api-tokens.index')->with('success', 'Token dihapus.');
    }
}
