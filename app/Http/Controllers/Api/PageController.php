<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{

    // GET ALL
    public function index()
    {
        return Cache::remember('pages_all', 60, function () {
            return Page::all();
        });
    }

    // GET ONE
    public function show($id)
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        Log::info('Page consultée', [
            'id' => $id,
            'user_id' => auth()->id()
        ]);

        return response()->json($page);
    }

    // CREATE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'status'  => 'in:draft,published',
            'image'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $validated['title'] = strip_tags($validated['title']);
        $validated['content'] = Purifier::clean($validated['content']);
        $validated['slug'] = Str::slug($validated['title']);
        $validated['status'] = $validated['status'] ?? 'draft';

        if ($request->hasFile('image')) {

            $filename = Str::uuid().'.'.$request->file('image')->getClientOriginalExtension();

            $path = $request->file('image')->storeAs('pages', $filename, 'public');

            $validated['image'] = $path;
        }

        $page = Page::create($validated);

        Cache::forget('pages_all');

        Log::info('Page créée', [
            'id' => $page->id,
            'title' => $page->title,
            'user_id' => auth()->id()
        ]);

        return response()->json($page, 201);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'title'   => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'status'  => 'sometimes|in:draft,published'
        ]);

        if (isset($validated['title'])) {
            $validated['title'] = strip_tags($validated['title']);
            $validated['slug'] = Str::slug($validated['title']);
        }

        if (isset($validated['content'])) {
            $validated['content'] = Purifier::clean($validated['content']);
        }

        $page->update($validated);

        Cache::forget('pages_all');

        Log::info('Page mise à jour', [
            'id' => $page->id,
            'user_id' => auth()->id()
        ]);

        return response()->json($page);
    }

    // UPDATE IMAGE
    public function updateImage(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($page->image && Storage::disk('public')->exists($page->image)) {
            Storage::disk('public')->delete($page->image);
        }

        $filename = Str::uuid().'.'.$request->file('image')->getClientOriginalExtension();

        $path = $request->file('image')->storeAs('pages', $filename, 'public');

        $page->image = $path;
        $page->save();

        Cache::forget('pages_all');

        Log::info('Image page mise à jour', [
            'id' => $page->id,
            'user_id' => auth()->id()
        ]);

        return response()->json($page);
    }
    
    public function deleteImage($id)
{
    $page = Page::findOrFail($id);

    if ($page->image && Storage::disk('public')->exists($page->image)) {
        Storage::disk('public')->delete($page->image);
    }

    $page->image = null;
    $page->save();

    Cache::forget('pages_all');

    Log::info('Image page supprimée', [
        'id' => $page->id,
        'user_id' => auth()->id()
    ]);

    return response()->json([
        'message' => 'Image supprimée avec succès'
    ]);
}
    // DELETE
    public function destroy($id)
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }
          // supprimer l'image si elle existe
    if ($page->image && Storage::disk('public')->exists($page->image)) {
        Storage::disk('public')->delete($page->image);
    }

        $page->delete();

        Cache::forget('pages_all');

        Log::info('Page supprimée', [
            'id' => $id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'message' => 'Page deleted successfully'
        ]);
    }
}