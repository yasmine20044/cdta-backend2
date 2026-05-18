<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoticeController extends Controller
{
    public function index()
    {
        return response()->json(Notice::orderBy('created_at', 'desc')->get());
    }

    public function show($id)
    {
        $notice = Notice::findOrFail($id);
        return response()->json($notice);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'nullable|string|in:draft,published,archived'
        ]);

        $notice = Notice::create($validated);

        return response()->json($notice, 201);
    }

    public function update(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|nullable|string|in:draft,published,archived'
        ]);

        $notice->update($validated);

        return response()->json($notice);
    }

    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        
        if ($notice->pdf_file) {
            Storage::disk('public')->delete($notice->pdf_file);
        }
        
        $notice->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function updateFile(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);

        $request->validate([
            'file' => 'required|mimes:pdf|max:500000', // 50MB max
        ]);

        if ($notice->pdf_file) {
            Storage::disk('public')->delete($notice->pdf_file);
        }

        $path = $request->file('file')->store('notices', 'public');
        
        $notice->pdf_file = $path;
        $notice->save();

        return response()->json($notice);
    }
}
