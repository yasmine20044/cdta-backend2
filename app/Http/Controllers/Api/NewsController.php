<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\NewsResource;

class NewsController extends Controller
{
    public function index()
    {
        return Cache::remember('news_all', 60, fn() => News::all());
    }

    public function show($id)
    {
        $news = News::find($id);
        if (!$news) return response()->json(['message' => 'News not found'], 404);

        Log::info('News consultée', ['id' => $id, 'user_id' => auth()->id() ?? null]);
       
        return new NewsResource($news);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'in:draft,published',
            'excerpt' => 'nullable|string|max:500',
            'author' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'gallery_images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:10240'
        ]);

        foreach (['content','excerpt','author','category'] as $field) {
            if (isset($validated[$field])) $validated[$field] = Purifier::clean($validated[$field]);
        }

        $validated['title'] = strip_tags($validated['title']);
        $validated['slug'] = Str::slug($validated['title']);
        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['author'] = $validated['author'] ?? 'CDTA';
        $validated['published_at'] = now();

        if ($request->hasFile('image')) {
            $filename = \Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('news', $filename, 'public');
            $validated['image'] = $path;
        }

        $galleryPaths = [];
        if ($request->hasFile('gallery_images')) {
            $files = $request->file('gallery_images');
            if (is_array($files)) {
                $files = array_slice($files, 0, 4); // Max 4 extra images (total 5)
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $filename = \Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('news/gallery', $filename, 'public');
                        $galleryPaths[] = $path;
                    }
                }
            }
        }
        $validated['additional_images'] = $galleryPaths;

        $news = News::create($validated);

        Log::info('News créée', ['id' => $news->id, 'title' => $news->title, 'user_id' => auth()->id()]);
        Cache::forget('news_all');

        return response()->json($news, 201);
    }

    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'status' => 'sometimes|in:draft,published',
            'excerpt' => 'nullable|string|max:500',
            'author' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'gallery_images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:10240'
        ]);

        foreach (['content','excerpt','author','category'] as $field) {
            if (isset($validated[$field])) $validated[$field] = Purifier::clean($validated[$field]);
        }

        if (isset($validated['title'])) {
            $validated['title'] = strip_tags($validated['title']);
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('image')) {
            if ($news->image && Storage::disk('public')->exists($news->image)) {
                Storage::disk('public')->delete($news->image);
            }
            $filename = \Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('news', $filename, 'public');
            $validated['image'] = $path;
        }

        if ($request->hasFile('gallery_images')) {
            // Replace old gallery
            if (is_array($news->additional_images)) {
                foreach ($news->additional_images as $oldPath) {
                    if (Storage::disk('public')->exists($oldPath)) Storage::disk('public')->delete($oldPath);
                }
            }

            $galleryPaths = [];
            $files = $request->file('gallery_images');
            if (is_array($files)) {
                $files = array_slice($files, 0, 4);
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $filename = \Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('news/gallery', $filename, 'public');
                        $galleryPaths[] = $path;
                    }
                }
            }
            $validated['additional_images'] = $galleryPaths;
        }

        $news->update($validated);
        Cache::forget('news_all');

        Log::info('News mise à jour', ['id' => $news->id, 'title' => $news->title, 'user_id' => auth()->id()]);
        return response()->json($news);
    }

    public function updateImage(Request $request, $id)
    {
        $news = News::findOrFail($id);

        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png|max:10240']);

        if ($news->image && Storage::disk('public')->exists($news->image)) {
            Storage::disk('public')->delete($news->image);
        }

        $filename = \Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
        $path = $request->file('image')->storeAs('news', $filename, 'public');

        $news->image = $path;
        $news->save();

        Cache::forget('news_all');
        Log::info('Image news mise à jour', ['id' => $news->id, 'user_id' => auth()->id()]);

        return response()->json($news);
    }

     public function deleteImage($id)
    {
        $news = News::findOrFail($id);

        if ($news->image && Storage::disk('public')->exists($news->image)) {
            Storage::disk('public')->delete($news->image);
        }

        $news->image = null;
        $news->save();

        Cache::forget('news_all');

        Log::info('Image news supprimée', [
            'id' => $news->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Image supprimée avec succès'
        ]);
    }

    public function destroy($id)
    {
        $news = News::find($id);
        if (!$news) return response()->json(['message' => 'News not found'], 404);
        
            // supprimer l'image si elle existe 
        if ($news->image && Storage::disk('public')->exists($news->image)) {
            Storage::disk('public')->delete($news->image);
        }
        if (is_array($news->additional_images)) {
            foreach ($news->additional_images as $oldPath) {
                if (Storage::disk('public')->exists($oldPath)) Storage::disk('public')->delete($oldPath);
            }
        }
        
        $news->delete();
        Cache::forget('news_all');

        Log::info('News supprimée', ['id' => $id, 'user_id' => auth()->id()]);
        return response()->json(['message' => 'News deleted successfully']);
    }
}