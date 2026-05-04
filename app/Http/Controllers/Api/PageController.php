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
            'content' => 'nullable|string',
            'status'  => 'in:draft,published',
            'image'   => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png|max:10240'
        ]);

        $validated['title'] = strip_tags($validated['title']);
        $validated['content'] = isset($validated['content']) ? Purifier::clean($validated['content']) : '';
        
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $count = 1;
        while (Page::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-" . $count++;
        }
        $validated['slug'] = $slug;
        
        $validated['status'] = $validated['status'] ?? 'draft';

        if ($request->hasFile('image')) {

            $filename = Str::uuid().'.'.$request->file('image')->getClientOriginalExtension();

            $path = $request->file('image')->storeAs('pages', $filename, 'public');

            $validated['image'] = $path;
        }

        // Process Gallery
        if ($request->hasFile('gallery_images')) {
            $galleryPaths = [];
            foreach ($request->file('gallery_images') as $image) {
                $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('pages/gallery', $filename, 'public');
                $galleryPaths[] = $path;
            }
            $validated['gallery'] = $galleryPaths;
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
            'content' => 'nullable|string',
            'status'  => 'sometimes|in:draft,published'
        ]);

        if (isset($validated['title'])) {
            $validated['title'] = strip_tags($validated['title']);
            
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $count = 1;
            while (Page::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = "{$originalSlug}-" . $count++;
            }
            $validated['slug'] = $slug;
        }

        if (isset($validated['content'])) {
            $validated['content'] = Purifier::clean($validated['content']);
        }

        // Process Cover Image if present in FormData (POST with _method=PUT)
        if ($request->hasFile('image')) {
            if ($page->image && Storage::disk('public')->exists($page->image)) {
                Storage::disk('public')->delete($page->image);
            }
            $filename = Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('pages', $filename, 'public');
            $validated['image'] = $path;
        }

        // Process Gallery if present
        if ($request->hasFile('gallery_images')) {
            // Delete old gallery images
            if ($page->gallery) {
                foreach ($page->gallery as $oldPath) {
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
            }
            
            $galleryPaths = [];
            foreach ($request->file('gallery_images') as $image) {
                $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('pages/gallery', $filename, 'public');
                $galleryPaths[] = $path;
            }
            $validated['gallery'] = $galleryPaths;
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
            'image' => 'required|image|mimes:jpg,jpeg,png|max:10240'
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

    // supprimer la galerie
    if ($page->gallery) {
        foreach ($page->gallery as $oldPath) {
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
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