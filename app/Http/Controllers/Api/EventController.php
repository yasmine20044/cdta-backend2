<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    // Lister tous les events avec cache
    public function index()
    {
        return Cache::remember('events_all', 60, function () {
            return Event::all();
        });
    }

    // Créer un event
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'status'      => 'required|string|max:50',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date',
            'location'    => 'required|string|max:255',
            'category'    => 'required|string|max:100',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png|max:10240'
        ]);

        // Nettoyage
        $validated['title']       = strip_tags($validated['title']);
        $validated['status']      = strip_tags($validated['status']);
        $validated['location']    = strip_tags($validated['location']);
        $validated['category']    = strip_tags($validated['category']);
        $validated['slug']        = Str::slug($validated['title']);
        $validated['description'] = Purifier::clean($validated['description']); // le setter de model chiffre

        // Upload image
        if ($request->hasFile('image')) {
            $filename = Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('events', $filename, 'public');
            $validated['image'] = $path;
        }

        $event = Event::create($validated);

        Log::info('Event créé', [
            'id' => $event->id,
            'title' => $event->title,
            'user_id' => auth()->id(),
        ]);

        Cache::forget('events_all');

        return response()->json($event, 201);
    }

    // Afficher un event
    public function show($id)
    {
        $event = Event::findOrFail($id);

        Log::info('Event consulté', [
            'id' => $event->id,
            'title' => $event->title,
            'user_id' => auth()->id() ?? null,
        ]);

        return response()->json($event);
    }

    // Mettre à jour un event (JSON pour texte + Form-Data pour image)
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        // Validation JSON pour les champs texte
        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status'      => 'sometimes|string|max:50',
            'start_date'  => 'sometimes|date',
            'end_date'    => 'nullable|date',
            'location'    => 'sometimes|string|max:255',
            'category'    => 'sometimes|string|max:100',
        ]);

        // Nettoyage texte
        if (isset($validated['description'])) {
            $validated['description'] = Purifier::clean($validated['description']);
        }
        foreach (['title','status','location','category'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = strip_tags($validated[$field]);
            }
        }
        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Image séparée (Form-Data)
        if ($request->hasFile('image')) {
            // Supprimer ancienne image
            if ($event->image && Storage::disk('public')->exists($event->image)) {
                Storage::disk('public')->delete($event->image);
            }

            $filename = Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('events', $filename, 'public');
            $validated['image'] = $path;
        }

        $event->update($validated);

        Log::info('Event mis à jour', [
            'id' => $event->id,
            'title' => $event->title,
            'user_id' => auth()->id(),
        ]);

        Cache::forget('events_all');

        return response()->json($event);
    }

    // Supprimer un event
    public function destroy($id)
    {
        $event = Event::findOrFail($id);


        // Supprimer image si existante
        if ($event->image && Storage::disk('public')->exists($event->image)) {
            Storage::disk('public')->delete($event->image);
        }


        $event->delete();

        Log::info('Event supprimé', [
            'id' => $id,
            'user_id' => auth()->id(),
        ]);

        Cache::forget('events_all');

        return response()->json(['message' => 'Deleted']);
    }

    // Mettre à jour uniquement l'image (optionnel)
    public function updateImage(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:10240'
        ]);

        // Supprimer ancienne image
        if ($event->image && Storage::disk('public')->exists($event->image)) {
            Storage::disk('public')->delete($event->image);
        }

        $filename = Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
        $path = $request->file('image')->storeAs('events', $filename, 'public');
        $event->image = $path;
        $event->save();

        Cache::forget('events_all');

        Log::info('Image event mise à jour', [
            'id' => $event->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json($event);
    }
    public function deleteImage($id)
    {
        $event = Event::findOrFail($id);

        if ($event->image && Storage::disk('public')->exists($event->image)) {
            Storage::disk('public')->delete($event->image);
        }

        $event->image = null;
        $event->save();

        Cache::forget('events_all');

        Log::info('Image event supprimée', [
            'id' => $event->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Image supprimée avec succès'
        ]);
    }
}