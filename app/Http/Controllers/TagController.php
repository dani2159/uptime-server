<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('monitors')->orderBy('name')->get();
        return view('tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50|unique:tags,name',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);
        $tag = Tag::create($data);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'tag' => $tag]);
        }
        return back()->with('success', 'Tag berhasil dibuat.');
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50|unique:tags,name,' . $tag->id,
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);
        $tag->update($data);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'tag' => $tag]);
        }
        return back()->with('success', 'Tag diperbarui.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Tag dihapus.');
    }
}
