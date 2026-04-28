<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    public function index()
    {
        return Cache::remember('services_all', 60, fn() => Service::all());
    }

    public function show($id)
    {
        $service = Service::find($id);
        if (!$service) return response()->json(['message' => 'Service not found'], 404);

        Log::info('Service consulté', ['id' => $id, 'user_id' => auth()->id() ?? null]);
        return response()->json($service);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'in:draft,published',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $validated['description'] = Purifier::clean($validated['description']);
        $validated['title'] = strip_tags($validated['title']);
        $validated['slug'] = Str::slug($validated['title']);
        $validated['status'] = $validated['status'] ?? 'draft';

        if ($request->hasFile('image')) {
            $filename = \Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('services', $filename, 'public');
            $validated['image'] = $path;
        }

        $service = Service::create($validated);
        Cache::forget('services_all');

        Log::info('Service créé', ['id' => $service->id, 'title' => $service->title, 'user_id' => auth()->id()]);
        return response()->json($service, 201);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:draft,published',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if (isset($validated['description'])) $validated['description'] = Purifier::clean($validated['description']);
        if (isset($validated['title'])) {
            $validated['title'] = strip_tags($validated['title']);
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('image')) {
            $filename = \Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('services', $filename, 'public');
            $validated['image'] = $path;
        }

        $service->update($validated);
        Cache::forget('services_all');

        Log::info('Service mis à jour', ['id' => $service->id, 'title' => $service->title, 'user_id' => auth()->id()]);
        return response()->json($service);
    }

    public function updateImage(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png|max:2048']);

        if ($service->image && Storage::disk('public')->exists($service->image)) {
            Storage::disk('public')->delete($service->image);
        }

        $filename = \Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
        $path = $request->file('image')->storeAs('services', $filename, 'public');

        $service->image = $path;
        $service->save();
        Cache::forget('services_all');

        Log::info('Image service mise à jour', ['id' => $service->id, 'user_id' => auth()->id()]);
        return response()->json($service);
    }

     public function deleteImage($id)
    {
        $service = Service::findOrFail($id);

        if ($service->image && Storage::disk('public')->exists($service->image)) {
            Storage::disk('public')->delete($service->image);
        }

        $service->image = null;
        $service->save();

        Cache::forget('services_all');

        Log::info('Image service supprimée', [
            'id' => $service->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Image supprimée avec succès'
        ]);
    }

    public function destroy($id)
    {
        $service = Service::find($id);
        if (!$service) return response()->json(['message' => 'Service not found'], 404);
            // supprimer l'image si elle existe
        if ($service->image && Storage::disk('public')->exists($service->image)) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();
        Cache::forget('services_all');

        Log::info('Service supprimé', ['id' => $id, 'user_id' => auth()->id()]);
        return response()->json(['message' => 'Service deleted successfully']);
    }
}